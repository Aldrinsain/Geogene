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
        if (!cert_ocr_available()) {
            return '';
        }

        $ocrImg = cert_auto_rotate_image($imagePath);
        $isTempCopy = ($ocrImg !== $imagePath);

        $outBase = sys_get_temp_dir() . '/geogene_ocr_' . uniqid();
        $safeOut = escapeshellarg($outBase);

        // Grayscale + contrast boost + sharpen dramatically improves
        // real-world phone-camera scans of these forms - tested against an
        // actual submitted certificate, this alone was the difference
        // between the Registry No. box reading as garbage ("9NNE_2476" /
        // blank) and reading correctly ("2005-3476"). See
        // cert_preprocess_for_ocr() below.
        $preppedImg = cert_preprocess_for_ocr($ocrImg);
        $isPreppedTemp = ($preppedImg !== $ocrImg);
        $safePrepped = escapeshellarg($preppedImg);

        // --psm 6 = "assume a single UNIFORM block of text". Tested against
        // a real submitted certificate side by side with the previous
        // --psm 4 ("single column of variable sizes"): psm 4 dropped entire
        // boxes outright (the child's Last Name and the whole Registry No.
        // row vanished completely), while psm 6 kept every row and read
        // them far more accurately on this densely-boxed government form
        // layout. preserve_interword_spaces keeps the multi-space gaps
        // between side-by-side boxes intact, which the column-splitting
        // logic below relies on to tell "this is a separate field" apart
        // from "this is still the same value".
        @shell_exec("tesseract $safePrepped $safeOut --psm 6 -c preserve_interword_spaces=1 2>&1");

        if ($isPreppedTemp) @unlink($preppedImg);

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
            // A short leading fragment (<=2 letters) is virtually always
            // noise - either the vertical CHILD/MOTHER/FATHER sidebar
            // letter bleeding in, or a stray OCR artifact from a table
            // border/underline right before the real first column (e.g.
            // "(ae   DICKLUM ..." -> "DICKLUM ..."). A lone DIGIT is kept -
            // the mother's "2  2  0" children counts row and similar
            // numeric columns legitimately start with one digit.
            if ($bare !== '' && strlen($bare) <= 2 && ctype_alpha($bare)) {
                array_shift($tokens);
                continue;
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
                // Punctuation-tolerant match - see cert_label_pos().
                $pos = cert_label_pos($line, $label);
                if ($pos === null) continue;

                $after = substr($line, $pos);
                $after = trim($after, " :._-\t");
                $after = trim(preg_split('/\s{2,}/', $after)[0] ?? '');

                // A token already isolated from the SAME line as its label
                // is positionally proven to be that label's value - don't
                // let the ALL-CAPS heuristic (meant for judging a whole
                // separate line) throw away a legitimate all-caps answer
                // like a barangay name, "MALE", or "SINGLE".
                if ($after !== '' && strlen($after) < 120 && !cert_looks_like_label($after, false)) {
                    return $after;
                }

                for ($j = $i + 1; $j <= min($i + 3, count($lines) - 1); $j++) {
                    $next = trim($lines[$j]);
                    if ($next === '') continue;
                    // Step over a printed sub-label row, same as in
                    // cert_extract_value_row() - the answer is the line
                    // after it, not the scaffolding itself.
                    if (preg_match_all('/\([^)]*\)/', $next) >= 1
                        && strlen(trim(preg_replace('/[^A-Za-z]+/', ' ', preg_replace('/\([^)]*\)/', ' ', $next)))) < 4) {
                        continue;
                    }
                    // A wrapped label tail ("BIRTH" under "3. DATE OF") -
                    // step over it too instead of stopping the search here.
                    if (cert_is_wrapped_label_tail($next)) continue;
                    if (cert_looks_like_label($next)) break;
                    $next = trim(preg_split('/\s{2,}/', $next)[0] ?? '', " :._-\t");
                    // Re-check the ISOLATED token too, not just the whole
                    // raw line - a line like "Date Prepared   April 03,
                    // 2003" doesn't look like a label as a whole (mixed
                    // case, has a comma), but its first token in isolation
                    // ("Date Prepared") is itself a printed label bleeding
                    // in from a neighboring box, not the answer.
                    if ($next !== '' && strlen($next) < 120 && !cert_looks_like_label($next)) return $next;
                    break;
                }
            }
        }
        return '';
    }
}

