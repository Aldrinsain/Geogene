<?php
/**
 * CertificateOcrAdvanced
 * -----------------------
 * "OLD CERTIFICATE MODE" - an opt-in, slower-but-more-thorough OCR pass for
 * old, faded, wrinkled, low-contrast, or otherwise hard-to-read scanned
 * certificates. Built on top of CertificateOcr.php (which stays the FAST
 * default path for normal, clear photos - nothing here changes that).
 *
 * WHAT THIS ACTUALLY DOES (realistic scope for plain PHP + GD + Tesseract,
 * no OpenCV/cloud OCR available on a typical XAMPP/shared-hosting box):
 *   1) Generates several image-processing variants of the SAME photo
 *      (grayscale+contrast, binarized/threshold, upscaled+denoised,
 *      faint-preserving, and - in old-certificate mode - a histogram
 *      stretch pass that fights yellowed paper / uneven lighting).
 *   2) Runs Tesseract on the original PLUS every variant.
 *   3) Extracts fields from EACH variant's text using the existing
 *      cert_auto_extract() label/section logic.
 *   4) Builds a CONSENSUS per field: if 2+ variants agree on a value,
 *      confidence goes up; if variants disagree, confidence goes down
 *      and the field is flagged for staff verification. Word-level
 *      confidence scores from Tesseract's TSV output are blended in
 *      where available.
 *   5) NEVER invents a value: a field with no reliable match across any
 *      variant is simply left blank, same as the existing engine.
 *
 * WHAT THIS DELIBERATELY DOES NOT DO (out of scope without OpenCV/a real
 * CV pipeline - see the chat reply for the honest explanation):
 *   - True 4-corner document detection / perspective (keystone) correction.
 *     Tesseract's built-in orientation detection (0/90/180/270 rotation,
 *     already used in cert_auto_rotate_image()) is the closest equivalent
 *     available here.
 *   - Real handwriting recognition. Tesseract is a PRINTED-text engine;
 *     handwritten entries will simply OCR poorly/blank rather than being
 *     guessed at, which matches the "never invent data" requirement.
 *   - Per-field pixel cropping against a fixed template. Photographed
 *     paper forms are never perfectly aligned/scaled the same way twice,
 *     so this engine works on the FULL page text per variant instead of
 *     cropping individual boxes.
 */

if (!function_exists('cert_adv_load_image')) {
    function cert_adv_load_image(string $path)
    {
        $info = @getimagesize($path);
        if (!$info) return null;
        switch ($info['mime']) {
            case 'image/jpeg': return @imagecreatefromjpeg($path);
            case 'image/png':  return @imagecreatefrompng($path);
            case 'image/webp': return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null;
        }
        return null;
    }
}

if (!function_exists('cert_adv_clone_image')) {
    function cert_adv_clone_image($im)
    {
        $w = imagesx($im); $h = imagesy($im);
        $dst = imagecreatetruecolor($w, $h);
        imagecopy($dst, $im, 0, 0, 0, 0, $w, $h);
        return $dst;
    }
}

if (!function_exists('cert_adv_resize_cap')) {
    /** Downscales so neither dimension exceeds $maxDim - keeps per-pixel passes fast. */
    function cert_adv_resize_cap($im, int $maxDim = 2000)
    {
        $w = imagesx($im); $h = imagesy($im);
        if (max($w, $h) <= $maxDim) return $im;
        $scale = $maxDim / max($w, $h);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $dst;
    }
}

if (!function_exists('cert_adv_upscale')) {
    function cert_adv_upscale($im, float $factor)
    {
        $w = imagesx($im); $h = imagesy($im);
        $nw = max(1, (int) round($w * $factor));
        $nh = max(1, (int) round($h * $factor));
        $dst = imagecreatetruecolor($nw, $nh);
        imagecopyresampled($dst, $im, 0, 0, 0, 0, $nw, $nh, $w, $h);
        return $dst;
    }
}

if (!function_exists('cert_adv_sharpen')) {
    function cert_adv_sharpen($im): void
    {
        $matrix = [[-1, -1, -1], [-1, 16, -1], [-1, -1, -1]];
        $divisor = array_sum(array_map('array_sum', $matrix));
        imageconvolution($im, $matrix, $divisor, 0);
    }
}

