<?php
require_once __DIR__ . '/includes/staff-auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/includes/name-format.php';

// Never let the browser (or any proxy) cache a stale copy of this page -
// genealogy data changes, and this page is also loaded inside an iframe
// from the Admin portal, where stale caching has been observed.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

/**
 * Normalizes a name for matching. Every relative lookup on this page goes
 * through this, so it ignores CASE and ORDER:
 * "Abao, Xander Sarzosa" (older records), "XANDER SARZOSA ABAO" and
 * "Xander Sarzosa Abao" all become the same key and match the same person.
 */
function ft_normalize(string $s): string
{
    return gg_name_key($s);
}

function ft_full_name(array $r): string
{
    // Always build the display name fresh from first/middle/last (never
    // folding in Suffix, and never trusting a possibly-stale stored
    // full_name value) - falls back to the stored full_name only for
    // virtual/text-only nodes (e.g. an unregistered parent's name typed
    // on someone else's record) that have no first/middle/last at all.
    // Shown as "Given Middle Surname" in Mixed Case (formatFullName does both).
    $fromParts = formatFullName($r['last_name'] ?? '', $r['first_name'] ?? '', $r['middle_name'] ?? '');
    if ($fromParts !== '') return $fromParts;
    return gg_display_name($r['full_name'] ?? '');   // text-only relative typed as "SURNAME, GIVEN" or ALL CAPS
}

// ---------- Load ALL civil registry records once (small dataset, simplest + fastest) ----------
$allRecords = $pdo->query("SELECT * FROM civil_registry_records")->fetchAll();

$nameIndex = []; // normalized full name -> record (first match wins)
foreach ($allRecords as $r) {
    $key = ft_normalize(ft_full_name($r));
    if ($key !== '' && !isset($nameIndex[$key])) {
        $nameIndex[$key] = $r;
    }
}

function ft_find_by_name(array $nameIndex, ?string $name)
{
    if (!$name) return null;
    $key = ft_normalize($name);
    return $nameIndex[$key] ?? null;
}

// ---------- Search ----------
$search = trim($_GET['q'] ?? '');
$searchResults = [];
if ($search !== '') {
    $searchNorm = ft_normalize($search);
    foreach ($allRecords as $r) {
        // any order, any case, partial words: "abao xan" / "Xander Sarzosa Abao" / "Abao, Xander"
        if ($searchNorm !== '' && gg_name_search_match(ft_full_name($r), $search)) {
            $searchResults[] = $r;
            if (count($searchResults) >= 8) break;
        }
    }
}

// ---------- Build the family graph for the selected person ----------
$selfRecord = null;
$tree = null;

if (isset($_GET['record_id'])) {
    $rid = (int) $_GET['record_id'];
    foreach ($allRecords as $r) {
        if ((int) $r['record_id'] === $rid) { $selfRecord = $r; break; }
    }
}

// Straight-to-tree: searching a full name that resolves to exactly ONE
// person (or that matches someone's full name exactly) opens that
// person's family tree immediately - no extra click on a result chip.
if (!$selfRecord && $search !== '' && $searchResults) {
    $exact = ft_find_by_name($nameIndex, $search);
    if ($exact) {
        $selfRecord = $exact;
    } elseif (count($searchResults) === 1) {
        $selfRecord = $searchResults[0];
    }
}

// ---------- Navigation trail (para sa Back arrow) ----------
// Ang "trail" mao ang listahan sa record_id sa mga tree nga gibisita na, gikan sa
// gi-search nga tawo (una) paingon sa sunod-sunod nga relative nga gi-click.
// Gidala kini sa URL (?trail=1,5,9) busa sakto ra bisan i-refresh, naay duha ka
// tab, o naka-embed sa admin iframe. Ang search sa bag-ong tawo walay trail,
// mao nga mao na kana ang bag-ong root ug walay Back arrow.
$ftTrail = [];
$ftNextTrail = '';
if ($selfRecord) {
    $validIds = [];
    foreach ($allRecords as $r) $validIds[(int) $r['record_id']] = true;
    $selfIdForTrail = (int) $selfRecord['record_id'];
    foreach (explode(',', (string) ($_GET['trail'] ?? '')) as $part) {
        $tid = (int) trim($part);
        if ($tid <= 0 || !isset($validIds[$tid])) continue;       // dili valid nga id, i-skip
        if ($ftTrail && end($ftTrail) === $tid) continue;          // dili doble-doble sunod-sunod
        $ftTrail[] = $tid;
    }
    // Kung ang kataposang trail entry kay mao gihapon ang karon nga tawo, tangtangon
    while ($ftTrail && end($ftTrail) === $selfIdForTrail) array_pop($ftTrail);
    $ftTrail = array_slice($ftTrail, -40);                        // limit para dili mugwang ang URL
    // Trail nga gamiton sa mga link padulong sa sunod nga relative = karon nga trail + karon nga tawo
    $ftNextTrail = implode(',', array_merge($ftTrail, [$selfIdForTrail]));
}

