<?php
require_once __DIR__ . '/../includes/auth-guard.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../staff/includes/name-format.php';   // shared name rules (Given Middle Surname, Mixed Case, order-insensitive match)

// Never let the browser (or any proxy) cache a stale copy of this page -
// genealogy data changes, and old cached copies could show an outdated
// tree layout.
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$userId = $_SESSION['user_id'];
$activeNav = 'tree';

// ---------- Navigation: click any relative's card -> their own tree ----------
// Clicking Father, Mother, a grandparent, a sibling, a spouse, or a child
// re-centers the WHOLE tree on THAT person - exactly like the staff tool -
// and the Back arrow walks all the way back out through however many of
// those clicks were made, always ending on the signed-in user's OWN tree
// (never a blank page). Ported straight from staff/family-tree.php so both
// tools behave identically.
//
// "trail" is the list of record_ids of every tree visited so far, oldest
// first, carried on the URL (?trail=5,9,14) - so Back is correct even
// after a refresh, in another tab, or embedded anywhere else. The
// signed-in user's own tree has NO trail and therefore no Back arrow.
$viewRecordId = isset($_GET['record_id']) && ctype_digit((string) $_GET['record_id']) ? (int) $_GET['record_id'] : null;

// Matching ignores CASE and ORDER: "Abao, Xander Sarzosa" (older records),
// "XANDER SARZOSA ABAO" and "Xander Sarzosa Abao" are the same person.
function ft_normalize(string $s): string
{
    return gg_name_key($s);
}

// Displayed as "Given Middle Surname" in Mixed Case.
function ft_full_name(array $r): string
{
    $fromParts = formatFullName($r['last_name'] ?? '', $r['first_name'] ?? '', $r['middle_name'] ?? '');
    if ($fromParts !== '') return $fromParts;
    return gg_display_name($r['full_name'] ?? '');
}

