<?php
// GeoGene - ID Verification settings
// Usba ra diri kung kinahanglan. Wala nay laing file nga kinahanglan i-edit.

// TRUE = ipakita ang OCR text sa result screen (para lang sa testing, ayaw i-on sa production)
if (!defined('ID_VERIFY_DEBUG'))        define('ID_VERIFY_DEBUG', false);

// Secret nga gigamit sa pag-hash sa ID number (dili gitipigan ang tinood nga ID number).
// USBA ni og taas ug random nga text sa imong server.
if (!defined('ID_VERIFY_PEPPER'))       define('ID_VERIFY_PEPPER', 'CHANGE-THIS-TO-A-LONG-RANDOM-STRING');

// Path sa Tesseract OCR sa SERVER. Kung walay sulod (''), i-auto-detect.
// Windows/XAMPP pananglit: 'C:\\Program Files\\Tesseract-OCR\\tesseract.exe'
// Kung naay Tesseract sa server, ang SERVER ang magbasa sa ID (dili na ang browser),
// mao nga dili na ma-pasa ang peke nga text gikan sa browser. Lig-on kaayo ni.
if (!defined('ID_VERIFY_TESSERACT'))    define('ID_VERIFY_TESSERACT', '');

// TRUE = ang birthdate sa ID kinahanglan GYUD mabasa. FALSE (default) = igo na kung mabasa ang ID number
// O ang birthdate (ang usa niini igo na; ang birthdate gi-compare sa birth record sa MCRO kung mabasa).
// Ang FALSE mas luwas para sa tinood nga ID nga dili tanan detalye mabasa sa camera.
if (!defined('ID_VERIFY_REQUIRE_DOB'))  define('ID_VERIFY_REQUIRE_DOB', false);

// TRUE = ang ID number kinahanglan GYUD mabasa ug sakto ang format (mas higpit batok sa peke nga ID,
// apan mas daghan ang tinood nga ID nga ma-reject kung dili mabasa sa camera ang number, labi na ang PhilHealth).
// FALSE (default) = igo na ang ID number O ang birthdate (nga gi-compare sa MCRO record).
if (!defined('ID_VERIFY_REQUIRE_NUMBER')) define('ID_VERIFY_REQUIRE_NUMBER', false);

// Max nga scan attempts matag session sulod sa 15 minutos
if (!defined('ID_VERIFY_MAX_ATTEMPTS')) define('ID_VERIFY_MAX_ATTEMPTS', 6);

// Minimum nga gidak-on sa litrato (pixels) ug minimum sharpness (Laplacian variance)
if (!defined('ID_VERIFY_MIN_WIDTH'))     define('ID_VERIFY_MIN_WIDTH', 600);
if (!defined('ID_VERIFY_MIN_SHARPNESS')) define('ID_VERIFY_MIN_SHARPNESS', 30.0);

// Gitugotan nga layo (sa words) tali sa first name ug last name sa OCR text
if (!defined('ID_VERIFY_NAME_WINDOW'))   define('ID_VERIFY_NAME_WINDOW', 25);

// TRUE = kinahanglan naay Tesseract sa SERVER (mas lig-on batok sa peke). Kung FALSE ug walay
// Tesseract sa server, ang OCR text gikan sa browser ang gamiton (mas dali i-fake).
if (!defined('ID_VERIFY_REQUIRE_SERVER_OCR')) define('ID_VERIFY_REQUIRE_SERVER_OCR', false);
