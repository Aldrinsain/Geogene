const idFrame = document.getElementById('idFrame');
const camStream = document.getElementById('camStream');
const camError = document.getElementById('camError');
const idPreview = document.getElementById('idPreview');
const idCanvas = document.getElementById('idCanvas');
const captureRow = document.getElementById('captureRow');
const captureBtn = document.getElementById('captureBtn');
const confirmRow = document.getElementById('confirmRow');
const confirmBtn = document.getElementById('confirmBtn');
const retakeBtn = document.getElementById('retakeBtn');
const restartBtn = document.getElementById('restartBtn');

const screenScan = document.getElementById('screen-scan');
const screenVerify = document.getElementById('screen-verify');
const screenResult = document.getElementById('screen-result');
const resultWrap = document.getElementById('resultWrap');
const verifyStatus = document.getElementById('verifyStatus');

let stream = null;