if (!function_exists('cert_extract_value_row')) {
    /**
     * Finds a header line (matching any of $headerLabels), then returns
     * the FIRST non-blank line after it split into column tokens - this
     * is the "answer row" underneath a row of boxed sub-labels like
     * "(First)  (Middle)  (Last)" or "SINGLE  NOT APPLICABLE  SECOND  2722 grams".
     */
    function cert_extract_value_row(string $text, array $headerLabels): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        foreach ($headerLabels as $label) {
            foreach ($lines as $i => $line) {
                // Punctuation-tolerant match - see cert_label_pos().
                $pos = cert_label_pos($line, $label);
                if ($pos === null) continue;

                // 1) Same line, right after the label (common when the form
                //    was filled in and OCR read label + answer as one row,
                //    e.g. "1. NAME   Dela Cruz   Juan   Santos").
                $after = trim(substr($line, $pos), " :._-\t");
                $sameLineCols = cert_split_row_columns($after);
                // Same-line token right after its own label - see the note
                // in cert_extract_value_after_label() on why the ALL-CAPS
                // heuristic must be skipped here (it would wrongly discard
                // real answers like "MALE", "SINGLE", a barangay name, etc.).
                $sameLineCols = cert_strip_sidebar_letters($sameLineCols);
                if (!empty($sameLineCols) && !cert_looks_like_label($sameLineCols[0], false)) {
                    return $sameLineCols;
                }

                // 2) Nothing usable on the same line - the answer is on the
                //    line below. On the real form there is almost always a
                //    printed sub-label row in between, e.g.
                //        1. NAME
                //        (First)      (Middle)      (Last)
                //        ALDRIN                     PILOTON
                //    so that scaffolding row is SKIPPED (it used to abort
                //    the search, which is why names came back blank). We
                //    still STOP at a genuinely different field label, so we
                //    never steal the next box's answer.
                $skipped = 0;
                for ($j = $i + 1; $j < count($lines); $j++) {
                    $next = trim($lines[$j]);
                    if ($next === '') continue;

                    // Printed scaffolding like "(First) (Middle) (Last)" -
                    // step over it and keep looking (max 2 in a row).
                    if (preg_match_all('/\([^)]*\)/', $next) >= 1
                        && strlen(trim(preg_replace('/[^A-Za-z]+/', ' ', preg_replace('/\([^)]*\)/', ' ', $next)))) < 4) {
                        if (++$skipped > 2) break;
                        continue;
                    }
                    // A wrapped label tail ("BIRTH" under "4. PLACE OF") -
                    // step over it too instead of stopping the search here.
                    if (cert_is_wrapped_label_tail($next)) {
                        if (++$skipped > 2) break;
                        continue;
                    }

                    if (cert_looks_like_label($next)) break;
                    return cert_strip_sidebar_letters(cert_split_row_columns($next));
                }
            }
        }
        return [];
    }
}

