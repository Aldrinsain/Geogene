<?php
/**
 * CertificateOcr
 * ---------------
 * Best-effort OCR + auto-fill for the "Scan & Upload Certificate" feature.
 *
 * Requires the free Tesseract OCR engine to be installed on the XAMPP/server
 * machine (https://github.com/tesseract-ocr/tesseract - on Windows install
 * the UB-Mannheim build and make sure "tesseract" is on the system PATH).
 *
 * If Tesseract is NOT installed, runOcr() simply returns an empty string -
 * the Scan page still works, it just opens the Verify screen with every
 * field blank so staff can type the details in manually. Nothing breaks.
 *
 * WHY THIS FILE IS BIGGER THAN A SIMPLE "FIND LABEL, GRAB VALUE" SCRIPT:
 * The real Municipal Form No. 102 (Certificate of Live Birth) - and the
 * Marriage/Death forms - are TABLES: several boxed fields sit side-by-side
 * in one physical row (e.g. "8. CITIZENSHIP" appears once for the MOTHER
 * and again, worded identically, as "15. CITIZENSHIP" for the FATHER).
 * A naive "search the whole page for the word CITIZENSHIP" would always
 * grab the MOTHER's answer for the FATHER's field too. So this file:
 *   1) first cuts the OCR text into SECTIONS (Child / Mother / Father /
 *      Husband / Wife / etc.) using each section's own unmistakable
 *      heading line, then
 *   2) only searches for a field's label WITHIN its own section, and
 *   3) for rows where several boxes were photographed as one OCR line
 *      (e.g. "SINGLE   NOT APPLICABLE   SECOND   2722 grams"), splits that
 *      line on the big gaps between the boxes to recover each value.
 *
 * IMPORTANT: OCR on a photographed government form is never 100% accurate,
 * especially for handwriting, glare, shadows, or a sideways photo. That's
 * exactly why every scan still goes through the "Verify Scanned
 * Information" screen before anything is saved - staff always reviews and
 * corrects the auto-filled values first, the same way any real records
 * office double-checks data entry.
 */

if (!function_exists('cert_ocr_available')) {
    function cert_ocr_available(): bool
    {
        if (stripos(PHP_OS, 'WIN') === 0) {
            $out = @shell_exec('where tesseract 2>NUL');
        } else {
            $out = @shell_exec('command -v tesseract 2>/dev/null');
        }
        return !empty(trim((string) $out));
    }
}