if (!function_exists('cert_adv_binarize')) {
    /**
     * Global-threshold black/white pass (a fast, dependency-free
     * approximation of adaptive thresholding). Threshold = average sampled
     * luminance, nudged down a bit so thin/faded strokes survive instead
     * of getting wiped out. Image must already be size-capped - this is a
     * full per-pixel loop, kept fast by resizing first.
     */
    function cert_adv_binarize($im): void
    {
        $w = imagesx($im); $h = imagesy($im);

        $sum = 0; $n = 0;
        $stepX = max(1, (int) ($w / 300));
        $stepY = max(1, (int) ($h / 300));
        for ($y = 0; $y < $h; $y += $stepY) {
            for ($x = 0; $x < $w; $x += $stepX) {
                $c = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                $sum += 0.299 * $c['red'] + 0.587 * $c['green'] + 0.114 * $c['blue'];
                $n++;
            }
        }
        $threshold = $n > 0 ? max(60, ($sum / $n) - 12) : 128;

        $black = imagecolorallocate($im, 0, 0, 0);
        $white = imagecolorallocate($im, 255, 255, 255);
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                $lum = 0.299 * $c['red'] + 0.587 * $c['green'] + 0.114 * $c['blue'];
                imagesetpixel($im, $x, $y, $lum < $threshold ? $black : $white);
            }
        }
    }
}

if (!function_exists('cert_adv_histogram_stretch')) {
    /** Stretches contrast to fill the full 0-255 range - helps yellowed/faded paper and uneven lighting. */
    function cert_adv_histogram_stretch($im): void
    {
        $w = imagesx($im); $h = imagesy($im);
        $min = 255; $max = 0;
        $stepX = max(1, (int) ($w / 300));
        $stepY = max(1, (int) ($h / 300));
        for ($y = 0; $y < $h; $y += $stepY) {
            for ($x = 0; $x < $w; $x += $stepX) {
                $c = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                $lum = (int) round(0.299 * $c['red'] + 0.587 * $c['green'] + 0.114 * $c['blue']);
                if ($lum < $min) $min = $lum;
                if ($lum > $max) $max = $lum;
            }
        }
        if ($max <= $min) return;
        $range = $max - $min;
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $c = imagecolorsforindex($im, imagecolorat($im, $x, $y));
                $lum = 0.299 * $c['red'] + 0.587 * $c['green'] + 0.114 * $c['blue'];
                $v = max(0, min(255, (int) round((($lum - $min) / $range) * 255)));
                imagesetpixel($im, $x, $y, imagecolorallocate($im, $v, $v, $v));
            }
        }
    }
}

if (!function_exists('cert_adv_save_tmp')) {
    function cert_adv_save_tmp($im): string
    {
        $p = sys_get_temp_dir() . '/geogene_ocradv_' . uniqid() . '.png';
        imagepng($im, $p);
        return $p;
    }
}

if (!function_exists('cert_adv_generate_versions')) {
    /**
     * Builds several enhanced copies of ONE photo as temp PNG files.
     * @return array<int,array{label:string,path:string}>
     */
    function cert_adv_generate_versions(string $imagePath, bool $oldMode): array
    {
        $base = cert_adv_load_image($imagePath);
        if (!$base) return [];
        $base = cert_adv_resize_cap($base, 2000);

        $versions = [];

        // Grayscale + mild contrast + sharpen - solid general-purpose pass.
        $v = cert_adv_clone_image($base);
        imagefilter($v, IMG_FILTER_GRAYSCALE);
        imagefilter($v, IMG_FILTER_CONTRAST, -15);
        cert_adv_sharpen($v);
        $versions[] = ['label' => 'gray_contrast', 'path' => cert_adv_save_tmp($v)];

        // Binarized (black/white) - makes faded ink stand out.
        $v = cert_adv_clone_image($base);
        imagefilter($v, IMG_FILTER_GRAYSCALE);
        $v = cert_adv_resize_cap($v, 1400);
        cert_adv_binarize($v);
        $versions[] = ['label' => 'binarized', 'path' => cert_adv_save_tmp($v)];

        // Upscaled + denoised + sharpened - helps small/blurry text.
        $v = cert_adv_upscale($base, 1.8);
        imagefilter($v, IMG_FILTER_SMOOTH, 6);
        cert_adv_sharpen($v);
        imagefilter($v, IMG_FILTER_CONTRAST, -10);
        $versions[] = ['label' => 'upscale_denoise', 'path' => cert_adv_save_tmp($v)];

        // Faint-preserving - gentle contrast only, no thresholding, so
        // thin/faded handwriting-adjacent strokes aren't wiped out.
        $v = cert_adv_clone_image($base);
        imagefilter($v, IMG_FILTER_GRAYSCALE);
        imagefilter($v, IMG_FILTER_BRIGHTNESS, 8);
        imagefilter($v, IMG_FILTER_CONTRAST, -6);
        $versions[] = ['label' => 'faint_preserve', 'path' => cert_adv_save_tmp($v)];

        if ($oldMode) {
            // Old/faded-paper recovery - histogram stretch fights yellowed
            // paper + uneven lighting/shadows better than a flat contrast bump.
            $v = cert_adv_clone_image($base);
            imagefilter($v, IMG_FILTER_GRAYSCALE);
            cert_adv_histogram_stretch($v);
            imagefilter($v, IMG_FILTER_CONTRAST, -20);
            cert_adv_sharpen($v);
            $versions[] = ['label' => 'old_certificate_recovery', 'path' => cert_adv_save_tmp($v)];
        }

        return $versions;
    }
}

