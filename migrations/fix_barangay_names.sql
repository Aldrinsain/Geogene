-- ============================================================
-- FIX: 3 sa atong "22 barangay" nga naa sa database dili diay
-- barangay sa Manolo Fortich - naka-sayop ang orihinal nga seed data:
--   "Baclayon"   -> barangay diay ni sa LANTAPAN, laing municipality
--   "Bantuanon"  -> barangay diay ni sa LANTAPAN, laing municipality
--   "Sinaysayan" -> barangay diay ni sa KITAOTAO, laing municipality
-- Mao nga ang tulo ka tinuod nga barangay sa Manolo Fortich nga NAWALA
-- sa atong listahan (Santiago, Sto. Niño, Tankulan/Poblacion) - dili
-- gyud sila ma-plot sa mapa bisan pa og sakto ang barangay text sa
-- civil registry record, kay wala man sila sa atong "barangays" table.
--
-- Gi-verify ni gikan sa opisyal nga listahan sa 22 barangay sa
-- Manolo Fortich (Wikipedia, PhilAtlas, atbp): Agusan Canyon, Alae,
-- Dahilayan, Dalirig, Damilag, Dicklum, Guilang-guilang, Kalugmanan,
-- Lindaban, Lingion, Lunocan, Maluko, Mambatangan, Mampayag,
-- Mantibugao, Minsuro, San Miguel, Sankanan, Santiago, Sto. Niño,
-- Tankulan (Pob.), Ticala.
--
-- Kini nga script kay safe i-re-run (ang UPDATE dili mo-apekto kung
-- na-apply na ang pag-usab kaniadto, kay wala nay row nga match sa
-- lumang ngalan).
-- ============================================================

-- Baclayon -> Santiago (coordinates: within-municipality estimate -
-- i-adjust lang if naa kay mas sakto nga GPS gikan sa LGU/barangay hall)
UPDATE barangays SET name = 'Santiago' WHERE name = 'Baclayon';

-- Bantuanon -> Sto. Niño (coordinates: within-municipality estimate)
UPDATE barangays SET name = 'Sto. Niño' WHERE name = 'Bantuanon';

-- Sinaysayan -> Tankulan (Pob.) - mao ni ang Poblacion/town center,
-- gi-set nato ang coordinates sa opisyal nga municipal center point.
UPDATE barangays
SET name = 'Tankulan (Pob.)', latitude = 8.3675, longitude = 124.8656
WHERE name = 'Sinaysayan';

-- Diclum -> Dicklum (mao ni ang mas komon nga spelling nga gigamit sa
-- Civil Registry data - dili ni matukma ang "Diclum" sa "Dicklum" nga
-- gi-type sa Excel/records, mao nga wala na-plot ang mga tawo diha)
UPDATE barangays SET name = 'Dicklum' WHERE name = 'Diclum';

-- Lingi-on -> Lingion (tangtangon ang hyphen para ma-match sa
-- naandan/opisyal nga spelling)
UPDATE barangays SET name = 'Lingion' WHERE name = 'Lingi-on';

-- Kung wala pa madala ang bag-o nga rename (e.g. fresh install nga wala
-- pa ni-agi sa daan nga sayop nga seed), siguradohon lang nga naa gyud
-- ang 22 ka tinuod nga barangay - i-insert lang kung wala pa:
INSERT INTO barangays (name)
SELECT 'Santiago' WHERE NOT EXISTS (SELECT 1 FROM barangays WHERE name = 'Santiago');
INSERT INTO barangays (name)
SELECT 'Sto. Niño' WHERE NOT EXISTS (SELECT 1 FROM barangays WHERE name = 'Sto. Niño');
INSERT INTO barangays (name)
SELECT 'Tankulan (Pob.)' WHERE NOT EXISTS (SELECT 1 FROM barangays WHERE name = 'Tankulan (Pob.)');
INSERT INTO barangays (name)
SELECT 'Dicklum' WHERE NOT EXISTS (SELECT 1 FROM barangays WHERE name = 'Dicklum');
INSERT INTO barangays (name)
SELECT 'Lingion' WHERE NOT EXISTS (SELECT 1 FROM barangays WHERE name = 'Lingion');

-- Siguradohon nga naay coordinates ang bag-ong/na-rename nga barangay
-- (kung wala pa - e.g. bag-ong INSERT gikan sa taas):
UPDATE barangays SET latitude = 8.3390, longitude = 124.8310 WHERE name = 'Santiago' AND latitude IS NULL;
UPDATE barangays SET latitude = 8.2980, longitude = 124.8720 WHERE name = 'Sto. Niño' AND latitude IS NULL;
UPDATE barangays SET latitude = 8.3675, longitude = 124.8656 WHERE name = 'Tankulan (Pob.)' AND latitude IS NULL;
UPDATE barangays SET latitude = 8.3750, longitude = 124.8450 WHERE name = 'Dicklum' AND latitude IS NULL;
UPDATE barangays SET latitude = 8.3290, longitude = 124.8520 WHERE name = 'Lingion' AND latitude IS NULL;