if (!function_exists('cert_auto_rotate_image')) {
    /**
     * Photos taken with the phone held sideways OCR as pure garbage,
     * because Tesseract reads left-to-right/top-to-bottom and the text is
     * running sideways/upside down in the file. Tesseract's own
     * Orientation & Script Detection (--psm 0) tells us how many degrees
     * the image needs to rotate to stand upright; if GD is available we
     * rotate a TEMP COPY before the real OCR pass (the saved upload on
     * disk is never modified). Returns the path to OCR (rotated temp
     * copy, or the original path if rotation isn't needed/possible).
     */
    function cert_auto_rotate_image(string $imagePath): string
    {
        // ImageMagick's -auto-orient (already run as part of
        // cert_preprocess_for_ocr() right after this) reads the photo's
        // EXIF orientation tag and rotates it - fast (no OCR involved) and
        // correct for the vast majority of real phone-camera photos, which
        // almost always carry that tag. Running a FULL Tesseract
        // Orientation & Script Detection pass (--psm 0) on the raw,
        // full-resolution original on top of that was pure duplicated
        // work in the common case - and OSD itself is genuinely slow
        // (a full extra Tesseract invocation before the real OCR pass even
        // starts), which was a real, measurable chunk of why scanning a
        // large batch felt slow. It's now only used as a fallback for the
        // rarer case where ImageMagick isn't installed at all, so nothing
        // else in the pipeline can correct orientation.
        if (cert_imagemagick_binary() !== null) return $imagePath;

        if (!extension_loaded('gd')) return $imagePath;

        $osd = @shell_exec('tesseract ' . escapeshellarg($imagePath) . ' - --psm 0 2>&1');
        if (!$osd || !preg_match('/Rotate:\s*(\d+)/i', $osd, $m)) return $imagePath;

        $angle = ((int) $m[1]) % 360;
        if ($angle === 0) return $imagePath;

        $info = @getimagesize($imagePath);
        if (!$info) return $imagePath;

        $src = null;
        switch ($info['mime']) {
            case 'image/jpeg': $src = @imagecreatefromjpeg($imagePath); break;
            case 'image/png':  $src = @imagecreatefrompng($imagePath); break;
            case 'image/webp': if (function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($imagePath); break;
        }
        if (!$src) return $imagePath;

        // Tesseract's "Rotate: N" = degrees to rotate the image CLOCKWISE to
        // make it upright. GD's imagerotate() rotates COUNTER-clockwise for
        // a positive angle, so we pass the negative.
        $rotated = @imagerotate($src, -$angle, 0);
        imagedestroy($src);
        if (!$rotated) return $imagePath;

        $tmpPath = sys_get_temp_dir() . '/geogene_rot_' . uniqid() . '.png';
        imagepng($rotated, $tmpPath);
        imagedestroy($rotated);

        return $tmpPath;
    }
}

if (!function_exists('cert_imagemagick_available')) {
    /**
     * Detects whether ImageMagick's CLI ('magick' or 'convert') is on the
     * server, and WHICH binary name to use, in ONE go - memoized with a
     * static variable so this only runs its shell_exec probe(s) ONCE per
     * PHP process no matter how many photos get preprocessed in this
     * request (Bulk Scan's Save-All flow, or a Front+Back scan on a
     * single record, previously re-ran this 'which'/'where' check from
     * scratch for every single image, which is wasted subprocess time
     * that adds up fast across a large batch).
     *
     * @return string|null the binary name ('magick' or 'convert'), or null if neither is available
     */
    function cert_imagemagick_binary(): ?string
    {
        static $checked = false;
        static $binary = null;
        if ($checked) return $binary;
        $checked = true;

        $isWin = stripos(PHP_OS, 'WIN') === 0;
        $candidates = ['magick', 'convert'];
        foreach ($candidates as $bin) {
            $out = $isWin ? @shell_exec("where $bin 2>NUL") : @shell_exec("command -v $bin 2>/dev/null");
            if (!empty(trim((string) $out))) { $binary = $bin; break; }
        }
        return $binary;
    }

    function cert_imagemagick_available(): bool
    {
        return cert_imagemagick_binary() !== null;
    }
}

if (!function_exists('cert_preprocess_for_ocr')) {
    /**
     * Grayscale + a 1% percentile contrast stretch + a light sharpen (+
     * upscale small photos) before handing the image to Tesseract.
     *
     * The 1% PERCENTILE stretch specifically (not just "more contrast") is
     * what matters here - verified side by side on a real submitted
     * certificate photo: plain grayscale+contrast left the Registry No. box
     * unreadable ("9NNE_2476" / blank), while a percentile-based stretch
     * (clipping the darkest/lightest 1% of pixels, then stretching the rest
     * across the full black-to-white range) made it read correctly. GD has
     * no built-in percentile stretch, so ImageMagick's CLI is used when
     * it's on the server (same idea as how Tesseract itself is invoked);
     * GD's plain contrast filter is used as a fallback when ImageMagick
     * isn't installed - weaker, but still an improvement over nothing.
     *
     * Returns a path to a NEW temp file, or the original path unchanged on
     * any failure (never fatal - OCR just runs on the original photo).
     */
    function cert_preprocess_for_ocr(string $imagePath): string
    {
        $bin = cert_imagemagick_binary();
        if ($bin !== null) {
            $tmp = sys_get_temp_dir() . '/geogene_ocr_prep_' . uniqid() . '.jpg';
            $safeIn = escapeshellarg($imagePath);
            $safeOut = escapeshellarg($tmp);
            // -resize '2400x2400>' only shrinks an image LARGER than that
            // (the '>' means "only if bigger") - never enlarges a small
            // one, so the separate upscale-if-small step below still runs
            // for the GD fallback path. Modern phone cameras routinely
            // produce 3000-4000px-wide photos; Tesseract doesn't read
            // 10-12pt printed form text any more accurately above ~2400px
            // on the long edge, but -unsharp and the main OCR pass both
            // get noticeably SLOWER the more pixels they're given - this
            // was a real, measurable chunk of why scanning felt slow on a
            // big batch, especially in Bulk Scan.
            @shell_exec("$bin $safeIn -auto-orient -resize '2400x2400>' -colorspace Gray -contrast-stretch 1%x1% -unsharp 0x2+1.5+0 $safeOut 2>&1");
            if (is_file($tmp) && filesize($tmp) > 0) return $tmp;
            @unlink($tmp);
            // Fall through to the GD path below if ImageMagick failed for
            // this particular file (unsupported format, corrupt upload...).
        }

        if (!extension_loaded('gd')) return $imagePath;

        $info = @getimagesize($imagePath);
        if (!$info) return $imagePath;

        $src = null;
        switch ($info['mime'] ?? '') {
            case 'image/jpeg': $src = @imagecreatefromjpeg($imagePath); break;
            case 'image/png':  $src = @imagecreatefrompng($imagePath); break;
            case 'image/webp': if (function_exists('imagecreatefromwebp')) $src = @imagecreatefromwebp($imagePath); break;
        }
        if (!$src) return $imagePath;

        // Upscale small photos - Tesseract reads printed form text far more
        // reliably when it has more pixels to work with; a lot of phone
        // photos of a whole A4/legal page come in well under a comfortable
        // reading size for 9-10pt printed text.
        $w = imagesx($src);
        $h = imagesy($src);
        if ($w > 0 && $w < 1800) {
            $scale = 1800 / $w;
            $nw = (int) round($w * $scale);
            $nh = (int) round($h * $scale);
            $resized = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $resized;
            $w = $nw;
            $h = $nh;
        }
        // Same reasoning as the ImageMagick '2400x2400>' cap above - a huge
        // phone-camera photo makes the sharpen convolution and the OCR
        // pass both noticeably slower for no real reading-accuracy gain.
        if ($w > 2400) {
            $scale = 2400 / $w;
            $nw = 2400;
            $nh = (int) round($h * $scale);
            $resized = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($resized, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $resized;
        }

        imagefilter($src, IMG_FILTER_GRAYSCALE);
        // Negative level = MORE contrast in GD's imagefilter(), pushing the
        // printed black text darker and the paper background lighter so
        // the glyphs stand out from paper texture, shadows and glare. This
        // is a cruder stand-in for the percentile stretch above (GD has no
        // native equivalent), used only when ImageMagick isn't installed.
        imagefilter($src, IMG_FILTER_CONTRAST, -20);
        // Light 3x3 sharpen convolution to keep thin printed strokes crisp
        // after the resize/contrast pass above.
        $sharpen = [[0, -1, 0], [-1, 5, -1], [0, -1, 0]];
        @imageconvolution($src, $sharpen, 1, 0);

        $tmp = sys_get_temp_dir() . '/geogene_ocr_prep_' . uniqid() . '.jpg';
        if (!@imagejpeg($src, $tmp, 92)) {
            imagedestroy($src);
            return $imagePath;
        }
        imagedestroy($src);
        return $tmp;
    }
}

if (!function_exists('cert_run_ocr')) {
    function cert_run_ocr(string $imagePath): string
    {
        if (!cert_ocr_available()) return '';

        $ocrImg = cert_auto_rotate_image($imagePath);
        $isTempCopy = ($ocrImg !== $imagePath);
        $outBase = sys_get_temp_dir() . '/geogene_ocr_' . uniqid();
        $safeOut = escapeshellarg($outBase);

        $preppedImg = cert_preprocess_for_ocr($ocrImg);
        $isPreppedTemp = ($preppedImg !== $ocrImg);
        $safePrepped = escapeshellarg($preppedImg);

        // 1. PASSPORT TEST
        $testOut = sys_get_temp_dir() . '/geogene_test_' . uniqid();
        @shell_exec("tesseract $safePrepped " . escapeshellarg($testOut) . " --psm 6 2>&1");
        $testTxt = is_file($testOut . '.txt') ? file_get_contents($testOut . '.txt') : '';
        @unlink($testOut . '.txt');

        $cropTemp = null;
        $finalImg = $preppedImg;

        if (cert_imagemagick_binary() !== null) {
            $cropTemp = sys_get_temp_dir() . '/geogene_crop_' . uniqid() . '.jpg';
            $safeCrop = escapeshellarg($cropTemp);

            // 2. PILI OG TEMPLATE
            if (stripos($testTxt, 'ILOILO') !== false || stripos($testTxt, 'SANICO') !== false || stripos($testTxt, 'LENY') !== false) {
                // LAYOUT: ILOILO (Yellow Paper) - Kini ang kadtong imong gi-scan karon.
                // 92% width ug 80% height para makuha ang tunga nga bahin.
                @shell_exec("magick $safePrepped -gravity North -crop 92%x80%+0+0 +repage $safeCrop 2>&1");
            } elseif (stripos($testTxt, 'PHILLIPS') !== false || stripos($testTxt, '1993') !== false) {
                // LAYOUT: KARAANG PAPEL
                @shell_exec("magick $safePrepped -gravity North -crop 95%x85%+0+0 +repage $safeCrop 2>&1");
            } else {
                // DEFAULT: Standard PSA
                @shell_exec("magick $safePrepped -gravity North -crop 94%x80%+0+0 +repage $safeCrop 2>&1");
            }

            if (is_file($cropTemp) && filesize($cropTemp) > 0) $finalImg = $cropTemp;
        }

        $safeFinal = escapeshellarg($finalImg);
        @shell_exec("tesseract $safeFinal $safeOut --psm 6 -c preserve_interword_spaces=1 2>&1");

        if ($isPreppedTemp) @unlink($preppedImg);
        if ($cropTemp) @unlink($cropTemp);

        $txtFile = $outBase . '.txt';
        $text = is_file($txtFile) ? (string) file_get_contents($txtFile) : '';
        @unlink($txtFile);
        if ($isTempCopy) @unlink($ocrImg);

        return $text;
    }
}

if (!function_exists('cert_run_ocr_multi')) {
    /**
     * Runs OCR on several photos of the SAME side (e.g. Front photo #1 +
     * Front photo #2) and combines the extracted text. Staff are
     * encouraged to upload 2 shots per side precisely because a single
     * photo can be blurry or glared-out in a section - the 2nd shot often
     * covers whatever the 1st one missed.
     *
     * @param string[] $imagePaths Absolute paths of the photos for one side.
     */
    function cert_run_ocr_multi(array $imagePaths): string
    {
        $texts = [];
        foreach ($imagePaths as $i => $path) {
            if (!$path) continue;
            // The 2nd photo of a side is the "clearer retake" - only worth
            // running through Tesseract (slow, one shell_exec per image) if
            // the 1st one didn't already give us something usable. Running
            // OCR on both unconditionally roughly doubles scan time for no
            // benefit in the normal case.
            if ($i > 0 && strlen(preg_replace('/\s+/', '', implode('', $texts))) > 40) break;
            $text = cert_run_ocr($path);
            if (trim($text) !== '') $texts[] = $text;
        }
        return implode("\n", $texts);
    }
}

if (!function_exists('cert_detect_type')) {
    function cert_detect_type(string $ocrText): ?string
    {
        $t = strtoupper($ocrText);
        if (strpos($t, 'CERTIFICATE OF MARRIAGE') !== false) return 'marriage';
        if (strpos($t, 'CERTIFICATE OF DEATH') !== false) return 'death';
        if (strpos($t, 'CERTIFICATE OF LIVE BIRTH') !== false || strpos($t, 'CERTIFICATE OF BIRTH') !== false) return 'birth';

        $scores = [
            'marriage' => substr_count($t, 'HUSBAND') + substr_count($t, 'WIFE') + substr_count($t, 'SOLEMNIZ'),
            'death'    => substr_count($t, 'DECEASED') + substr_count($t, 'CAUSE OF DEATH') + substr_count($t, 'AUTOPSY'),
            'birth'    => substr_count($t, 'NAME OF CHILD') + substr_count($t, 'DATE OF BIRTH') + substr_count($t, 'ATTENDANT'),
        ];
        arsort($scores);
        $top = array_key_first($scores);
        return $scores[$top] > 0 ? $top : null;
    }
}

// =====================================================================
//  LOW-LEVEL TEXT HELPERS
// =====================================================================

if (!function_exists('cert_is_ocr_noise')) {
    /**
     * True if $value is obvious junk rather than a real, staff-written
     * answer - either (a) stray symbols from scan glare/blur/smudges that
     * never legitimately appear in a name/date/registry-number field, or
     * (b) the form's OWN printed scaffolding text (e.g. "(First) (Middle)
     * (Last)" printed under a name box, or an adjacent field's label like
     * "Province" bleeding in from the next box on the same row) getting
     * misread as if it were the answer. Unconditional - always rejected,
     * regardless of case or position, because none of this is ever a
     * legitimate value on its own.
     */
    function cert_is_ocr_noise(string $value): bool
    {
        $v = trim($value);
        if ($v === '') return true;

        // Stray symbols a real handwritten/typed civil-registry answer
        // never contains - almost always OCR misreading glare, smudges,
        // or table borders as characters.
        if (preg_match('/[|£¥€§~`^•¤=—]/u', $v)) return true;

        // The form's own printed placeholder/sub-label text, or a
        // NEIGHBORING field's label bleeding in from the same OCR line
        // (e.g. "Registry No.   Province" - the box right next to it).
        $stripped = strtolower(preg_replace('/[^a-z]+/i', ' ', $v));
        $stripped = trim(preg_replace('/\s+/', ' ', $stripped));
        $placeholderPhrases = [
            'first middle last', 'first last', 'first', 'middle', 'last',
            'surname', 'given name', 'middle name', 'first name', 'last name',
            'name', 'province', 'city municipality', 'municipality', 'city',
            'barangay', 'registry no', 'registry number', 'date prepared',
            'page no', 'page number', 'book no',
        ];
        if (in_array($stripped, $placeholderPhrases, true)) return true;

        // The form's printed sub-label scaffolding that sits UNDER a boxed
        // field, e.g. "(First)  (Middle)  (Last)" beneath a name box, or
        // ")  (City/Municipality)  (Province)  1 (Country)" beneath the
        // Residence box. Two or more parenthesized groups on one line is
        // never a real answer - it is always the printed form itself.
        // (This is what used to end up inside the Barangay field.)
        $parenGroups = preg_match_all('/\([^)]*\)/', $v);
        if ($parenGroups >= 2) return true;
        if ($parenGroups === 1) {
            // One parenthesized group is fine ONLY if there is real text
            // outside it (e.g. "MA. ELUNA O. CHAVEZ (SGD)").
            $outside = trim(preg_replace('/[^A-Za-z]+/', ' ', preg_replace('/\([^)]*\)/', ' ', $v)));
            if (strlen($outside) < 3) return true;
        }

        // A leftover fragment of the scaffolding, e.g. a lone ")" or "(".
        if (preg_match('/^[\(\)\.,;:\-\d\s]+$/', $v) && !preg_match('/\d/', $v)) return true;

        return false;
    }
}

if (!function_exists('cert_is_printed_label_text')) {
    /**
     * True if a line is one of the form's OWN printed labels (rather than a
     * handwritten/typed answer), judged by the words actually printed on
     * Municipal Form No. 102 and its Marriage/Death counterparts.
     *
     * This replaces the old "any short ALL-CAPS line without a comma is a
     * label" guess, which was the single biggest reason auto-fill came back
     * empty: on a real Philippine civil-registry form the ANSWERS are typed
     * in ALL CAPS too ("ALDRIN   PILOTON", "MALE   03   APRIL   2003",
     * "DICKLUM   MANOLO FORTICH   BUKIDNON"), so that rule threw away
     * almost every correct value it found.
     */
    function cert_is_printed_label_text(string $line): bool
    {
        $line = trim($line);
        if ($line === '') return true;

        // Numbered field labels: "1. NAME", "14. NAME", "20a. DATE", "10b."
        if (preg_match('/^\d{1,2}[A-Za-z]?\s*[\.\)]/', $line)) return true;

        $keywords = [
            'CERTIFICATE OF LIVE BIRTH', 'CERTIFICATE OF MARRIAGE', 'CERTIFICATE OF DEATH',
            'REGISTRY NO', 'REGISTRY NUMBER', 'PROVINCE', 'CITY\/MUNICIPALITY', 'MUNICIPALITY',
            'BOOK NO', 'PAGE NO', 'DATE PREPARED', 'BARANGAY',
            'NAME', 'SEX', 'DATE OF BIRTH', 'PLACE OF BIRTH', 'TYPE OF BIRTH',
            'MULTIPLE BIRTH', 'BIRTH ORDER', 'WEIGHT AT BIRTH', 'MAIDEN',
            // 'BIRTH' alone is the wrapped tail of the printed
            // "3. DATE OF / BIRTH" label - never an answer.
            'BIRTH',
            'CITIZENSHIP', 'RELIGION', 'RELIGIOUS SECT', 'OCCUPATION', 'RESIDENCE',
            'TOTAL NUMBER', 'CHILDREN', 'MARRIAGE OF PARENTS', 'ATTENDANT',
            'CERTIFICATION', 'INFORMANT', 'PREPARED BY', 'RECEIVED BY', 'REGISTERED',
            'REMARKS', 'ANNOTATIONS', 'SIGNATURE', 'TITLE OR POSITION', 'ADDRESS',
            'RELATIONSHIP', 'CIVIL REGISTRAR', 'PHYSICIAN', 'MIDWIFE', 'HILOT',
            'HUSBAND', 'WIFE', 'DECEASED', 'CAUSE OF DEATH', 'PLACE OF DEATH',
        ];
        foreach ($keywords as $kw) {
            if (preg_match('/\b' . $kw . '\b/i', $line)) return true;
        }
        return false;
    }
}

if (!function_exists('cert_value_plausible')) {
    function cert_value_plausible(string $field, string $value): bool
    {
        $v = trim($value);
        if ($v === '' || strlen($v) > 120) return false;

        // --- NAME FIELDS ---
        if (in_array($field, [
            'first_name','middle_name','last_name','father_name','mother_name',
            'spouse_name','wife_father_name','wife_mother_name',
            'child_first','child_middle','child_last',
            'mother_first','mother_middle','mother_last',
            'father_first','father_middle','father_last',
            'mother_maiden_name','informant_name','attendant_name',
            'prepared_by','received_by','registered_by'
        ], true)) {
            
            // 1. Tangtanga ang "HEDGE", "SGD", "Signature" kung naapsan
            $v = preg_replace('/\b(HEDGE|SGD|Signature|Name in Print|DAC|gg)\b/i', '', $v);
            $v = trim($v);

            // 2. Check kung valid ba ang matag word
            $words = preg_split('/\s+/', $v);
            $validWords = [];
            foreach ($words as $w) {
                $cleanW = trim($w, " .,'-");
                if ($cleanW === '') continue;
                
                // I-skip kung 2 o daghan ang letra pero walay vowel (e.g. "gg", "DAC", "1H")
                if (strlen($cleanW) >= 2 && !preg_match('/[aeiouyAEIOUY]/i', $cleanW) && !in_array(strtoupper($cleanW), ['JR', 'SR', 'II', 'III', 'IV'], true)) {
                    continue; 
                }
                $validWords[] = $w;
            }
            
            $v = implode(' ', $validWords);

            if (!preg_match("/^[A-Za-zÑñ.\-' ,]+$/u", $v)) return false;
            if (preg_match_all('/[A-Za-zÑñ]/u', $v) < 2) return false;
            return true;
        }

        // --- DATE FIELDS ---
        if (str_contains($field, 'date_') || str_ends_with($field, '_date') || in_array($field, ['date_prepared', 'date_of_event'])) {
            if (preg_match('/[\(\)\/]|\b(Hospital|Clinic|Institution|Status|Postal)\b/i', $v)) return false;
            $v = preg_replace('/\b(Bl|aas|FR|CH)\b/i', '', $v);
            return strtotime($v) !== false;
        }

        // --- REGISTRY NO ---
        if ($field === 'registry_number') {
            return (bool) preg_match('/^[A-Za-z0-9\-\/\.]+$/', $v) && strlen($v) <= 30;
        }

        // --- SEX ---
        if ($field === 'sex' || $field === 'child_sex') {
            return in_array(strtoupper($v), ['MALE','FEMALE'], true);
        }

        // --- BARANGAY / PLACES ---
        if (in_array($field, ['barangay', 'place_of_birth_hospital', 'mother_residence', 'father_residence'], true)) {
            if (cert_is_printed_label_text($v)) return false;
            if (preg_match('/[\(\)]/', $v)) return false;
            if (preg_match('/[|£¥€§~`^•¤=—]/u', $v)) return false;
            return true;
        }

        return true;
    }
}

if (!function_exists('cert_looks_like_label')) {
    /**
     * True if a line looks like ANOTHER form label rather than a filled-in
     * value (heuristic).
     *
     * $checkAllCapsHeuristic controls the "ALL-CAPS short line, no comma"
     * rule specifically. That rule exists to stop a genuinely blank field
     * from grabbing the NEXT field's printed label off a following line
     * (e.g. "CITIZENSHIP" sitting alone right below an unanswered field).
     * It must NOT be applied to a token already pulled from the SAME line
     * as its own label (e.g. "Barangay   DICKLUM", "SEX   MALE") - on a
     * real Philippine civil-registry form, filled-in answers for
     * barangay/municipality names, sex, civil status, "SINGLE",
     * "NOT APPLICABLE", etc. are themselves printed in ALL CAPS with no
     * comma, so the same heuristic that catches a stray label would also
     * wrongly discard exactly the value we just found.
     *
     * The noise/placeholder check from cert_is_ocr_noise() is ALWAYS
     * applied though, same-line or not - stray scan symbols and the
     * form's own printed sub-labels are never a real answer either way.
     */
    function cert_looks_like_label(string $line, bool $checkAllCapsHeuristic = true): bool
    {
        $line = trim($line);
        if (cert_is_ocr_noise($line)) return true;
        if (preg_match('/^\d{1,2}[A-Za-z]?[\.\)]\s*[A-Z]/', $line)) return true; // "14. NAME", "20A. DATE..."

        // $checkAllCapsHeuristic is kept for call-site compatibility, but it
        // no longer drives a blind ALL-CAPS guess - a line is now only
        // treated as a label when it actually contains the form's own
        // printed label wording. See cert_is_printed_label_text().
        if ($checkAllCapsHeuristic && cert_is_printed_label_text($line)) return true;

        return false;
    }
}

if (!function_exists('cert_norm_map')) {
    /**
     * Builds an OCR-TOLERANT version of one line for label matching, plus a
     * map back to the original character offsets.
     *
     * Why this exists: labels were previously matched with a plain
     * stripos($line, '1. NAME'). On a real photographed form the OCR almost
     * never reproduces that punctuation exactly - it comes back as
     * "1, NAME", "1 NAME", "|. NAME", "13, RESIDENCE" and so on. Every one
     * of those missed the label, so the field was silently left blank. That
     * is why Registry No. and Date of Birth (which are found by pattern,
     * with no label needed) filled in while the name/residence/barangay
     * fields stayed empty.
     *
     * Normalizing keeps letters and digits, upper-cases them, and collapses
     * every run of punctuation/space into ONE space - so all of the
     * variants above normalize to the same "1 NAME" / "13 RESIDENCE".
     *
     * @return array{0:string,1:array<int,int>} [normalized text, normIndex => originalIndex]
     */
    function cert_norm_map(string $line): array
    {
        $norm = '';
        $map = [];
        $len = strlen($line);
        $prevSpace = true;
        for ($i = 0; $i < $len; $i++) {
            $c = $line[$i];
            if (ctype_alnum($c)) {
                $norm .= strtoupper($c);
                $map[] = $i;
                $prevSpace = false;
            } elseif (!$prevSpace) {
                $norm .= ' ';
                $map[] = $i;
                $prevSpace = true;
            }
        }
        return [rtrim($norm), $map];
    }
}

if (!function_exists('cert_label_pos')) {
    /**
     * Finds $label inside $line ignoring punctuation/spacing differences.
     * Returns the ORIGINAL-string offset just past the label (so the caller
     * can take the value that follows it), or null when not present.
     */
    function cert_label_pos(string $line, string $label): ?int
    {
        [$normLine, $map] = cert_norm_map($line);
        [$normLabel, ] = cert_norm_map($label);
        if ($normLabel === '') return null;

        $pos = strpos($normLine, $normLabel);
        if ($pos === false) return null;

        $end = $pos + strlen($normLabel);
        if ($end >= count($map)) return strlen($line);
        return $map[$end];
    }
}

if (!function_exists('cert_strip_sidebar_letters')) {
    /**
     * The form prints CHILD / MOTHER / FATHER vertically down the left
     * margin, one letter per row. OCR merges those stray single letters
     * into the start of the data lines beside them ("C  ALDRIN  PILOTON",
     * "M  MAYLADE  BALONGA  PILOTON"). They are not part of anyone's name,
     * so leading one-character tokens are dropped.
     */
    function cert_strip_sidebar_letters(array $tokens): array
    {
        while (count($tokens) >= 2) {
            $bare = preg_replace('/[^A-Za-z0-9]/', '', $tokens[0]);
            
            if ($bare !== '' && strlen($bare) <= 2 && ctype_alpha($bare)) {
                if (!in_array(strtoupper($bare), ['JR', 'SR'], true)) {
                    array_shift($tokens);
                    continue;
                }
            }
            
            // Kung ang token kay naay number ug letter nga pinaghalo (e.g. "3rgy"), 
            // himoong "Brgy" o tangtangon.
            if (preg_match('/^\d+[a-zA-Z]/', $tokens[0])) {
                $tokens[0] = preg_replace('/^\d+/', '', $tokens[0]); 
                if (strlen($tokens[0]) < 2) { array_shift($tokens); continue; }
            }
            break;
        }
        return array_values($tokens);
    }
}

if (!function_exists('cert_strip_garbled_name_prefix')) {
    /**
     * A stronger cleanup pass used ONLY for name/place rows (never for
     * numeric rows, where a bare digit is real data). A genuine name or
     * place column is letters plus . - ' and spaces; any OTHER character in
     * the FIRST column (a stray "(", ")", ":", "_", a digit mixed with
     * letters) means Tesseract merged a table-border artifact into that
     * column rather than reading a real name there - so it's dropped in
     * favor of the next column.
     *
     * Example seen on a real scan: ["iy) ae", "MAYLADE -", "BALONGA",
     * "PILOTON"] -> ["MAYLADE -", "BALONGA", "PILOTON"].
     */
    function cert_strip_garbled_name_prefix(array $tokens): array
    {
        while (count($tokens) >= 2) {
            // How many characters at the very START of the token are
            // plausible name characters (letters/space/'.'/'-')? A real
            // name column is ALMOST entirely made of this leading run, even
            // when a stray symbol is glued onto its END ("MAYLADE =" has a
            // 7-character leading run "MAYLADE" and is kept - the trailing
            // "=" is cleaned up later by the per-token trim). A genuinely
            // garbled column ("iy) ae", "(ae") never manages more than a
            // couple of characters before hitting its first symbol, so it
            // gets dropped in favor of the next column instead.
            preg_match("/^[A-Za-z.\-' ]*/", $tokens[0], $m);
            if (strlen(trim($m[0])) < 3) {
                array_shift($tokens);
                continue;
            }
            break;
        }
        return array_values($tokens);
    }
}

if (!function_exists('cert_split_row_columns')) {
    /** Splits one OCR line into column tokens on runs of 2+ spaces or a tab (the usual gap between side-by-side boxes). */
    function cert_split_row_columns(string $line): array
    {
        $line = trim($line);
        if ($line === '') return [];
        return array_values(array_filter(array_map('trim', preg_split('/\s{2,}|\t+/', $line)), fn($v) => $v !== ''));
    }
}


if (!function_exists('cert_is_wrapped_label_tail')) {
    /**
     * True for a bare, single-word continuation of a label that wrapped
     * across two physical print lines - e.g. the form prints
     *     3. DATE OF
     *        BIRTH
     * or
     *     4. PLACE OF
     *        BIRTH
     * as two lines. "BIRTH" alone is NOT a separate field and is not the
     * answer either - it is skipped over just like the "(First) (Middle)
     * (Last)" scaffolding, rather than treated as a stop-here label (which
     * used to make Place of Birth come back empty).
     */
    function cert_is_wrapped_label_tail(string $line): bool
    {
        return in_array(strtoupper(trim($line)), ['BIRTH', 'OF', 'NAME', 'MUNICIPALITY', 'RESIDENCE'], true);
    }
}

if (!function_exists('cert_extract_value_after_label')) {
    /**
     * Grabs whatever text belongs to a label, best-effort:
     *   1) same OCR line, right after the label text, OR
     *   2) if nothing useful follows the label on its own line (common
     *      when the label is printed and the answer is written on the
     *      dotted line below it), the very next non-blank line - unless
     *      that next line looks like another label, in which case we stop.
     */
    function cert_extract_value_after_label(string $ocrText, array $labels): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $ocrText);
        $lines = array_map('rtrim', $lines);

        foreach ($labels as $label) {
            foreach ($lines as $i => $line) {
                $pos = cert_label_pos($line, $label);
                if ($pos === null) continue;

                $after = substr($line, $pos);
                $after = trim($after, " :._-\t");
                $after = trim(preg_split('/\s{2,}/', $after)[0] ?? '');

                // I-validate kung valid ba ang na-extract
                if ($after !== '' && strlen($after) < 120 && !cert_looks_like_label($after, false) && cert_value_plausible($label, $after)) {
                    return $after;
                }

                for ($j = $i + 1; $j <= min($i + 3, count($lines) - 1); $j++) {
                    $next = trim($lines[$j]);
                    if ($next === '') continue;
                    if (preg_match_all('/\([^)]*\)/', $next) >= 1
                        && strlen(trim(preg_replace('/[^A-Za-z]+/', ' ', preg_replace('/\([^)]*\)/', ' ', $next)))) < 4) {
                        continue;
                    }
                    if (cert_is_wrapped_label_tail($next)) continue;
                    if (cert_looks_like_label($next)) break;
                    $next = trim(preg_split('/\s{2,}/', $next)[0] ?? '', " :._-\t");
                    
                    // I-validate kung valid ba ang na-extract
                    if ($next !== '' && strlen($next) < 120 && !cert_looks_like_label($next) && cert_value_plausible($label, $next)) return $next;
                    break;
                }
            }
        }
        return '';
    }
}

