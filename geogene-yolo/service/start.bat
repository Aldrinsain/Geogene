@echo off
cd /d "%~dp0"
echo Starting GeoGene YOLO+OCR service on http://127.0.0.1:5005 ...
python app.py
pause
