Ibutang diri ang imong na-train nga model, exact niini nga ngalan:

    best.pt

Human ma-train (e.g. via `yolo train ...` o Ultralytics/Colab), ang
resulta nga best.pt kay naa sa imong training output folder
(`runs/detect/train/weights/best.pt`) - kopyaha lang dinhi:

    geogene-yolo/models/best.pt

Walay kinahanglan i-restart ang Python service (geogene-yolo/service/app.py)
human ibutang ang file - awtomatik na kining ma-detect sa sunod nga scan.

Hangtod wala pa diri ang best.pt, ang /scan endpoint kay mo-return og
error nga "wala pa na-train ang model", ug ang civil-registry-scan-inline.php
sa PHP side kay automatic nga mo-fallback sa naa na nga browser-OCR nga
scanning (Tesseract.js + text pattern matching) - functional gihapon ang
Bulk Scan bisan wala pa ni nga file.