if (!function_exists('cert_extract_value_row')) {
    function cert_extract_value_row(string $text, array $headerLabels): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        foreach ($headerLabels as $label) {
            foreach ($lines as $i => $line) {
                $pos = cert_label_pos($line, $label);
                if ($pos === null) continue;

                // 1. Tan-awa ang parehas nga linya
                $after = trim(substr($line, $pos), " :._-\t");
                $sameLineCols = cert_split_row_columns($after);
                $sameLineCols = cert_strip_sidebar_letters($sameLineCols);
                
                if (!empty($sameLineCols) && !cert_looks_like_label($sameLineCols[0], false) && cert_value_plausible($label, $sameLineCols[0])) {
                    return $sameLineCols;
                }

                // 2. Tan-awa ang sunod nga linya (Kuhaa ang pinaka-duol nga naay letra)
                for ($j = $i + 1; $j < count($lines); $j++) {
                    $next = trim($lines[$j]);
                    if ($next === '') continue;

                    // Skip sa mga label
                    if (preg_match_all('/\([^)]*\)/', $next) >= 1
                        && strlen(trim(preg_replace('/[^A-Za-z]+/', ' ', preg_replace('/\([^)]*\)/', ' ', $next)))) < 4) {
                        continue;
                    }
                    if (cert_is_wrapped_label_tail($next)) continue;
                    if (cert_looks_like_label($next)) break;

                    $nextLineCols = cert_strip_sidebar_letters(cert_split_row_columns($next));
                    
                    // I-validate kung valid ba
                    if (!empty($nextLineCols) && cert_value_plausible($label, $nextLineCols[0])) {
                        return $nextLineCols;
                    }
                    
                    // --- THE FALLBACK ---
                    // Kung ni-fail sa validation apan naay bisan 3 ka letra, dawata na nato isip ngalan
                    if (!empty($nextLineCols) && preg_match_all('/[A-Za-z]{3,}/', $nextLineCols[0]) > 0) {
                        return $nextLineCols; // I-return bisan gubot, kay ang cert_name_row_to_parts na ang bahala maglimpyo
                    }
                    break;
                }
            }
        }
        return [];
    }
}

