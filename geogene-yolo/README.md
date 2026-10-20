GeoGene - YOLO + OCR Bulk Scan Integration
============================================

WHAT THIS ADDS
--------------
A local Python service that detects birth-certificate fields with YOLO and
reads each one with Tesseract OCR, called automatically by the EXISTING
Bulk Scan page (staff/civil-registry-bulk-scan.php) - no UI changes, no
change to Save All, no change to the review screen. If the service isn't
running, or the model isn't trained yet, Bulk Scan keeps working exactly
as it does today (browser OCR + text pattern matching).


1. FOLDER STRUCTURE
--------------------
geogene-yolo/
├── dataset/                    <- (already existed) training images/labels
├── models/
│   ├── best.pt                 <- YOU add this after training (not included)
│   └── README.txt
└── service/
    ├── app.py                  <- the Flask API (YOLO + OCR)
    ├── requirements.txt
    └── start.bat                <- double-click to run on Windows

staff/lib/YoloOcrClient.php     <- PHP side: calls the service, maps its
                                    response into the existing field format
staff/civil-registry-scan-inline.php  <- modified: tries YOLO first, falls
                                    back to the existing OCR pipeline


2. ONE-TIME SETUP
------------------
a) Install Python 3.10+ if you don't have it: https://www.python.org/downloads/
   (tick "Add Python to PATH" during install)

b) Install Tesseract OCR itself (the pytesseract package is just a Python
   wrapper around it - it needs the real program installed separately):
   https://github.com/UB-Mannheim/tesseract/wiki  (Windows installer)
   Install to the default path (C:\Program Files\Tesseract-OCR) - pytesseract
   finds it there automatically. If you installed it elsewhere, add this
   near the top of geogene-yolo/service/app.py:
       pytesseract.pytesseract.tesseract_cmd = r'C:\path\to\tesseract.exe'

c) Install the Python packages:
       cd C:\xampp\htdocs\geogene\geogene-yolo\service
       pip install -r requirements.txt

   (First install of ultralytics can take a few minutes - it pulls in
   PyTorch. That's normal.)


3. RUNNING THE SERVICE
------------------------
Either double-click:
    geogene-yolo\service\start.bat

Or from a terminal:
    cd C:\xampp\htdocs\geogene\geogene-yolo\service
    python app.py

You should see:
    * Running on http://127.0.0.1:5005

Leave this window open while staff are using Bulk Scan. XAMPP (Apache/
MySQL) still needs to be running separately, as always - this is a THIRD,
independent process alongside them.


4. TESTING THE SERVICE DIRECTLY
----------------------------------
Check it's up and see whether it found a trained model yet:
    curl http://127.0.0.1:5005/health

Expect (before you've trained/placed best.pt):
    {"status":"ok","model_loaded":false,"model_path":"...","error":"Wala pa na-train ang model...."}

Send an actual test scan (only works once best.pt is in place):
    curl -X POST http://127.0.0.1:5005/scan -F "document_type=birth" -F "image=@C:\path\to\a_birth_certificate.jpg"

Expect a JSON response shaped like:
    {"success":true,"document_type":"birth","fields":{"registry_no":{"text":"2005-3476","confidence":0.95}, "child_name": null, ...}}
(any field YOLO didn't detect, or Tesseract couldn't read, comes back as
null - never a guess.)


5. CONNECTING YOUR TRAINED MODEL LATER
------------------------------------------
Once you've trained the YOLO model (the dataset is already set up under
geogene-yolo/dataset/ with classes.txt matching the 7 birth fields),
your training run produces a weights file, usually at:
    runs/detect/train/weights/best.pt

Copy that file to:
    geogene-yolo/models/best.pt

That's it - no code change, no restart needed. The Python service checks
for this file on every /scan request, so the very next scan after you
drop it in will start using it. Bulk Scan itself needs zero changes on
your end either; civil-registry-scan-inline.php already tries the YOLO
service first on every scan.


6. TROUBLESHOOTING
---------------------
"Bulk Scan still uses the old OCR, not YOLO"
  -> That's expected until best.pt exists - check with `curl
     http://127.0.0.1:5005/health`. If model_loaded is false, the
     fallback is working correctly, not broken.

"php_error_log shows '[YOLO OCR] service unreachable'"
  -> The Python service (start.bat / app.py) isn't running, or is running
     on a different port than 5005. Start it, or edit YOLO_SERVICE_URL at
     the top of staff/lib/YoloOcrClient.php to match the port app.py is
     actually using.

"curl: (7) Failed to connect to 127.0.0.1 port 5005"
  -> Same as above - the service isn't running. Run start.bat.

"ModuleNotFoundError: No module named 'ultralytics'" (or flask/PIL/pytesseract)
  -> `pip install -r requirements.txt` wasn't run, or was run with a
     different Python than the one actually launching app.py. Re-run
     step 2c using the same `python`/`pip` you use to start the service.

"TesseractNotFoundError" when a scan runs
  -> Tesseract OCR itself (the program, not the pip package) isn't
     installed, or isn't at the default path. See step 2b.

"Every field comes back null even with best.pt in place"
  -> The model likely just needs more/better training data (only one
     sample image exists in the dataset right now) - this is a training
     quality issue, not an integration bug. Add more labeled examples and
     retrain.

"Marriage/Death Bulk Scan doesn't use YOLO"
  -> Correct, by design for now - only birth certificate classes exist in
     the dataset. The service returns a clear "not implemented" response
     for those types, and civil-registry-scan-inline.php falls back to
     the existing OCR pipeline for them, same as always.