if (!function_exists('cert_adv_get_word_confidences')) {
    /** Runs Tesseract's TSV output to get a per-word confidence list for one image. */
    function cert_adv_get_word_confidences(string $imagePath, string $psm = '4'): array
    {
        if (!cert_ocr_available()) return [];

        $outBase = sys_get_temp_dir() . '/geogene_ocradv_' . uniqid();
        $safeImg = escapeshellarg($imagePath);
        $safeOut = escapeshellarg($outBase);
        @shell_exec("tesseract $safeImg $safeOut --psm $psm tsv 2>&1");

        $tsvFile = $outBase . '.tsv';
        $words = [];
        if (is_file($tsvFile)) {
            $fh = fopen($tsvFile, 'r');
            if ($fh) {
                fgetcsv($fh, 0, "\t"); // header row
                while (($row = fgetcsv($fh, 0, "\t")) !== false) {
                    if (count($row) < 12) continue;
                    $level = (int) $row[0];
                    $conf  = (float) $row[10];
                    $text  = trim((string) $row[11]);
                    if ($level === 5 && $text !== '' && $conf >= 0) {
                        $words[] = ['text' => $text, 'conf' => $conf];
                    }
                }
                fclose($fh);
            }
            @unlink($tsvFile);
        }
        return $words;
    }
}

if (!function_exists('cert_adv_match_word_confidence')) {
    /** Best-effort average Tesseract confidence for the tokens making up $value. */
    function cert_adv_match_word_confidence(string $value, array $words): ?float
    {
        $value = trim($value);
        if ($value === '' || empty($words)) return null;

        $confs = [];
        foreach (preg_split('/\s+/', $value) as $tok) {
            $tok = trim($tok, " ,.():;-");
            if ($tok === '' || strlen($tok) < 2) continue;
            foreach ($words as $w) {
                if (stripos($w['text'], $tok) !== false || stripos($tok, $w['text']) !== false) {
                    $confs[] = $w['conf'];
                    break;
                }
            }
        }
        return $confs ? array_sum($confs) / count($confs) : null;
    }
}

if (!function_exists('cert_adv_status_for_confidence')) {
    function cert_adv_status_for_confidence(int $confidence): string
    {
        if ($confidence >= 90) return 'HIGH';
        if ($confidence >= 75) return 'MEDIUM';
        return 'LOW';
    }
}