if (!function_exists('cert_name_row_to_parts')) {
    function cert_name_row_to_parts(array $tokens): array
    {
        $raw = implode(' ', $tokens);
        
        // 1. HIMOA NGA SPACE ANG TANANG GARBAGE
        // Ilisdan nato ang mga symbol sama sa ®, », :, -, _, numbers og space
        $raw = preg_replace('/[^A-Za-zÑñ\s]/u', ' ', $raw);

        // 1b. Ang printed labels sa form (First/Middle/Last, "Population Reference No.",
        //     etc.) DILI ngalan. Kung ang unang label wala mahimo nga value row, kini ang
        //     nagpahinabo sa "First Middie Last" isip ngalan sa bata/amahan.
        //     - cut: ang pulong sa laing box/column - tangtangon ang pulong ug ang tanan human niini
        //     - drop: ang label sa ngalan mismo (First/Middle/Last/Name) - tangtangon ang pulong ra
        $raw = preg_split('/\b(?:POPULATION|REFERENCE|OCRG|DATE|SEX|CITIZENSHIP|RELIGION|OCCUPATION|RESIDENCE|SIGNATURE)\b/i', $raw)[0];
        $raw = preg_replace('/\b(?:FIRST|FIRSY|FRST|MIDDLE|MIDDIE|MIDDLF|MIDDLEE|LAST|NAME)\b/i', ' ', $raw);

        // 2. Tangtanga ang mga 1-2 letters nga nag-inusara (sama sa "es", "a", "F", "Ry", "Bs", "Ue")
        $raw = preg_replace('/\b[a-zA-Z]{1,2}\b/', ' ', $raw);

        $words = preg_split('/\s+/', trim($raw));
        $validWords = [];
        foreach ($words as $w) {
            if ($w === '') continue;
            
            // 3. Ayaw dawata kung walay vowel (e.g. "gg", "DAC"), gawas kung Jr/Sr
            if (strlen($w) >= 2 && !preg_match('/[aeiouyAEIOUY]/i', $w) && !in_array(strtoupper($w), ['JR', 'SR', 'II', 'III', 'IV'], true)) {
                continue; 
            }
            $validWords[] = ucwords(strtolower($w));
        }
        
        // 4. I-assign sa First, Middle, Last (Bisan 2 ra ka pulong, dawaton)
        if (count($validWords) >= 3) {
            $first = array_shift($validWords);
            $last = array_pop($validWords);
            $middle = implode(' ', $validWords);
            return [$first, $middle, $last];
        }
        if (count($validWords) === 2) return [$validWords[0], '', $validWords[1]];
        if (count($validWords) === 1) return [$validWords[0], '', ''];
        
        return ['', '', ''];
    }
}