$stmt = $pdo->prepare("SELECT matched_record_id, full_name, identity_verified FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$account = $stmt->fetch();

// $ownRecord is the signed-in user's OWN identity - fixed, the "Home" to
// always be reachable from. $selfRecord is whichever person the tree is
// CENTERED on right now: $ownRecord by default, or a relative navigated
// to via $viewRecordId.
$ownRecord = null;
if ($account) {
    if (!empty($account['matched_record_id'])) {
        $stmt = $pdo->prepare("SELECT * FROM civil_registry_records WHERE record_id = ?");
        $stmt->execute([$account['matched_record_id']]);
        $ownRecord = $stmt->fetch() ?: null;
    }
    if (!$ownRecord && !empty($account['identity_verified']) && !empty($account['full_name'])) {
        $wanted = ft_normalize($account['full_name']);
        $all = $pdo->query("SELECT * FROM civil_registry_records")->fetchAll();
        foreach ($all as $r) {
            if (ft_normalize(ft_full_name($r)) === $wanted) { $ownRecord = $r; break; }
        }
    }
}

$selfRecord = $ownRecord;
$tree = null;
$ftTrail = [];

if ($ownRecord) {
    $allRecords = $pdo->query("SELECT * FROM civil_registry_records")->fetchAll();

    // A relative was clicked ($viewRecordId set) - switch $selfRecord to
    // THEM, validating the id is a real record first so a stale/tampered
    // URL falls back to "home" instead of a blank page.
    if ($viewRecordId !== null && $viewRecordId !== (int) $ownRecord['record_id']) {
        foreach ($allRecords as $r) {
            if ((int) $r['record_id'] === $viewRecordId) { $selfRecord = $r; break; }
        }
        if ($selfRecord === $ownRecord) $viewRecordId = null; // id wasn't found - stay home
    }

    // Build the trail (same validate-and-dedupe rules as the staff tool).
    $validIds = [];
    foreach ($allRecords as $r) $validIds[(int) $r['record_id']] = true;
    $selfIdForTrail = (int) $selfRecord['record_id'];
    foreach (explode(',', (string) ($_GET['trail'] ?? '')) as $part) {
        $tid = (int) trim($part);
        if ($tid <= 0 || !isset($validIds[$tid])) continue;
        if ($ftTrail && end($ftTrail) === $tid) continue;
        $ftTrail[] = $tid;
    }
    while ($ftTrail && end($ftTrail) === $selfIdForTrail) array_pop($ftTrail);
    $ftTrail = array_slice($ftTrail, -40);
}
$ftNextTrail = $ftTrail ? implode(',', array_merge($ftTrail, [(int) $selfRecord['record_id']])) : (string) ((int) ($selfRecord['record_id'] ?? 0));
$isOwnTree = $ownRecord && (int) $ownRecord['record_id'] === (int) $selfRecord['record_id'];
$showBackArrow = $ownRecord && !$isOwnTree;

if ($selfRecord) {
    $nameIndex = [];
    foreach ($allRecords as $r) {
        $key = ft_normalize(ft_full_name($r));
        if ($key !== '' && !isset($nameIndex[$key])) $nameIndex[$key] = $r;
    }
    $ft_find_by_name = function (?string $name) use ($nameIndex) {
        if (!$name) return null;
        $key = ft_normalize($name);
        return $nameIndex[$key] ?? null;
    };
    $ft_virtual = function (?string $nameText) {
        $nameText = trim((string) $nameText);
        if ($nameText === '') return null;
        return ['record_id' => null, 'full_name' => $nameText, 'virtual' => true];
    };

    $selfName = ft_normalize(ft_full_name($selfRecord));

    $father = $ft_find_by_name($selfRecord['father_name'] ?? null) ?? $ft_virtual($selfRecord['father_name'] ?? null);
    $mother = $ft_find_by_name($selfRecord['mother_name'] ?? null) ?? $ft_virtual($selfRecord['mother_name'] ?? null);

    // Every marriage (current AND past) tied to ANY given person, not just
    // self - this is what lets Father's or Mother's OWN earlier marriage
    // (before they had self with their other parent, or after) be found
    // too, for the "Previous Partner" branch further down.
    $findMarriagesOf = function (?array $personRecord) use ($allRecords, $ft_find_by_name, $ft_virtual) {
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
            $partnerRecord = $ft_find_by_name($partnerName) ?? $ft_virtual($partnerName);
            if (!$partnerRecord) continue;
            $marriages[] = ['record' => $partnerRecord, 'date' => $r['date_of_event'] ?? null];
        }
        $directSpouseName = $personRecord['spouse_name'] ?? null;
        if (!$marriages && $directSpouseName) {
            $directSpouse = $ft_find_by_name($directSpouseName) ?? $ft_virtual($directSpouseName);
            if ($directSpouse) $marriages[] = ['record' => $directSpouse, 'date' => null];
        }
        $seen = [];
        $out = [];
        foreach ($marriages as $m) {
            $key = ft_normalize(ft_full_name($m['record']));
            if ($key === '' || isset($seen[$key])) continue;
            $seen[$key] = true;
            $out[] = $m;
        }
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

    $children = [];
    foreach ($allRecords as $r) {
        if (ft_normalize($r['father_name'] ?? '') === $selfName || ft_normalize($r['mother_name'] ?? '') === $selfName) {
            $children[] = $r;
        }
    }

    $sideOf = function (?array $parentRecord) use ($allRecords, $ft_find_by_name, $ft_virtual) {
        if (!$parentRecord || !empty($parentRecord['virtual'])) {
            return ['grandfather' => null, 'grandmother' => null, 'siblings' => []];
        }
        $gf = $ft_find_by_name($parentRecord['father_name'] ?? null) ?? $ft_virtual($parentRecord['father_name'] ?? null);
        $gm = $ft_find_by_name($parentRecord['mother_name'] ?? null) ?? $ft_virtual($parentRecord['mother_name'] ?? null);

        $pFatherNorm = ft_normalize($parentRecord['father_name'] ?? '');
        $pMotherNorm = ft_normalize($parentRecord['mother_name'] ?? '');
        $sibs = [];
        if ($pFatherNorm && $pMotherNorm) {
            foreach ($allRecords as $r) {
                if ((int) $r['record_id'] === (int) $parentRecord['record_id']) continue;
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
    // Either one means this was a recognized union, so that person gets
    // shown as a "Previous Partner" card with their own parents and the
    // shared children.
    //
    // With NEITHER on file, there is nothing documenting an actual
    // relationship - just a shared child - so that other parent is NOT
    // added to the tree at all (no card, no parents, no line to them).
    // The child still counts, but ONLY if Father/Mother acknowledged them
    // by name - i.e. the child carries Father's/Mother's own surname. No
    // surname match means no acknowledgment on file either, so that child
    // is left out too.
    $findOtherUnions = function (?array $parentRecord, ?array $excludeRecord) use ($findMarriagesOf, $sideOf, $allRecords, $ft_find_by_name, $ft_virtual) {
        $result = ['shown' => [], 'unshownChildren' => []];
        if (!$parentRecord || !empty($parentRecord['virtual'])) return $result;
        $excludeNorm = $excludeRecord ? ft_normalize(ft_full_name($excludeRecord)) : null;
        $parentNorm = ft_normalize(ft_full_name($parentRecord));
        if ($parentNorm === '') return $result;

        $others = []; // normalized other-parent name => ['name' => raw text, 'children' => [...]]
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
        $marriagesOfParent = $findMarriagesOf($parentRecord);
        foreach ($marriagesOfParent as $m) {
            $mk = ft_normalize(ft_full_name($m['record']));
            if ($mk === '' || $mk === $excludeNorm) continue;
            if (!isset($others[$mk])) $others[$mk] = ['name' => ft_full_name($m['record']), 'children' => []];
        }

        $parentSurnameNorm = ft_normalize($parentRecord['last_name'] ?? '');
        $addIfAcknowledged = function (array $kids) use (&$result, $parentSurnameNorm) {
            foreach ($kids as $c) {
                if ($parentSurnameNorm !== '' && ft_normalize($c['last_name'] ?? '') === $parentSurnameNorm) {
                    $result['unshownChildren'][] = $c;
                }
            }
        };

        foreach ($others as $norm => $info) {
            $partnerRecord = $ft_find_by_name($info['name']) ?? $ft_virtual($info['name']);
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

    // "Relatives": other records sharing the same SURNAME as the person
    // centered right now - NOT a confirmed relationship (two different
    // families can share a common surname), so these are always shown
    // separately and are NEVER clickable (see ftNode()'s $clickable param).
    $excludeIds = [(int) $selfRecord['record_id']];
    foreach ([$father, $mother] as $p) if ($p && !empty($p['record_id'])) $excludeIds[] = (int) $p['record_id'];
    foreach ($allSpouses as $m) if (!empty($m['record']['record_id'])) $excludeIds[] = (int) $m['record']['record_id'];
    foreach (array_merge($siblings, $children, $paternal['siblings'], $maternal['siblings']) as $r) $excludeIds[] = (int) $r['record_id'];
    foreach ([$paternal['grandfather'], $paternal['grandmother'], $maternal['grandfather'], $maternal['grandmother']] as $p) if ($p && !empty($p['record_id'])) $excludeIds[] = (int) $p['record_id'];
    foreach ([$fatherUnions, $motherUnions] as $unions) {
        foreach ($unions['shown'] as $op) {
            if (($op['record']['record_id'] ?? null) !== null) $excludeIds[] = (int) $op['record']['record_id'];
            foreach ([$op['parents']['grandfather'], $op['parents']['grandmother']] as $r) { if ($r && ($r['record_id'] ?? null) !== null) $excludeIds[] = (int) $r['record_id']; }
            foreach ($op['children'] as $r) $excludeIds[] = (int) $r['record_id'];
        }
        foreach ($unions['unshownChildren'] as $r) $excludeIds[] = (int) $r['record_id'];
    }
    $excludeIds = array_flip($excludeIds);

    $selfLastNorm = ft_normalize($selfRecord['last_name'] ?? '');
    $sameSurname = [];
    if ($selfLastNorm !== '') {
        foreach ($allRecords as $r) {
            if (isset($excludeIds[(int) $r['record_id']])) continue;
            if (ft_normalize($r['last_name'] ?? '') === $selfLastNorm) $sameSurname[] = $r;
        }
    }

    $tree = [
        'self' => $selfRecord, 'father' => $father, 'mother' => $mother, 'allSpouses' => $allSpouses,
        'siblings' => $siblings, 'children' => $children,
        'paternal' => $paternal, 'maternal' => $maternal,
        'fatherUnions' => $fatherUnions, 'motherUnions' => $motherUnions,
        'sameSurname' => $sameSurname,
    ];
}

/**
 * The link for a clickable relative's card: null when it shouldn't be
 * clickable (no real record behind it, or it's the person already
 * centered). Carries the current trail + current person forward as
 * ?trail=... so Back can retrace every hop, same as the staff tool.
 */
function ft_relative_href($record): ?string
{
    global $selfRecord, $ftNextTrail;
    if (!$record || empty($record['record_id']) || !empty($record['virtual'])) return null;
    $targetId = (int) $record['record_id'];
    if ($selfRecord && (int) $selfRecord['record_id'] === $targetId) return null;
    return 'tree.php?record_id=' . $targetId . '&trail=' . urlencode($ftNextTrail);
}

/** Pops the most recent stop off the trail - "home" (bare tree.php) once it's empty. */
function ft_back_href(): string
{
    global $ftTrail;
    if (!$ftTrail) return 'tree.php';
    $path = $ftTrail;
    $prevId = array_pop($path);
    $href = 'tree.php?record_id=' . $prevId;
    if ($path) $href .= '&trail=' . urlencode(implode(',', $path));
    return $href;
}

/**
 * The header label on a relative's "Side" page. Father/Mother keep the
 * exact wording from the approved design ("FATHER SIDE" / "MOTHER SIDE");
 * anyone else (a sibling, a child, a grandparent...) gets a generic
 * "{First Name}'S SIDE" so the label still makes sense for them.
 */
function ft_side_label(array $viewed, ?array $ownRecord): string
{
    if ($ownRecord) {
        $fatherNorm = ft_normalize($ownRecord['father_name'] ?? '');
        $motherNorm = ft_normalize($ownRecord['mother_name'] ?? '');
        $viewedNorm = ft_normalize(ft_full_name($viewed));
        if ($fatherNorm !== '' && $viewedNorm === $fatherNorm) return 'FATHER SIDE';
        if ($motherNorm !== '' && $viewedNorm === $motherNorm) return 'MOTHER SIDE';
    }
    $first = trim((string) ($viewed['first_name'] ?? '')) ?: explode(' ', ft_full_name($viewed))[0];
    return strtoupper($first) . "'S SIDE";
}

function ftNode($record, $role = '', $isSelf = false, $gen = 1, $rel = '', $clickable = true, $extraClass = '') {
    static $seq = 0;
    $seq++;
    $id = 'ftn' . $seq;

    if (!$record) {
        echo '<div class="tree-node unmapped" data-gen="' . $gen . '" data-rel="' . $rel . '" data-ft-node="' . $id . '">
                <div class="tree-avatar">?</div>
                <div class="tree-name">Not on file</div>' .
                ($role ? '<div class="tree-role">' . htmlspecialchars($role) . '</div>' : '') .
              '</div>';
        return $id;
    }

    $name = ft_full_name($record);
    $initial = strtoupper(substr($name, 0, 1) ?: '?');

    if (!empty($record['virtual'])) {
        echo '<div class="tree-node unmapped' . ($extraClass ? ' ' . htmlspecialchars($extraClass) : '') . '" data-gen="' . $gen . '" data-rel="' . $rel . '" data-ft-node="' . $id . '">
                <div class="tree-avatar">' . $initial . '</div>
                <div class="tree-name">' . htmlspecialchars($name) . '</div>' .
                ($role ? '<div class="tree-role">' . htmlspecialchars($role) . '</div>' : '') .
                '<div class="tree-role">(name on file only)</div>
              </div>';
        return $id;
    }

    $cls = $isSelf ? 'tree-node self' : 'tree-node';
    $cls .= $extraClass ? ' ' . $extraClass : '';
    $inner = '<div class="tree-avatar">' . $initial . '</div>
            <div class="tree-name">' . htmlspecialchars($name) . '</div>' .
            ($role ? '<div class="tree-role">' . htmlspecialchars($role) . '</div>' : '');

    // Relatives (same surname, unconfirmed relationship) are deliberately
    // never clickable, even though they have a real record_id - see where
    // ftNode() is called for the Relatives grid.
    $href = $clickable ? ft_relative_href($record) : null;
    if ($href) {
        // A real relative with their own Civil Registry record - clicking
        // re-centers the whole tree on THEM (Father -> his own tree with
        // his parents/siblings, Mother -> hers, a sibling -> theirs, a
        // child -> theirs - same click-through-to-anyone behavior as the
        // staff tool).
        echo '<a class="' . $cls . ' clickable" href="' . htmlspecialchars($href) . '" data-gen="' . $gen . '" data-rel="' . $rel . '" data-ft-node="' . $id . '" title="View ' . htmlspecialchars($name) . '\'s family tree">
                ' . $inner . '
              </a>';
    } else {
        echo '<div class="' . $cls . '" data-gen="' . $gen . '" data-rel="' . $rel . '" data-ft-node="' . $id . '">
                ' . $inner . '
              </div>';
    }
    return $id;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GeoGene - Family Tree</title>
<link rel="stylesheet" href="../assets/css/main.css?v=3">
<style>
  .phone{padding-bottom:0;overflow:visible;min-height:100dvh;}
  .content-scroll{padding-bottom:90px;}
  /* Pin this to the real device screen (not just the ".phone" box) so the
     page underneath can grow taller than one screen and scroll normally,
     while the bottom nav stays floating at the bottom the whole time. */
  .bottom-nav{position:fixed;bottom:14px;left:14px;right:14px;}
  .app-header{justify-content:space-between;}
  .filter-icon{font-size:16px;color:var(--text-main);cursor:pointer;}

  .tree-diagram{display:flex;flex-direction:column;align-items:center;gap:34px;min-width:340px;position:relative;z-index:1;}
  .tree-tier{display:flex;justify-content:center;align-items:flex-start;gap:22px;position:relative;flex-wrap:wrap;}
  .tree-side{display:flex;flex-direction:column;align-items:center;}
  .tree-group-title{font-size:9.5px;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:.04em;margin-bottom:8px;text-align:center;}
  .tree-couple{display:flex;align-items:center;gap:8px;}
  .tree-couple .plus{color:var(--text-muted);font-size:12px;font-weight:700;}

  .tree-node{
    display:flex;flex-direction:column;align-items:center;gap:6px;
    /* Was 66px - a longer name ("Mary Vie Balonga Piloton") had nowhere
       to go and either got clipped or spilled out of its card. Wider +
       explicit wrapping keeps every name fully readable inside its card,
       however long it is. */
    width:92px;text-align:center;position:relative;z-index:2;
  }
  .tree-avatar{
    width:44px;height:44px;border-radius:50%;flex-shrink:0;
    background:var(--blue-lighter);color:var(--text-muted);
    display:flex;align-items:center;justify-content:center;
    font-weight:700;font-size:15px;border:2px solid var(--border);box-shadow:0 1px 6px rgba(0,0,0,0.15);
  }
  .tree-node.self .tree-avatar{background:var(--primary);color:#fff;}
  .tree-name{
    font-size:10px;font-weight:600;color:var(--text-main);line-height:1.25;
    width:100%;overflow-wrap:break-word;word-break:break-word;white-space:normal;
  }
  .tree-role{font-size:8.5px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.02em;}

  .side-title{
    text-align:center;font-size:13px;font-weight:800;letter-spacing:.04em;
    color:var(--text-main);text-transform:uppercase;margin:4px 0 12px;
  }
  .relatives-block{margin-top:18px;padding-top:14px;border-top:1px dashed var(--border);}
  .relatives-title{font-size:11px;font-weight:800;color:var(--text-main);text-transform:uppercase;letter-spacing:.03em;}
  .relatives-sub{font-size:10px;color:var(--text-muted);margin:2px 0 12px;line-height:1.4;}
  .relatives-grid{justify-content:flex-start;}
  .tree-node.unmapped .tree-avatar{background:var(--bg-page);border-color:var(--border);color:var(--text-muted);}
  .tree-node.unmapped .tree-name{color:var(--text-muted);}
  /* Father's/Mother's OWN other partner - a separate branch, not part of
     the signed-in user's direct line, so it's colored distinctly (orange)
     rather than the usual green/blue, matching the approved design. */
  .tree-node.prev-partner .tree-avatar{background:rgba(245,158,11,0.18);color:#f59e0b;border-color:#f59e0b;}
  .tree-node.prev-partner .tree-name{color:#f59e0b;}
  .tree-group-title.prev-partner-title{color:#f59e0b;}
  a.tree-node{text-decoration:none;cursor:pointer;-webkit-tap-highlight-color:transparent;}
  a.tree-node.clickable .tree-avatar{transition:transform .12s ease, box-shadow .12s ease;}
  a.tree-node.clickable:hover .tree-avatar,
  a.tree-node.clickable:active .tree-avatar{transform:translateY(-2px);box-shadow:0 3px 10px rgba(0,0,0,0.22);}

  .back-arrow-btn{
    display:flex;align-items:center;justify-content:center;
    width:30px;height:30px;border-radius:50%;
    background:var(--blue-lighter);color:var(--text-main);
    flex-shrink:0;
  }

  .tree-connectors{position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;overflow:visible;z-index:0;}

  #treeWrap{
    background:var(--blue-light);border:1px solid var(--border);border-radius:20px;
    padding:20px 16px;box-shadow: 0 4px 5px rgba(0,0,0,0.14), 0 6px 10px rgba(0,0,0,0.12);
    /* A deep/wide tree (several generations, many siblings or aunts/uncles)
       can grow taller AND wider than the screen - this box scrolls on
       BOTH axes on its own, so the zoom bar and bottom nav stay put while
       the diagram underneath gets panned around. */
    overflow:auto;
    max-height:calc(100dvh - 230px);
    -webkit-overflow-scrolling:touch;
  }

  .zoom-bar {
    position: fixed; bottom: 105px; left: 50%; transform: translateX(-50%);
    background: var(--primary); color: #fff; border-radius: 20px;
    display: flex; align-items: center; gap: 14px; padding: 6px 16px;
    font-size: 13px; font-weight: 600; z-index: 10;
  }
  .zoom-bar button { background: none; border: none; color: #fff; font-size: 16px; cursor: pointer; padding: 0 4px; }

  .empty-state{text-align:center;padding:60px 16px;color:var(--text-secondary);font-size:13px;line-height:1.6;}
  .empty-state .e-icon{font-size:34px;margin-bottom:12px;}

  .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.35);display:none;align-items:center;justify-content:center;z-index:2000;}
  .modal-overlay.active{display:flex;}
  .filter-modal{
    background:var(--blue-light);border:1px solid rgba(255,255,255,0.08);border-radius:20px;padding:20px;backdrop-filter:blur(20px);color:var(--text-main);
    width:88%;max-width:320px;max-height:80vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.5);
  }
  .filter-modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;}
  .filter-modal-header h3{margin:0;font-size:16px;font-weight:700;}
  .filter-modal-header .close-x{cursor:pointer;font-size:16px;color:var(--text-muted);}
  .filter-section-label{font-size:12px;font-weight:700;color:var(--text-main);margin:14px 0 8px;}
  .filter-check-row{display:flex;align-items:center;gap:10px;padding:6px 0;font-size:13px;color:var(--text-main);}
  .filter-check-row input[type=checkbox]{width:16px;height:16px;accent-color:var(--green);}
  .filter-modal-actions{display:flex;gap:10px;margin-top:18px;}
  .filter-modal-actions button{flex:1;margin-top:0;}
  .e-state{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    text-align:center;

    min-height:300px; /* adjust kung kinahanglan */
}

.e-icon{
    margin-bottom:20px;
}

.e-icon svg,
.e-icon img{
    width:48px;
    height:48px;
}
</style>
</head>
<body class="<?= $isDark ? 'dark-bg' : 'light-bg' ?>">

<div class="phone">
  <div class="content-scroll">
    <div class="app-header">
      <span style="display:flex;align-items:center;gap:10px;">
        <?php if ($showBackArrow): ?>
          <a class="back-arrow-btn" href="<?= htmlspecialchars(ft_back_href()) ?>" title="Back">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor" width="16" height="16"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>
          </a>
        <?php endif; ?>
        <span>Family Tree</span>
      </span>
      <span class="filter-icon" onclick="openFilterModal()">⚙</span>
    </div>
    <?php if ($tree && !$isOwnTree): ?>
      <p style="font-size:11.5px;color:var(--text-muted);margin:-6px 0 10px;text-align:center;">
        Viewing <?= htmlspecialchars(ft_full_name($tree['self'])) ?>'s family tree
      </p>
    <?php endif; ?>

    <?php if (!$tree): ?>
      <div class="empty-state">
        <div class="e-icon"><svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" id="Layer_1" style="" version="1.1" viewBox="0 0 100.353 100.352" xml:space="preserve" preserveAspectRatio="xMidYMid meet"><g ><path d="M58.23,69.992l14.993-24.108c0.049-0.078,0.09-0.16,0.122-0.245c2.589-4.222,3.956-9.045,3.956-13.969   c0-14.772-12.018-26.79-26.79-26.79S23.72,16.898,23.72,31.67c0,4.925,1.369,9.75,3.96,13.975c0.03,0.074,0.065,0.146,0.107,0.216   l14.455,24.191c-11.221,1.586-18.6,6.2-18.6,11.797c0,6.935,11.785,12.366,26.829,12.366S77.3,88.783,77.3,81.849   C77.301,76.226,69.578,71.509,58.23,69.992z M30.373,44.294c-2.39-3.804-3.653-8.169-3.653-12.624   c0-13.118,10.672-23.79,23.791-23.79c13.118,0,23.79,10.672,23.79,23.79c0,4.457-1.263,8.822-3.652,12.624   c-0.05,0.08-0.091,0.163-0.124,0.249L54.685,70.01c-0.238,0.365-0.285,0.448-0.576,0.926l-4,6.432L30.507,44.564   C30.472,44.471,30.427,44.38,30.373,44.294z M50.472,91.215c-14.043,0-23.829-4.937-23.829-9.366c0-4.02,7.37-7.808,17.283-8.981   l4.87,8.151c0.269,0.449,0.751,0.726,1.274,0.73c0.004,0,0.009,0,0.013,0c0.518,0,1-0.268,1.274-0.708l5.12-8.232   C66.548,73.9,74.3,77.784,74.3,81.849C74.301,86.279,64.515,91.215,50.472,91.215z" style="fill:#10B981"/><path d="M60.213,31.67c0-5.371-4.37-9.741-9.741-9.741s-9.741,4.37-9.741,9.741s4.37,9.741,9.741,9.741   C55.843,41.411,60.213,37.041,60.213,31.67z M43.731,31.67c0-3.717,3.024-6.741,6.741-6.741s6.741,3.024,6.741,6.741   s-3.023,6.741-6.741,6.741S43.731,35.387,43.731,31.67z" style="fill:#10B981"/></g></svg></div>
        <p>Your account isn't linked to a Civil Registry record yet, so we can't show your family tree.
        Complete identity verification to unlock this.</p>
      </div>
    <?php else: ?>

      <?php if (!$isOwnTree): ?>
        <h2 class="side-title"><?= htmlspecialchars(ft_side_label($tree['self'], $ownRecord)) ?></h2>
      <?php endif; ?>

      <div id="treeWrap" style="position:relative;">
        <svg class="tree-connectors"></svg>
        <div class="tree-diagram">

          <?php if ($isOwnTree): ?>
            <!-- ================= HOME TREE =================
                 Standard top-down genealogy order: Grandparents (oldest)
                 at the TOP, their children (Father + Mother) in the
                 MIDDLE, and You + your Siblings (the youngest generation
                 shown here) at the BOTTOM - matching the approved layout
                 exactly, and matching how the staff tool already reads.
                 Aunts/uncles are intentionally NOT mixed into this view
                 anymore - they live on Father's/Mother's own "Side" page
                 instead (click Father or Mother to see them). -->
            <?php
              $ftEdges = [];
              $idFather = $idMother = $idSelf = null;
              $idPatGF = $idPatGM = $idMatGF = $idMatGM = null;
              $idSibs = [];
              $spouseNodes = [];
              $hasPaternal = $tree['paternal']['grandfather'] || $tree['paternal']['grandmother'];
              $hasMaternal = $tree['maternal']['grandfather'] || $tree['maternal']['grandmother'];
              // Each entry in 'shown' is a DOCUMENTED previous partner (a
              // marriage record, or a death record, is on file for them) -
              // there can be more than one. Anything in 'unshownChildren'
              // has no such documentation - just a shared, acknowledged
              // child - so that other parent is never drawn as a card.
              $fatherShownUnions = $tree['fatherUnions']['shown'];
              $motherShownUnions = $tree['motherUnions']['shown'];
            ?>

            <!-- TIER 1 (top): Grandparents - paternal couple left, maternal
                 couple right, each documented Previous Partner's own
                 parents flanking the outer edge on the matching side. -->
            <?php
              $anyFatherPrevGP = array_filter($fatherShownUnions, fn($u) => $u['parents']['grandfather'] || $u['parents']['grandmother']);
              $anyMotherPrevGP = array_filter($motherShownUnions, fn($u) => $u['parents']['grandfather'] || $u['parents']['grandmother']);
            ?>
            <?php if ($hasPaternal || $hasMaternal || $anyFatherPrevGP || $anyMotherPrevGP): ?>
              <div class="tree-tier">
                <?php foreach ($fatherShownUnions as $i => $u): if (!$u['parents']['grandfather'] && !$u['parents']['grandmother']) continue; ?>
                  <div class="tree-side">
                    <div class="tree-group-title prev-partner-title">Father's Previous Partner's Parents</div>
                    <div class="tree-couple">
                      <?php $gfId = ftNode($u['parents']['grandfather'], 'Grandfather', false, 3, 'grandparents', true, 'prev-partner'); ?>
                      <span class="plus">+</span>
                      <?php $gmId = ftNode($u['parents']['grandmother'], 'Grandmother', false, 3, 'grandparents', true, 'prev-partner'); ?>
                    </div>
                  </div>
                  <?php $fatherShownUnions[$i]['_gfId'] = $gfId; $fatherShownUnions[$i]['_gmId'] = $gmId; ?>
                <?php endforeach; ?>
                <?php if ($hasPaternal): ?>
                  <div class="tree-side">
                    <div class="tree-group-title">Paternal Grandparents</div>
                    <div class="tree-couple">
                      <?php $idPatGF = ftNode($tree['paternal']['grandfather'], 'Grandfather', false, 3, 'grandparents'); ?>
                      <span class="plus">+</span>
                      <?php $idPatGM = ftNode($tree['paternal']['grandmother'], 'Grandmother', false, 3, 'grandparents'); ?>
                    </div>
                  </div>
                <?php endif; ?>
                <?php if ($hasMaternal): ?>
                  <div class="tree-side">
                    <div class="tree-group-title">Maternal Grandparents</div>
                    <div class="tree-couple">
                      <?php $idMatGF = ftNode($tree['maternal']['grandfather'], 'Grandfather', false, 3, 'grandparents'); ?>
                      <span class="plus">+</span>
                      <?php $idMatGM = ftNode($tree['maternal']['grandmother'], 'Grandmother', false, 3, 'grandparents'); ?>
                    </div>
                  </div>
                <?php endif; ?>
                <?php foreach ($motherShownUnions as $i => $u): if (!$u['parents']['grandfather'] && !$u['parents']['grandmother']) continue; ?>
                  <div class="tree-side">
                    <div class="tree-group-title prev-partner-title">Mother's Previous Partner's Parents</div>
                    <div class="tree-couple">
                      <?php $gfId = ftNode($u['parents']['grandfather'], 'Grandfather', false, 3, 'grandparents', true, 'prev-partner'); ?>
                      <span class="plus">+</span>
                      <?php $gmId = ftNode($u['parents']['grandmother'], 'Grandmother', false, 3, 'grandparents', true, 'prev-partner'); ?>
                    </div>
                  </div>
                  <?php $motherShownUnions[$i]['_gfId'] = $gfId; $motherShownUnions[$i]['_gmId'] = $gmId; ?>
                <?php endforeach; ?>
              </div>
              <?php
                if ($idPatGF && $idPatGM) $ftEdges[] = ['type' => 'couple', 'a' => $idPatGF, 'b' => $idPatGM];
                if ($idMatGF && $idMatGM) $ftEdges[] = ['type' => 'couple', 'a' => $idMatGF, 'b' => $idMatGM];
                foreach ($fatherShownUnions as $u) { if (!empty($u['_gfId']) && !empty($u['_gmId'])) $ftEdges[] = ['type' => 'couple', 'a' => $u['_gfId'], 'b' => $u['_gmId']]; }
                foreach ($motherShownUnions as $u) { if (!empty($u['_gfId']) && !empty($u['_gmId'])) $ftEdges[] = ['type' => 'couple', 'a' => $u['_gfId'], 'b' => $u['_gmId']]; }
              ?>
            <?php endif; ?>

            <!-- TIER 2 (middle): Father + Mother as the main couple, with
                 each one's documented Previous Partner(s) (if any) just
                 outside them, orange-highlighted as a separate branch. -->
            <?php if ($tree['father'] || $tree['mother'] || $fatherShownUnions || $motherShownUnions): ?>
              <div class="tree-tier">
                <?php foreach ($fatherShownUnions as $i => $u): ?>
                  <?php $pid = ftNode($u['record'], "Father's Previous Partner" . (!empty($u['deceased']) ? ' (deceased)' : ''), false, 2, 'parents', true, 'prev-partner'); ?>
                  <?php $fatherShownUnions[$i]['_personId'] = $pid; ?>
                <?php endforeach; ?>

                <div class="tree-couple">
                  <?php $idFather = ftNode($tree['father'], 'Father', false, 2, 'parents'); ?>
                  <?php if ($tree['father'] && $tree['mother']): ?><span class="plus">+</span><?php endif; ?>
                  <?php $idMother = ftNode($tree['mother'], 'Mother', false, 2, 'parents'); ?>
                </div>

                <?php foreach ($motherShownUnions as $i => $u): ?>
                  <?php $pid = ftNode($u['record'], "Mother's Previous Partner" . (!empty($u['deceased']) ? ' (deceased)' : ''), false, 2, 'parents', true, 'prev-partner'); ?>
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

            <!-- TIER 3 (bottom of this section): You + Siblings + any
                 half-siblings (documented Previous Partner or not) +
                 Spouse(s). -->
            <div class="tree-tier">
              <?php
                // Half-siblings from a DOCUMENTED previous partner connect
                // to that specific couple (Father + that partner).
                // Half-siblings from an UNDOCUMENTED union (no partner
                // card anywhere) connect to the single parent alone.
                $halfSibEdges = [];
                foreach ($fatherShownUnions as $u) {
                    if (!$u['children']) continue;
                    $ids = [];
                    foreach ($u['children'] as $k) $ids[] = ftNode($k, 'Half-sibling', false, 1, 'siblings', true, 'prev-partner');
                    $halfSibEdges[] = ['parents' => array_values(array_filter([$idFather, $u['_personId'] ?? null])), 'kids' => $ids];
                }
              ?>
              <?php foreach ($tree['fatherUnions']['unshownChildren'] as $k): ?>
                <?php $uid = ftNode($k, 'Half-sibling', false, 1, 'siblings'); ?>
                <?php $halfSibEdges[] = ['parents' => array_values(array_filter([$idFather])), 'kids' => [$uid]]; ?>
              <?php endforeach; ?>

              <?php foreach ($tree['siblings'] as $sib): ?>
                <?php $idSibs[] = ftNode($sib, 'Sibling', false, 1, 'siblings'); ?>
              <?php endforeach; ?>
              <?php $idSelf = ftNode($tree['self'], 'You', true, 1, 'self'); ?>

              <?php foreach ($motherShownUnions as $u): ?>
                <?php if (!$u['children']) continue; ?>
                <?php $ids = []; ?>
                <?php foreach ($u['children'] as $k): ?>
                  <?php $ids[] = ftNode($k, 'Half-sibling', false, 1, 'siblings', true, 'prev-partner'); ?>
                <?php endforeach; ?>
                <?php $halfSibEdges[] = ['parents' => array_values(array_filter([$idMother, $u['_personId'] ?? null])), 'kids' => $ids]; ?>
              <?php endforeach; ?>
              <?php foreach ($tree['motherUnions']['unshownChildren'] as $k): ?>
                <?php $uid = ftNode($k, 'Half-sibling', false, 1, 'siblings'); ?>
                <?php $halfSibEdges[] = ['parents' => array_values(array_filter([$idMother])), 'kids' => [$uid]]; ?>
              <?php endforeach; ?>

              <?php foreach ($tree['allSpouses'] as $m): ?>
                <span class="plus" style="align-self:center;">+</span>
                <?php $spId = ftNode($m['record'], $m['role'], false, 1, 'spouse'); ?>
                <?php $spouseNodes[] = ['id' => $spId, 'record' => $m['record']]; ?>
              <?php endforeach; ?>
            </div>
            <?php
              foreach ($spouseNodes as $sp) $ftEdges[] = ['type' => 'couple', 'a' => $idSelf, 'b' => $sp['id']];
              // Father+Mother (ABOVE) -> You+Siblings (BELOW).
              $selfGenIds = array_values(array_filter(array_merge([$idSelf], $idSibs)));
              $parentIds = array_values(array_filter([$idFather, $idMother]));
              if ($parentIds && $selfGenIds) $ftEdges[] = ['type' => 'parent-child', 'parents' => $parentIds, 'children' => $selfGenIds];
              // Each half-sibling group (documented partner, or
              // undocumented/no card) -> its own children, as a separate
              // edge each.
              foreach ($halfSibEdges as $e) {
                  if ($e['parents'] && $e['kids']) $ftEdges[] = ['type' => 'parent-child', 'parents' => $e['parents'], 'children' => $e['kids']];
              }
            ?>

            <!-- Children (if any) - below You. -->
            <?php if ($tree['children']): ?>
              <div class="tree-tier">
                <?php $idKids = []; ?>
                <?php foreach ($tree['children'] as $child): ?>
                  <?php $idKids[] = ftNode($child, 'Child', false, 0, 'children'); ?>
                <?php endforeach; ?>
              </div>
              <?php
                $selfNameNorm = ft_normalize(ft_full_name($tree['self']));
                $kidsBySpouse = []; $kidsUnmatched = [];
                foreach ($tree['children'] as $i => $child) {
                    $otherParentName = ft_normalize($child['father_name'] ?? '') === $selfNameNorm
                        ? ft_normalize($child['mother_name'] ?? '') : ft_normalize($child['father_name'] ?? '');
                    $matchedSpouseId = null;
                    foreach ($spouseNodes as $sp) {
                        if ($otherParentName !== '' && ft_normalize(ft_full_name($sp['record'])) === $otherParentName) { $matchedSpouseId = $sp['id']; break; }
                    }
                    if ($matchedSpouseId) $kidsBySpouse[$matchedSpouseId][] = $idKids[$i];
                    else $kidsUnmatched[] = $idKids[$i];
                }
                foreach ($kidsBySpouse as $spId => $kidIds) $ftEdges[] = ['type' => 'parent-child', 'parents' => [$idSelf, $spId], 'children' => $kidIds];
                if ($kidsUnmatched) $ftEdges[] = ['type' => 'parent-child', 'parents' => [$idSelf], 'children' => $kidsUnmatched];
              ?>
            <?php endif; ?>

          <?php else: ?>
            <!-- ================= "SIDE" PAGE =================
                 Reached by clicking ANY relative (Father, Mother, a
                 sibling, a child, a grandparent...) - centers on THAT
                 person, with THEIR OWN siblings shown as Tito/Tita cards
                 around them, and a separate, non-clickable "Relatives"
                 pool below (same surname, not a confirmed relationship).
                 Every Tito/Tita card is itself clickable - click through
                 to THEIR side the same way, as deep as the data goes. -->
            <?php
              $ftEdges = [];
              $idCenter = ftNode($tree['self'], '', true, 1, 'self');
              $idSibs = [];
              if ($tree['siblings']) {
                  echo '<div class="tree-group-title" style="margin-top:4px;">Siblings</div>';
                  echo '<div class="tree-tier">';
                  foreach ($tree['siblings'] as $sib) $idSibs[] = ftNode($sib, 'Tito/Tita', false, 1, 'siblings');
                  echo '</div>';
                  $ftEdges[] = ['type' => 'parent-child', 'parents' => [$idCenter], 'children' => $idSibs];
              }
            ?>
          <?php endif; ?>

        </div>
      </div>

      <?php if ($tree['sameSurname']): ?>
        <div class="relatives-block">
          <div class="relatives-title">Other <?= htmlspecialchars($tree['self']['last_name'] ?? '') ?> Relatives on File</div>
          <div class="relatives-sub">Same surname only - not a confirmed relationship, so these cards are not clickable.</div>
          <div class="tree-tier relatives-grid">
            <?php foreach ($tree['sameSurname'] as $rel): ?>
              <?php ftNode($rel, 'Relative', false, 0, 'relatives', false); ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <script>window.__ftEdges = <?= json_encode($ftEdges) ?>;</script>

    <?php endif; ?>

  </div>

  <?php if ($tree): ?>
  <div class="zoom-bar">
    <button onclick="zoom(-10)">−</button>
    <span id="zoomLabel">100%</span>
    <button onclick="zoom(10)">+</button>
  </div>
  <?php endif; ?>

  <?php include __DIR__ . '/../includes/bottom-nav.php'; ?>
</div>

<div class="modal-overlay" id="filterModal">
  <div class="filter-modal">
    <div class="filter-modal-header">
      <h3>Filter Family Tree</h3>
      <span class="close-x" onclick="closeFilterModal()">✕</span>
    </div>

    <div class="filter-section-label">Generation</div>
    <label class="filter-check-row"><input type="checkbox" class="gen-check" value="3" checked> Grandparents</label>
    <label class="filter-check-row"><input type="checkbox" class="gen-check" value="2" checked> Parents</label>
    <label class="filter-check-row"><input type="checkbox" class="gen-check" value="1" checked> You / Siblings / Spouse</label>
    <label class="filter-check-row"><input type="checkbox" class="gen-check" value="0" checked> Children</label>

    <div class="filter-section-label">Relationship</div>
    <label class="filter-check-row"><input type="checkbox" class="rel-check" value="parents" checked> Parents</label>
    <label class="filter-check-row"><input type="checkbox" class="rel-check" value="grandparents" checked> Grandparents</label>
    <label class="filter-check-row"><input type="checkbox" class="rel-check" value="siblings" checked> Siblings</label>
    <label class="filter-check-row"><input type="checkbox" class="rel-check" value="children" checked> Children</label>

    <div class="filter-modal-actions">
      <button type="button" class="btn-outline" onclick="resetFilter()">Reset</button>
      <button type="button" class="btn-primary" onclick="applyFilter()">Apply Filter</button>
    </div>
  </div>
</div>

<script>
function openFilterModal() { document.getElementById('filterModal').classList.add('active'); }
function closeFilterModal() { document.getElementById('filterModal').classList.remove('active'); }

function drawFamilyTreeConnectors() {
  const container = document.getElementById('treeWrap');
  const svg = document.querySelector('.tree-connectors');
  if (!container || !svg || !window.__ftEdges) return;

  const containerRect = container.getBoundingClientRect();
  svg.setAttribute('width', container.scrollWidth);
  svg.setAttribute('height', container.scrollHeight);
  svg.innerHTML = '';

  const ns = 'http://www.w3.org/2000/svg';
  const STROKE = getComputedStyle(document.body).getPropertyValue('--text-muted').trim() || '#3a4a80';

  function nodeRect(id) {
    const el = container.querySelector('[data-ft-node="' + id + '"]');
    if (!el || el.offsetParent === null || el.style.display === 'none') return null;
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
    p.setAttribute('stroke-width', '2');
    p.setAttribute('fill', 'none');
    svg.appendChild(p);
  }

  window.__ftEdges.forEach(edge => {
    if (edge.type === 'couple') {
      const a = nodeRect(edge.a), b = nodeRect(edge.b);
      if (!a || !b) return;
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

      const originY = Math.max(...parentRects.map(r => r.bottom));
      const childTopY = Math.min(...childRects.map(r => r.top));
      const busY = originY + (childTopY - originY) / 2;

      // Bus spans however wide it needs to, to reach EVERY parent AND
      // every child - not just whichever side happens to have more than
      // one member (a generation of several siblings can sit on either
      // side of this edge, depending on the tier order).
      const allXs = parentRects.map(r => r.centerX).concat(childRects.map(r => r.centerX));
      const busLeft = Math.min(...allXs);
      const busRight = Math.max(...allXs);

      parentRects.forEach(p => drawPath(`M ${p.centerX} ${p.bottom} L ${p.centerX} ${busY}`));
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

function applyFilter() {
  const allowedGens = [...document.querySelectorAll('.gen-check:checked')].map(c => c.value);
  const allowedRels = [...document.querySelectorAll('.rel-check:checked')].map(c => c.value);

  document.querySelectorAll('.tree-node').forEach(node => {
    const gen = node.dataset.gen;
    const rel = node.dataset.rel;
    const genOk = rel === 'self' || rel === 'relatives' || allowedGens.includes(gen);
    const relOk = rel === 'self' || rel === 'spouse' || rel === 'relatives' || allowedRels.includes(rel);
    node.style.display = (genOk && relOk) ? '' : 'none';
  });

  closeFilterModal();
  drawFamilyTreeConnectors();
}

function resetFilter() {
  document.querySelectorAll('.gen-check, .rel-check').forEach(c => c.checked = true);
  document.querySelectorAll('.tree-node').forEach(node => node.style.display = '');
  drawFamilyTreeConnectors();
}

let zoomLevel = 100;
function zoom(delta) {
  zoomLevel = Math.max(50, Math.min(150, zoomLevel + delta));
  const label = document.getElementById('zoomLabel');
  if (label) label.textContent = zoomLevel + '%';
  const diagram = document.querySelector('.tree-diagram');
  if (diagram) diagram.style.transform = 'scale(' + (zoomLevel / 100) + ')';
  ftScheduleRedraw();
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', drawFamilyTreeConnectors);
} else {
  drawFamilyTreeConnectors();
}
window.addEventListener('load', drawFamilyTreeConnectors);
window.addEventListener('resize', ftScheduleRedraw);
</script>
</body>
</html>