if (!function_exists('cert_adv_consensus')) {
    /**
     * Combines the field guesses extracted from several OCR passes (one per
     * image variant) into one value-per-field with a confidence score.
     *
     * @param string $type
     * @param array  $ocrResults  [ ['label'=>string,'text'=>string,'words'=>array], ... ]
     * @return array key => ['value'=>string,'confidence'=>int,'status'=>string,'alternatives'=>array]
     */
    function cert_adv_consensus(string $type, array $ocrResults): array
    {
        $perVersionFields = [];
        foreach ($ocrResults as $idx => $r) {
            $perVersionFields[$idx] = trim($r['text']) !== '' ? cert_auto_extract($type, $r['text']) : [];
        }

        $keys = [];
        foreach ($perVersionFields as $f) {
            foreach (array_keys($f) as $k) $keys[$k] = true;
        }

        $result = [];
        foreach (array_keys($keys) as $key) {
            $candidates = []; // normalized value => ['count','display','versions']
            foreach ($perVersionFields as $idx => $f) {
                if (!isset($f[$key])) continue;
                $val = trim((string) $f[$key]);
                if ($val === '') continue;
                $norm = strtoupper($val);
                if (!isset($candidates[$norm])) {
                    $candidates[$norm] = ['count' => 0, 'display' => $val, 'versions' => []];
                }
                $candidates[$norm]['count']++;
                $candidates[$norm]['versions'][] = $idx;
            }
            if (empty($candidates)) continue;

            uasort($candidates, function ($a, $b) {
                return $b['count'] <=> $a['count'] ?: strlen($b['display']) <=> strlen($a['display']);
            });
            $top = reset($candidates);
            $totalVersions = count($ocrResults);
            $agreeCount = $top['count'];

            if ($agreeCount >= 2 && $agreeCount === $totalVersions) {
                $confidence = 96;
            } elseif ($agreeCount >= 2) {
                $confidence = 85;
            } elseif ($totalVersions <= 1) {
                $confidence = 65;
            } else {
                $confidence = 45; // only 1 of several versions found it - could be noise
            }

            $wordConf = null;
            foreach ($top['versions'] as $vIdx) {
                $match = cert_adv_match_word_confidence($top['display'], $ocrResults[$vIdx]['words'] ?? []);
                if ($match !== null) $wordConf = $wordConf === null ? $match : max($wordConf, $match);
            }
            if ($wordConf !== null) {
                $confidence = (int) round(($confidence + $wordConf) / 2);
            }
            $confidence = max(1, min(99, $confidence));

            $alternatives = [];
            if (count($candidates) > 1) {
                $rest = array_slice($candidates, 1, 2, true);
                foreach ($rest as $c) $alternatives[] = $c['display'];
            }

            $result[$key] = [
                'value'        => $top['display'],
                'confidence'   => $confidence,
                'status'       => cert_adv_status_for_confidence($confidence),
                'alternatives' => $alternatives,
            ];
        }

        return $result;
    }
}

if (!function_exists('cert_adv_scan_photo')) {
    /**
     * Full "OLD CERTIFICATE MODE" pipeline for ONE photo: generate variants,
     * OCR every one of them, and return a consensus field set + combined
     * raw text. Slower than cert_run_ocr() on purpose (see file header) -
     * only call this when the staff has opted into old-certificate mode.
     */
    function cert_adv_scan_photo(string $imagePath, string $type, bool $oldMode = false): array
    {
        @set_time_limit(120); // multi-pass OCR on a big old photo can take a while

        $rotated = cert_auto_rotate_image($imagePath);

        $results = [];
        $baseText = cert_run_ocr($rotated);
        if (trim($baseText) !== '') {
            $results[] = ['label' => 'original', 'text' => $baseText, 'words' => cert_adv_get_word_confidences($rotated)];
        }

        if (extension_loaded('gd')) {
            foreach (cert_adv_generate_versions($rotated, $oldMode) as $v) {
                $text = cert_run_ocr($v['path']);
                if (trim($text) !== '') {
                    $results[] = ['label' => $v['label'], 'text' => $text, 'words' => cert_adv_get_word_confidences($v['path'])];
                }
                @unlink($v['path']);
            }
        }

        if ($rotated !== $imagePath) @unlink($rotated);

        $combinedText = trim(implode("\n", array_map(fn($r) => $r['text'], $results)));

        return [
            'ocr_text'       => $combinedText,
            'fields'         => cert_adv_consensus($type, $results),
            'versions_tried' => count($results),
        ];
    }
}

if (!function_exists('cert_adv_merge_field_sets')) {
    /**
     * Merges the consensus field sets from SEVERAL photos (e.g. Front 1 +
     * Front 2) into one: keeps the higher-confidence value per field, and
     * boosts confidence further when independent photos agree.
     * @param array $sets array of [key => ['value','confidence','status','alternatives']]
     */
    function cert_adv_merge_field_sets(array $sets): array
    {
        $best = [];
        foreach ($sets as $set) {
            foreach ($set as $key => $info) {
                if (!isset($best[$key])) {
                    $best[$key] = $info;
                    continue;
                }
                if (strtoupper($info['value']) === strtoupper($best[$key]['value'])) {
                    $best[$key]['confidence'] = max(1, min(99, $best[$key]['confidence'] + 5));
                    $best[$key]['status'] = cert_adv_status_for_confidence($best[$key]['confidence']);
                } elseif ($info['confidence'] > $best[$key]['confidence']) {
                    $best[$key] = $info;
                }
            }
        }
        return $best;
    }
}