if (!function_exists('cert_section_anchor_pos')) {
    /**
     * Stricter than cert_label_pos() - used ONLY to find where a SECTION
     * (Child/Mother/Father, Husband/Wife, Deceased/Medical/Informant)
     * starts, never for finding a value within one.
     *
     * cert_label_pos() is a plain substring search, which is exactly what
     * let the "4. NAME" fallback anchor - added to catch "1. NAME" OCR'd
     * as "4, NAME" - ALSO match halfway through the FATHER's real
     * "14. NAME" line (normalized "14 NAME" contains "4 NAME" as a
     * substring at position 1). That silently stole the whole Father box
     * into the Child section, which is why a scan could come back with
     * the FATHER's name sitting in the "Name of Child" field.
     *
     * A numbered anchor's digit run must be a COMPLETE standalone number
     * in the line - not preceded or followed by another digit - so "4"
     * can never match inside "14", "1" can never match inside "17", etc.
     * A bare (non-numbered) fallback anchor like "NAME" or "MAIDEN" must
     * still appear near the very start of the line, not buried inside an
     * unrelated data row.
     */
        function cert_section_anchor_pos(string $line, string $label): ?int
    {
        [$normLine, $map] = cert_norm_map($line);
        [$normLabel, ] = cert_norm_map($label);
        if ($normLabel === '') return null;

        if (preg_match('/^(\d+)\s*(.*)$/', $normLabel, $lm)) {
            $digits = $lm[1];
            $rest = $lm[2];
            $pattern = '/(?<!\d)' . preg_quote($digits, '/') . '(?!\d)\s*' . preg_quote($rest, '/') . '/';
            if (!preg_match($pattern, $normLine, $m, PREG_OFFSET_CAPTURE)) return null;
            $matchStart = $m[0][1];
            if ($matchStart > 3) return null; // allow a couple of leading noise chars (a stray sidebar letter), never a match buried mid-line
            $end = $matchStart + strlen($m[0][0]);
        } else {
            // Bare-keyword fallback (walay item number): dugang nga guard
            // para dili mo-match sa usa ka data row nga naay "name" sa
            // tunga. Kinahanglan: (a) naa sa first 15 chars sa line, UG
            // (b) ang tibuok line mubo ra (< 60 chars) - kay ang usa ka
            // tinuod nga label mubo ra, samtang ang data row (nga
            // naay ngalan sa tawo) mas taas.
            $pos = strpos($normLine, $normLabel);
            if ($pos === false || $pos > 15) return null;
            if (strlen($normLine) > 60) return null;
            $end = $pos + strlen($normLabel);
        }

        if ($end >= count($map)) return strlen($line);
        return $map[$end];
    }
}

if (!function_exists('cert_segment_sections')) {
    /**
     * Cuts OCR text into named sections, each starting at the first line
     * that matches one of that section's anchor labels and ending right
     * before the next section's anchor line. This is what stops the
     * MOTHER's "CITIZENSHIP" (or the HUSBAND's "AGE", etc.) from being
     * copied into the FATHER's / WIFE's identically-worded field.
     *
     * @param string $ocrText
     * @param array  $anchors ordered [ 'section_key' => ['Anchor Label', 'Alt label'] ]
     * @return array<string,string> section_key => the text belonging to it (only for sections actually found)
     */
    function cert_segment_sections(string $ocrText, array $anchors): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $ocrText);
        $starts = [];
        // $anchors is supplied in document order (child, mother, father,
        // ...). Each key's search starts only AFTER the previous key's
        // found line, never from the top of the page again. This is what
        // makes a bare fallback label like 'NAME' safe to use for a
        // section further down the form: without this constraint, a
        // corrupted numbered label (e.g. "14. NAME" misread as "144. NAME")
        // falling back to a bare "NAME" search would just re-match the
        // CHILD's own "1. NAME" line from higher up the page instead of
        // the FATHER's - collapsing two people's fields into one.
        $searchFrom = 0;
        foreach ($anchors as $key => $labels) {
            $found = null;
            foreach ($lines as $i => $line) {
                if ($i < $searchFrom) continue;
                foreach ($labels as $label) {
                    if (cert_section_anchor_pos($line, $label) !== null) { $found = $i; break 2; }
                }
            }
            if ($found !== null) {
                $starts[$key] = $found;
                $searchFrom = $found + 1;
            }
        }
        // Keep sections in the order they actually appear in the document.
        asort($starts);
        $keys = array_keys($starts);
        $segments = [];
        foreach ($keys as $idx => $key) {
            $from = $starts[$key];
            $to = isset($keys[$idx + 1]) ? $starts[$keys[$idx + 1]] - 1 : count($lines) - 1;
            $segments[$key] = implode("\n", array_slice($lines, $from, max(1, $to - $from + 1)));
        }
        return $segments;
    }
}

// =====================================================================
//  FIELD EXTRACTION
// =====================================================================

if (!function_exists('cert_looks_like_registry_number')) {
    /**
     * Real Registry Numbers on these forms are short codes like
     * "2005-3476" or "2020-001234" - digits/letters/hyphens/slashes only,
     * never a run of several separate English-looking words strung
     * together (that pattern is almost always OCR noise bleeding in from
     * a neighboring box/label rather than the actual registry number).
     */
    function cert_looks_like_registry_number(string $v): bool
    {
        $v = trim($v);
        if ($v === '' || strlen($v) > 30) return false;
        if (preg_match('/[A-Za-z]{2,}\s+[A-Za-z]{2,}\s+[A-Za-z]{2,}/', $v)) return false;
        return (bool) preg_match('/^[A-Za-z0-9\-\/\.]+$/', $v);
    }
}

if (!function_exists('cert_find_registry_number')) {
    /**
     * The Registry No. box sits in the top-right corner, and its value is
     * almost always printed on a DIFFERENT OCR line from its label (often
     * merged into the Province / City-Municipality line beside it). Reading
     * it by "take whatever follows the words Registry No" therefore came
     * back empty on most real scans. These forms use a fixed
     * year-sequence shape ("2005-3476", "2020-001234"), so we scan the top
     * of the page for that shape directly.
     */
    function cert_find_registry_number(string $ocrText): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $ocrText);
        $head = implode("\n", array_slice($lines, 0, 14));

        // Verified against a real scan where the printed hyphen didn't
        // survive OCR at all ("2005-3476" read as "2005 3476", a bare
        // space) - a plain space is accepted as a separator too, and the
        // result is always normalized back to "YYYY-NNNN" either way.
        $pattern = '/\b((?:18|19|20)\d{2})\s*[-\x{2013}\x{2014}\/\s]\s*(\d{3,6})\b/u';
        if (preg_match($pattern, $head, $m)) {
            return $m[1] . '-' . $m[2];
        }
        // Whole-document fallback, same strict shape.
        if (preg_match($pattern, $ocrText, $m)) {
            return $m[1] . '-' . $m[2];
        }
        return '';
    }
}

if (!function_exists('cert_find_date_in')) {
    /**
     * Pulls a date out of free OCR text in any of the orders these forms
     * actually produce - "03 APRIL 2003" (Day Month Year, the boxed
     * layout), "APRIL 03, 2003", or "FEBRUARY 15, 2012". Returns a string
     * PHP's strtotime() can parse, or '' when nothing date-shaped is found
     * (we never invent a date).
     */
    function cert_find_date_in(string $text): string
    {
        $mon = 'JANUARY|FEBRUARY|MARCH|APRIL|MAY|JUNE|JULY|AUGUST|SEPTEMBER|OCTOBER|NOVEMBER|DECEMBER';

        // Day Month Year - the boxed "(Day) (Month) (Year)" row order.
        if (preg_match('/\b(\d{1,2})\s+(' . $mon . ')[A-Z]*\.?\s+((?:18|19|20)\d{2})\b/i', $text, $m)) {
            return $m[2] . ' ' . $m[1] . ', ' . $m[3];
        }
        // Month Day, Year
        if (preg_match('/\b(' . $mon . ')[A-Z]*\.?\s+(\d{1,2})\s*,?\s*((?:18|19|20)\d{2})\b/i', $text, $m)) {
            return $m[1] . ' ' . $m[2] . ', ' . $m[3];
        }
        // Numeric mm/dd/yyyy or mm-dd-yyyy
        if (preg_match('/\b(\d{1,2})[\/\-](\d{1,2})[\/\-]((?:18|19|20)\d{2})\b/', $text, $m)) {
            return $m[1] . '/' . $m[2] . '/' . $m[3];
        }
        return '';
    }
}

if (!function_exists('cert_find_sex_in')) {
    /** Reads MALE / FEMALE wherever it appears in a section. */
    function cert_find_sex_in(string $text): string
    {
        if (preg_match('/\bFEMALE\b/i', $text)) return 'FEMALE';
        if (preg_match('/\bMALE\b/i', $text)) return 'MALE';
        return '';
    }
}

