"""
GeoGene YOLO + OCR local service
---------------------------------
Detects civil-registry certificate fields with YOLO, crops each detected
region, and reads its text with Tesseract OCR. Runs on localhost only -
PHP (civil-registry-scan-inline.php) calls this over HTTP; nothing here
is exposed to the internet.

BIRTH certificate classes (class id -> field name), matching
geogene-yolo/dataset/classes.txt:
  0 = registry_no
  1 = child_name
  2 = date_of_birth
  3 = place_of_birth
  4 = mother_name
  5 = father_name
  6 = barangay

Marriage/Death are not implemented yet - /scan returns a clear "not
implemented" error for those document_types until their own datasets +
classes are added later.

RUN:
    python app.py
Listens on http://127.0.0.1:5005 (change PORT below if that's taken).

The model is loaded from ../models/best.pt. That file does NOT exist
until you train it - until then, every /scan request returns a clear
"model not trained yet" error, and civil-registry-scan-inline.php falls
back to the existing browser-OCR pipeline automatically. No restart is
needed once you DO drop best.pt in - the very next scan picks it up.
"""

import io
import os

from flask import Flask, jsonify, request
from PIL import Image

try:
    import pytesseract
except ImportError:
    pytesseract = None

MODEL_PATH = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'models', 'best.pt')
PORT = 5005

# Only "birth" has a class list defined yet - keep this in sync with
# geogene-yolo/dataset/classes.txt if that file's order ever changes.
CLASS_NAMES = {
    'birth': {
        0: 'registry_no',
        1: 'child_name',
        2: 'date_of_birth',
        3: 'place_of_birth',
        4: 'mother_name',
        5: 'father_name',
        6: 'barangay',
    },
}

app = Flask(__name__)

_model = None  # cached once successfully loaded; re-attempted every
               # request until then, so dropping best.pt in later just
               # works on the next scan - no service restart needed.


def get_model():
    global _model
    if _model is not None:
        return _model, None
    if not os.path.exists(MODEL_PATH):
        return None, f'Wala pa na-train ang model. Ibutang ang best.pt dinhi: {os.path.abspath(MODEL_PATH)}'
    try:
        from ultralytics import YOLO
        _model = YOLO(MODEL_PATH)
        return _model, None
    except Exception as e:
        return None, f'Na-load ang best.pt pero na-error pag-open: {e}'


@app.route('/health', methods=['GET'])
def health():
    model, error = get_model()
    return jsonify({
        'status': 'ok',
        'model_loaded': model is not None,
        'model_path': os.path.abspath(MODEL_PATH),
        'tesseract_available': pytesseract is not None,
        'error': error,
    })


@app.route('/scan', methods=['POST'])
def scan():
    document_type = request.form.get('document_type', 'birth')

    if document_type not in CLASS_NAMES:
        return jsonify({
            'success': False,
            'error': f'"{document_type}" scanning wala pa i-implement (birth ra sa karon).',
        }), 400

    if pytesseract is None:
        return jsonify({
            'success': False,
            'error': 'pytesseract wala pa na-install (pip install pytesseract), o wala na-install ang Tesseract OCR binary mismo.',
        }), 503

    if 'image' not in request.files:
        return jsonify({'success': False, 'error': 'Walay na-upload nga image.'}), 400

    model, error = get_model()
    if model is None:
        return jsonify({'success': False, 'error': error}), 503

    try:
        image_bytes = request.files['image'].read()
        image = Image.open(io.BytesIO(image_bytes)).convert('RGB')
    except Exception as e:
        return jsonify({'success': False, 'error': f'Dili ma-basa ang na-upload nga image: {e}'}), 400

    class_names = CLASS_NAMES[document_type]
    fields = {name: None for name in class_names.values()}

    try:
        results = model(image, verbose=False)
    except Exception as e:
        return jsonify({'success': False, 'error': f'YOLO inference failed: {e}'}), 500

    if results and len(results) > 0:
        boxes = results[0].boxes
        # If a field somehow gets detected more than once, keep only the
        # highest-confidence box for it.
        best_by_class = {}
        for box in boxes:
            cls_id = int(box.cls[0])
            conf = float(box.conf[0])
            if cls_id not in class_names:
                continue
            if cls_id not in best_by_class or conf > best_by_class[cls_id][1]:
                best_by_class[cls_id] = (box, conf)

        for cls_id, (box, conf) in best_by_class.items():
            field_name = class_names[cls_id]
            x1, y1, x2, y2 = map(int, box.xyxy[0].tolist())
            # A little padding around the detected box helps OCR - a tight
            # crop can clip the top/bottom of tall letters right at the edge.
            pad = 4
            x1, y1 = max(0, x1 - pad), max(0, y1 - pad)
            x2, y2 = min(image.width, x2 + pad), min(image.height, y2 + pad)
            crop = image.crop((x1, y1, x2, y2))

            try:
                # --psm 7 = treat the crop as a single line of text, which
                # fits how these fields are boxed (one line/value per field).
                text = pytesseract.image_to_string(crop, config='--psm 7').strip()
            except Exception:
                text = ''

            # Never invent a value: if YOLO found the box but OCR couldn't
            # read anything usable from it, that field stays null so staff
            # sees a blank to fill in themselves - not a guess.
            fields[field_name] = {'text': text, 'confidence': round(conf, 4)} if text else None

    return jsonify({
        'success': True,
        'document_type': document_type,
        'fields': fields,
    })


if __name__ == '__main__':
    app.run(host='127.0.0.1', port=PORT, debug=False)
