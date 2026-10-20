<?php
/**
 * Shared name helper for the whole GeoGene system
 * (Civil Registry, Family Tree, Map, Records, ID verification).
 *
 * ONE standard everywhere - exactly how a Philippine civil registry certificate
 * prints a name, left to right:
 *
 *        Given Name  Middle Name  Surname        e.g.  "Aldrin Piloton Sain"
 *
 * and ALWAYS in "Mixed Case" (Title Case), no matter if the text was typed or
 * scanned as ALL CAPS, all lowercase, or a mix:  "ALDRIN PILOTON SAIN",
 * "aldrin piloton sain" and "aLdRiN pILoTon SaIn"  ->  "Aldrin Piloton Sain".
 *
 * Storage: first_name / middle_name / last_name stay separate columns (unchanged).
 * The single-text columns (full_name, father_name, mother_name, spouse_name,
 * wife_father_name, wife_mother_name) are written in the same
 * "Given Middle Surname" order.
 *
 * Older records were saved as "SURNAME, GIVEN MIDDLE" (or ALL CAPS). Those still
 * work: every function here that READS a name understands both orders, and
 * gg_name_key() ignores order and case, so a parent saved the old way is still
 * linked to the right person in the Family Tree and on the Map.
 */

if (!function_exists('gg_title_case')) {
    /**
     * "Mixed Case": first letter of every word capital, the rest small.
     * Handles hyphens, apostrophes (O'Brien), periods (Jr.) and roman numerals (III).
     */
    function gg_title_case(?string $s): string
    {
        $s = trim(preg_replace('/\s+/u', ' ', (string) $s));
        if ($s === '') return '';
        $s = mb_strtolower($s, 'UTF-8');
        $s = preg_replace_callback(
            '/(^|[\s\-\.\'’\(\/])(\p{L})/u',
            fn($m) => $m[1] . mb_strtoupper($m[2], 'UTF-8'),
            $s
        );
        // roman-numeral suffixes stay in capitals (Ii -> II, Iii -> III, Iv -> IV)
        $s = preg_replace_callback('/\b(Ii|Iii|Iv)\b/u', fn($m) => strtoupper($m[1]), $s);
        return $s;
    }
}

if (!function_exists('gg_display_name')) {
    /**
     * Any stored/typed name -> "Given Middle Surname" in Mixed Case.
     * Accepts the old "SURNAME, GIVEN MIDDLE" convention too.
     */
    function gg_display_name(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') return '';
        if (strpos($name, ',') !== false) {
            [$last, $given] = array_map('trim', explode(',', $name, 2));
            $name = trim($given . ' ' . $last);
        }
        return gg_title_case($name);
    }
}

if (!function_exists('gg_name_key')) {
    /**
     * Order- and case-insensitive identity key for matching the SAME person:
     * "Abao, Xander Sarzosa", "XANDER SARZOSA ABAO" and "Xander Sarzosa Abao"
     * all give the same key. Letters only; Ñ/accents folded to plain letters.
     */
    function gg_name_key(?string $name): string
    {
        $s = (string) $name;
        if ($s === '') return '';
        if (function_exists('transliterator_transliterate')) {
            $t = @transliterator_transliterate('Any-Latin; Latin-ASCII', $s);
            if (is_string($t) && $t !== '') $s = $t;
        } else {
            $s = strtr($s, ['Ñ' => 'N', 'ñ' => 'n']);
        }
        $s = strtoupper($s);
        $s = preg_replace('/[^A-Z\s]/', ' ', $s);
        $tokens = preg_split('/\s+/', trim($s), -1, PREG_SPLIT_NO_EMPTY);
        sort($tokens, SORT_STRING);
        return implode(' ', $tokens);
    }
}

if (!function_exists('gg_name_search_match')) {
    /**
     * Search match, any order, any case, partial words allowed:
     * "xander abao" and "ABAO XAN" both find "Xander Sarzosa Abao".
     */
    function gg_name_search_match(?string $nameText, ?string $query): bool
    {
        $hay = gg_name_key($nameText);
        $q = gg_name_key($query);
        if ($q === '' || $hay === '') return false;
        foreach (explode(' ', $q) as $tok) {
            if (strpos($hay, $tok) === false) return false;
        }
        return true;
    }
}

if (!function_exists('gg_canonical_barangay')) {
    /**
     * Barangay typed or scanned in ANY case ("DICKLUM", "dicklum") -> the exact
     * spelling used in the barangays table ("Dicklum"), so the Map always
     * finds it. Unknown barangays are just Mixed-Cased.
     */
    function gg_canonical_barangay(?PDO $pdo, ?string $barangay): ?string
    {
        $b = trim(preg_replace('/\s+/u', ' ', (string) $barangay));
        if ($b === '') return null;
        static $cache = null;
        if ($cache === null) {
            $cache = [];
            if ($pdo) {
                try {
                    foreach ($pdo->query("SELECT name FROM barangays") as $row) {
                        $cache[mb_strtolower(trim($row['name']), 'UTF-8')] = $row['name'];
                    }
                } catch (Throwable $e) { /* no barangays table: just Mixed-Case below */ }
            }
        }
        $k = mb_strtolower($b, 'UTF-8');
        return $cache[$k] ?? gg_title_case($b);
    }
}