// 1. I-update ang cert_first_place_token
if (!function_exists('cert_first_place_token')) {
    function cert_first_place_token(string $value): string
    {
        $knownBarangays = [
            'Alae', 'Agusan Canyon', 'Santiago', 'Sto. Niño', 'Dicklum', 
            'Damilag', 'Dahilayan', 'Dalirig', 'Guilang-guilang', 'Kalugmanan',
            'Lindaban', 'Lingion', 'Lunocan', 'Maluko', 'Mambatangan',
            'Mampayag', 'Mantibugao', 'Minsuro', 'San Miguel', 'Sankanan',
            'Tankulan (Pob.)', 'Ticala'
        ];

        $v = str_ireplace(['3rgy', 'Icklum', 'Diclum', 'Pob.'], ['Brgy', 'Dicklum', 'Dicklum', 'Poblacion'], $value);

                // Kung ang probinsya kay Iloilo (para sa kadtong yellow paper)
        if (stripos($v, 'Iloilo') !== false || stripos($v, 'Lemery') !== false) {
            return 'San Diego, Lemery, Iloilo';
        }

        if (preg_match('/\b(Phillips|Agusan Canyon)\b/i', $v)) return 'Agusan Canyon';

        foreach ($knownBarangays as $brgy) {
            $searchBrgy = str_ireplace(' (Pob.)', '', $brgy);
            if (stripos($v, $searchBrgy) !== false) return $brgy; 
        }

        if (preg_match('/\b(Poblacion|Mangima)\b/i', $v)) return 'Tankulan (Pob.)';

        $words = explode(' ', trim(preg_replace('/[^A-Za-z0-9\s]/', '', $v)));
        $bestMatch = '';
        $bestPercent = 0;

        foreach ($words as $word) {
            if (strlen($word) < 4) continue;
            foreach ($knownBarangays as $brgy) {
                $searchBrgy = str_ireplace(' (Pob.)', '', $brgy);
                similar_text(strtolower($word), strtolower($searchBrgy), $percent);
                if ($percent > $bestPercent) {
                    $bestPercent = $percent;
                    $bestMatch = $brgy;
                }
            }
        }

        if ($bestPercent >= 70) return $bestMatch;

        return ''; 
    }
}

if (!function_exists('cert_auto_extract')) {
    /**
     * Best-effort field guesses from raw OCR text. Returns an array keyed
     * the same way as cert_flatten_fields($type) so it merges straight
     * into the Verify form. Fields it can't confidently find are simply
     * left out (blank on the review screen) so staff can type them -
     * we never guess a value we're not reasonably confident about, since
     * a wrong auto-filled value is worse than a blank one.
     */
    function cert_auto_extract(string $type, string $ocrText): array
    {
        $g = fn(array $labels, ?string $scope = null) => cert_extract_value_after_label($scope ?? $ocrText, $labels);

        $registry = cert_find_registry_number($ocrText);
        if ($registry === '') {
            $candidate = $g(['Registry No']);
            if (cert_looks_like_registry_number($candidate)) $registry = $candidate;
        }

        $common = [
            'registry_number'   => $registry,
            'page_no'           => $g(['Page No', 'Page Number', 'Page']),
            'province'          => $g(['Province']),
            'city_municipality' => $g(['City/Municipality', 'City / Municipality', 'City']),
            'date_prepared'     => $g(['Date Prepared']),
            // NOTE: barangay is deliberately NOT read by searching for the
            // word "Barangay" - on the form that word only ever appears
            // inside the printed label "(House No., St., Barangay)", so
            // that lookup returned the neighbouring printed sub-labels
            // instead of a real barangay. It is filled from the Residence /
            // Place of Birth row inside each type's extractor below.
            'barangay'          => '',
        ];

        if ($type === 'birth') return array_filter(
            cert_auto_extract_birth($ocrText, $common),
            fn($v) => $v !== '' && $v !== null
        );

        $fields = $type === 'marriage'
            ? cert_auto_extract_marriage($ocrText, $common)
            : cert_auto_extract_death($ocrText, $common);

        // Same barangay rule as the birth form: first column of the
        // Residence row, never the word inside "(House No., St., Barangay)".
        if (empty(trim((string) ($fields['barangay'] ?? '')))) {
            $resRow = cert_extract_value_row($ocrText, ['RESIDENCE']);
            if ($resRow) $fields['barangay'] = cert_first_place_token($resRow[0]);
        }

        return array_filter($fields, fn($v) => $v !== '' && $v !== null);
    }
}

