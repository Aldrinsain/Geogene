<?php
/**
 * Helpers for civil-registry-bulk-save.php (kept in their own file so they can be tested without a database).
 */
require_once __DIR__ . '/name-format.php';

if (!function_exists('gg_bulk_letters')) {
    /** Letters only, lowercase: "Ma. Luisa  Reyes" and "ma luisa reyes" compare equal. */
    function gg_bulk_letters(string $s): string
    {
        return mb_strtolower(preg_replace('/[^\p{L}]/u', '', $s), 'UTF-8');
    }

    /**
     * A person's name as First / Middle / Last.
     *
     * The scan already READ the three parts separately, but the review table shows (and sends) one text
     * "First Middle Last", and parseGivenMiddleSurname() then GUESSES the split again from word positions - which
     * is wrong for two-word given names ("Mary Grace | Lopez | Cruz" became "Mary | Grace Lopez | Cruz").
     *
     * So: if the three scanned parts, put together, still say exactly what the review table cell says (nobody
     * edited it), the scanned split is used. If staff changed the name, their text is parsed as before.
     *
     * $cert        = the row's certificate_data (scan result) as an array
     * $keys        = [firstKey, middleKey, lastKey] in that array, e.g. ['child_first','child_middle','child_last']
     * $typedName   = the text in the review table
     * returns [first, middle, last]
     */
    function gg_bulk_person_parts(array $cert, ?array $keys, string $typedName): array
    {
        $typedName = trim($typedName);
        if ($keys) {
            $f = trim((string) ($cert[$keys[0]] ?? ''));
            $m = trim((string) ($cert[$keys[1]] ?? ''));
            $l = trim((string) ($cert[$keys[2]] ?? ''));
            if ($f !== '' && $l !== '' && gg_bulk_letters("$f $m $l") === gg_bulk_letters($typedName)) {
                $t = function_exists('gg_title_case') ? 'gg_title_case' : fn($x) => mb_convert_case(mb_strtolower($x, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
                return [$t($f), $m !== '' ? $t($m) : '', $t($l)];
            }
        }
        return parseGivenMiddleSurname($typedName);
    }

    /** Which certificate_data keys hold the main person's / the wife's name parts, per certificate type. */
    function gg_bulk_person_keys(string $type): ?array
    {
        return [
            'birth'    => ['child_first', 'child_middle', 'child_last'],
            'marriage' => ['husband_first', 'husband_middle', 'husband_last'],
            'death'    => ['first_name', 'middle_name', 'last_name'],
        ][$type] ?? null;
    }
}
