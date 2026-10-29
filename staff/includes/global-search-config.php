<?php
/**
 * GeoGene - what the top search bar can jump to.
 * Used by BOTH portals: gg_search_script('staff') in staff-header.php and
 * gg_search_script('admin') in admin-header.php. Links are relative to the portal's own folder.
 *
 *   t = title shown      u = link       d = short description
 *   k = extra keywords people may type (synonyms)      g = tag       i = icon
 *   top = 1 -> listed as a "Quick link" when the box is empty
 * To add a new place, just add one line to the list for that portal.
 */

if (!function_exists('gg_search_script')) {
    function gg_search_items(string $portal): array
    {
        if ($portal === 'admin') {
            return [
                ['t' => 'Dashboard',                'u' => 'dashboard.php',                 'd' => 'System overview',                           'k' => 'home overview statistics summary stats charts', 'g' => 'Page', 'i' => '🏠', 'top' => 1],
                ['t' => 'Staff Management',         'u' => 'staff-management.php',          'd' => 'Add, view and manage Civil Registry Staff', 'k' => 'staff employees accounts enable disable personnel', 'g' => 'Page', 'i' => '👥', 'top' => 1],
                ['t' => 'Add Staff Account',        'u' => 'staff-management.php?add=1',    'd' => 'Create a new staff login',                  'k' => 'new create register staff password', 'g' => 'Action', 'i' => '➕'],
                ['t' => 'User Monitoring',          'u' => 'user-monitoring.php',           'd' => 'Citizen accounts and their status',         'k' => 'users citizens accounts verify identity', 'g' => 'Page', 'i' => '🛡️', 'top' => 1],
                ['t' => 'Disabled Users',           'u' => 'user-monitoring.php?status=disabled', 'd' => 'Citizen accounts that were disabled', 'k' => 'suspended blocked inactive', 'g' => 'Filter', 'i' => '🚫'],
                ['t' => 'Pending Users',            'u' => 'user-monitoring.php?status=pending',  'd' => 'Citizen accounts not yet active',    'k' => 'waiting verification unverified', 'g' => 'Filter', 'i' => '⏳'],
                ['t' => 'Civil Registry Records',   'u' => 'civil-registry.php',            'd' => 'Birth, marriage and death records',         'k' => 'records registry certificates mcro list', 'g' => 'Page', 'i' => '📑', 'top' => 1],
                ['t' => 'Birth Records',            'u' => 'civil-registry.php?type=birth',    'd' => 'Certificates of Live Birth',    'k' => 'born child baby', 'g' => 'Records', 'i' => '👶'],
                ['t' => 'Marriage Records',         'u' => 'civil-registry.php?type=marriage', 'd' => 'Certificates of Marriage',      'k' => 'married husband wife spouse wedding', 'g' => 'Records', 'i' => '💍'],
                ['t' => 'Death Records',            'u' => 'civil-registry.php?type=death',    'd' => 'Certificates of Death',         'k' => 'died deceased patay', 'g' => 'Records', 'i' => '🕊️'],
                ['t' => 'Family Tree',              'u' => 'family-tree.php',               'd' => 'View or generate a family lineage',         'k' => 'genealogy lineage relatives ancestors parents children pamilya', 'g' => 'Page', 'i' => '🌳', 'top' => 1],
                ['t' => 'Geographic Map',           'u' => 'map.php',                       'd' => 'Where families live, by barangay',          'k' => 'location barangay mapa markers places', 'g' => 'Page', 'i' => '🗺️', 'top' => 1],
                ['t' => 'Reports',                  'u' => 'reports.php',                   'd' => 'Summaries and activity reports',            'k' => 'report logs summary statistics export', 'g' => 'Page', 'i' => '📊', 'top' => 1],
                ['t' => 'Staff Activity Logs',      'u' => 'reports-staff-logs.php',        'd' => 'What staff did in the system',              'k' => 'audit history log activity staff', 'g' => 'Reports', 'i' => '📝'],
                ['t' => 'User Activity Logs',       'u' => 'reports-user-logs.php',         'd' => 'Citizen accounts and their activity',       'k' => 'audit history log activity users citizens', 'g' => 'Reports', 'i' => '📝'],
                ['t' => 'My Profile',               'u' => 'profile.php',                   'd' => 'Your account details',                      'k' => 'account me admin photo', 'g' => 'Page', 'i' => '👤'],
                ['t' => 'Edit Profile',             'u' => 'edit-profile.php',              'd' => 'Change your name, email or photo',          'k' => 'update change picture avatar', 'g' => 'Action', 'i' => '✏️'],
                ['t' => 'Logout',                   'u' => 'logout.php',                    'd' => 'Sign out of GeoGene',                       'k' => 'sign out exit', 'g' => 'Action', 'i' => '🚪'],
            ];
        }
        return [
            ['t' => 'Dashboard',                  'u' => 'dashboard.php',                'd' => 'Overview and statistics',                     'k' => 'home overview statistics summary stats', 'g' => 'Page', 'i' => '🏠', 'top' => 1],
            ['t' => 'Civil Registry Records',     'u' => 'civil-registry.php',           'd' => 'Birth, marriage and death records',           'k' => 'records registry certificates mcro list search', 'g' => 'Page', 'i' => '📑', 'top' => 1],
            ['t' => 'Birth Records',              'u' => 'civil-registry.php?type=birth',    'd' => 'Certificates of Live Birth',      'k' => 'born child baby', 'g' => 'Records', 'i' => '👶'],
            ['t' => 'Marriage Records',           'u' => 'civil-registry.php?type=marriage', 'd' => 'Certificates of Marriage',        'k' => 'married husband wife spouse wedding', 'g' => 'Records', 'i' => '💍'],
            ['t' => 'Death Records',              'u' => 'civil-registry.php?type=death',    'd' => 'Certificates of Death',           'k' => 'died deceased patay', 'g' => 'Records', 'i' => '🕊️'],
            ['t' => 'Add Civil Registry Record',  'u' => 'civil-registry-form.php',      'd' => 'Encode a new birth, marriage or death record', 'k' => 'new create encode register add', 'g' => 'Action', 'i' => '➕', 'top' => 1],
            ['t' => 'Add Birth Record',           'u' => 'civil-registry-form.php?type=birth',    'd' => 'New Certificate of Live Birth',  'k' => 'new born child baby add', 'g' => 'Action', 'i' => '➕'],
            ['t' => 'Add Marriage Record',        'u' => 'civil-registry-form.php?type=marriage', 'd' => 'New Certificate of Marriage', 'k' => 'new married husband wife add', 'g' => 'Action', 'i' => '➕'],
            ['t' => 'Add Death Record',           'u' => 'civil-registry-form.php?type=death',    'd' => 'New Certificate of Death',    'k' => 'new died deceased add', 'g' => 'Action', 'i' => '➕'],
            ['t' => 'Scan a Certificate',         'u' => 'civil-registry-scan.php',      'd' => 'Read a certificate photo (OCR)',              'k' => 'ocr scan upload photo camera picture', 'g' => 'Action', 'i' => '📷'],
            ['t' => 'Bulk Scan Certificates',     'u' => 'civil-registry-bulk-scan.php', 'd' => 'Scan many certificates at once',              'k' => 'ocr scan many multiple batch upload photos', 'g' => 'Action', 'i' => '🗂️'],
            ['t' => 'Import Records from Excel',  'u' => 'civil-registry-import.php',    'd' => 'Upload a spreadsheet of records',             'k' => 'excel xlsx spreadsheet upload import template', 'g' => 'Action', 'i' => '📥'],
            ['t' => 'All Records',                'u' => 'all-records-saved.php',        'd' => 'Everything that has been saved',              'k' => 'saved list everything history', 'g' => 'Page', 'i' => '🗃️', 'top' => 1],
            ['t' => 'Verification Monitoring',    'u' => 'verification-monitoring.php',  'd' => 'Check and approve citizen identity verification', 'k' => 'verify identity id approve reject pending review', 'g' => 'Page', 'i' => '🛡️', 'top' => 1],
            ['t' => 'User Management',            'u' => 'user-management.php',          'd' => 'Citizen accounts: search, filter, enable/disable', 'k' => 'users citizens accounts enable disable filter', 'g' => 'Page', 'i' => '👥', 'top' => 1],
            ['t' => 'Disabled Users',             'u' => 'user-management.php?status=disabled', 'd' => 'Accounts that were disabled',          'k' => 'suspended blocked inactive', 'g' => 'Filter', 'i' => '🚫'],
            ['t' => 'Pending Users',              'u' => 'user-management.php?status=pending',  'd' => 'Accounts not yet active',              'k' => 'waiting unverified', 'g' => 'Filter', 'i' => '⏳'],
            ['t' => 'Generate Family Tree',       'u' => 'family-tree.php',              'd' => 'View or generate a family lineage',           'k' => 'family tree genealogy lineage relatives ancestors parents children pamilya', 'g' => 'Page', 'i' => '🌳', 'top' => 1],
            ['t' => 'Geographic Map',             'u' => 'map.php',                      'd' => 'Where families live, by barangay',            'k' => 'location barangay mapa markers places', 'g' => 'Page', 'i' => '🗺️', 'top' => 1],
            ['t' => 'My Profile',                 'u' => 'profile.php',                  'd' => 'Your account details',                        'k' => 'account me staff photo', 'g' => 'Page', 'i' => '👤'],
            ['t' => 'Edit Profile',               'u' => 'edit-profile.php',             'd' => 'Change your name, email or photo',            'k' => 'update change picture avatar password', 'g' => 'Action', 'i' => '✏️'],
            ['t' => 'Logout',                     'u' => 'logout.php',                   'd' => 'Sign out of GeoGene',                         'k' => 'sign out exit', 'g' => 'Action', 'i' => '🚪'],
        ];
    }

    /** "Search for <what was typed>" shortcuts (the typed text is appended to the link). */
    function gg_search_actions(string $portal): array
    {
        if ($portal === 'admin') {
            return [
                ['t' => 'Find “{q}” in Civil Registry Records', 'd' => 'Name, registry no., barangay...', 'u' => 'civil-registry.php?q=', 'i' => '📑'],
                ['t' => 'Find “{q}” in Family Tree',            'd' => 'Open that person’s family tree', 'u' => 'family-tree.php?q=',    'i' => '🌳'],
                ['t' => 'Locate “{q}” on the Map',               'd' => 'Show where they live',            'u' => 'map.php?q=',            'i' => '🗺️'],
                ['t' => 'Find “{q}” in User Monitoring',        'd' => 'Citizen accounts',                'u' => 'user-monitoring.php?q=', 'i' => '🛡️'],
                ['t' => 'Find “{q}” in Staff Management',       'd' => 'Staff accounts',                  'u' => 'staff-management.php?q=', 'i' => '👥'],
            ];
        }
        return [
            ['t' => 'Find “{q}” in Civil Registry Records', 'd' => 'Name, registry no., barangay...', 'u' => 'civil-registry.php?q=', 'i' => '📑'],
            ['t' => 'Find “{q}” in Family Tree',            'd' => 'Open that person’s family tree', 'u' => 'family-tree.php?q=',    'i' => '🌳'],
            ['t' => 'Locate “{q}” on the Map',               'd' => 'Show where they live',            'u' => 'map.php?q=',            'i' => '🗺️'],
            ['t' => 'Find “{q}” in User Management',        'd' => 'Citizen accounts',                'u' => 'user-management.php?q=', 'i' => '👥'],
            ['t' => 'Find “{q}” in Verification Monitoring', 'd' => 'Identity verification requests', 'u' => 'verification-monitoring.php?q=', 'i' => '🛡️'],
        ];
    }

    /** The <script> tag with this portal's search data (the behaviour is in assets/js/global-search.js). */
    function gg_search_script(string $portal): string
    {
        $data = [
            'placeholder' => 'Search...',
            'items'       => gg_search_items($portal),
            'actions'     => gg_search_actions($portal),
        ];
        return '<script>window.GG_SEARCH = ' . json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . ';</script>';
    }
}