if (!function_exists('cert_auto_extract_birth')) {
    function cert_auto_extract_birth(string $ocrText, array $common): array
    {
        // 1. Ibalik nato ang Sections (Kadtong original nimo nga natarong)
        $sections = cert_segment_sections($ocrText, [
            'child'      => ['1. NAME', 'NAME OF CHILD', '4. NAME', '2. NAME'],
            'mother'     => ['6. MAIDEN', 'MAIDEN NAME', '7. MAIDEN', 'MAIDEN'],
            'father'     => ['13. NAME', '14. NAME', 'NAME OF FATHER', 'FATHER'],
            'marriage'   => ['18. DATE AND PLACE OF MARRIAGE', 'MARRIAGE OF PARENTS'],
            'attendant'  => ['19A. ATTENDANT', '19. ATTENDANT', 'ATTENDANT'],
            'informant'  => ['20. INFORMANT', 'CERTIFICATION OF INFORMANT'],
            'prepared'   => ['21. PREPARED BY', '21 PREPARED BY', 'PREPARED BY'],
            'remarks'    => ['REMARKS'],
        ]);
        
        $childSeg    = $sections['child']     ?? $ocrText;
        $motherSeg   = $sections['mother']    ?? '';
        $fatherSeg   = $sections['father']    ?? '';
        $marriageSeg = $sections['marriage']  ?? '';
        $attendSeg   = $sections['attendant'] ?? '';
        $informSeg   = $sections['informant'] ?? '';
        $prepSeg     = $sections['prepared']  ?? '';
        $remarksSeg  = $sections['remarks']   ?? '';

        $fields = $common;

        // 2. Helper para sa Fallback niadtong Iloilo nga papel nga naay "Rene"
        $findFallbackName = function($textSeg, $excludeWords = []) {
            $lines = preg_split('/\r\n|\r|\n/', $textSeg);
            foreach ($lines as $line) {
                $line = trim($line);
                if (strlen($line) < 8) continue;
                
                // Ayaw dawata kung naay label/lugar
                if (preg_match('/\b(DATE|SEX|BIRTH|CITIZEN|RELIGION|ADDRESS|RESIDENCE|OCCUPATION|MARRIAGE|ATTENDANT|INFORMANT|REGISTRAR|SIGNATURE|CLERK|PHYSICIAN|HOSPITAL|PROVINCE|CITY|MUNICIPALITY|BARANGAY|REGISTRY|PAGE|BOOK|NO\.|FIRST|MIDDLE|LAST|DICKLUM|ILOILO|BUKIDNON|MANOLO|FORTICH|PHILIPPINES|PLACE|SCHOOL|UNIVERSITY|COLLEGE|DOC|DOCUMENT|FORM|REPUBLIC|SAN DIEGO|LEMERY|LENY|ASST|MOTHER|FATHER|MALE|FEMALE|SINGLE|MARRIED|THE|OF|AND|THIS|THAT|WHO|WAS|ARE|NOT|FOR|WITH|DELIVERY|INCLUDING|BORN|ALIVE|CERTIFY|TIME|AGE|NUMBER|CHILDREN|TOTAL|WEIGHT|ORDER|TYPE|TWIN|TRIPLET|OTHERS|SPECIFY|GRAMS|SECOND|THIRD|STILL|LIVING|DEAD|NOW|POPULATION|REFERENCE)\b/i', $line)) continue;
                
                if (preg_match('/[0-9]/', $line)) continue;

                $cleanLine = preg_replace('/[^A-Za-zÑñ\s]/u', ' ', $line);
                $words = array_values(array_filter(preg_split('/\s+/', trim($cleanLine)), fn($w) => $w !== ''));
                // Ang 1-2 ka letra nga stray ("M Bs MAYLADE BALONGA PILOTON") dili ihapon - gitangtang gihapon sila sa cert_name_row_to_parts
                $words = array_values(array_filter($words, fn($w) => strlen($w) >= 3 || in_array(strtoupper($w), ['JR', 'SR'], true)));

                if (count($words) >= 2 && count($words) <= 4) {
                    $isValid = true;
                    foreach ($words as $w) {
                        if (strlen($w) > 2 && !preg_match('/[aeiouy]/i', $w)) { $isValid = false; break; }
                        if (in_array(strtoupper($w), $excludeWords)) { $isValid = false; break; }
                    }
                    if ($isValid) {
                        $parsed = cert_name_row_to_parts($words);
                        if ($parsed[0] !== '') return $parsed;
                    }
                }
            }
            return ['', '', ''];
        };

        // --- CHILD ---
        $childRow = cert_extract_value_row($childSeg, ['1. NAME', 'NAME OF CHILD', '4. NAME']);
        [$fields['child_first'], $fields['child_middle'], $fields['child_last']] = cert_name_row_to_parts($childRow);

        // Kung ni-fail ang label (walay pulong, o usa ra ka pulong nga walay apelyido), gamita ang Fallback
        $nameIncomplete = fn(string $first, string $last): bool => $first === '' || $last === '';
        if ($nameIncomplete($fields['child_first'], $fields['child_last'])) {
            $fb = $findFallbackName($childSeg);
            if (!$nameIncomplete($fb[0], $fb[2]) || $fields['child_first'] === '') {
                [$fields['child_first'], $fields['child_middle'], $fields['child_last']] = $fb;
            }
        }

        // --- SEX and DATE OF BIRTH ---
        $sexDobRow = cert_extract_value_row($childSeg, ['SEX']);
        if (count($sexDobRow) >= 4) {
            $fields['child_sex'] = $sexDobRow[0];
            $fields['date_of_birth'] = trim($sexDobRow[2] . ' ' . $sexDobRow[1] . ', ' . $sexDobRow[3]);
        } elseif (count($sexDobRow) >= 1) {
            $fields['child_sex'] = $sexDobRow[0];
        }
        if (!preg_match('/^(MALE|FEMALE)$/i', trim((string) ($fields['child_sex'] ?? '')))) $fields['child_sex'] = '';
        if (empty($fields['child_sex'])) $fields['child_sex'] = cert_extract_value_after_label($childSeg, ['SEX']);
        if (empty($fields['date_of_birth'])) $fields['date_of_birth'] = cert_extract_value_after_label($childSeg, ['DATE OF BIRTH']);
        if (empty(trim((string) $fields['child_sex']))) $fields['child_sex'] = cert_find_sex_in($childSeg);
        if (empty(trim((string) ($fields['date_of_birth'] ?? ''))) || !strtotime((string) $fields['date_of_birth'])) {
            $found = cert_find_date_in($childSeg);
            if ($found !== '') $fields['date_of_birth'] = $found;
        }

        // --- MOTHER ---
        if ($motherSeg !== '') {
            $mRow = cert_extract_value_row($motherSeg, ['MAIDEN NAME', '7. MAIDEN', 'MAIDEN']);
            [$fields['mother_first'], $fields['mother_middle'], $fields['mother_last']] = cert_name_row_to_parts($mRow);

            if ($nameIncomplete($fields['mother_first'], $fields['mother_last'])) {
                $exclude = [$fields['child_first'], $fields['child_last']];
                $fb = $findFallbackName($motherSeg, $exclude);
                if (!$nameIncomplete($fb[0], $fb[2]) || $fields['mother_first'] === '') {
                    [$fields['mother_first'], $fields['mother_middle'], $fields['mother_last']] = $fb;
                }
            }
        }

        // --- FATHER ---
        if ($fatherSeg !== '') {
            $fRow = cert_extract_value_row($fatherSeg, ['13. NAME', '14. NAME', 'NAME OF FATHER']);
            [$fields['father_first'], $fields['father_middle'], $fields['father_last']] = cert_name_row_to_parts($fRow);

            if ($nameIncomplete($fields['father_first'], $fields['father_last'])) {
                $exclude = [$fields['child_first'], $fields['child_last'], $fields['mother_first'], $fields['mother_last']];
                $fb = $findFallbackName($fatherSeg, $exclude);
                if (!$nameIncomplete($fb[0], $fb[2]) || $fields['father_first'] === '') {
                    [$fields['father_first'], $fields['father_middle'], $fields['father_last']] = $fb;
                }
            }
        }

        // --- PLACE OF BIRTH ---
        $pobRow = cert_extract_value_row($childSeg, ['PLACE OF BIRTH', 'PLACE OF']);
        if (count($pobRow) >= 1) $fields['place_of_birth_hospital'] = $pobRow[0] ?? '';
        if (count($pobRow) >= 2) $fields['place_of_birth_city']     = $pobRow[1] ?? '';
        if (count($pobRow) >= 3) $fields['place_of_birth_province'] = $pobRow[2] ?? '';

        // --- TYPE OF BIRTH ---
        $typeRow = cert_extract_value_row($childSeg, ['TYPE OF BIRTH']);
        if (count($typeRow) >= 4) {
            [$fields['type_of_birth'], $fields['birth_order'], $fields['birth_order_overall'], $fields['birth_weight']] = $typeRow;
        } else {
            if (!empty($typeRow[0])) $fields['type_of_birth'] = $typeRow[0];
            $fields['birth_weight'] = $fields['birth_weight'] ?? cert_extract_value_after_label($childSeg, ['WEIGHT AT BIRTH', 'WEIGHT']);
        }

        // --- MOTHER DETAILS ---
        if ($motherSeg !== '') {
            $fields['mother_citizenship'] = cert_extract_value_after_label($motherSeg, ['CITIZENSHIP']);
            $fields['mother_religion']    = cert_extract_value_after_label($motherSeg, ['RELIGION']);
            $countsRow = cert_extract_value_row($motherSeg, ['TOTAL NUMBER OF CHILDREN', 'TOTAL CHILDREN BORN ALIVE', '10A']);
            if (count($countsRow) >= 5) {
                [$fields['mother_children_born_alive'], $fields['mother_children_still_living'], $fields['mother_children_now_dead'], $fields['mother_occupation'], $fields['mother_age']] = $countsRow;
            } else {
                $fields['mother_occupation'] = $fields['mother_occupation'] ?? cert_extract_value_after_label($motherSeg, ['OCCUPATION']);
                $fields['mother_age'] = $fields['mother_age'] ?? cert_extract_value_after_label($motherSeg, ['AGE AT']);
            }
            $residenceRow = cert_extract_value_row($motherSeg, ['13. RESIDENCE', 'RESIDENCE']);
            if ($residenceRow) $fields['mother_residence'] = implode(', ', $residenceRow);
        }

        // --- FATHER DETAILS ---
        if ($fatherSeg !== '') {
            $fatherRow = cert_extract_value_row($fatherSeg, ['15. CITIZENSHIP', 'CITIZENSHIP']);
            if (count($fatherRow) >= 4) {
                [$fields['father_citizenship'], $fields['father_religion'], $fields['father_occupation'], $fields['father_age']] = $fatherRow;
            } else {
                $fields['father_citizenship'] = cert_extract_value_after_label($fatherSeg, ['CITIZENSHIP']);
                $fields['father_religion']    = cert_extract_value_after_label($fatherSeg, ['RELIGION']);
                $fields['father_occupation']  = cert_extract_value_after_label($fatherSeg, ['OCCUPATION']);
                $fields['father_age']         = cert_extract_value_after_label($fatherSeg, ['AGE AT']);
            }
            $fResidenceRow = cert_extract_value_row($fatherSeg, ['19. RESIDENCE', 'RESIDENCE']);
            if ($fResidenceRow) $fields['father_residence'] = implode(', ', $fResidenceRow);
        }

        // --- MARRIAGE OF PARENTS ---
        if ($marriageSeg !== '') {
            $mRow = cert_extract_value_row($marriageSeg, ['MARRIAGE OF PARENTS', '20A. DATE', 'DATE']);
            if (count($mRow) >= 3) {
                $fields['parents_marriage_date'] = trim($mRow[0] . ' ' . $mRow[1] . ', ' . $mRow[2]);
                if (count($mRow) > 3) $fields['parents_marriage_place'] = implode(', ', array_slice($mRow, 3));
            }
            if (empty($fields['parents_marriage_date'])) $fields['parents_marriage_date'] = cert_extract_value_after_label($marriageSeg, ['DATE']);
            if (empty($fields['parents_marriage_place'])) $fields['parents_marriage_place'] = cert_extract_value_after_label($marriageSeg, ['PLACE']);
        }

        // --- ATTENDANT ---
        if ($attendSeg !== '') {
            if (preg_match('/[X✕]\s*\d\.\s*([A-Za-z][A-Za-z \(\)\/]+)/', $attendSeg, $m)) $fields['attendant_type'] = trim($m[1]);
            if (preg_match('/\b(\d{1,2}:\d{2}\s*(AM|PM))\b/i', $attendSeg, $m)) $fields['attendant_time_born'] = strtoupper($m[1]);
            $fields['attendant_name']  = cert_extract_value_after_label($attendSeg, ['Name in Print']);
            $fields['attendant_title'] = cert_extract_value_after_label($attendSeg, ['Title or Position']);
        }

        // --- INFORMANT ---
        if ($informSeg !== '') {
            $fields['informant_name']         = cert_extract_value_after_label($informSeg, ['Name in Print']);
            $fields['informant_relationship'] = cert_extract_value_after_label($informSeg, ['Relationship to the Child']);
            $fields['informant_address']      = cert_extract_value_after_label($informSeg, ['Address']);
            $fields['informant_date']         = cert_extract_value_after_label($informSeg, ['Date']);
        }

        // --- PREPARED BY ---
        if ($prepSeg !== '') {
            $fields['prepared_by']         = cert_extract_value_after_label($prepSeg, ['Name in Print']);
            $fields['prepared_by_title']   = cert_extract_value_after_label($prepSeg, ['Title or Position']);
            $fields['prepared_by_address'] = cert_extract_value_after_label($prepSeg, ['Address']);
            $fields['prepared_by_date']    = cert_extract_value_after_label($prepSeg, ['Date']);
        }

        // --- DATE PREPARED ---
        if (empty(trim((string) ($fields['date_prepared'] ?? '')))) {
            $candidate = trim((string) ($fields['prepared_by_date'] ?? ''));
            if ($candidate === '' && $prepSeg !== '') $candidate = cert_find_date_in($prepSeg);
            if ($candidate === '' && $informSeg !== '') $candidate = cert_find_date_in($informSeg);
            if ($candidate !== '') $fields['date_prepared'] = $candidate;
        }

        // --- BARANGAY ---
        if (empty(trim((string) ($fields['barangay'] ?? '')))) $fields['barangay'] = cert_first_place_token($fields['mother_residence'] ?? '');
        if (empty(trim((string) ($fields['barangay'] ?? '')))) $fields['barangay'] = cert_first_place_token($fields['father_residence'] ?? '');
        if (empty(trim((string) ($fields['barangay'] ?? '')))) {
            $pob = $fields['place_of_birth_hospital'] ?? '';
            if (stripos($pob, 'House') === false && stripos($pob, 'Street') === false && stripos($pob, 'Barangay') === false) {
                $fields['barangay'] = cert_first_place_token($pob);
            }
        }

        // --- REMARKS ---
        if ($remarksSeg !== '') {
            $remarkLines = array_slice(preg_split('/\r\n|\r|\n/', $remarksSeg), 1);
            $remarkLines = array_filter(array_map('trim', $remarkLines), fn($l) => $l !== '');
            $remarksText = trim(implode(' ', $remarkLines));
            if ($remarksText !== '') $fields['remarks'] = $remarksText;
        }

        return $fields;
    }
}