if ($selfRecord) {
    $selfName = ft_normalize(ft_full_name($selfRecord));

    // Text-only fallback: if the Father's/Mother's Name text on file doesn't
    // match anyone's OWN civil registry record, still show a card with that
    // name instead of hiding the relationship entirely - this is also what
    // lets the connector lines explain a shared sibling group even when
    // neither parent is separately registered.
    $ft_virtual = function (?string $nameText) {
        $nameText = trim((string) $nameText);
        if ($nameText === '') return null;
        return ['record_id' => null, 'full_name' => $nameText, 'virtual' => true];
    };

    $father = ft_find_by_name($nameIndex, $selfRecord['father_name'] ?? null) ?? $ft_virtual($selfRecord['father_name'] ?? null);
    $mother = ft_find_by_name($nameIndex, $selfRecord['mother_name'] ?? null) ?? $ft_virtual($selfRecord['mother_name'] ?? null);
    $spouse = ft_find_by_name($nameIndex, $selfRecord['spouse_name'] ?? null) ?? $ft_virtual($selfRecord['spouse_name'] ?? null);

    // ---------- All marriages (current AND past/ex), for ANY person ----------
    // A marriage-type record represents one union: its own name is one
    // party, spouse_name is the other. Someone can appear in more than one
    // marriage record (remarriage) - every one of those unions should stay
    // visible and connected on the tree, not just the latest. The most
    // recent (by date_of_event) is labeled "Spouse"; any earlier ones are
    // labeled "Ex-Spouse" - none of them are dropped.
    //
    // Generalized into a closure (rather than hardcoded to $selfRecord) so
    // it can also find FATHER's or MOTHER's OWN other partners - e.g. a
    // father who has since separated from the searched person's mother and
    // remarried someone else. That other partner, her own parents, and any
    // children from that union show up as a "Previous Partner" branch
    // flanking the main Father+Mother couple (see TIER 1/TIER 2 below).
    $findMarriagesOf = function (?array $personRecord) use ($allRecords, $nameIndex, $ft_virtual) {
        if (!$personRecord || !empty($personRecord['virtual'])) return [];
        $personNorm = ft_normalize(ft_full_name($personRecord));
        if ($personNorm === '') return [];

        $marriages = [];
        foreach ($allRecords as $r) {
            if (($r['registry_type'] ?? '') !== 'marriage') continue;
            $rFullNorm = ft_normalize(ft_full_name($r));
            $rSpouseNorm = ft_normalize($r['spouse_name'] ?? '');
            $partnerName = null;
            if ($rFullNorm === $personNorm) {
                $partnerName = $r['spouse_name'] ?? null;
            } elseif ($rSpouseNorm !== '' && $rSpouseNorm === $personNorm) {
                $partnerName = ft_full_name($r);
            }
            if ($partnerName === null || trim($partnerName) === '') continue;
            $partnerRecord = ft_find_by_name($nameIndex, $partnerName) ?? $ft_virtual($partnerName);
            if (!$partnerRecord) continue;
            $marriages[] = ['record' => $partnerRecord, 'date' => $r['date_of_event'] ?? null];
        }
        // Fallback: a spouse_name typed directly on this record (e.g. on a
        // birth record) that isn't backed by its own marriage record.
        $directSpouseName = $personRecord['spouse_name'] ?? null;
        if (!$marriages && $directSpouseName) {
            $directSpouse = ft_find_by_name($nameIndex, $directSpouseName) ?? $ft_virtual($directSpouseName);
            if ($directSpouse) $marriages[] = ['record' => $directSpouse, 'date' => null];
        }
        // De-duplicate by partner name.
        $seenPartner = [];
        $out = [];
        foreach ($marriages as $m) {
            $key = ft_normalize(ft_full_name($m['record']));
            if ($key === '' || isset($seenPartner[$key])) continue;
            $seenPartner[$key] = true;
            $out[] = $m;
        }
        // Most recent marriage first (nulls/unknown dates sort last).
        usort($out, function ($a, $b) {
            if ($a['date'] === $b['date']) return 0;
            if (!$a['date']) return 1;
            if (!$b['date']) return -1;
            return strcmp($b['date'], $a['date']);
        });
        foreach ($out as $i => &$m) { $m['role'] = $i === 0 ? 'Spouse' : 'Ex-Spouse'; }
        unset($m);
        return $out;
    };

    $allSpouses = $findMarriagesOf($selfRecord);
    if ($allSpouses) $spouse = $allSpouses[0]['record']; // keep single-spouse var in sync for anything still using it

    // Siblings: other records sharing BOTH parents (only when both are on file)
    $siblings = [];
    $selfFatherNorm = ft_normalize($selfRecord['father_name'] ?? '');
    $selfMotherNorm = ft_normalize($selfRecord['mother_name'] ?? '');
    if ($selfFatherNorm && $selfMotherNorm) {
        foreach ($allRecords as $r) {
            if ((int) $r['record_id'] === (int) $selfRecord['record_id']) continue;
            if (ft_normalize($r['father_name'] ?? '') === $selfFatherNorm && ft_normalize($r['mother_name'] ?? '') === $selfMotherNorm) {
                $siblings[] = $r;
            }
        }
    }

    // Children: other records that list self as father or mother
    $children = [];
    foreach ($allRecords as $r) {
        if ((int) $r['record_id'] === (int) $selfRecord['record_id']) continue;
        if (ft_normalize($r['father_name'] ?? '') === $selfName || ft_normalize($r['mother_name'] ?? '') === $selfName) {
            $children[] = $r;
        }
    }

    // Helper: given a parent's own record, find grandparents + that parent's siblings (aunts/uncles)
    $sideOf = function (?array $parentRecord) use ($allRecords, $nameIndex, $ft_virtual) {
        if (!$parentRecord || !empty($parentRecord['virtual'])) {
            // A virtual (text-only) parent has no father_name/mother_name data
            // of their own to look further back with.
            return ['grandfather' => null, 'grandmother' => null, 'siblings' => []];
        }

        $gf = ft_find_by_name($nameIndex, $parentRecord['father_name'] ?? null) ?? $ft_virtual($parentRecord['father_name'] ?? null);
        $gm = ft_find_by_name($nameIndex, $parentRecord['mother_name'] ?? null) ?? $ft_virtual($parentRecord['mother_name'] ?? null);

        $pFatherNorm = ft_normalize($parentRecord['father_name'] ?? '');
        $pMotherNorm = ft_normalize($parentRecord['mother_name'] ?? '');
        $pNorm = ft_normalize(ft_full_name($parentRecord));

        $sibs = [];
        if ($pFatherNorm && $pMotherNorm) {
            foreach ($allRecords as $r) {
                if (ft_normalize(ft_full_name($r)) === $pNorm) continue;
                if (ft_normalize($r['father_name'] ?? '') === $pFatherNorm && ft_normalize($r['mother_name'] ?? '') === $pMotherNorm) {
                    $sibs[] = $r;
                }
            }
        }

        return ['grandfather' => $gf, 'grandmother' => $gm, 'siblings' => $sibs];
    };

    $paternal = $sideOf($father);
    $maternal = $sideOf($mother);

    // ---------- Father's / Mother's OWN other partner(s) ("Previous Partner") ----------
    // Besides each other, Father or Mother may each have had a child with
    // someone else before. Whether that other person is shown on the tree
    // at all depends on whether there is actual DOCUMENTATION of a real
    // union with them, not just the shared child:
    //
    //   - a MARRIAGE record with Father/Mother, OR
    //   - a DEATH record on file for them (the common real case: that
    //     earlier partner passed away, and Father/Mother later remarried -
    //     Mother/Father, shown as usual in the main couple)
    //
    // Either one of those means this was a recognized union, so that
    // person gets shown as a "Previous Partner" card with her/his own
    // parents and the shared children.
    //
    // With NEITHER on file, there is nothing documenting an actual
    // relationship - just a shared child - which this tree treats as a
    // child born outside of marriage rather than a union to represent.
    // That other person is therefore NOT added to the tree at all (no
    // card, no parents, no line to them) - only the CHILD still appears,
    // directly under Father/Mother the same way any other child would.
    $findOtherUnions = function (?array $parentRecord, ?array $excludeRecord) use ($findMarriagesOf, $sideOf, $allRecords, $nameIndex, $ft_virtual) {
        $result = ['shown' => [], 'unshownChildren' => []];
        if (!$parentRecord || !empty($parentRecord['virtual'])) return $result;
        $excludeNorm = $excludeRecord ? ft_normalize(ft_full_name($excludeRecord)) : null;
        $parentNorm = ft_normalize(ft_full_name($parentRecord));
        if ($parentNorm === '') return $result;

        // Every OTHER parent name tied to Father/Mother through a shared
        // child, collected straight from the birth/marriage/death records
        // themselves (not just marriage records) - this is what lets a
        // deceased partner with no marriage record on file still be found,
        // purely from the child's own Father's/Mother's Name fields.
        $others = []; // normalized name => ['name' => raw text, 'children' => [...]]
        foreach ($allRecords as $r) {
            if (($r['registry_type'] ?? 'birth') !== 'birth') continue;
            $fn = ft_normalize($r['father_name'] ?? '');
            $mn = ft_normalize($r['mother_name'] ?? '');
            $otherNameRaw = null;
            if ($fn === $parentNorm) $otherNameRaw = $r['mother_name'] ?? '';
            elseif ($mn === $parentNorm) $otherNameRaw = $r['father_name'] ?? '';
            if ($otherNameRaw === null) continue;
            $otherNorm = ft_normalize($otherNameRaw);
            if ($otherNorm === '' || $otherNorm === $excludeNorm) continue;
            if (!isset($others[$otherNorm])) $others[$otherNorm] = ['name' => $otherNameRaw, 'children' => []];
            $others[$otherNorm]['children'][] = $r;
        }
        // Also fold in anyone found only through a marriage record (covers
        // a documented marriage where no shared child is on file).
        $marriagesOfParent = $findMarriagesOf($parentRecord);
        foreach ($marriagesOfParent as $m) {
            $mk = ft_normalize(ft_full_name($m['record']));
            if ($mk === '' || $mk === $excludeNorm) continue;
            if (!isset($others[$mk])) $others[$mk] = ['name' => ft_full_name($m['record']), 'children' => []];
        }

        // For an UNDOCUMENTED union (no marriage, no death record - just a
        // shared child), that child only counts as family here if Father/
        // Mother actually acknowledged them BY NAME - i.e. the child
        // carries Father's/Mother's own surname. No surname match means no
        // acknowledgment on file either, so the child is left out too; the
        // other parent is never shown as a person either way.
        $parentSurnameNorm = ft_normalize($parentRecord['last_name'] ?? '');
        $addIfAcknowledged = function (array $kids) use (&$result, $parentSurnameNorm) {
            foreach ($kids as $c) {
                if ($parentSurnameNorm !== '' && ft_normalize($c['last_name'] ?? '') === $parentSurnameNorm) {
                    $result['unshownChildren'][] = $c;
                }
            }
        };

        foreach ($others as $norm => $info) {
            $partnerRecord = ft_find_by_name($nameIndex, $info['name']) ?? $ft_virtual($info['name']);
            if (!$partnerRecord) { $addIfAcknowledged($info['children']); continue; }

            $hasMarriage = false;
            foreach ($marriagesOfParent as $m) {
                if (ft_normalize(ft_full_name($m['record'])) === $norm) { $hasMarriage = true; break; }
            }
            $hasDeathRecord = false;
            if (empty($partnerRecord['virtual'])) {
                foreach ($allRecords as $r) {
                    if (($r['registry_type'] ?? '') === 'death' && ft_normalize(ft_full_name($r)) === $norm) { $hasDeathRecord = true; break; }
                }
            }

            if ($hasMarriage || $hasDeathRecord) {
                // A documented union (marriage, or the partner has since
                // passed away) - shown as a Previous Partner with their own
                // parents, and EVERY child from that union counts, surname
                // or not (the union itself is what's documented here, not
                // just the child's name).
                $result['shown'][] = [
                    'record' => $partnerRecord,
                    'parents' => $sideOf($partnerRecord),
                    'children' => $info['children'],
                    'deceased' => $hasDeathRecord,
                ];
            } else {
                $addIfAcknowledged($info['children']);
            }
        }
        return $result;
    };
    $fatherUnions = $findOtherUnions($father, $mother);
    $motherUnions = $findOtherUnions($mother, $father);

    // ---------- Extended family: everyone else on file sharing the same
    // surname (apilyedo). Father/Mother/Spouse/Children/Siblings/Grandparents
    // above are matched precisely (exact name match), so they get the
    // correct role label; this pool catches every OTHER record with the
    // same last name so relatives with mismatched middle names, missing
    // suffixes, or who simply aren't wired up via father_name/mother_name
    // text still show up in the genealogy instead of being left out. ----------
    $shownIds = [(int) $selfRecord['record_id']];
    foreach ([$father, $mother] as $r) { if ($r && ($r['record_id'] ?? null) !== null) $shownIds[] = (int) $r['record_id']; }
    foreach ($allSpouses as $m) { if (($m['record']['record_id'] ?? null) !== null) $shownIds[] = (int) $m['record']['record_id']; }
    foreach ($siblings as $r) $shownIds[] = (int) $r['record_id'];
    foreach ($children as $r) $shownIds[] = (int) $r['record_id'];
    foreach ([$paternal, $maternal] as $side) {
        foreach ([$side['grandfather'], $side['grandmother']] as $r) { if ($r && ($r['record_id'] ?? null) !== null) $shownIds[] = (int) $r['record_id']; }
        foreach ($side['siblings'] as $r) $shownIds[] = (int) $r['record_id'];
    }
    foreach ([$fatherUnions, $motherUnions] as $unions) {
        foreach ($unions['shown'] as $op) {
            if (($op['record']['record_id'] ?? null) !== null) $shownIds[] = (int) $op['record']['record_id'];
            foreach ([$op['parents']['grandfather'], $op['parents']['grandmother']] as $r) { if ($r && ($r['record_id'] ?? null) !== null) $shownIds[] = (int) $r['record_id']; }
            foreach ($op['children'] as $r) $shownIds[] = (int) $r['record_id'];
        }
        foreach ($unions['unshownChildren'] as $r) $shownIds[] = (int) $r['record_id'];
    }
    $shownIds = array_flip($shownIds);

    $selfLastNorm = ft_normalize($selfRecord['last_name'] ?? '');
    $sameSurname = [];
    if ($selfLastNorm !== '') {
        foreach ($allRecords as $r) {
            if (isset($shownIds[(int) $r['record_id']])) continue;
            if (ft_normalize($r['last_name'] ?? '') === $selfLastNorm) {
                $sameSurname[] = $r;
            }
        }
    }

    $tree = [
        'self' => $selfRecord, 'father' => $father, 'mother' => $mother, 'spouse' => $spouse,
        'allSpouses' => $allSpouses,
        'siblings' => $siblings, 'children' => $children,
        'paternal' => $paternal, 'maternal' => $maternal,
        'fatherUnions' => $fatherUnions, 'motherUnions' => $motherUnions,
        'sameSurname' => $sameSurname,
    ];
}