if (!function_exists('cert_name_row_to_parts')) {
    /**
     * Interprets a First/Middle/Last column row. Philippine civil-registry
     * forms very commonly leave the MIDDLE column blank (2 tokens instead
     * of 3) - when exactly 2 tokens come back we assume [First, Last] and
     * leave Middle blank, since a missing First or Last name is far rarer.
     */
    function cert_name_row_to_parts(array $tokens): array
    {
        $tokens = cert_strip_garbled_name_prefix(cert_strip_sidebar_letters(array_values($tokens)));

        // A name row whose column gaps OCR'd as single spaces arrives as ONE
        // token ("ALDRIN PILOTON" / "MAYLADE BALONGA PILOTON"). Splitting it
        // on spaces recovers the First/Middle/Last columns instead of
        // dumping the whole name into the Given Name box.
        if (count($tokens) === 1) {
            $words = preg_split('/\s+/', trim($tokens[0]));
            $words = array_values(array_filter($words, fn($w) => $w !== ''));
            if (count($words) >= 2 && count($words) <= 3) $tokens = $words;
        }

        // Trim trailing OCR noise off each column - a smudge/underline
        // right after a real name often reads as extra symbols glued onto
        // the end ("PILOTON!: - + |"). Cut at the first character that
        // cannot be part of a real name, then drop any leftover leading/
        // trailing "-"/"." (e.g. a maiden-name column that OCR'd as just
        // "MAYLADE -" keeps the dash unless this runs).
        $clean = function (string $tok): string {
            if (preg_match("/^[A-Za-z\\x{00C0}-\\x{017F}'.\\- ]+/u", trim($tok), $m)) {
                $tok = $m[0];
            }
            return trim($tok, " -.'");
        };
        $tokens = array_values(array_filter(array_map($clean, $tokens), fn($t) => $t !== ''));

        if (count($tokens) >= 3) return [$tokens[0], $tokens[1], $tokens[2]];
        if (count($tokens) === 2) return [$tokens[0], '', $tokens[1]];
        if (count($tokens) === 1) return [$tokens[0], '', ''];
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
            $pos = strpos($normLine, $normLabel);
            if ($pos === false || $pos > 15) return null;
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

if (!function_exists('cert_first_place_token')) {
    /**
     * The barangay is the FIRST column of the Residence / Place of Birth
     * row ("DICKLUM   MANOLO FORTICH   BUKIDNON   PHILIPPINES"), never the
     * word that follows the printed label "(House No., St., Barangay)" -
     * reading it that way is what used to dump
     * ") (City/Municipality) (Province) 1 (Country)" into the Barangay box.
     */
    function cert_first_place_token(string $value): string
    {
        $parts = array_values(array_filter(
            array_map('trim', preg_split('/\s*,\s*/', trim($value))),
            fn($p) => $p !== ''
        ));

        // A genuine place-name column is letters/digits/.-' and spaces; any
        // OTHER character (a stray "(", ")", ":", "_", "|" from a table
        // border bleeding into the scan) marks that piece as OCR noise, not
        // a real barangay - skip it in favor of the next comma-separated
        // piece, same idea as cert_strip_garbled_name_prefix() for names.
        foreach ($parts as $first) {
            if ($first === '' || cert_is_ocr_noise($first) || cert_is_printed_label_text($first)) continue;
            if (preg_match("/[^A-Za-z0-9.\\-' ]/", $first)) continue;
            if (preg_replace('/[^A-Za-z]/', '', $first) === '') continue;
            // Trim any trailing junk glued onto an otherwise-good token.
            return rtrim($first, " -.'");
        }
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
        $sections = cert_segment_sections($ocrText, [
            // A trailing bare-keyword fallback is included on each anchor
            // because OCR frequently misreads the leading item NUMBER
            // (1 <-> 4 <-> 7 are common confusions), which would otherwise
            // miss the anchor line entirely and merge two people's fields
            // together. Verified against a real scan where "1. NAME" (the
            // CHILD's own name box) was read as "4, NAME".
            'child'      => ['1. NAME', 'NAME OF CHILD', '4. NAME'],
            'mother'     => ['MAIDEN NAME', '7. MAIDEN', 'MAIDEN'],
            'father'     => ['14. NAME', 'NAME'], // bare 'NAME' fallback is safe now that section search is sequential (see cert_segment_sections) - it can only match AFTER the mother section, so it can't jump back up to the child's own name line
            'marriage'   => ['MARRIAGE OF PARENTS'],
            'attendant'  => ['ATTENDANT'], // was ['ATTENDANT','21'] - a bare '21' also matched stray digits elsewhere on the page and cut the sections in the wrong place
            'informant'  => ['CERTIFICATION OF INFORMANT'],
            'prepared'   => ['PREPARED BY'],
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

        // --- Child: name, sex, date/place of birth, type/order, weight ---
        [$fields['child_first'], $fields['child_middle'], $fields['child_last']] =
            cert_name_row_to_parts(cert_extract_value_row($childSeg, ['1. NAME', 'NAME OF CHILD', '4. NAME']));

        // "SEX" and "DATE OF BIRTH" boxes usually sit on ONE physical row,
        // so their answers OCR onto one combined value line too, e.g.
        // "MALE   03   APRIL   2003" (Sex, Day, Month, Year).
        $sexDobRow = cert_extract_value_row($childSeg, ['SEX']);
        if (count($sexDobRow) >= 4) {
            $fields['child_sex'] = $sexDobRow[0];
            $fields['date_of_birth'] = trim($sexDobRow[2] . ' ' . $sexDobRow[1] . ', ' . $sexDobRow[3]); // Month Day, Year
        } elseif (count($sexDobRow) >= 1) {
            $fields['child_sex'] = $sexDobRow[0];
        }

        // Sex can only ever be MALE or FEMALE. Anything else that landed
        // here is a fragment of the printed form (the "3. DATE OF / BIRTH"
        // label wraps onto its own line right under the SEX box, and used
        // to get picked up as the answer) - discard it so the reader below
        // gets a chance instead.
        if (!preg_match('/^(MALE|FEMALE)$/i', trim((string) ($fields['child_sex'] ?? '')))) {
            $fields['child_sex'] = '';
        }
        if (empty($fields['child_sex'])) $fields['child_sex'] = cert_extract_value_after_label($childSeg, ['SEX']);
        if (empty($fields['date_of_birth'])) $fields['date_of_birth'] = cert_extract_value_after_label($childSeg, ['DATE OF BIRTH']);

        // Last-resort readers: when the Sex/Date row didn't split cleanly
        // into columns (single spaces, a smudged gap, a tilted photo), read
        // the word MALE/FEMALE and the date shape straight out of the child
        // section instead of leaving both blank.
        if (empty(trim((string) $fields['child_sex']))) {
            $fields['child_sex'] = cert_find_sex_in($childSeg);
        }
        if (empty(trim((string) ($fields['date_of_birth'] ?? '')))
            || !strtotime((string) $fields['date_of_birth'])) {
            $found = cert_find_date_in($childSeg);
            if ($found !== '') $fields['date_of_birth'] = $found;
        }

        // 'PLACE OF' alone is included as a fallback because the form prints
        // "4. PLACE OF" / "BIRTH" as two physical lines, and a smudge or
        // fold right on the word "BIRTH" occasionally drops it from OCR
        // entirely, leaving only "PLACE OF" to anchor on.
        $pobRow = cert_extract_value_row($childSeg, ['PLACE OF BIRTH', 'PLACE OF']);
        if (count($pobRow) >= 1) $fields['place_of_birth_hospital'] = $pobRow[0] ?? '';
        if (count($pobRow) >= 2) $fields['place_of_birth_city']     = $pobRow[1] ?? '';
        if (count($pobRow) >= 3) $fields['place_of_birth_province'] = $pobRow[2] ?? '';

        // "TYPE OF BIRTH / IF MULTIPLE BIRTH / BIRTH ORDER / WEIGHT AT BIRTH" - one row, 4 columns.
        $typeRow = cert_extract_value_row($childSeg, ['TYPE OF BIRTH']);
        if (count($typeRow) >= 4) {
            [$fields['type_of_birth'], $fields['birth_order'], $fields['birth_order_overall'], $fields['birth_weight']] = $typeRow;
        } else {
            if (!empty($typeRow[0])) $fields['type_of_birth'] = $typeRow[0];
            $fields['birth_weight'] = $fields['birth_weight'] ?? cert_extract_value_after_label($childSeg, ['WEIGHT AT BIRTH', 'WEIGHT']);
        }

        // --- Mother section ---
        if ($motherSeg !== '') {
            [$mFirst, $mMiddle, $mLast] = cert_name_row_to_parts(cert_extract_value_row($motherSeg, ['MAIDEN NAME', '7. MAIDEN', 'MAIDEN']));
            $fields['mother_first'] = $mFirst;
            $fields['mother_middle'] = $mMiddle;
            $fields['mother_last'] = $mLast;

            // "CITIZENSHIP" alone often shares its row with nothing else for
            // the mother row (no merged row like father's), so try direct
            // label lookup first, falling back to the multi-column row.
            $fields['mother_citizenship'] = cert_extract_value_after_label($motherSeg, ['CITIZENSHIP']);
            $fields['mother_religion']    = cert_extract_value_after_label($motherSeg, ['RELIGION']);

            // "10a. TOTAL... / 10b. STILL LIVING / 10c. NOW DEAD / 11. OCCUPATION / 12. AGE" - one row, 5 columns.
            $countsRow = cert_extract_value_row($motherSeg, ['TOTAL NUMBER OF CHILDREN', 'TOTAL CHILDREN BORN ALIVE', '10A']);
            if (count($countsRow) >= 5) {
                [$fields['mother_children_born_alive'], $fields['mother_children_still_living'],
                 $fields['mother_children_now_dead'], $fields['mother_occupation'], $fields['mother_age']] = $countsRow;
            } else {
                $fields['mother_occupation'] = $fields['mother_occupation'] ?? cert_extract_value_after_label($motherSeg, ['OCCUPATION']);
                $fields['mother_age'] = $fields['mother_age'] ?? cert_extract_value_after_label($motherSeg, ['AGE AT']);
            }

            $residenceRow = cert_extract_value_row($motherSeg, ['13. RESIDENCE', 'RESIDENCE']);
            if ($residenceRow) $fields['mother_residence'] = implode(', ', $residenceRow);
        }

        // --- Father section ---
        if ($fatherSeg !== '') {
            [$fFirst, $fMiddle, $fLast] = cert_name_row_to_parts(cert_extract_value_row($fatherSeg, ['14. NAME']));
            $fields['father_first'] = $fFirst;
            $fields['father_middle'] = $fMiddle;
            $fields['father_last'] = $fLast;

            // "15. CITIZENSHIP / 16. RELIGION / 17. OCCUPATION / 18. AGE" - one row, 4 columns.
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

        // --- Marriage of parents: "20a. DATE (Month)(Day)(Year)  20b. PLACE (City)(Province)(Country)" ---
        if ($marriageSeg !== '') {
            $mRow = cert_extract_value_row($marriageSeg, ['MARRIAGE OF PARENTS', '20A. DATE', 'DATE']);
            if (count($mRow) >= 3) {
                $fields['parents_marriage_date'] = trim($mRow[0] . ' ' . $mRow[1] . ', ' . $mRow[2]); // Month Day, Year
                if (count($mRow) > 3) $fields['parents_marriage_place'] = implode(', ', array_slice($mRow, 3));
            }
            if (empty($fields['parents_marriage_date'])) $fields['parents_marriage_date'] = cert_extract_value_after_label($marriageSeg, ['DATE']);
            if (empty($fields['parents_marriage_place'])) $fields['parents_marriage_place'] = cert_extract_value_after_label($marriageSeg, ['PLACE']);
        }

        // --- Attendant (back page): checkbox type, time born, name/title ---
        if ($attendSeg !== '') {
            if (preg_match('/[X✕]\s*\d\.\s*([A-Za-z][A-Za-z \(\)\/]+)/', $attendSeg, $m)) {
                $fields['attendant_type'] = trim($m[1]);
            }
            if (preg_match('/\b(\d{1,2}:\d{2}\s*(AM|PM))\b/i', $attendSeg, $m)) {
                $fields['attendant_time_born'] = strtoupper($m[1]);
            }
            $fields['attendant_name']  = cert_extract_value_after_label($attendSeg, ['Name in Print']);
            $fields['attendant_title'] = cert_extract_value_after_label($attendSeg, ['Title or Position']);
        }

        // --- Certification of Informant ---
        if ($informSeg !== '') {
            $fields['informant_name']         = cert_extract_value_after_label($informSeg, ['Name in Print']);
            $fields['informant_relationship'] = cert_extract_value_after_label($informSeg, ['Relationship to the Child']);
            $fields['informant_address']      = cert_extract_value_after_label($informSeg, ['Address']);
            $fields['informant_date']         = cert_extract_value_after_label($informSeg, ['Date']);
        }

        // --- Prepared By ---
        if ($prepSeg !== '') {
            $fields['prepared_by']         = cert_extract_value_after_label($prepSeg, ['Name in Print']);
            $fields['prepared_by_title']   = cert_extract_value_after_label($prepSeg, ['Title or Position']);
            $fields['prepared_by_address'] = cert_extract_value_after_label($prepSeg, ['Address']);
            $fields['prepared_by_date']    = cert_extract_value_after_label($prepSeg, ['Date']);
        }

        // --- Date Prepared ---
        // Municipal Form No. 102 has no box literally labelled "Date
        // Prepared" - the date the record was prepared is the one signed in
        // the "PREPARED BY" block (23), so use that rather than leaving the
        // field blank.
        if (empty(trim((string) ($fields['date_prepared'] ?? '')))) {
            $candidate = trim((string) ($fields['prepared_by_date'] ?? ''));
            if ($candidate === '' && $prepSeg !== '') $candidate = cert_find_date_in($prepSeg);
            if ($candidate === '' && $informSeg !== '') $candidate = cert_find_date_in($informSeg);
            if ($candidate !== '') $fields['date_prepared'] = $candidate;
        }

        // --- Barangay ---
        // Read from BOX 1 (the CHILD's own Place of Birth row) FIRST -
        // that's the box that actually carries the barangay the child was
        // born in, and it's already scoped to $childSeg so it can never
        // pick up a stray value from the Mother's or Father's box below
        // it. The Mother's / Father's Residence row is only a fallback for
        // when Place of Birth itself didn't OCR cleanly (it's usually the
        // SAME barangay on these forms anyway, but Place of Birth is the
        // more correct source).
        if (empty(trim((string) ($fields['barangay'] ?? '')))) {
            $fields['barangay'] = cert_first_place_token($fields['place_of_birth_hospital'] ?? '');
        }
        if (empty(trim((string) ($fields['barangay'] ?? '')))) {
            $fields['barangay'] = cert_first_place_token($fields['mother_residence'] ?? '');
        }
        if (empty(trim((string) ($fields['barangay'] ?? '')))) {
            $fields['barangay'] = cert_first_place_token($fields['father_residence'] ?? '');
        }

        // --- Remarks / Annotations (free text - grab everything after the heading) ---
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