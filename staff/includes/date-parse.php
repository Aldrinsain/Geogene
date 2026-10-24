<?php
/**
 * Strict date reading for scanned / typed dates.
 *
 * Why not strtotime(): it guesses, and the guesses are silent and wrong for civil-registry dates:
 *   "13/04/2021" -> false (the row was saved with NO date and no warning)
 *   "1990"       -> 1990-<today's month>-<today's day> (an invented date)
 *   "03-04-2021" -> April 3, while "03/04/2021" -> March 4 (dash and slash read differently)
 * gg_parse_date_strict() reads a date only when it is really one, in ONE consistent order, and says why when not.
 *
 * Order for all-numeric dates: month/day/year (the review table's placeholder is "mm/dd/yyyy"). When the first number
 * is above 12 it can only be the day (13/04/2021 = April 13), so it is read as day/month/year.
 *
 * Returns ['ok' => bool, 'ymd' => 'YYYY-MM-DD'|null, 'why' => string]
 */
if (!function_exists('gg_parse_date_strict')) {
    function gg_parse_date_strict(string $input): array
    {
        $fail = fn(string $why) => ['ok' => false, 'ymd' => null, 'why' => $why];
        $s = trim(preg_replace('/\s+/', ' ', $input));
        if ($s === '') return $fail('empty');

        $thisYear = (int) date('Y');
        // "8th of March, 1980" -> "8 March 1980"
        $t = preg_replace('/\b(\d{1,2})(st|nd|rd|th)\b/i', '$1', $s);
        $t = preg_replace('/\bof\b/i', ' ', $t);
        $t = trim(preg_replace('/\s+/', ' ', str_replace(',', ' ', $t)));

        $months = ['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'];
        $monthNo = function (string $name) use ($months): ?int {
            $n = strtolower(rtrim($name, '.'));
            if (strlen($n) < 3) return null;
            foreach ($months as $i => $full) {
                if (strpos($full, $n) === 0) return $i + 1; // "Mar", "Sept", "March"
            }
            return null;
        };

        $y = $m = $d = null;
        if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $t, $x)) {            // 2021-03-04
            [$y, $m, $d] = [(int) $x[1], (int) $x[2], (int) $x[3]];
        } elseif (preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{2}|\d{4})$/', $t, $x)) { // 03/04/2021, 3-4-21
            $a = (int) $x[1]; $b = (int) $x[2]; $y = (int) $x[3];
            if (strlen($x[3]) === 2) $y = $y <= $thisYear % 100 ? 2000 + $y : 1900 + $y;
            if ($a <= 12) { $m = $a; $d = $b; }
            elseif ($b <= 12) { $m = $b; $d = $a; }                                         // 13/04/2021 can only be day first
            else return $fail('no month 1-12 in it');
        } elseif (preg_match('/^(\d{1,2}) ([A-Za-z]{3,9})\.? (\d{4})$/', $t, $x)) {        // 08 March 1980
            $m = $monthNo($x[2]); $d = (int) $x[1]; $y = (int) $x[3];
            if (!$m) return $fail('unknown month name');
        } elseif (preg_match('/^([A-Za-z]{3,9})\.? (\d{1,2}) (\d{4})$/', $t, $x)) {        // March 8 1980
            $m = $monthNo($x[1]); $d = (int) $x[2]; $y = (int) $x[3];
            if (!$m) return $fail('unknown month name');
        } else {
            return $fail('not a complete day-month-year date');
        }

        if (!checkdate($m, $d, $y)) return $fail('that day does not exist in that month');
        if ($y < 1800) return $fail('year is too early');
        $ymd = sprintf('%04d-%02d-%02d', $y, $m, $d);
        if ($ymd > date('Y-m-d', strtotime('+1 day'))) return $fail('date is in the future');
        return ['ok' => true, 'ymd' => $ymd, 'why' => ''];
    }
}
