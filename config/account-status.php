<?php
/**
 * GeoGene - Account status helper (Enable / Disable accounts)
 * -----------------------------------------------------------
 * Ang database column `users.status` kay ENUM('pending','active','suspended').
 * Ang "Disabled" nga account gitipigan isip 'suspended' (valid na nga ENUM value,
 * dili na kinahanglan i-usab ang database). Ang gipakita sa screen kanunay "Disabled".
 *
 * Tungod kay pipila ka page kaniadto nagsulat og 'Disable' / 'disable' / 'disabled'
 * (wala sa ENUM, mao nga nahimong blangko ''), ang tanang niini giisip na gihapon
 * nga DISABLED aron wala'y account nga makalusot.
 */

if (!defined('GEOGENE_DB_DISABLED'))        define('GEOGENE_DB_DISABLED', 'disable');
if (!defined('GEOGENE_DISABLED_MESSAGE'))   define('GEOGENE_DISABLED_MESSAGE', 'This account has been disabled. Please contact the administrator.');

/** TRUE kung ang account dili na tugotan nga mo-login. */
function geogene_account_is_disabled($status): bool
{
    $s = strtolower(trim((string) $status));
    return in_array($s, ['disable', 'disabled', 'disable', ''], true);
}

/**
 * TRUE lang kung ang status kay 'active' gyud. Gamiton sa Staff login ug Staff guard
 * (fail-closed): bisan unsa nga laing value (suspended, pending, inactive, blangko, NULL...)
 * dili makasulod. Dili kini mausab ang citizen login nga nagtugot sa 'pending'.
 */
function geogene_account_is_active($status): bool
{
    return strtolower(trim((string) $status)) === 'active';
}

/** Ang text nga makita sa screen. */
function geogene_account_status_label($status): string
{
    if (geogene_account_is_disabled($status)) return 'Disabled';
    return ucfirst(strtolower(trim((string) $status)));   // Active / Pending
}

/** Kolor sa badge: green | orange | red */
function geogene_account_badge_class($status): string
{
    if (geogene_account_is_disabled($status)) return 'red';
    return strtolower(trim((string) $status)) === 'active' ? 'green' : 'orange';
}

/** SQL fragment para sa "disabled" nga mga account (column name gikan sa code, dili sa user input). */
function geogene_sql_disabled(string $column = 'status'): string
{
    return "($column NOT IN ('active','pending') OR $column IS NULL)";
}