$pageTitle = $tree ? 'Your Family' : 'Generate Family Tree';
$pageSub = $tree ? '' : 'Search a person to view or generate their family lineage - powered by Civil Registry Records.';
$activeStaffNav = 'tree';
require_once __DIR__ . '/includes/staff-header.php';

/** Birth/event year shown under each name, e.g. "b. 1975". */
function ft_year($record): string
{
    if (!$record || empty($record['date_of_event'])) return '';
    $y = substr((string) $record['date_of_event'], 0, 4);
    if (!preg_match('/^\d{4}$/', $y)) return '';
    $type = $record['registry_type'] ?? '';
    if ($type === 'birth') return 'b. ' . $y;
    if ($type === 'death') return 'd. ' . $y;
    if ($type === 'marriage') return 'm. ' . $y;
    return $y;
}
?>

<style>
  /* These same rules apply two ways:
     1) @media print - a safety net for a manual Ctrl+P / browser Print
        menu (bypassing our own button below).
     2) body.ft-printing - applied by JS a moment BEFORE calling
        window.print() (see printFamilyTree() below), so the connector
        lines can be measured and redrawn against the EXACT final print
        layout first. */
  @media print, screen {
    body.ft-printing .sidebar, body.ft-printing .topbar,
    body.ft-printing .ft-search-panel, body.ft-printing .ft-print-btn,
    body.ft-printing .ft-back-btn { display:none !important; }
    body.ft-printing .print-only { display:block !important; }
    body.ft-printing .content-area { padding:0 !important; }
    body.ft-printing .panel { box-shadow:none !important; border:none !important; }
    body.ft-printing .ft-diagram-outer { overflow-x:visible !important; }
    body.ft-printing .ft-diagram-inner { min-width:0 !important; }
  }
  @media print {
    .sidebar, .topbar, .ft-search-panel, .ft-print-btn, .ft-back-btn { display:none !important; }
    .print-only { display:block !important; }
    .content-area { padding:0 !important; }
    .panel { box-shadow:none !important; border:none !important; }
    .ft-diagram-outer { overflow-x:visible !important; }
    .ft-diagram-inner { min-width:0 !important; }
  }

  /* ---------- Person card ----------
     Horizontal card (avatar left, name + year stacked right), matching
     the approved layout mock. Fixed width so every generation lines up
     in neat columns instead of each tier drifting its own width. */
  .ft-node{
    display:flex; flex-direction:row; align-items:center; gap:9px;
    padding:8px 11px; border-radius:10px; width:132px; text-align:left;
    background:var(--bg-card); border:1.5px solid var(--primary);
    transition:transform .12s ease, box-shadow .12s ease;
  }
  a[data-ft-node]{ text-decoration:none; display:block; }
  a[data-ft-node]:hover .ft-node{ transform:translateY(-2px); box-shadow:0 4px 12px rgba(0,0,0,.14); }
  .ft-node.self{ background:var(--primary-light); border-color:var(--primary); border-width:2px; }
  .ft-node.unmapped{ background:var(--bg-page); border-color:var(--border); border-style:dashed; }
  /* Father's/Mother's OWN other partner - a separate branch, not part of
     the searched person's direct line, so it's colored distinctly
     (orange) rather than the usual green, matching the approved mock. */
  .ft-node.prev-partner{ border-color:#f59e0b; border-width:2px; }
  .ft-node.prev-partner .ft-avatar{ background:rgba(245,158,11,0.15); color:#f59e0b; }
  .ft-group-title.prev-partner-title{ color:#f59e0b; }
  .ft-avatar{
    width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center;
    background:var(--primary-light); color:var(--primary); flex-shrink:0;
  }
  .ft-node.self .ft-avatar{ background:var(--primary); color:#fff; }
  .ft-node.unmapped .ft-avatar{ background:var(--border); color:var(--text-muted); }
  .ft-avatar svg{ width:16px; height:16px; }
  .ft-text{ display:flex; flex-direction:column; gap:1px; min-width:0; }
  .ft-name{ font-size:11px; font-weight:700; color:var(--text-main); line-height:1.25; word-break:break-word; }
  .ft-node.unmapped .ft-name{ color:var(--text-secondary); font-weight:600; }
  .ft-year{ font-size:9.5px; color:var(--text-muted); line-height:1.2; }
  .ft-role{ font-size:9px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.03em; line-height:1.2; }

  /* ---------- Tiers (one generation per row) ---------- */
  .ft-tier{ display:flex; justify-content:center; align-items:flex-start; gap:30px; flex-wrap:nowrap; }
  .ft-couple{ display:flex; align-items:center; gap:26px; }
  .ft-couple .plus{ color:var(--text-muted); font-size:12px; font-weight:700; }
  /* Aunts/uncles sit BESIDE the parent they belong to, stacked in a
     compact 2-column block so a large set of tito/tita never stretches
     the whole diagram sideways. */
  .ft-sibgroup{ display:grid; grid-template-columns:repeat(2, auto); gap:10px 14px; align-content:center; }
  .ft-sibgroup.one-col{ grid-template-columns:auto; }
  .ft-side{ display:flex; flex-direction:column; align-items:center; }
  .ft-group-title{ font-size:10px; font-weight:800; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.04em; margin-bottom:10px; text-align:center; }

  .ft-tiers-stack{ display:flex; flex-direction:column; align-items:center; gap:46px; position:relative; z-index:1; }
  .ft-connectors{ position:absolute; top:0; left:0; width:100%; height:100%; pointer-events:none; overflow:visible; z-index:0; }

  /* Same-surname pool: a flat grid, deliberately with NO connector lines. */
  .ft-samesurname-block{ margin-top:26px; padding-top:18px; border-top:1px dashed var(--border); }
  .ft-relatives-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:12px; padding:4px 2px 10px; }

  .ft-page-head{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:6px; }
  .ft-back-btn{
    display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px;
    border-radius:50%; border:1.5px solid var(--border); background:var(--bg-card);
    color:var(--text-main); cursor:pointer; flex-shrink:0;
  }
  .ft-back-btn:hover{ background:var(--primary-light); color:var(--primary); border-color:var(--primary); }
  .ft-page-title{ font-size:18px; font-weight:800; color:var(--text-main); }
<?php if ($tree): ?>
  /* The tree view carries its own "Your Family" heading (with the Back
     arrow and Print button beside it), so the generic page title above
     the panel is hidden here to avoid showing the same heading twice. */
  .content-area > h1.page-title{ display:none; }
<?php endif; ?>
</style>

<?php if (!$tree): ?>
<div class="panel ft-search-panel">
  <form method="GET" class="table-toolbar">
    <div class="search-box">
      <span><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16" style="display:inline-block;vertical-align:middle;"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg></span>
      <input type="text" name="q" placeholder="Search full name..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
  </form>

  <?php if ($search && $searchResults): ?>
    <p style="font-size:12.5px;color:var(--text-muted);margin-top:12px;">
      <?= count($searchResults) ?> people match that name - pick the right person to open their family tree.
    </p>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px;">
      <?php foreach ($searchResults as $p): ?>
        <a href="?q=<?= urlencode($search) ?>&record_id=<?= $p['record_id'] ?>" class="btn btn-outline btn-sm">
          <?= htmlspecialchars(ft_full_name($p)) ?>
          <span class="badge <?= $p['registry_type'] === 'birth' ? 'green' : ($p['registry_type'] === 'marriage' ? 'blue' : 'gray') ?>" style="margin-left:6px;"><?= ucfirst($p['registry_type']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  <?php elseif ($search): ?>
    <p style="font-size:12.5px;color:var(--text-muted);margin-top:10px;">No matching person was found in Civil Registry Records for that name.</p>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($tree): ?>
<?php
  /**
   * Renders one person's card and returns a unique id for it
   * (data-ft-node="..."). drawFamilyTreeConnectors() (JS, below) looks
   * cards up by this id to draw real relationship lines between them.
   *
   * Every card backed by its own Civil Registry record is a LINK: clicking
   * it re-centres the whole diagram on that person, so you can walk from
   * one relative to the next without going back to the search box.
   */
  function ftNode($record, $role = '', $isSelf = false, $extraClass = '') {
      static $seq = 0;
      $seq++;
      $id = 'ftn' . $seq;

      $icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-4.4 0-8 2.2-8 5v1h16v-1c0-2.8-3.6-5-8-5Z"/></svg>';

      if (!$record) {
          echo '<div class="ft-node unmapped" data-ft-node="' . $id . '">
                  <span class="ft-avatar">' . $icon . '</span>
                  <span class="ft-text"><span class="ft-name">Not on file</span>' .
                  ($role ? '<span class="ft-role">' . htmlspecialchars($role) . '</span>' : '') .
                  '</span></div>';
          return $id;
      }

      $name = ft_full_name($record);
      $year = ft_year($record);

      if (!empty($record['virtual'])) {
          // Name is on file (e.g. as someone else's Father's/Mother's Name
          // text) but this person has no separate Civil Registry record of
          // their own - so there is nothing to click through to.
          echo '<div class="ft-node unmapped' . ($extraClass ? ' ' . htmlspecialchars($extraClass) : '') . '" data-ft-node="' . $id . '">
                  <span class="ft-avatar">' . $icon . '</span>
                  <span class="ft-text">
                    <span class="ft-name">' . htmlspecialchars($name) . '</span>' .
                    ($role ? '<span class="ft-role">' . htmlspecialchars($role) . '</span>' : '') .
                    '<span class="ft-role">(name on file only)</span>
                  </span>
                </div>';
          return $id;
      }

      // Clicking any card opens THAT person's own complete family tree.
      // Dad-on ang trail (karon nga tree) aron makabalik ang Back arrow. Kung ang gi-click
      // mao gihapon ang karon nga tawo, walay idugang sa trail.
      global $ftTrail, $ftNextTrail;
      $link = '?record_id=' . (int) $record['record_id'];
      if ($isSelf) {
          if ($ftTrail) $link .= '&trail=' . implode(',', $ftTrail);
      } elseif ($ftNextTrail !== '') {
          $link .= '&trail=' . $ftNextTrail;
      }
      echo '<a href="' . $link . '" data-ft-node="' . $id . '" title="View ' . htmlspecialchars($name) . '\'s family tree">
              <div class="ft-node ' . ($isSelf ? 'self' : '') . ($extraClass ? ' ' . htmlspecialchars($extraClass) : '') . '">
                <span class="ft-avatar">' . $icon . '</span>
                <span class="ft-text">
                  <span class="ft-name">' . htmlspecialchars($name) . '</span>' .
                  ($year ? '<span class="ft-year">' . htmlspecialchars($year) . '</span>' : '') .
                  ($role ? '<span class="ft-role">' . htmlspecialchars($role) . '</span>' : '') .
                '</span>
              </div>
            </a>';
      return $id;
  }

  // Relationship edges collected while rendering, drawn by JS afterward.
  // Every edge comes from a relationship the PHP/DB logic above already
  // resolved - NEVER from the "same surname" pool, which is rendered in
  // its own block with no edges pointing to or from it at all.
  $ftEdges = [];
?>

<div class="panel">
  <div class="ft-page-head">
    <div style="display:flex;align-items:center;gap:12px;">
      <?php if ($ftTrail): ?>
      <!-- Back arrow: makita lang kung naay nauna nga tree nga mabalikan (wala ni sa
           unang tree sa gi-search nga tawo). Mubalik sa immediate nga nauna nga tree
           ug ang nahabilin nga trail gipadayon. -->
      <a class="ft-back-btn" href="?record_id=<?= (int) end($ftTrail) ?><?= count($ftTrail) > 1 ? '&trail=' . implode(',', array_slice($ftTrail, 0, -1)) : '' ?>" title="Back to previous family tree">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
      </a>
      <?php endif; ?>
      <span class="ft-page-title">Your Family</span>
    </div>
    <button type="button" class="btn btn-outline ft-print-btn" onclick="printFamilyTree()">Print it</button>
  </div>

  <p style="font-size:12px;color:var(--text-muted);margin:0 0 14px <?= $ftTrail ? '44px' : '0' ?>;">
    <?= htmlspecialchars(ft_full_name($tree['self'])) ?> &middot; click any relative to open their own family tree.
  </p>

  <div class="print-only" style="display:none;margin-bottom:14px;">
    <h2 style="font-size:16px;">GeoGene - Family Tree</h2>
    <p style="font-size:12px;color:#555;">
      Municipal Civil Registry Office &middot; Manolo Fortich &middot;
      <?= htmlspecialchars(ft_full_name($tree['self'])) ?> &middot;
      Generated <?= date('F j, Y g:i A') ?>
    </p>
  </div>

  <div class="ft-diagram-outer" style="overflow-x:auto;padding:10px 0 6px;">
  <div class="ft-diagram-inner" style="position:relative;display:flex;flex-direction:column;align-items:center;min-width:680px;">
  <svg class="ft-connectors"></svg>
  <div class="ft-tiers-stack">

    <?php
      // Pre-declare every id used across tiers/edges below, so nothing is
      // ever "undefined" regardless of which branches render.
      $idSelf = $idFather = $idMother = null;
      $idPatGF = $idPatGM = $idMatGF = $idMatGM = null;
      $idSibs = [];
      $spouseNodes = []; // [id, role, record] per spouse
      $hasPaternal = $tree['paternal']['grandfather'] || $tree['paternal']['grandmother'];
      $hasMaternal = $tree['maternal']['grandfather'] || $tree['maternal']['grandmother'];
      // Each entry in 'shown' is a DOCUMENTED previous partner (a marriage
      // record, or a death record, is on file for them) - there can be
      // more than one (e.g. two earlier wives). Anything in
      // 'unshownChildren' has no such documentation behind it - just a
      // shared child with no union on file - so that other parent is never
      // drawn as a card at all; only the child appears, further below.
      $fatherShownUnions = $tree['fatherUnions']['shown'];
      $motherShownUnions = $tree['motherUnions']['shown'];
    ?>

    <!-- ============================================================
         GENERATION ORDER IS TOP-DOWN, OLDEST FIRST:
             1. Grandparents (paternal side | maternal side) - each
                documented Previous Partner's own parents flank the OUTER
                edge on the matching side.
             2. Father + Mother as the main couple in the centre - each
                documented Previous Partner (orange) sits just outside
                whichever of them they belong to. A child with no
                documented union behind it never gets a partner card here
                at all - see TIER 3.
             3. The searched person + their siblings (+ any half-siblings,
                documented union or not) + spouse(s).
             4. Children.
         Father's/Mother's OWN siblings (tito/tita) are intentionally NOT
         shown here anymore - click Father's or Mother's own card to open
         THEIR tree, where their siblings appear naturally as their own
         "Siblings" row. Keeping them out of this view is what keeps this
         diagram to the searched person's direct line only.
         ============================================================ -->

    <!-- TIER 1 (top): Grandparents. -->
    <?php
      $anyFatherPrevGP = array_filter($fatherShownUnions, fn($u) => $u['parents']['grandfather'] || $u['parents']['grandmother']);
      $anyMotherPrevGP = array_filter($motherShownUnions, fn($u) => $u['parents']['grandfather'] || $u['parents']['grandmother']);
    ?>
    <?php if ($hasPaternal || $hasMaternal || $anyFatherPrevGP || $anyMotherPrevGP): ?>
      <div class="ft-tier" style="gap:70px;">
        <?php foreach ($fatherShownUnions as $i => $u): if (!$u['parents']['grandfather'] && !$u['parents']['grandmother']) continue; ?>
          <div class="ft-side">
            <div class="ft-group-title prev-partner-title">Father's Previous Partner's Parents</div>
            <div class="ft-couple">
              <?php $u['_gfId'] = ftNode($u['parents']['grandfather'], 'Grandfather', false, 'prev-partner'); ?>
              <span class="plus">+</span>
              <?php $u['_gmId'] = ftNode($u['parents']['grandmother'], 'Grandmother', false, 'prev-partner'); ?>
            </div>
          </div>
          <?php $fatherShownUnions[$i]['_gfId'] = $u['_gfId']; $fatherShownUnions[$i]['_gmId'] = $u['_gmId']; ?>
        <?php endforeach; ?>
        <?php if ($hasPaternal): ?>
          <div class="ft-side">
            <div class="ft-group-title">Paternal Grandparents</div>
            <div class="ft-couple">
              <?php $idPatGF = ftNode($tree['paternal']['grandfather'], 'Grandfather'); ?>
              <span class="plus">+</span>
              <?php $idPatGM = ftNode($tree['paternal']['grandmother'], 'Grandmother'); ?>
            </div>
          </div>
        <?php endif; ?>
        <?php if ($hasMaternal): ?>
          <div class="ft-side">
            <div class="ft-group-title">Maternal Grandparents</div>
            <div class="ft-couple">
              <?php $idMatGF = ftNode($tree['maternal']['grandfather'], 'Grandfather'); ?>
              <span class="plus">+</span>
              <?php $idMatGM = ftNode($tree['maternal']['grandmother'], 'Grandmother'); ?>
            </div>
          </div>
        <?php endif; ?>
        <?php foreach ($motherShownUnions as $i => $u): if (!$u['parents']['grandfather'] && !$u['parents']['grandmother']) continue; ?>
          <div class="ft-side">
            <div class="ft-group-title prev-partner-title">Mother's Previous Partner's Parents</div>
            <div class="ft-couple">
              <?php $gfId = ftNode($u['parents']['grandfather'], 'Grandfather', false, 'prev-partner'); ?>
              <span class="plus">+</span>
              <?php $gmId = ftNode($u['parents']['grandmother'], 'Grandmother', false, 'prev-partner'); ?>
            </div>
          </div>
          <?php $motherShownUnions[$i]['_gfId'] = $gfId; $motherShownUnions[$i]['_gmId'] = $gmId; ?>
        <?php endforeach; ?>
      </div>
      <?php
        if ($idPatGF && $idPatGM) $ftEdges[] = ['type' => 'couple', 'a' => $idPatGF, 'b' => $idPatGM];
        if ($idMatGF && $idMatGM) $ftEdges[] = ['type' => 'couple', 'a' => $idMatGF, 'b' => $idMatGM];
        foreach ($fatherShownUnions as $u) {
            if (!empty($u['_gfId']) && !empty($u['_gmId'])) $ftEdges[] = ['type' => 'couple', 'a' => $u['_gfId'], 'b' => $u['_gmId']];
        }
        foreach ($motherShownUnions as $u) {
            if (!empty($u['_gfId']) && !empty($u['_gmId'])) $ftEdges[] = ['type' => 'couple', 'a' => $u['_gfId'], 'b' => $u['_gmId']];
        }
      ?>
    <?php endif; ?>

    <!-- TIER 2: Father + Mother as the main couple, with each one's
         documented Previous Partner(s) (if any) just outside them,
         orange-highlighted so each is clearly a separate branch. -->
    <?php if ($tree['father'] || $tree['mother'] || $fatherShownUnions || $motherShownUnions): ?>
      <div class="ft-tier" style="align-items:center;gap:44px;">
        <?php foreach ($fatherShownUnions as $i => $u): ?>
          <?php $pid = ftNode($u['record'], "Father's Previous Partner" . (!empty($u['deceased']) ? ' (deceased)' : ''), false, 'prev-partner'); ?>
          <?php $fatherShownUnions[$i]['_personId'] = $pid; ?>
        <?php endforeach; ?>

        <?php if ($tree['father'] || $tree['mother']): ?>
          <div class="ft-couple">
            <?php if ($tree['father']): $idFather = ftNode($tree['father'], 'Father'); endif; ?>
            <?php if ($tree['father'] && $tree['mother']): ?><span class="plus">+</span><?php endif; ?>
            <?php if ($tree['mother']): $idMother = ftNode($tree['mother'], 'Mother'); endif; ?>
          </div>
        <?php endif; ?>

        <?php foreach ($motherShownUnions as $i => $u): ?>
          <?php $pid = ftNode($u['record'], "Mother's Previous Partner" . (!empty($u['deceased']) ? ' (deceased)' : ''), false, 'prev-partner'); ?>
          <?php $motherShownUnions[$i]['_personId'] = $pid; ?>
        <?php endforeach; ?>
      </div>

      <?php
        if ($tree['father'] && $tree['mother']) $ftEdges[] = ['type' => 'couple', 'a' => $idFather, 'b' => $idMother];
        foreach ($fatherShownUnions as $u) { if ($idFather && !empty($u['_personId'])) $ftEdges[] = ['type' => 'couple', 'a' => $idFather, 'b' => $u['_personId']]; }
        foreach ($motherShownUnions as $u) { if ($idMother && !empty($u['_personId'])) $ftEdges[] = ['type' => 'couple', 'a' => $idMother, 'b' => $u['_personId']]; }

        // Grandparents (ABOVE) -> the couple they belong to (BELOW).
        if (($idPatGF || $idPatGM) && $idFather) $ftEdges[] = ['type' => 'parent-child', 'parents' => array_values(array_filter([$idPatGF, $idPatGM])), 'children' => [$idFather]];
        if (($idMatGF || $idMatGM) && $idMother) $ftEdges[] = ['type' => 'parent-child', 'parents' => array_values(array_filter([$idMatGF, $idMatGM])), 'children' => [$idMother]];
        foreach ($fatherShownUnions as $u) {
            if ((!empty($u['_gfId']) || !empty($u['_gmId'])) && !empty($u['_personId'])) {
                $ftEdges[] = ['type' => 'parent-child', 'parents' => array_values(array_filter([$u['_gfId'] ?? null, $u['_gmId'] ?? null])), 'children' => [$u['_personId']]];
            }
        }
        foreach ($motherShownUnions as $u) {
            if ((!empty($u['_gfId']) || !empty($u['_gmId'])) && !empty($u['_personId'])) {
                $ftEdges[] = ['type' => 'parent-child', 'parents' => array_values(array_filter([$u['_gfId'] ?? null, $u['_gmId'] ?? null])), 'children' => [$u['_personId']]];
            }
        }
      ?>
    <?php endif; ?>

    <!-- TIER 3: The searched person, their siblings, any half-siblings
         (whether their other parent is shown as a documented Previous
         Partner above, or wasn't documented at all), and every spouse of
         their own (current AND ex - none are dropped). Self highlighted. -->
    <div class="ft-tier">
      <?php
        // Half-siblings from a DOCUMENTED previous partner connect to that
        // specific couple (Father + that partner). Half-siblings from an
        // UNDOCUMENTED union (no partner card anywhere) connect to the
        // single parent alone - that other parent is never named or shown.
        $halfSibEdges = []; // [ [parents]=>[...], kids=>[id,...] ]
        foreach ($fatherShownUnions as $u) {
            if (!$u['children']) continue;
            $ids = [];
            foreach ($u['children'] as $k) $ids[] = ftNode($k, 'Half-sibling', false, 'prev-partner');
            $halfSibEdges[] = ['parents' => array_values(array_filter([$idFather, $u['_personId'] ?? null])), 'kids' => $ids];
        }
      ?>
      <?php foreach ($tree['fatherUnions']['unshownChildren'] as $k): ?>
        <?php $uid = ftNode($k, 'Half-sibling'); ?>
        <?php $halfSibEdges[] = ['parents' => array_values(array_filter([$idFather])), 'kids' => [$uid]]; ?>
      <?php endforeach; ?>

      <?php foreach ($tree['siblings'] as $sib): ?>
        <?php $idSibs[] = ftNode($sib, 'Sibling'); ?>
      <?php endforeach; ?>
      <?php $idSelf = ftNode($tree['self'], 'You', true); ?>

      <?php foreach ($motherShownUnions as $u): ?>
        <?php if (!$u['children']) continue; ?>
        <?php $ids = []; ?>
        <?php foreach ($u['children'] as $k): ?>
          <?php $ids[] = ftNode($k, 'Half-sibling', false, 'prev-partner'); ?>
        <?php endforeach; ?>
        <?php $halfSibEdges[] = ['parents' => array_values(array_filter([$idMother, $u['_personId'] ?? null])), 'kids' => $ids]; ?>
      <?php endforeach; ?>
      <?php foreach ($tree['motherUnions']['unshownChildren'] as $k): ?>
        <?php $uid = ftNode($k, 'Half-sibling'); ?>
        <?php $halfSibEdges[] = ['parents' => array_values(array_filter([$idMother])), 'kids' => [$uid]]; ?>
      <?php endforeach; ?>

      <?php foreach ($tree['allSpouses'] as $m): ?>
        <span class="plus" style="align-self:center;">+</span>
        <?php $spId = ftNode($m['record'], $m['role']); ?>
        <?php $spouseNodes[] = ['id' => $spId, 'role' => $m['role'], 'record' => $m['record']]; ?>
      <?php endforeach; ?>
    </div>

    <?php
      foreach ($spouseNodes as $sp) { $ftEdges[] = ['type' => 'couple', 'a' => $idSelf, 'b' => $sp['id']]; }

      // Father/Mother (ABOVE) -> self + siblings (BELOW).
      $selfGen = array_values(array_filter(array_merge($idSibs, [$idSelf])));
      $parentIds = array_values(array_filter([$idFather, $idMother]));
      if ($parentIds && $selfGen) {
          $ftEdges[] = ['type' => 'parent-child', 'parents' => $parentIds, 'children' => $selfGen];
      }
      // Each half-sibling group (documented partner, or undocumented/no
      // card) -> its own children, as a separate edge each.
      foreach ($halfSibEdges as $e) {
          if ($e['parents'] && $e['kids']) $ftEdges[] = ['type' => 'parent-child', 'parents' => $e['parents'], 'children' => $e['kids']];
      }
    ?>

    <!-- TIER 4 (bottom of the connected diagram): Children. -->
    <?php if ($tree['children']): ?>
      <div class="ft-tier">
        <?php $idKids = []; ?>
        <?php foreach ($tree['children'] as $child): ?>
          <?php $idKids[] = ftNode($child, 'Child'); ?>
        <?php endforeach; ?>
      </div>
      <?php
        // Match each child to whichever union (which specific spouse) they
        // belong to, by checking their OTHER parent's name against each
        // resolved spouse - so kids from an earlier (ex-spouse) marriage
        // connect under that couple, not the current one, and vice versa.
        $selfNameNorm = ft_normalize(ft_full_name($tree['self']));
        $kidsBySpouse = []; // spouse node id -> [child ids]
        $kidsUnmatched = [];
        foreach ($tree['children'] as $i => $child) {
            $otherParentName = ft_normalize($child['father_name'] ?? '') === $selfNameNorm
                ? ft_normalize($child['mother_name'] ?? '')
                : ft_normalize($child['father_name'] ?? '');
            $matchedSpouseId = null;
            foreach ($spouseNodes as $sp) {
                if ($otherParentName !== '' && ft_normalize(ft_full_name($sp['record'])) === $otherParentName) {
                    $matchedSpouseId = $sp['id'];
                    break;
                }
            }
            if ($matchedSpouseId) {
                $kidsBySpouse[$matchedSpouseId][] = $idKids[$i];
            } else {
                $kidsUnmatched[] = $idKids[$i];
            }
        }
        foreach ($kidsBySpouse as $spId => $kidIds) {
            $ftEdges[] = ['type' => 'parent-child', 'parents' => [$idSelf, $spId], 'children' => $kidIds];
        }
        if ($kidsUnmatched) {
            $ftEdges[] = ['type' => 'parent-child', 'parents' => [$idSelf], 'children' => $kidsUnmatched];
        }
      ?>
    <?php endif; ?>

    <?php if (!$hasPaternal && !$hasMaternal && !$tree['father'] && !$tree['mother'] && !$tree['siblings'] && !$tree['children'] && !$tree['allSpouses'] && !$tree['sameSurname']): ?>
      <p style="font-size:12.5px;color:var(--text-muted);padding:20px 0;">
        No family relationships found on file yet for this person (Father's Name / Mother's Name fields are
        blank, or their relatives haven't been imported into Civil Registry Records).
      </p>
    <?php endif; ?>

  </div><!-- /.ft-tiers-stack -->
  </div><!-- /.ft-diagram-inner -->
  </div><!-- /.ft-diagram-outer -->

  <?php if ($tree['sameSurname']): ?>
    <!-- Deliberately OUTSIDE the connected diagram above, and with NO
         connector drawn to it: sharing a surname is not a confirmed
         relationship, so this section must never visually look
         connected to the family tree above it. -->
    <div class="ft-samesurname-block">
      <div class="ft-group-title" style="margin-top:2px;text-align:left;">
        Other <?= htmlspecialchars(gg_title_case($tree['self']['last_name'])) ?> Relatives on File
        (<?= count($tree['sameSurname']) ?>) &middot; Same Surname Only - Not a Confirmed Relationship
      </div>
      <div class="ft-relatives-grid">
        <?php foreach ($tree['sameSurname'] as $rel): ?>
          <div><?php ftNode($rel, 'Relative'); ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>
</div>

<script>window.__ftEdges = <?= json_encode($ftEdges) ?>;</script>

<!-- ---------- Dynamic relationship-line connector layer ----------
     Draws real orthogonal (elbow) SVG lines between the actual rendered
     cards, computed from their live on-screen positions via
     getBoundingClientRect() - never hardcoded coordinates. Re-runs on
     load and window resize so it always matches the current layout. -->
<script>
function drawFamilyTreeConnectors() {
  const container = document.querySelector('.ft-diagram-inner');
  const svg = document.querySelector('.ft-connectors');
  if (!container || !svg || !window.__ftEdges) return;

  const containerRect = container.getBoundingClientRect();
  svg.setAttribute('width', container.scrollWidth);
  svg.setAttribute('height', container.scrollHeight);
  svg.innerHTML = '';

  const ns = 'http://www.w3.org/2000/svg';
  const STROKE = getComputedStyle(document.body).getPropertyValue('--border').trim() || '#c7cbd4';

  function nodeRect(id) {
    const el = container.querySelector('[data-ft-node="' + id + '"]');
    if (!el || el.offsetParent === null) return null; // missing or hidden
    const r = el.getBoundingClientRect();
    return {
      left: r.left - containerRect.left, right: r.right - containerRect.left,
      top: r.top - containerRect.top, bottom: r.bottom - containerRect.top,
      centerX: r.left - containerRect.left + r.width / 2,
      centerY: r.top - containerRect.top + r.height / 2,
    };
  }

  function drawPath(d) {
    const p = document.createElementNS(ns, 'path');
    p.setAttribute('d', d);
    p.setAttribute('stroke', STROKE);
    p.setAttribute('stroke-width', '1.6');
    p.setAttribute('fill', 'none');
    svg.appendChild(p);
  }

  window.__ftEdges.forEach(edge => {
    if (edge.type === 'couple') {
      const a = nodeRect(edge.a), b = nodeRect(edge.b);
      if (!a || !b) return;
      // Connect the INNER (facing) sides, at the vertical center.
      const y = (a.centerY + b.centerY) / 2;
      const x1 = a.centerX < b.centerX ? a.right : a.left;
      const x2 = a.centerX < b.centerX ? b.left : b.right;
      drawPath(`M ${x1} ${y} L ${x2} ${y}`);
      return;
    }

    if (edge.type === 'parent-child') {
      const parentRects = edge.parents.map(nodeRect).filter(Boolean);
      const childRects = edge.children.map(nodeRect).filter(Boolean);
      if (!parentRects.length || !childRects.length) return;

      // Parents always sit ABOVE their children now, so the drop always
      // starts at the lowest parent edge and ends at the highest child
      // edge - one shared horizontal bus in between, like a proper
      // genealogy chart.
      const originY = Math.max(...parentRects.map(r => r.bottom));
      const childTopY = Math.min(...childRects.map(r => r.top));
      const busY = originY + (childTopY - originY) / 2;

      // A couple drops ONE shared line from the midpoint of their
      // marriage line, rather than one line each, so the children hang
      // off the union instead of off each parent separately.
      let parentDrops;
      if (parentRects.length === 2) {
        parentDrops = [(parentRects[0].centerX + parentRects[1].centerX) / 2];
      } else {
        parentDrops = parentRects.map(r => r.centerX);
      }

      const allXs = parentDrops.concat(childRects.map(r => r.centerX));
      const busLeft = Math.min(...allXs);
      const busRight = Math.max(...allXs);

      parentDrops.forEach(x => drawPath(`M ${x} ${originY} L ${x} ${busY}`));
      if (busRight > busLeft) drawPath(`M ${busLeft} ${busY} L ${busRight} ${busY}`);
      childRects.forEach(c => drawPath(`M ${c.centerX} ${busY} L ${c.centerX} ${c.top}`));
    }
  });
}

let __ftRedrawTimer = null;
function ftScheduleRedraw() {
  clearTimeout(__ftRedrawTimer);
  __ftRedrawTimer = setTimeout(drawFamilyTreeConnectors, 60);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', drawFamilyTreeConnectors);
} else {
  drawFamilyTreeConnectors();
}
window.addEventListener('load', drawFamilyTreeConnectors);
window.addEventListener('resize', ftScheduleRedraw);
// Dark-mode toggle changes the line colour, so redraw when the class flips.
new MutationObserver(ftScheduleRedraw).observe(document.body, { attributes: true, attributeFilter: ['class'] });
window.addEventListener('beforeprint', drawFamilyTreeConnectors);
window.addEventListener('afterprint', drawFamilyTreeConnectors);

/**
 * Printing via the "Print it" button: switch to the print layout
 * OURSELVES first by adding the .ft-printing class, then redraw the
 * connectors. Reading element positions forces the browser to finish
 * applying that layout change synchronously, so by the time
 * window.print() runs the lines match the exact printed layout.
 */
function printFamilyTree() {
  document.body.classList.add('ft-printing');
  drawFamilyTreeConnectors();
  window.print();
  document.body.classList.remove('ft-printing');
  drawFamilyTreeConnectors();
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/staff-footer.php'; ?>