if (!function_exists('formatFullName')) {
    /**
     * Display / storage name:  "Given Name Middle Name Surname"  (Mixed Case).
     * NOTE: parameter order is unchanged (last, first, middle) so every existing
     * caller keeps working - only the OUTPUT order changed.
     * Suffix (Jr., Sr., III) is intentionally NOT appended - it stays its own field.
     */
    function formatFullName(?string $last, ?string $first, ?string $middle = '', ?string $suffix = ''): string
    {
        return formatGivenMiddleSurname($first, $middle, $last);
    }
}

if (!function_exists('formatGivenMiddleSurname')) {
    /**
     * "Given Name Middle Name Surname" in Mixed Case, no comma - e.g.
     * "Aldrin Piloton Sain" - the way a certificate prints it.
     */
    function formatGivenMiddleSurname(?string $first, ?string $middle, ?string $last): string
    {
        $parts = array_filter(
            [trim((string) $first), trim((string) $middle), trim((string) $last)],
            fn($p) => $p !== ''
        );
        return gg_title_case(implode(' ', $parts));
    }
}

if (!function_exists('parseGivenMiddleSurname')) {
    /**
     * Splits ONE typed/scanned name ("ALDRIN PILOTON SAIN") into
     * [first, middle, last] - already in Mixed Case - for the separate
     * first_name / middle_name / last_name columns.
     *
     * Convention (same as a certificate): FIRST word = given name, LAST word =
     * surname (with prefixes like Dela / De La / Delos / San), anything in between
     * = middle name. A comma means the old
     * "Surname, Given Middle" order and is honoured, so both work.
     *
     * @return array{0:string,1:string,2:string} [first, middle, last]
     */
    function parseGivenMiddleSurname(string $name): array
    {
        $name = trim(str_replace('/', ' ', $name));      // "Aldrin/Piloton/Sain" works too
        if ($name === '') return ['', '', ''];

        if (strpos($name, ',') !== false) {                // "Sain, Aldrin Piloton"
            [$last, $given] = array_map('trim', explode(',', $name, 2));
            $gw = array_values(array_filter(preg_split('/\s+/', $given), fn($w) => $w !== ''));
            return [
                gg_title_case($gw[0] ?? ''),
                gg_title_case(implode(' ', array_slice($gw, 1))),
                gg_title_case($last),
            ];
        }

        $words = array_values(array_filter(preg_split('/\s+/', $name), fn($w) => $w !== ''));
        $count = count($words);
        if ($count === 1) return [gg_title_case($words[0]), '', ''];
        if ($count === 2) return [gg_title_case($words[0]), '', gg_title_case($words[1])];

        // Surname = the LAST word, plus any surname prefix right before it, so
        // "Juan Pedro Dela Cruz" -> surname "Dela Cruz" (not "Cruz"), and
        // "Maria De La Cruz", "Jose Delos Santos", "Ana San Juan" work too.
        // The first word is always the given name, never part of the surname.
        static $prefixes = ['de', 'del', 'dela', 'delas', 'delos', 'la', 'las', 'los', 'san', 'santa', 'santo', 'sta', 'sto', 'st', 'van', 'von', 'da', 'di', 'mac', 'mc'];
        $lastStart = $count - 1;
        while ($lastStart - 1 >= 1 && in_array(strtolower(rtrim($words[$lastStart - 1], '.')), $prefixes, true)) {
            $lastStart--;
        }
        return [
            gg_title_case($words[0]),
            gg_title_case(implode(' ', array_slice($words, 1, max(0, $lastStart - 1)))),
            gg_title_case(implode(' ', array_slice($words, $lastStart))),
        ];
    }
}

if (!function_exists('splitCanonicalFullName')) {
    /**
     * Reads a STORED name in either order ("Sain, Jean Piloton" - old, or
     * "Jean Piloton Sain" - new) into [first, middle, last], Mixed Case.
     */
    function splitCanonicalFullName(string $name): array
    {
        return parseGivenMiddleSurname($name);
    }
}

if (!function_exists('gg_clean_person_name')) {
    /**
     * Free-text person name field (father / mother / spouse ...) -> saved as
     * "Given Middle Surname", Mixed Case. Returns null when empty.
     */
    function gg_clean_person_name(?string $name): ?string
    {
        $n = gg_display_name($name);
        return $n === '' ? null : $n;
    }
}
