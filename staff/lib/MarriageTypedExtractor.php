<?php
/**
 * Marriage certificate reader for the TWO-COLUMN layout
 * (Municipal Form No. 97, "Revised January 2007"): HUSBAND on the left,
 * WIFE on the right, one item per row.
 *
 * Why a separate reader: plain OCR merges both columns into one line of text
 * ("OBIE LUMONA SAIN   LORETO QUILANG PILOTON"), so the label-based text
 * extractor (made for the row-by-row 2016 layout) mixes the husband and the wife.
 *
 * How it works (no templates, no hard-coded coordinates):
 *   1. The browser OCRs a "typed text only" copy of the photo (only the dark
 *      pixels are kept, so the light-gray printed labels disappear), and sends
 *      every word WITH its position: [text, left, top, width, height, confidence].
 *      Several threshold variants are sent so the server can vote.
 *   2. The page is split into a LEFT and a RIGHT column at the midpoint between
 *      the printed words HUSBAND and WIFE.
 *   3. Each column's typed lines are read top-to-bottom and classified by what
 *      they look like (a date, a place, MALE/FEMALE, a religion, a civil status,
 *      a person's name ...). Order is only used where the form itself fixes it
 *      (child's name stack, then father, then mother).
 *   4. The item-18 line ("OMAR LAMBANA SAIN ... MAYLADE BALONGA PILOTON") is
 *      used to correct the spelling of the name stack.
 *   5. The variants vote field by field.
 *
 * Entry point:  cert_marriage_typed_extract(array $variants, array $common = []): array
 *   $variants = list of word lists, each word = [text, left, top, width, height, conf]
 * Returns the SAME field keys the text extractor produces (husband_first, wife_dob,
 * husband_father_first, place_of_marriage, ...). Empty array if the page doesn't
 * look like this layout.
 */