if (!function_exists('cert_auto_extract_marriage')) {
    function cert_auto_extract_marriage(string $ocrText, array $common): array
    {
        $sections = cert_segment_sections($ocrText, [
            'husband' => ['1. HUSBAND', 'HUSBAND'],
            'wife'    => ['2. WIFE', 'WIFE'],
            'details' => ['3. MARRIAGE DETAILS', 'PLACE OF MARRIAGE'],
            'officer' => ['CERTIFICATION OF THE SOLEMNIZING OFFICER', 'SOLEMNIZING OFFICER'],
        ]);
        $husbandSeg = $sections['husband'] ?? '';
        $wifeSeg    = $sections['wife']    ?? '';
        $detailsSeg = $sections['details'] ?? $ocrText;
        $officerSeg = $sections['officer'] ?? '';

        $fields = $common;

        $person = function (string $seg, string $prefix) use (&$fields) {
            if ($seg === '') return;
            [$first, $middle, $last] = cert_name_row_to_parts(cert_extract_value_row($seg, ['LAST NAME', 'NAME']));
            $fields["{$prefix}_first"]  = $first;
            $fields["{$prefix}_middle"] = $middle;
            $fields["{$prefix}_last"]   = $last;
            $fields["{$prefix}_dob"]         = cert_extract_value_after_label($seg, ['DATE OF BIRTH']);
            // Same last-resort date reader the birth form uses - catches a
            // date that didn't split cleanly into its own labeled row.
            if (empty(trim((string) $fields["{$prefix}_dob"])) || !strtotime((string) $fields["{$prefix}_dob"])) {
                $found = cert_find_date_in($seg);
                if ($found !== '') $fields["{$prefix}_dob"] = $found;
            }
            $fields["{$prefix}_age"]         = cert_extract_value_after_label($seg, ['AGE']);
            $fields["{$prefix}_sex"]         = cert_extract_value_after_label($seg, ['SEX']);
            if (empty(trim((string) $fields["{$prefix}_sex"]))) {
                $fields["{$prefix}_sex"] = cert_find_sex_in($seg);
            }
            $fields["{$prefix}_citizenship"] = cert_extract_value_after_label($seg, ['CITIZENSHIP']);
            $fields["{$prefix}_religion"]    = cert_extract_value_after_label($seg, ['RELIGION']);
            $fields["{$prefix}_civil_status"]= cert_extract_value_after_label($seg, ['CIVIL STATUS']);
            $residenceRow = cert_extract_value_row($seg, ['RESIDENCE']);
            if ($residenceRow) $fields["{$prefix}_residence"] = implode(', ', $residenceRow);
        };
        $person($husbandSeg, 'husband');
        $person($wifeSeg, 'wife');

        // --- Barangay ---
        // Read from BOX 1 (the HUSBAND's own Residence row) first, scoped
        // to $husbandSeg so it can never pick up the Wife's or an
        // officer's residence by mistake. This is the primary registrant
        // on a marriage record, matching how the Birth form reads the
        // Child's own box first.
        $fields['barangay'] = cert_first_place_token($fields['husband_residence'] ?? '');
        if (empty(trim((string) $fields['barangay']))) {
            $fields['barangay'] = cert_first_place_token($fields['wife_residence'] ?? '');
        }

        $fields['place_of_marriage']   = cert_extract_value_after_label($detailsSeg, ['PLACE OF MARRIAGE']);
        $fields['date_of_marriage']    = cert_extract_value_after_label($detailsSeg, ['DATE OF MARRIAGE']);
        $fields['time_of_marriage']    = cert_extract_value_after_label($detailsSeg, ['TIME OF MARRIAGE']);
        $fields['marriage_license_no'] = cert_extract_value_after_label($officerSeg ?: $ocrText, ['MARRIAGE LICENSE NO']);
        $fields['license_issued_on']   = cert_extract_value_after_label($officerSeg ?: $ocrText, ['LICENSE ISSUED ON', 'ISSUED ON']);
        $fields['license_issued_at']   = cert_extract_value_after_label($officerSeg ?: $ocrText, ['LICENSE ISSUED AT', 'ISSUED AT']);
        $fields['solemnizing_officer_name'] = cert_extract_value_after_label($officerSeg ?: $ocrText, ['SOLEMNIZING OFFICER']);

        return $fields;
    }
}

if (!function_exists('cert_auto_extract_death')) {
    function cert_auto_extract_death(string $ocrText, array $common): array
    {
        $sections = cert_segment_sections($ocrText, [
            // Trailing bare-keyword fallbacks, same reasoning as the birth
            // form's anchors - a misread item number shouldn't lose the
            // whole section.
            'deceased'  => ['1. NAME OF DECEASED', 'NAME OF DECEASED', 'NAME'],
            'medical'   => ['MEDICAL CERTIFICATE', 'CAUSES OF DEATH', 'CAUSE OF DEATH'],
            'attendant' => ['ATTENDANT & CERTIFICATION', 'ATTENDANT'],
            'informant' => ['INFORMANT & REGISTRATION', 'INFORMANT'],
        ]);
        $deceasedSeg  = $sections['deceased']  ?? $ocrText;
        $medicalSeg   = $sections['medical']   ?? '';
        $informantSeg = $sections['informant'] ?? '';

        [$first, $middle, $last] = cert_name_row_to_parts(cert_extract_value_row($deceasedSeg, ['NAME OF DECEASED', 'LAST NAME']));

        $fields = $common + [
            'last_name'   => $last,
            'first_name'  => $first,
            'middle_name' => $middle,
        ];

        // Everything below is read from $deceasedSeg (BOX 1 - the
        // deceased's own personal-details box) ONLY, never the whole
        // document - so a same-named label further down the form (the
        // informant's own address, an attendant's occupation, etc.) can
        // never bleed into the deceased's fields.
        $fields['sex']          = cert_extract_value_after_label($deceasedSeg, ['SEX']);
        if (empty(trim((string) $fields['sex']))) $fields['sex'] = cert_find_sex_in($deceasedSeg);

        $fields['civil_status'] = cert_extract_value_after_label($deceasedSeg, ['CIVIL STATUS']);

        $fields['date_of_death'] = cert_extract_value_after_label($deceasedSeg, ['DATE OF DEATH']);
        $fields['date_of_birth'] = cert_extract_value_after_label($deceasedSeg, ['DATE OF BIRTH']);
        // Last-resort date readers - same idea as the birth form: if the
        // labeled row didn't split cleanly (smudge, tight spacing), pull
        // any date-shaped text out of the box directly rather than leaving
        // it blank. Date of Death and Date of Birth both live in the same
        // box, so a single found-date match is checked against whichever
        // field is still empty.
        if (empty(trim((string) $fields['date_of_death'])) || !strtotime((string) $fields['date_of_death'])) {
            $found = cert_find_date_in($deceasedSeg);
            if ($found !== '') $fields['date_of_death'] = $found;
        }
        if (empty(trim((string) $fields['date_of_birth'])) || !strtotime((string) $fields['date_of_birth'])) {
            // Look for a SECOND, different date so Date of Birth doesn't
            // just duplicate whatever Date of Death already matched.
            $already = (string) ($fields['date_of_death'] ?? '');
            $rest = $already !== '' ? str_replace($already, '', $deceasedSeg) : $deceasedSeg;
            $found = cert_find_date_in($rest);
            if ($found !== '') $fields['date_of_birth'] = $found;
        }

        $fields['age_years']      = cert_extract_value_after_label($deceasedSeg, ['AGE']);
        $fields['place_of_death'] = cert_extract_value_after_label($deceasedSeg, ['PLACE OF DEATH']);
        $fields['religion']       = cert_extract_value_after_label($deceasedSeg, ['RELIGION']);
        $fields['citizenship']    = cert_extract_value_after_label($deceasedSeg, ['CITIZENSHIP']);
        $fields['occupation']     = cert_extract_value_after_label($deceasedSeg, ['OCCUPATION']);

        $residenceRow = cert_extract_value_row($deceasedSeg, ['RESIDENCE']);
        $fields['residence'] = $residenceRow ? implode(', ', $residenceRow) : cert_extract_value_after_label($deceasedSeg, ['RESIDENCE']);

        $fields['father_name']        = cert_extract_value_after_label($deceasedSeg, ['NAME OF FATHER']);
        $fields['mother_maiden_name'] = cert_extract_value_after_label($deceasedSeg, ['MAIDEN NAME OF MOTHER']);

        // --- Barangay ---
        // Read from BOX 1 (the Deceased's own Residence row) first,
        // matching how the Birth form reads the Child's own box first.
        $fields['barangay'] = cert_first_place_token($fields['residence'] ?? '');

        // --- Box 2: Medical Certificate / Cause of Death ---
        $fields['immediate_cause']  = cert_extract_value_after_label($medicalSeg ?: $ocrText, ['IMMEDIATE CAUSE']);
        $fields['antecedent_cause'] = cert_extract_value_after_label($medicalSeg ?: $ocrText, ['ANTECEDENT CAUSE']);
        $fields['underlying_cause'] = cert_extract_value_after_label($medicalSeg ?: $ocrText, ['UNDERLYING CAUSE']);
        $fields['manner_of_death']  = cert_extract_value_after_label($medicalSeg ?: $ocrText, ['MANNER OF DEATH']);
        $fields['autopsy']          = cert_extract_value_after_label($medicalSeg ?: $ocrText, ['AUTOPSY']);

        // --- Box 3: Informant / Registration ---
        $fields['informant_name']         = cert_extract_value_after_label($informantSeg ?: $ocrText, ['NAME OF INFORMANT']);
        $fields['informant_relationship'] = cert_extract_value_after_label($informantSeg ?: $ocrText, ['RELATIONSHIP TO THE DECEASED']);

        return $fields;
    }
}