if (!function_exists('mte_tok')) {
    /** Uppercase letters (incl. Ñ), apostrophe, hyphen and period only. */
    function mte_tok(string $t): string
    {
        $t = mb_strtoupper($t, 'UTF-8');
        return preg_replace("/[^A-ZÑ'\\-.]/u", '', $t) ?? '';
    }

    function mte_alpha(string $t): string
    {
        return preg_replace('/[^A-ZÑ]/u', '', mb_strtoupper($t, 'UTF-8')) ?? '';
    }

    /** True if $t looks like $word allowing OCR damage (edit distance). */
    function mte_like(string $t, string $word, int $maxDist): bool
    {
        $a = mte_alpha($t);
        if ($a === '') return false;
        if (abs(strlen($a) - strlen($word)) > $maxDist) return false;
        return levenshtein($a, $word) <= $maxDist;
    }

    function mte_title(string $s): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $s));
        if ($s === '') return '';
        if (function_exists('gg_title_case')) return gg_title_case($s);
        return mb_convert_case(mb_strtolower($s, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Typed values are dark, uppercase and read with decent confidence; leftovers of the
     * printed labels come out as low-confidence or lowercase fragments ("ods", "Tete", "Statas").
     */
    function mte_keep_word(array $w): bool
    {
        if (count($w) < 6) return false;
        $t = (string) $w[0];
        if (!preg_match('/[A-Za-z0-9]/', $t)) return false;
        $conf = (int) $w[5];
        if ($conf < 30) return false;
        if (preg_match('/^\d{1,2}(st|nd|rd|th)$/i', $t)) return true; // "14th" is a typed day, not a label
        if (preg_match('/[a-z]/', $t) && $conf < 70) return false;
        return true;
    }

    /** Group words into text lines (top to bottom), each line sorted left to right. */
    function mte_lines(array $words): array
    {
        $words = array_values(array_filter($words, function ($w) {
            return is_array($w) && count($w) >= 5 && trim((string) $w[0]) !== '' && preg_match('/[A-Za-z0-9]/', (string) $w[0]);
        }));
        if (!$words) return [];
        $hs = array_map(fn($w) => (int) $w[4], $words);
        sort($hs);
        $medH = max(8, $hs[(int) (count($hs) / 2)]);
        usort($words, fn($a, $b) => ($a[2] + $a[4] / 2) <=> ($b[2] + $b[4] / 2));
        $lines = [];
        foreach ($words as $w) {
            $cy = $w[2] + $w[4] / 2;
            $placed = false;
            if ($lines) {
                $last = &$lines[count($lines) - 1];
                if (abs($cy - $last['cy']) <= $medH * 0.65) {
                    $last['words'][] = $w;
                    $n = count($last['words']);
                    $last['cy'] = ($last['cy'] * ($n - 1) + $cy) / $n;
                    $placed = true;
                }
                unset($last);
            }
            if (!$placed) $lines[] = ['cy' => $cy, 'words' => [$w]];
        }
        foreach ($lines as &$ln) {
            usort($ln['words'], fn($a, $b) => $a[1] <=> $b[1]);
        }
        unset($ln);
        return ['lines' => $lines, 'medH' => $medH];
    }

    function mte_text(array $words): string
    {
        return trim(implode(' ', array_map(fn($w) => (string) $w[0], $words)));
    }

    if (!defined('MTE_MONTHS')) define('MTE_MONTHS', ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC']);

    /** Find "08 MARCH 1980" / "March 8, 1980" in text. Returns [d, m(1-12), y, endOffset] or null. */
    function mte_find_date(string $s): ?array
    {
        // OCR confuses digits and letters inside numbers: O/D->0, I/l->1
        $fix = fn(string $n) => strtr($n, ['O' => '0', 'o' => '0', 'D' => '0', 'I' => '1', 'l' => '1', '|' => '1', 'S' => '5', 'B' => '8']);
        $re = '/\b([0-9OoIl|]{1,2})\s*(?:st|nd|rd|th)?\s*[,.]?\s*(JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)[A-Z]*\.?\s*,?\s*([0-9OoIlSB]{4})\b/i';
        if (preg_match($re, $s, $m, PREG_OFFSET_CAPTURE)) {
            $d = (int) $fix($m[1][0]);
            $y = (int) $fix($m[3][0]);
            $mo = array_search(strtoupper($m[2][0]), MTE_MONTHS, true);
            if ($mo !== false && $d >= 1 && $d <= 31 && $y >= 1850 && $y <= 2100) {
                return [$d, $mo + 1, $y, $m[0][1] + strlen($m[0][0])];
            }
        }
        $re2 = '/\b(JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)[A-Z]*\.?\s+([0-9OoIl]{1,2})\s*,?\s*([0-9OoIlSB]{4})\b/i';
        if (preg_match($re2, $s, $m, PREG_OFFSET_CAPTURE)) {
            $d = (int) $fix($m[2][0]);
            $y = (int) $fix($m[3][0]);
            $mo = array_search(strtoupper($m[1][0]), MTE_MONTHS, true);
            if ($mo !== false && $d >= 1 && $d <= 31 && $y >= 1850 && $y <= 2100) {
                return [$d, $mo + 1, $y, $m[0][1] + strlen($m[0][0])];
            }
        }
        return null;
    }

    /**
     * The date of marriage is printed over a label row and is often damaged ("14 MAY I Bogyppe") but
     * the same date is repeated further down ("14th", "MAY 2016"). Collect every day / month / year
     * piece in the tail, let the most repeated MONTH win, then take the day and the year that go with it.
     */
    function mte_find_marriage_date(array $texts, int $minYear): ?array
    {
        $occ = []; // month => ['n'=>count, 'day'=>?, 'year'=>?, 'first'=>order]
        $order = 0;
        foreach ($texts as $t) {
            if (!preg_match_all('/(?:\b([0-9OIl]{1,2})\s*(?:st|nd|rd|th)?\s*[,.]?\s*)?\b(JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)[A-Z]*\b\.?\s*,?\s*(?:([0-9OIlSB]{4})\b)?/i', $t, $mm, PREG_SET_ORDER)) continue;
            foreach ($mm as $m) {
                $mo = array_search(strtoupper($m[2]), MTE_MONTHS, true);
                if ($mo === false) continue;
                $mo++;
                $occ[$mo] = $occ[$mo] ?? ['n' => 0, 'day' => null, 'year' => null, 'years' => [], 'first' => $order++];
                $occ[$mo]['n']++;
                if (!empty($m[1]) && $occ[$mo]['day'] === null) {
                    $d = (int) strtr($m[1], ['O' => '0', 'I' => '1', 'l' => '1']);
                    if ($d >= 1 && $d <= 31) $occ[$mo]['day'] = $d;
                }
                if (!empty($m[3]) && $occ[$mo]['year'] === null) {
                    $y = (int) strtr($m[3], ['O' => '0', 'I' => '1', 'l' => '1', 'S' => '5', 'B' => '8']);
                    if ($y >= $minYear && $y <= 2100) { $occ[$mo]['years'][] = $y; if ($occ[$mo]['year'] === null) $occ[$mo]['year'] = $y; }
                }
            }
        }
        if (!$occ) return null;
        uasort($occ, fn($a, $b) => [$b['n'], $a['first']] <=> [$a['n'], $b['first']]);
        $mo = array_key_first($occ);
        $o = $occ[$mo];
        if ($o['day'] === null || $o['year'] === null) return null;
        return [$o['day'], $mo, $o['year'], 0, $o['years']];
    }

    /**
     * "DD MONTH YYYY" found by POSITION: a day word just left of a month word, a year word just right of it.
     * (A tilted photo puts the pieces of one printed date on different text lines, so reading lines misses it.)
     * Only the day-month-year order is accepted: that is how the marriage date is typed, and it keeps out
     * the officer's "DECEMBER 31, 2017" (registry expiration) printed lower on the page.
     */
    function mte_find_marriage_date_words(array $words, int $minYear, float $medH): ?array
    {
        $fixNum = fn(string $n) => (int) strtr($n, ['O' => '0', 'o' => '0', 'I' => '1', 'l' => '1', 'S' => '5', 'B' => '8']);
        $cands = [];
        foreach ($words as $m) {
            $mt = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) $m[0]));
            if (strlen($mt) < 3 || strlen($mt) > 9 || !preg_match('/^(JAN|FEB|MAR|APR|MAY|JUN|JUL|AUG|SEP|OCT|NOV|DEC)[A-Z]*$/', $mt)) continue;
            $mo = array_search(substr($mt, 0, 3), MTE_MONTHS, true);
            if ($mo === false) continue;
            $mcy = $m[2] + $m[4] / 2; $mL = $m[1]; $mR = $m[1] + $m[3];
            $day = null; $dayGap = 1e9; $year = null; $yearGap = 1e9; $yearsNear = [];
            foreach ($words as $w) {
                if ($w === $m) continue;
                $cy = $w[2] + $w[4] / 2;
                if (abs($cy - $mcy) > 1.8 * $medH) continue;
                $t = trim((string) $w[0], '.,;:');
                if ($w[1] + $w[3] <= $mL + 4 && preg_match('/^([0-9OIl]{1,2})(?:st|nd|rd|th)?$/i', $t, $dm)) {
                    $gap = $mL - ($w[1] + $w[3]);
                    $d = $fixNum($dm[1]);
                    if ($gap <= 10 * $medH && $gap < $dayGap && $d >= 1 && $d <= 31) { $day = $d; $dayGap = $gap; }
                }
                if ($w[1] >= $mR - 4 && preg_match('/^([0-9OIlSB]{4})$/', $t, $ym)) {
                    $gap = $w[1] - $mR;
                    $y = $fixNum($ym[1]);
                    if ($gap <= 10 * $medH && $y >= $minYear && $y <= 2100) {
                        $yearsNear[] = $y;
                        if ($gap < $yearGap) { $year = $y; $yearGap = $gap; }
                    }
                }
            }
            if ($day !== null && $year !== null) $cands[] = [$day, $mo + 1, $year, 0, array_values(array_unique($yearsNear))];
        }
        if (!$cands) return null;
        $count = [];
        foreach ($cands as $c) { $k = $c[0] . '-' . $c[1] . '-' . $c[2]; $count[$k] = ($count[$k] ?? 0) + 1; }
        arsort($count);
        foreach ($cands as $c) if ($c[0] . '-' . $c[1] . '-' . $c[2] === array_key_first($count)) return $c;
        return $cands[0];
    }

    function mte_fmt_date(array $d): string
    {
        return sprintf('%02d %s %04d', $d[0], ucfirst(strtolower(date('F', mktime(0, 0, 0, $d[1], 1, 2000)))), $d[2]);
    }

    /** What kind of value is this line? */
    function mte_classify(string $text): string
    {
        $toks = array_values(array_filter(preg_split('/[\s,;]+/', $text), fn($t) => mte_alpha($t) !== ''));
        if (!$toks) return 'noise';
        if (mte_find_date($text)) return 'date';
        $up = mb_strtoupper($text, 'UTF-8');
        if (preg_match('/\b(CITIZENSHIP|RELIGION|RESIDENCE|STATUS|FATHER|MOTHER|MAIDEN|BIRTH|PLACE|CONSENT|RELATIONSHIP|CONTRACTING|PARTIES|AGE|SEX)\b/', $up)) return 'noise';
        if (preg_match('/PHILIPP|PHILIPINES/i', $up)) return 'place';

        $allCitizen = true;
        $anySex = false;
        $anyCivil = false;
        $anyReligion = false;
        foreach ($toks as $t) {
            $a = mte_alpha($t);
            if (!(strlen($a) >= 5 && mte_like($a, 'FILIPINO', 3))) $allCitizen = false;
            if (mte_like($a, 'FEMALE', 2) || (strlen($a) >= 3 && mte_like($a, 'MALE', 1))) $anySex = true;
            if (preg_match('/^(SINGLE|INGLE|SINGL|SNGLE|WIDOW|WIDOWED|WIDOWER|DIVORCED|ANNULLED|SEPARATED)$/', $a) || mte_like($a, 'SINGLE', 2) || mte_like($a, 'WIDOWED', 2)) $anyCivil = true;
            if (mte_like($a, 'ISLAM', 1) || mte_like($a, 'MUSLIM', 1) || preg_match('/CATHOL/', $a) || mte_like($a, 'CATHOLIC', 2)
                || in_array($a, ['INC', 'IGLESIA', 'PROTESTANT', 'BAPTIST', 'ADVENTIST', 'CHRISTIAN', 'EVANGELICAL', 'METHODIST', 'ANGLICAN', 'PENTECOSTAL'], true)) $anyReligion = true;
        }
        if ($allCitizen) return 'citizen';
        if ($anySex) return 'sex';
        if ($anyCivil) return 'civil';
        if ($anyReligion) return 'religion';
        // a person's name = 2+ real words
        $real = array_filter($toks, fn($t) => strlen(mte_alpha($t)) >= 3);
        if (count($real) >= 2) return 'name';
        if (count($real) === 1) return 'word';
        return 'noise';
    }

    /** Merge OCR fragments inside a name line: "L AMBANA" -> "LAMBANA"; drop 1-2 letter junk. */
    function mte_name_tokens(string $text): array
    {
        $raw = array_values(array_filter(preg_split('/[\s,;]+/', $text), fn($t) => mte_tok($t) !== ''));
        $out = [];
        $n = count($raw);
        for ($i = 0; $i < $n; $i++) {
            $t = mte_tok($raw[$i]);
            $a = mte_alpha($t);
            if ($a === '') continue;
            if (strlen($a) <= 2 && $i + 1 < $n && strlen(mte_alpha($raw[$i + 1])) >= 3 && !in_array($a, ['MA', 'JR', 'SR'], true)) {
                $raw[$i + 1] = $a . mte_tok($raw[$i + 1]); // glue the fragment to the next word
                continue;
            }
            if (strlen($a) <= 1) continue;
            $out[] = trim($t, '.-\'');
        }
        return array_values(array_filter($out, fn($t) => $t !== ''));
    }

    function mte_split_person(array $tokens): array
    {
        $tokens = array_values($tokens);
        $n = count($tokens);
        if ($n === 0) return ['', '', ''];
        if ($n >= 4 && strlen(mte_alpha($tokens[0])) <= 3 && !in_array(mte_alpha($tokens[0]), ['MA', 'JO'], true)) {
            array_shift($tokens); // stray OCR fragment in front of the name
            $n--;
        }
        if ($n === 1) return [mte_title($tokens[0]), '', ''];
        if (function_exists('parseGivenMiddleSurname')) {
            $p = parseGivenMiddleSurname(implode(' ', $tokens));
            return [$p[0] ?? '', $p[1] ?? '', $p[2] ?? ''];
        }
        if ($n === 2) return [mte_title($tokens[0]), '', mte_title($tokens[1])];
        return [mte_title($tokens[0]), mte_title(implode(' ', array_slice($tokens, 1, $n - 2))), mte_title($tokens[$n - 1])];
    }

    function mte_norm_religion(string $s): string
    {
        $u = mb_strtoupper($s, 'UTF-8');
        if (preg_match('/CATHOL/', $u) && preg_match('/ROMAN|OMAN|ROMA/', $u)) return 'Roman Catholic';
        if (preg_match('/CATHOL/', $u)) return 'Catholic';
        foreach (preg_split('/\s+/', $u) as $t) {
            if (mte_like($t, 'ISLAM', 1)) return 'Islam';
        }
        return mte_title(preg_replace('/[^A-Za-z ]/', ' ', $s));
    }

    function mte_norm_civil(string $s): string
    {
        foreach (preg_split('/[\s,]+/', mb_strtoupper($s, 'UTF-8')) as $t) {
            $a = mte_alpha($t);
            if ($a === '') continue;
            if (mte_like($a, 'SINGLE', 2)) return 'Single';
            if (mte_like($a, 'WIDOWED', 2) || mte_like($a, 'WIDOW', 1)) return 'Widowed';
            if (mte_like($a, 'DIVORCED', 2)) return 'Divorced';
            if (mte_like($a, 'ANNULLED', 2)) return 'Annulled';
        }
        return '';
    }

    /** Read ONE column (list of line strings, top to bottom, starting at its HUSBAND/WIFE line). */
    function mte_read_column(array $lineTexts, array $lineYs = []): array
    {
        $r = [];
        $stack = [];
        $dateIdx = null;
        foreach ($lineTexts as $i => $t) {
            if (mte_find_date($t)) { $dateIdx = $i; break; }
        }
        $limit = $dateIdx ?? min(count($lineTexts), 5);
        for ($i = 0; $i < $limit && count($stack) < 3; $i++) {
            $toks = mte_name_tokens($lineTexts[$i]);
            $real = array_values(array_filter($toks, fn($t) => strlen(mte_alpha($t)) >= 3));
            if ($real && mte_classify($lineTexts[$i]) !== 'place') $stack[] = implode(' ', $real);
        }
        $r['stack'] = $stack;
        if ($dateIdx === null) return $r;

        $d = mte_find_date($lineTexts[$dateIdx]);
        $r['dob'] = mte_fmt_date($d);
        if (preg_match('/\b(\d{2})\b/', substr($lineTexts[$dateIdx], $d[3]), $am)) $r['age'] = $am[1];

        $placeN = 0; $nameN = 0; $citizenAfterName = 0;
        $lastIdx = $dateIdx;
        for ($i = $dateIdx + 1; $i < count($lineTexts); $i++) {
            $t = $lineTexts[$i];
            $cls = mte_classify($t);
            if ($cls === 'place') {
                $placeN++;
                $clean = trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z0-9,.\-\' ]/', ' ', $t)));
                if ($placeN === 1) $r['birthplace'] = $clean; elseif ($placeN === 2) $r['residence'] = $clean;
                $lastIdx = $i;
            } elseif ($cls === 'sex' && !isset($r['sex'])) {
                $r['sex'] = preg_match('/FEMAL|FEMAI/i', $t) ? 'Female' : 'Male';
                foreach (preg_split('/\s+/', $t) as $tk) {
                    if (strlen(mte_alpha($tk)) >= 5 && mte_like($tk, 'FILIPINO', 3)) $r['citizenship'] = 'Filipino';
                }
                $lastIdx = $i;
            } elseif ($cls === 'religion' && !isset($r['religion'])) {
                $r['religion'] = mte_norm_religion($t);
                $lastIdx = $i;
            } elseif ($cls === 'civil' && !isset($r['civil'])) {
                $r['civil'] = mte_norm_civil($t);
                $lastIdx = $i;
            } elseif ($cls === 'name' && $nameN < 2 && (isset($r['civil']) || isset($r['religion']) || isset($r['residence']))) {
                $nameN++;
                $toks = mte_name_tokens($t);
                if ($nameN === 1) $r['father'] = $toks; else $r['mother'] = $toks;
                $lastIdx = $i;
            } elseif ($cls === 'citizen' && $nameN >= 1) {
                $citizenAfterName++;
                $lastIdx = $i;
            }
            if ($nameN === 2 && $citizenAfterName >= 2) break;
        }
        $r['lastIdx'] = $lastIdx;
        $r['lastY'] = $lineYs[$lastIdx] ?? null;
        return $r;
    }

    /** Registry number: a "YYYY-NNN" word in the top part of the page (searched in ALL words - the big
     *  printed number is often read with confidence 0). OCR often reads the leading 2 of the year as 9. */
    function mte_registry(array $rawWords): string
    {
        $pageB = 0;
        foreach ($rawWords as $w) if (is_array($w) && count($w) >= 5) $pageB = max($pageB, $w[2] + $w[4]);
        foreach ($rawWords as $w) {
            if (!is_array($w) || count($w) < 5 || $w[2] > 0.3 * $pageB) continue;
            $t = strtoupper(preg_replace('/[^0-9O\-\x{2013}]/u', '', strtr((string) $w[0], ['o' => 'O'])));
            $t = str_replace(['O', "\u{2013}"], ['0', '-'], $t);
            if (!preg_match('/^(\d{4})-(\d{1,6})$/', $t, $rm)) continue;
            $yr = (int) $rm[1];
            if ($yr > 2100 || $yr < 1900) {
                $try = (int) ('2' . substr($rm[1], 1));
                if ($yr >= 9000 && $try >= 2000 && $try <= 2100) $yr = $try; else continue;
            }
            return $yr . '-' . $rm[2];
        }
        return '';
    }

    /** Single-variant extraction. Returns [] if this variant doesn't look like the layout. */
    function mte_extract_one(array $words): array
    {
        $rawWords = $words; // the big registry number is often read with confidence 0, so it is searched in ALL words
        $words = array_values(array_filter($words, 'mte_keep_word'));
        if (count($words) < 20) return [];
        // If the typed values are in CAPITALS (as on most municipal forms), any word that still has
        // lowercase letters is a leftover of the printed Title-Case labels ("Citizenship", "Wali Who").
        $conf = array_values(array_filter($words, fn($w) => (int) $w[5] >= 80 && preg_match('/[A-Za-z]{3,}/', (string) $w[0])));
        if (count($conf) >= 15) {
            $upper = count(array_filter($conf, fn($w) => !preg_match('/[a-z]/', (string) $w[0])));
            if ($upper / count($conf) >= 0.7) {
                $words = array_values(array_filter($words, fn($w) => !preg_match('/[a-z]/', (string) $w[0]) || preg_match('/^\d{1,2}(st|nd|rd|th)$/i', (string) $w[0])));
            }
        }
        $hs = array_map(fn($w) => (int) $w[4], $words);
        sort($hs);
        $medH = max(8, $hs[(int) (count($hs) / 2)]);

        // --- the printed HUSBAND / WIFE headings give the column split and the start of the values ---
        $hw = $ww = null;
        foreach ($words as $w) {
            $a = mte_alpha((string) $w[0]);
            if ($hw === null && strlen($a) >= 6 && mte_like($a, 'HUSBAND', 1)) $hw = $w;
            if ($ww === null && $a === 'WIFE') $ww = $w;
        }
        if ($hw === null && $ww === null) return [];
        $pageR = 0;
        foreach ($words as $w) $pageR = max($pageR, $w[1] + $w[3]);
        $hx = $hw ? $hw[1] + $hw[3] / 2 : null;
        $wx = $ww ? $ww[1] + $ww[3] / 2 : null;
        if ($hx !== null && $wx !== null) $mid = ($hx + $wx) / 2;
        elseif ($hx !== null) $mid = $hx + 0.17 * $pageR;
        else $mid = $wx - 0.17 * $pageR;
        $startYL = ($hw ?? $ww)[2] - 1.0 * $medH;
        $startYR = ($ww ?? $hw)[2] - 1.0 * $medH;

        // --- split the WORDS into columns first, then make lines inside each column.
        //     (a tilted photo puts the same printed row at a different height left vs right) ---
        $colL = []; $colR = [];
        foreach ($words as $w) {
            $a = mte_alpha((string) $w[0]);
            if ($a === 'WIFE' || (strlen($a) >= 6 && mte_like($a, 'HUSBAND', 1))) continue;
            $cx = $w[1] + $w[3] / 2;
            if ($cx < $mid) { if ($w[2] >= $startYL) $colL[] = $w; }
            else { if ($w[2] >= $startYR) $colR[] = $w; }
        }
        $read = function (array $col) {
            $g = mte_lines($col);
            if (!$g) return [[], [], []];
            $texts = []; $ys = [];
            foreach ($g['lines'] as $ln) {
                $t = mte_text($ln['words']);
                if (mte_alpha($t) === '' && !preg_match('/\d/', $t)) continue;
                $texts[] = $t; $ys[] = $ln['cy'];
            }
            return [$texts, $ys, $g['lines']];
        };
        [$lt, $ly] = $read($colL);
        [$rt, $ry] = $read($colR);
        $left = mte_read_column($lt, $ly);
        $right = mte_read_column($rt, $ry);
        if (!isset($left['dob']) && !isset($right['dob'])) return [];

        // --- tail: place / date / time of marriage + officer, read from everything below the last parsed row ---
        $lastY = max($left['lastY'] ?? 0, $right['lastY'] ?? 0);
        $tailWords = array_values(array_filter($words, fn($w) => ($w[2] + $w[4] / 2) > $lastY + 0.5 * $medH));
        $tg = mte_lines($tailWords);
        $tail = [];
        foreach (($tg['lines'] ?? []) as $ln) {
            $lw = array_values(array_filter($ln['words'], fn($w) => ($w[1] + $w[3] / 2) < $mid));
            $rw = array_values(array_filter($ln['words'], fn($w) => ($w[1] + $w[3] / 2) >= $mid));
            $tail[] = ['text' => mte_text($ln['words']), 'words' => $ln['words'], 'left' => mte_text($lw), 'right' => mte_text($rw)];
        }
        $out = ['left' => $left, 'right' => $right, 'tail' => [], 'clean_names' => ['left' => [], 'right' => []]];

        // --- split the WORDS into columns first, then make lines inside each column.
        //     (a tilted photo puts the same printed row at a different height left vs right) ---
        $colL = []; $colR = [];
        foreach ($words as $w) {
            $a = mte_alpha((string) $w[0]);
            if ($a === 'WIFE' || (strlen($a) >= 6 && mte_like($a, 'HUSBAND', 1))) continue;
            $cx = $w[1] + $w[3] / 2;
            if ($cx < $mid) { if ($w[2] >= $startYL) $colL[] = $w; }
            else { if ($w[2] >= $startYR) $colR[] = $w; }
        }
        $read = function (array $col) {
            $g = mte_lines($col);
            if (!$g) return [[], [], []];
            $texts = []; $ys = [];
            foreach ($g['lines'] as $ln) {
                $t = mte_text($ln['words']);
                if (mte_alpha($t) === '' && !preg_match('/\d/', $t)) continue;
                $texts[] = $t; $ys[] = $ln['cy'];
            }
            return [$texts, $ys, $g['lines']];
        };
        [$lt, $ly] = $read($colL);
        [$rt, $ry] = $read($colR);
        $left = mte_read_column($lt, $ly);
        $right = mte_read_column($rt, $ry);
        if (!isset($left['dob']) && !isset($right['dob'])) return [];

        // --- tail: place / date / time of marriage + officer, read from everything below the last parsed row ---
        $lastY = max($left['lastY'] ?? 0, $right['lastY'] ?? 0);
        $tailWords = array_values(array_filter($words, fn($w) => ($w[2] + $w[4] / 2) > $lastY + 0.5 * $medH));
        $tg = mte_lines($tailWords);
        $tail = [];
        foreach (($tg['lines'] ?? []) as $ln) {
            $lw = array_values(array_filter($ln['words'], fn($w) => ($w[1] + $w[3] / 2) < $mid));
            $rw = array_values(array_filter($ln['words'], fn($w) => ($w[1] + $w[3] / 2) >= $mid));
            $tail[] = ['text' => mte_text($ln['words']), 'words' => $ln['words'], 'left' => mte_text($lw), 'right' => mte_text($rw)];
        }
        $out = ['left' => $left, 'right' => $right, 'tail' => [], 'clean_names' => ['left' => [], 'right' => []]];

        // registry number: a "YYYY-NNN" word in the header area. OCR often reads the leading 2 of the year as 9 ("9012-101").
        $pageB = 0;
        foreach ($words as $w) $pageB = max($pageB, $w[2] + $w[4]);
        foreach ($rawWords as $w) {
            if (!is_array($w) || count($w) < 5 || $w[2] > 0.3 * $pageB) continue;
            $t = strtoupper(preg_replace('/[^0-9O\-\x{2013}]/u', '', strtr((string) $w[0], ['o' => 'O'])));
            $t = str_replace(['O', "\u{2013}"], ['0', '-'], $t);
            if (!preg_match('/^(\d{4})-(\d{1,6})$/', $t, $rm)) continue;
            $yr = (int) $rm[1];
            if ($yr > 2100 || $yr < 1900) {
                $try = (int) ('2' . substr($rm[1], 1));
                if ($yr >= 9000 && $try >= 2000 && $try <= 2100) $yr = $try; else continue;
            }
            $out['registry'] = $yr . '-' . $rm[2];
            break;
        }

        // place of marriage: first tail line with 2+ real words that is not a printed sentence
        foreach ($tail as $k => $ln) {
            $real = array_filter(preg_split('/[\s,]+/', $ln['text']), fn($t) => strlen(mte_alpha($t)) >= 4);
            if (count($real) >= 2 && !mte_find_date($ln['text'])
                && !preg_match('/CERTIF|THIS IS|WITNESS|SIGNATURE|DATE OF|TIME OF|MARRIAGE|CONTRACT|PARTIES|LICENSE/i', $ln['text'])
                && !preg_match('/\b(NAME|MIDDLE|LAST|FIRST|CONSENT|PERSON|RELATIONSHIP|RESIDENCE|HOUSE|MOSQUE|BARANGAY|PROVINCE|CITY|MUNICIPALITY|COUNTRY|OFFICE)\b/i', $ln['text'])  // printed labels, not an answer
                && !preg_match('/^\W*\d{1,2}\s/', $ln['text'])) {                                                                // starts with an item number
                $segs = []; $cur = []; $prevEnd = null;
                foreach ($ln['words'] as $w) {
                    if ($prevEnd !== null && ($w[1] - $prevEnd) > $medH * 2.2) { $segs[] = mte_text($cur); $cur = []; }
                    $cur[] = $w; $prevEnd = $w[1] + $w[3];
                }
                if ($cur) $segs[] = mte_text($cur);
                $segs = array_values(array_filter(array_map(fn($x) => trim(preg_replace('/[^A-Za-z0-9.\-\' ]/', ' ', $x)), $segs), fn($x) => strlen(mte_alpha($x)) >= 3));
                $seen = [];
                foreach ($segs as $si => $sg) {
                    $keep = [];
                    foreach (preg_split('/\s+/', $sg) as $tk) {
                        $parts = array_values(array_filter(explode('-', mb_strtolower($tk, 'UTF-8'))));
                        if ($parts && !array_diff($parts, $seen)) continue; // every part already read = a repeat from another variant
                        foreach ($parts as $pt) $seen[] = $pt;
                        $keep[] = $tk;
                    }
                    $segs[$si] = implode(' ', $keep);
                }
                $segs = array_values(array_filter($segs, fn($x) => strlen(mte_alpha($x)) >= 3));
                $out['tail']['place'] = implode(', ', array_map('mte_title', $segs));
                unset($tail[$k]);
                break;
            }
        }
        $dobYears = [];
        foreach ([$left, $right] as $c) if (isset($c['dob'])) $dobYears[] = (int) substr($c['dob'], -4);
        $minYear = $dobYears ? max($dobYears) + 14 : 1900;
        $tailTexts = array_map(fn($ln) => $ln['text'], $tail);
        $md = mte_find_marriage_date_words($tailWords, $minYear, (float) $medH) ?? mte_find_marriage_date($tailTexts, $minYear);
        if ($md) { $out['tail']['date'] = mte_fmt_date($md); $out['tail']['date_years'] = $md[4] ?? []; }
        foreach ($tail as $ln) {
            if (!isset($out['tail']['time']) && preg_match('/(?<![\d])(\d{1,2})\s*[:.]?\s*(00|15|30|45)(?![\d])\s*(?:([AaPp])\w{0,3}\b)?/', $ln['text'], $tm) && (int) $tm[1] >= 1 && (int) $tm[1] <= 12
                && !mte_find_date($ln['text'])) {
                // "01.00 PM" - the AM/PM part is sometimes lost by the OCR; then only the clock time is returned
                $out['tail']['time'] = sprintf('%02d:%02d', (int) $tm[1], (int) $tm[2]) . (!empty($tm[3]) ? ' ' . strtoupper($tm[3]) . 'M' : '');
            }
            if (!isset($out['tail']['officer']) && preg_match('/\b(REV|FR|PASTOR|JUDGE|MAYOR|IMAM|BISHOP|MINISTER|HON)\b\.?/i', $ln['left'] ?: $ln['text'])) {
                $o = $ln['left'] !== '' ? $ln['left'] : $ln['text'];
                $o = preg_replace('/\b(ASST|ASSISTANT|PARISH|PRIEST|PRESIDING)\b.*$/i', '', $o);
                $o = trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Za-z.\' ]/', ' ', $o)));
                if (strlen(mte_alpha($o)) >= 6) $out['tail']['officer'] = mte_title($o);
            }
        }

        // item 18 repeats both full names in clean typed print: used to correct the name stacks
        foreach ($tail as $ln) {
            foreach (['left' => $ln['left'], 'right' => $ln['right']] as $side => $txt) {
                $tk = mte_name_tokens($txt);
                if (count($tk) >= 2 && count($tk) <= 5 && mte_classify($txt) === 'name' && !preg_match('/CERTIF|CONTRACT|PARTIES|THIS/i', $txt) && !$out['clean_names'][$side]) {
                    $out['clean_names'][$side] = $tk;
                }
            }
        }
        return $out;
    }

    /** Turn one side's raw reading into final husband_/wife_ fields. */
    function mte_side_fields(string $p, array $col, array $clean): array
    {
        $f = [];
        // person name: stack (first / middle / last lines), spelling corrected from the item-18 line
        $stack = [];
        foreach ($col['stack'] ?? [] as $s) $stack[] = $s;
        $fix = function (string $tok) use ($clean) {
            $best = $tok; $bd = 99;
            foreach ($clean as $c) {
                if (strlen($c) < 3) continue;
                $ta = mte_alpha($tok); $ca = mte_alpha($c);
                $d = levenshtein($ta, $ca);
                if (strlen($ta) >= 3 && str_contains($ca, $ta)) $d = min($d, 1); // "LADE" is part of "MAYLADE"
                if ($d < $bd && $d <= max(2, (int) ceil(strlen($ca) * 0.5))) { $bd = $d; $best = $c; }
            }
            return $best;
        };
        if (count($stack) >= 3) {
            $f["{$p}_first"] = mte_title($fix($stack[0]));
            $f["{$p}_middle"] = mte_title($fix($stack[1]));
            $f["{$p}_last"] = mte_title($fix($stack[2]));
        } elseif ($clean) {
            [$a, $b, $c] = mte_split_person($clean);
            $f["{$p}_first"] = $a; $f["{$p}_middle"] = $b; $f["{$p}_last"] = $c;
        } elseif ($stack) {
            [$a, $b, $c] = mte_split_person(mte_name_tokens(implode(' ', $stack)));
            $f["{$p}_first"] = $a; $f["{$p}_middle"] = $b; $f["{$p}_last"] = $c;
        }
        foreach (['father', 'mother'] as $who) {
            if (!empty($col[$who])) {
                [$a, $b, $c] = mte_split_person($col[$who]);
                $f["{$p}_{$who}_first"] = $a; $f["{$p}_{$who}_middle"] = $b; $f["{$p}_{$who}_last"] = $c;
            }
        }
        $map = ['dob' => 'dob', 'age' => 'age', 'sex' => 'sex', 'citizenship' => 'citizenship', 'religion' => 'religion',
                'civil' => 'civil_status', 'residence' => 'residence', 'birthplace' => 'place_of_birth'];
        foreach ($map as $src => $dst) {
            if (!empty($col[$src])) $f["{$p}_{$dst}"] = (string) $col[$src];
        }
        return $f;
    }

    function mte_fields_from_one(array $one): array
    {
        if (!$one) return [];
        $f = [];
        $f += mte_side_fields('husband', $one['left'], $one['clean_names']['left'] ?? []);
        $f += mte_side_fields('wife', $one['right'], $one['clean_names']['right'] ?? []);
        if (!empty($one['tail']['place'])) $f['place_of_marriage'] = $one['tail']['place'];
        if (!empty($one['tail']['date'])) {
            $f['date_of_marriage'] = $one['tail']['date'];
            $f['_date_years'] = implode(',', $one['tail']['date_years'] ?? []);
        }
        if (!empty($one['tail']['time'])) $f['time_of_marriage'] = $one['tail']['time'];
        if (!empty($one['tail']['officer'])) $f['solemnizing_officer_name'] = $one['tail']['officer'];
        return $f;
    }

    function mte_completeness(array $f): int
    {
        $n = 0;
        foreach ($f as $v) if (trim((string) $v) !== '') $n++;
        return $n;
    }

    /**
     * Every variant sees the SAME page at the same scale, so a word that one variant lost ("MAY") is often
     * present in another one, in the same place. Merge all variants' words into one list; where words
     * overlap, the one read with the highest confidence is kept.
     */
    function mte_pool_words(array $variants): array
    {
        $all = [];
        foreach ($variants as $words) {
            if (!is_array($words)) continue;
            foreach ($words as $x) {
                if (is_array($x) && count($x) >= 6 && (int) $x[5] >= 25 && preg_match('/[A-Za-z0-9]/', (string) $x[0])) $all[] = $x;
            }
        }
        usort($all, fn($a, $b) => (int) $b[5] <=> (int) $a[5]);
        $out = [];
        foreach ($all as $x) {
            $dup = false;
            foreach ($out as $o) {
                // the same word seen twice = the two boxes really overlap (not just lie close to each other)
                $ix = min($o[1] + $o[3], $x[1] + $x[3]) - max($o[1], $x[1]);
                $iy = min($o[2] + $o[4], $x[2] + $x[4]) - max($o[2], $x[2]);
                if ($ix > 0 && $iy > 0) {
                    $inter = $ix * $iy;
                    $union = $o[3] * $o[4] + $x[3] * $x[4] - $inter;
                    if ($inter / max(1, $union) > 0.5) { $dup = true; break; } // IoU: a small junk fragment must not swallow a real word
                }
            }
            if (!$dup) $out[] = $x;
        }
        return $out;
    }

    /**
     * Run every variant and let them VOTE field by field (OCR noise differs between
     * thresholds, so the value most variants agree on is usually the right one).
     */
    function cert_marriage_typed_extract(array $variants, array $common = []): array
    {
        $per = [];
        $regVotes = [];
        foreach ($variants as $words) {
            if (!is_array($words)) continue;
            $reg = mte_registry($words); // counted for every variant, even if the rest of it could not be parsed
            if ($reg !== '') $regVotes[$reg] = ($regVotes[$reg] ?? 0) + 1;
            $f = mte_fields_from_one(mte_extract_one($words));
            if ($f) $per[] = $f;
        }
        if (!$per) return [];
        usort($per, fn($a, $b) => mte_completeness($b) <=> mte_completeness($a)); // most complete first = wins ties
        $keys = [];
        foreach ($per as $f) foreach ($f as $k => $_) $keys[$k] = true;
        unset($keys['_date_years']);
        $isDate = fn(string $k) => str_ends_with($k, '_dob') || $k === 'date_of_marriage';
        $out = [];
        foreach (array_keys($keys) as $k) {
            if ($isDate($k)) continue; // dates are voted per component below
            $votes = []; $first = [];
            foreach ($per as $f) {
                $v = trim((string) ($f[$k] ?? ''));
                if ($v === '') continue;
                $norm = mb_strtoupper($v, 'UTF-8');
                $votes[$norm] = ($votes[$norm] ?? 0) + 1;
                $first[$norm] = $first[$norm] ?? $v;
            }
            if (!$votes) continue;
            arsort($votes); // stable: ties keep the value from the most complete variant
            $out[$k] = $first[array_key_first($votes)];
        }
        // dates: vote day, month and year separately (a damaged digit rarely hits the same place twice)
        $pick = function (array $vals) {
            $vals = array_values(array_filter($vals, fn($v) => $v !== null));
            if (!$vals) return null;
            $c = array_count_values($vals);
            arsort($c);
            return array_key_first($c);
        };
        foreach (array_keys($keys) as $k) {
            if (!$isDate($k)) continue;
            $d = []; $m = []; $y = [];
            foreach ($per as $f) {
                $pd = isset($f[$k]) ? mte_find_date((string) $f[$k]) : null;
                if ($pd) { $d[] = $pd[0]; $m[] = $pd[1]; $y[] = $pd[2]; }
            }
            if (!$d) continue;
            if ($k === 'date_of_marriage') { // every printed copy of the date, in every variant, gets a vote
                foreach ($per as $f) foreach (array_filter(explode(',', (string) ($f['_date_years'] ?? ''))) as $yy) $y[] = (int) $yy;
            }
            $out[$k] = ['d' => $pick($d), 'm' => $pick($m), 'y' => $pick($y), 'ys' => $y];
        }
        // a person's date of birth must agree with the age printed beside it and the year of marriage
        $marY = isset($out['date_of_marriage']) ? $out['date_of_marriage']['y'] : null;
        foreach (['husband', 'wife'] as $p) {
            if (!isset($out["{$p}_dob"])) continue;
            $age = isset($out["{$p}_age"]) ? (int) $out["{$p}_age"] : 0;
            if ($age > 0 && $marY) {
                $okYears = [$marY - $age, $marY - $age - 1];
                $cands = array_unique($out["{$p}_dob"]['ys']);
                $good = array_values(array_filter($cands, fn($yy) => in_array($yy, $okYears, true)));
                if ($good && !in_array($out["{$p}_dob"]['y'], $okYears, true)) $out["{$p}_dob"]['y'] = $good[0];
            }
        }
        foreach (array_keys($out) as $k) {
            if (is_array($out[$k])) $out[$k] = mte_fmt_date([$out[$k]['d'], $out[$k]['m'], $out[$k]['y']]);
        }
        // The date / time / place of marriage are printed over a label row and each variant loses different
        // pieces of them. Read the page once more from the POOLED words and use it to fill what is missing.
        $pool = mte_pool_words($variants);
        $poolYears = [];
        if (count($pool) >= 20) {
            $pf = mte_fields_from_one(mte_extract_one($pool));
            if ($pf) {
                if (empty($out['date_of_marriage']) && !empty($pf['date_of_marriage'])) {
                    $out['date_of_marriage'] = $pf['date_of_marriage'];
                    foreach (array_filter(explode(',', (string) ($pf['_date_years'] ?? ''))) as $yy) $poolYears[] = (int) $yy;
                }
                $vt = (string) ($out['time_of_marriage'] ?? '');
                $pt = (string) ($pf['time_of_marriage'] ?? '');
                if ($pt !== '' && ($vt === '' || (!preg_match('/[AP]M$/', $vt) && preg_match('/[AP]M$/', $pt)))) $out['time_of_marriage'] = $pt;
                if (!empty($pf['place_of_marriage']) && substr_count($pf['place_of_marriage'], ',') > substr_count((string) ($out['place_of_marriage'] ?? ''), ',')) $out['place_of_marriage'] = $pf['place_of_marriage'];
                if (empty($out['solemnizing_officer_name']) && !empty($pf['solemnizing_officer_name'])) $out['solemnizing_officer_name'] = $pf['solemnizing_officer_name'];
            }
        }
        // A damaged digit in the year of marriage ("2046" for "2016") is caught by the people's own data:
        // marriage year = year of birth + age (give or take one). Use it to choose between the candidate years.
        if (!empty($out['date_of_marriage'])) {
            $cand = $poolYears;
            foreach ($per as $f) foreach (array_filter(explode(',', (string) ($f['_date_years'] ?? ''))) as $yy) $cand[] = (int) $yy;
            $cand = array_values(array_unique(array_filter($cand)));
            $expected = [];
            foreach (['husband', 'wife'] as $pp) {
                if (!empty($out["{$pp}_dob"]) && !empty($out["{$pp}_age"])) {
                    $by = (int) substr($out["{$pp}_dob"], -4);
                    $expected[] = $by + (int) $out["{$pp}_age"];
                    $expected[] = $by + (int) $out["{$pp}_age"] + 1;
                }
            }
            $good = array_values(array_intersect($cand, $expected));
            $curY = (int) substr($out['date_of_marriage'], -4);
            if ($good && !in_array($curY, $expected, true)) {
                $out['date_of_marriage'] = substr($out['date_of_marriage'], 0, -4) . $good[0];
            }
        }
        if ($regVotes) { arsort($regVotes); $out['registry_number'] = array_key_first($regVotes); }
        // barangay from the husband's residence, like the text extractor does
        if (function_exists('cert_first_place_token')) {
            $b = cert_first_place_token($out['husband_residence'] ?? '');
            if (trim((string) $b) === '') $b = cert_first_place_token($out['wife_residence'] ?? '');
            if (trim((string) $b) !== '') $out['barangay'] = $b;
        }
        unset($out['_date_years']);
        return $out + $common;
    }
}
