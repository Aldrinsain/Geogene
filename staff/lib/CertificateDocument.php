<?php
/**
 * CertificateDocument
 * --------------------
 * Single source of truth for the FRONT + BACK bond-paper layout of the
 * official Municipal Forms (97 = Marriage, 103 = Death, 102 = Live Birth
 * temporary layout), built from the exact front/back Word templates
 * supplied for Marriage and Death.
 *
 * Used by BOTH:
 *   - civil-registry-scan.php  (Verify Scanned Information -> $editable = true,
 *     every value becomes a fillable input bound to cert[key])
 *   - civil-registry-print.php (Print Official Certificate Format -> $editable = false,
 *     values are shown as plain text, exactly as on the real form)
 *
 * Because both pages call the SAME functions below, the field layout,
 * wording, and numbering can never drift between "what staff fills in"
 * and "what gets printed/viewed" - edit here once and both follow.
 */

if (!function_exists('cert_document_css')) {
    function cert_document_css(): string
    {
        return <<<CSS
  .cert-page{background:#fff;color:#111;font-family:Georgia,'Times New Roman',serif;font-size:12.5px;}
  .cert-top{display:flex;justify-content:space-between;font-size:10.5px;margin-bottom:6px;color:#666;}
  .cert-head{text-align:center;margin-bottom:10px;}
  .cert-head .rp{font-size:11.5px;}
  .cert-head .off{font-size:11.5px;letter-spacing:.03em;}
  .cert-head h1{font-size:19px;letter-spacing:.04em;margin:5px 0 8px;text-transform:uppercase;}
  .cert-reg{display:flex;justify-content:space-between;border-bottom:2px solid #111;padding-bottom:5px;margin-bottom:10px;font-size:11.5px;flex-wrap:wrap;gap:6px;}

  .box-grid{display:grid;border:1px solid #333;border-top:none;}
  .box-row{display:grid;border-top:1px solid #333;}
  .box{border-left:1px solid #333;padding:5px 8px 6px;min-height:38px;}
  .box:first-child{border-left:none;}
  .box .bl{font-size:9px;text-transform:uppercase;letter-spacing:.03em;color:#555;}
  .box .bv{font-size:12.5px;font-weight:600;margin-top:2px;min-height:15px;}
  .box .bv.multi{display:flex;flex-wrap:wrap;gap:2px 6px;}

  .cert-section-title{font-weight:700;font-size:11.5px;margin:12px 0 0;text-transform:uppercase;background:#f2f2f2;border:1px solid #333;border-bottom:none;padding:4px 8px;}
  .cert-sig-row{display:flex;justify-content:space-between;margin-top:18px;font-size:10.5px;gap:20px;}
  .cert-sig-row div{flex:1;border-top:1px solid #333;text-align:center;padding-top:4px;}
  .temp-flag{background:#fff3cd;border:1px dashed #cc9a00;color:#7a5c00;font-size:10.5px;padding:5px 10px;margin-bottom:8px;text-align:center;}
  .affidavit-block{border:1px solid #333;border-top:none;padding:10px 12px;font-size:11.5px;line-height:1.9;}
  .affidavit-block .aff-title{text-align:center;font-weight:700;font-size:12.5px;text-transform:uppercase;background:#f2f2f2;border:1px solid #333;padding:5px;margin:12px -12px 10px;}
  .affidavit-block .aff-title:first-child{margin-top:-1px;}
  .blank-line{display:inline-block;border-bottom:1px solid #333;min-width:160px;padding:0 4px;}

  /* Editable-only look: fields become underlined fill-ins, same as the
     paper form, so the page still reads like a document, not a webform. */
  .cert-page.editable input[type=text]{
    border:none;border-bottom:1px dashed #999;background:transparent;
    font-family:inherit;font-size:inherit;font-weight:600;color:#151515;
    padding:2px 3px;min-width:90px;
  }
  .cert-page.editable input[type=text]:focus{outline:none;border-bottom:1.5px solid #1a5aa8;}
  .cert-page.editable .box .bv input[type=text]{width:100%;}
  .cert-page.editable .affidavit-block input[type=text]{min-width:140px;}
CSS;
    }
}

if (!function_exists('cert_confidence_map_store')) {
    /**
     * Tiny static store for the CURRENT "OLD CERTIFICATE MODE" confidence
     * map, so cert_field() can show a confidence badge WITHOUT every one
     * of its ~100 call sites inside cert_render_front()/cert_render_back()
     * having to be touched to pass it through. Call cert_set_confidence_map()
     * once right before rendering; empty/omitted = no badges, unchanged
     * behavior for normal scans and for the print page.
     */
    function cert_confidence_map_store(?array $map = null): array
    {
        static $stored = [];
        if ($map !== null) $stored = $map;
        return $stored;
    }
}

if (!function_exists('cert_set_confidence_map')) {
    function cert_set_confidence_map(array $map): void
    {
        cert_confidence_map_store($map);
    }
}

if (!function_exists('cert_field')) {
    /**
     * Renders one value: an editable <input name="cert[key]"> when
     * $editable is true, otherwise the plain saved text (or a blank
     * underline, matching the physical form, when there's nothing saved).
     *
     * When an "OLD CERTIFICATE MODE" confidence map has been set via
     * cert_set_confidence_map() and covers this $key, a small colored
     * badge (HIGH/MEDIUM/LOW confidence %) is shown next to the input so
     * staff instantly sees which auto-filled values are worth double-
     * checking. No map set (the normal case) = no badge, nothing changes.
     */
    function cert_field(array $d, string $key, bool $editable, string $minWidth = '140px'): string
    {
        $val = htmlspecialchars((string) ($d[$key] ?? ''));
        $input = $editable
            ? '<input type="text" name="cert[' . htmlspecialchars($key) . ']" value="' . $val . '" style="min-width:' . $minWidth . ';">'
            : ($val !== '' ? $val : '<span class="blank-line" style="min-width:' . $minWidth . ';">&nbsp;</span>');

        if (!$editable) return $input;

        $map = cert_confidence_map_store();
        if (empty($map[$key])) return $input;

        $info = $map[$key];
        $status = $info['status'] ?? 'MEDIUM';
        $colors = [
            'HIGH'   => ['#1a7a2e', '#e6f7ea'],
            'MEDIUM' => ['#8a6d00', '#fff6da'],
            'LOW'    => ['#a3271f', '#fde8e6'],
        ];
        [$fg, $bg] = $colors[$status] ?? $colors['MEDIUM'];
        $title = 'Confidence: ' . (int) ($info['confidence'] ?? 0) . '%';
        if (!empty($info['alternatives'])) {
            $title .= ' | Alternative OCR reading(s): ' . htmlspecialchars(implode(', ', $info['alternatives']));
        }
        $badge = '<span title="' . htmlspecialchars($title) . '" style="display:inline-block;font-size:9px;font-weight:700;'
            . 'padding:1px 5px;border-radius:8px;margin-left:4px;vertical-align:middle;color:' . $fg . ';background:' . $bg . ';">'
            . (int) ($info['confidence'] ?? 0) . '%</span>';

        return $input . $badge;
    }
}

if (!function_exists('cert_box')) {
    /** One bordered box: caption label on top + one or more cert_field()s below. */
    function cert_box(string $label, string $valueHtml, bool $multi = false): string
    {
        $cls = $multi ? ' multi' : '';
        return '<div class="box"><div class="bl">' . htmlspecialchars($label) . '</div><div class="bv' . $cls . '">' . $valueHtml . '</div></div>';
    }
}

if (!function_exists('cert_head')) {
    function cert_head(string $type, string $side): void
    {
        $formNo = $type === 'birth' ? '102' : ($type === 'marriage' ? '97' : '103');
        $revision = $type === 'birth' ? 'January 2007' : 'August 2016';
        $title = $type === 'birth' ? 'Live Birth' : ucfirst($type);
        echo '<div class="cert-top"><span>Municipal Form No. ' . $formNo . '<br>(Revised ' . $revision . ')</span>';
        echo '<span style="text-align:right;">' . ($side === 'front' ? 'To be accomplished in quadruplicate<br>using black ink' : 'Page 2 of 2 &mdash; Back') . '</span></div>';
        echo '<div class="cert-head"><div class="rp">Republic of the Philippines</div><div class="off">OFFICE OF THE CIVIL REGISTRAR GENERAL</div>';
        echo '<h1>Certificate of ' . $title . ($side === 'back' ? ' <span style="font-size:13px;">(continued)</span>' : '') . '</h1></div>';
    }
}

if (!function_exists('cert_render_front')) {
    function cert_render_front(string $type, array $d, bool $editable): void
    {
        cert_head($type, 'front');
        ?>

        <div class="cert-reg">
          <span>Province: <strong><?= cert_field($d, 'province', $editable, '110px') ?></strong> &nbsp;&nbsp; City/Municipality: <strong><?= cert_field($d, 'city_municipality', $editable, '140px') ?></strong></span>
          <span>Registry No.: <strong><?= cert_field($d, 'registry_number', $editable, '120px') ?></strong></span>
        </div>

        <?php if ($type === 'death'): ?>

          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:2fr 1fr 1fr 1fr;">
              <?= cert_box('1. Name', cert_field($d,'first_name',$editable,'80px').' '.cert_field($d,'middle_name',$editable,'80px').' '.cert_field($d,'last_name',$editable,'90px'), true) ?>
              <?= cert_box('2. Sex', cert_field($d,'sex',$editable,'60px')) ?>
              <?= cert_box('3. Date of Death', cert_field($d,'date_of_death',$editable,'100px')) ?>
              <?= cert_box('4. Date of Birth', cert_field($d,'date_of_birth',$editable,'100px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('5a. Age at Time of Death - If 1 Year or Above (Completed Years)', cert_field($d,'age_years',$editable,'70px')) ?>
              <?= cert_box('5b. If Under 1 Year (Months / Days)', cert_field($d,'age_months',$editable,'60px').' mo(s) '.cert_field($d,'age_days',$editable,'60px').' day(s)', true) ?>
              <?= cert_box('5c. If Under 24 Hrs (Hours / Min-Sec)', cert_field($d,'age_hours',$editable,'60px').' hr(s) '.cert_field($d,'age_minutes',$editable,'60px').' min/sec', true) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr;"><?= cert_box('6. Place of Death (Name of Hospital/Clinic/Institution/House No., St., Barangay, City/Municipality, Province)', cert_field($d,'place_of_death',$editable,'400px')) ?></div>
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('7. Civil Status (Single, Married, Widower, Widow, Annulled, Divorced)', cert_field($d,'civil_status',$editable,'110px')) ?>
              <?= cert_box('8. Religion/Religious Sect', cert_field($d,'religion',$editable,'110px')) ?>
              <?= cert_box('9. Citizenship', cert_field($d,'citizenship',$editable,'100px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr;"><?= cert_box('10. Residence (House No., St., Barangay, City/Municipality, Province, Country)', cert_field($d,'residence',$editable,'400px')) ?></div>
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('11. Occupation', cert_field($d,'occupation',$editable,'100px')) ?>
              <?= cert_box('12. Name of Father (First, Middle, Last)', cert_field($d,'father_name',$editable,'160px')) ?>
              <?= cert_box('13. Maiden Name of Mother (First, Middle, Last)', cert_field($d,'mother_maiden_name',$editable,'160px')) ?>
            </div>
          </div>

          <div class="cert-section-title">Medical Certificate &mdash; 19b. Causes of Death (if deceased is aged 8 days and over)</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:2fr 1fr;">
              <?= cert_box('I. Immediate cause: a.', cert_field($d,'immediate_cause',$editable,'400px')) ?>
              <?= cert_box('Interval Between Onset and Death', cert_field($d,'immediate_cause_interval',$editable,'120px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:2fr 1fr;">
              <?= cert_box('Antecedent cause: b.', cert_field($d,'antecedent_cause',$editable,'400px')) ?>
              <?= cert_box('Interval Between Onset and Death', cert_field($d,'antecedent_cause_interval',$editable,'120px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:2fr 1fr;">
              <?= cert_box('Underlying cause: c.', cert_field($d,'underlying_cause',$editable,'400px')) ?>
              <?= cert_box('Interval Between Onset and Death', cert_field($d,'underlying_cause_interval',$editable,'120px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr;"><?= cert_box('II. Other significant conditions contributing to death', cert_field($d,'other_conditions',$editable,'500px')) ?></div>
          </div>

          <div class="cert-section-title">19c. Maternal Condition <span style="font-weight:400;text-transform:none;">(if the deceased is female aged 15&ndash;49 years old)</span></div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr;"><?= cert_box('a. Pregnant not in labour / b. Pregnant, in labour / c. Less than 42 days after delivery / d. 42 days to 1 year after delivery / e. None', cert_field($d,'maternal_condition',$editable,'260px')) ?></div>
          </div>

          <div class="cert-section-title">19d. Death by External Causes / 20. Autopsy</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('a. Manner of Death (Homicide, Suicide, Accident, Legal Intervention, etc.)', cert_field($d,'manner_of_death',$editable,'150px')) ?>
              <?= cert_box('b. Place of Occurrence of External Cause (home, farm, factory, street, sea, etc.)', cert_field($d,'place_of_occurrence',$editable,'150px')) ?>
              <?= cert_box('20. Autopsy (Yes/No)', cert_field($d,'autopsy',$editable,'60px')) ?>
            </div>
          </div>

          <div class="cert-section-title">21. Attendant</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:2fr 1fr 1fr;">
              <?= cert_box('21a. Attendant (1. Private Physician / 2. Public Health Officer / 3. Hospital Authority / 4. None / 5. Others, Specify)', cert_field($d,'attendant_type',$editable,'220px')) ?>
              <?= cert_box('21b. If Attended, State Duration - From (mm/dd/yy)', cert_field($d,'attended_from',$editable,'90px')) ?>
              <?= cert_box('To (mm/dd/yy)', cert_field($d,'attended_to',$editable,'90px')) ?>
            </div>
          </div>

          <div class="cert-section-title">22. Certification of Death</div>
          <div class="affidavit-block" style="margin-bottom:10px;">
            <p style="margin:0 0 8px;">I hereby certify that the foregoing particulars are correct as near as same can be ascertained and I further certify that I <?= cert_field($d,'certifying_attended',$editable,'150px') ?> the deceased and that death occurred at <?= cert_field($d,'time_of_death',$editable,'90px') ?> am/pm on the date of death specified above.</p>
          </div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('Name in Print', cert_field($d,'certifying_physician',$editable,'180px')) ?>
              <?= cert_box('Title of Position', cert_field($d,'certifying_title',$editable,'150px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('Address', cert_field($d,'certifying_address',$editable,'220px')) ?>
              <?= cert_box('Date', cert_field($d,'certifying_date',$editable,'110px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('Reviewed By - Signature Over Printed Name of Health Officer', cert_field($d,'reviewed_by_health_officer',$editable,'220px')) ?>
              <?= cert_box('Date', cert_field($d,'reviewed_by_date',$editable,'110px')) ?>
            </div>
          </div>

          <div class="cert-section-title">23&ndash;25. Corpse Disposal &amp; Permits</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('23. Corpse Disposal (Burial, Cremation, Others - Specify)', cert_field($d,'corpse_disposal',$editable,'150px')) ?>
              <?= cert_box('24a. Burial/Cremation Permit No. / Date Issued', cert_field($d,'burial_permit_no',$editable,'120px').' &mdash; '.cert_field($d,'burial_permit_date',$editable,'100px'), true) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('24b. Transfer Permit No. / Date Issued', cert_field($d,'transfer_permit_no',$editable,'120px').' &mdash; '.cert_field($d,'transfer_permit_date',$editable,'100px'), true) ?>
              <?= cert_box('25. Name and Address of Cemetery/Crematory', cert_field($d,'cemetery_name',$editable,'260px')) ?>
            </div>
          </div>

          <div class="cert-section-title">26. Certification of Informant</div>
          <div class="affidavit-block" style="margin-bottom:10px;">
            <p style="margin:0;">I hereby certify that all information supplied are true and correct to my own knowledge and belief.</p>
          </div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr 1fr;">
              <?= cert_box('Name in Print', cert_field($d,'informant_name',$editable,'140px')) ?>
              <?= cert_box('Relationship to the Deceased', cert_field($d,'informant_relationship',$editable,'110px')) ?>
              <?= cert_box('Address', cert_field($d,'informant_address',$editable,'160px')) ?>
              <?= cert_box('Date', cert_field($d,'informant_date',$editable,'90px')) ?>
            </div>
          </div>

          <div class="cert-section-title">27&ndash;29. Prepared / Received / Registered</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('27. Prepared By - Name in Print', cert_field($d,'prepared_by',$editable,'140px')) ?>
              <?= cert_box('Title or Position', cert_field($d,'prepared_by_title',$editable,'110px')) ?>
              <?= cert_box('Date', cert_field($d,'prepared_by_date',$editable,'90px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('28. Received By - Name in Print', cert_field($d,'received_by',$editable,'140px')) ?>
              <?= cert_box('Title or Position', cert_field($d,'received_by_title',$editable,'110px')) ?>
              <?= cert_box('Date', cert_field($d,'received_by_date',$editable,'90px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('29. Registered At the Office of the Civil Registrar - Name in Print', cert_field($d,'registered_by',$editable,'160px')) ?>
              <?= cert_box('Title or Position', cert_field($d,'registered_by_title',$editable,'110px')) ?>
              <?= cert_box('Date', cert_field($d,'registered_by_date',$editable,'90px')) ?>
            </div>
          </div>

          <div class="cert-section-title">Remarks / Annotations (for LCRO/OCRG Use Only)</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr;"><?= cert_box('Remarks', cert_field($d,'remarks',$editable,'400px')) ?></div>
          </div>

        <?php elseif ($type === 'marriage'): ?>

          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <div class="box" style="text-align:center;"><div class="bl" style="font-size:11px;">HUSBAND</div></div>
              <div class="box" style="text-align:center;"><div class="bl" style="font-size:11px;">WIFE</div></div>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('1. Name', cert_field($d,'husband_first',$editable,'80px').' '.cert_field($d,'husband_middle',$editable,'80px').' '.cert_field($d,'husband_last',$editable,'90px'), true) ?>
              <?= cert_box('1. Name', cert_field($d,'wife_first',$editable,'80px').' '.cert_field($d,'wife_middle',$editable,'80px').' '.cert_field($d,'wife_last',$editable,'90px'), true) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('2a. Date of Birth / 2b. Age', cert_field($d,'husband_dob',$editable,'110px').' &mdash; '.cert_field($d,'husband_age',$editable,'40px').' y/o', true) ?>
              <?= cert_box('2a. Date of Birth / 2b. Age', cert_field($d,'wife_dob',$editable,'110px').' &mdash; '.cert_field($d,'wife_age',$editable,'40px').' y/o', true) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('3. Place of Birth', cert_field($d,'husband_pob_city',$editable,'110px').', '.cert_field($d,'husband_pob_province',$editable,'100px').', '.cert_field($d,'husband_pob_country',$editable,'90px'), true) ?>
              <?= cert_box('3. Place of Birth', cert_field($d,'wife_pob_city',$editable,'110px').', '.cert_field($d,'wife_pob_province',$editable,'100px').', '.cert_field($d,'wife_pob_country',$editable,'90px'), true) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('4a. Sex / 4b. Citizenship', cert_field($d,'husband_sex',$editable,'70px').' &mdash; '.cert_field($d,'husband_citizenship',$editable,'110px'), true) ?>
              <?= cert_box('4a. Sex / 4b. Citizenship', cert_field($d,'wife_sex',$editable,'70px').' &mdash; '.cert_field($d,'wife_citizenship',$editable,'110px'), true) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('5. Residence', cert_field($d,'husband_residence',$editable,'260px')) ?>
              <?= cert_box('5. Residence', cert_field($d,'wife_residence',$editable,'260px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('6. Religion / 7. Civil Status', cert_field($d,'husband_religion',$editable,'110px').' &mdash; '.cert_field($d,'husband_civil_status',$editable,'90px'), true) ?>
              <?= cert_box('6. Religion / 7. Civil Status', cert_field($d,'wife_religion',$editable,'110px').' &mdash; '.cert_field($d,'wife_civil_status',$editable,'90px'), true) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('8. Name of Father', cert_field($d,'husband_father_first',$editable,'80px').' '.cert_field($d,'husband_father_middle',$editable,'80px').' '.cert_field($d,'husband_father_last',$editable,'90px'), true) ?>
              <?= cert_box('8. Name of Father', cert_field($d,'wife_father_first',$editable,'80px').' '.cert_field($d,'wife_father_middle',$editable,'80px').' '.cert_field($d,'wife_father_last',$editable,'90px'), true) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('9. Citizenship of Father', cert_field($d,'husband_father_citizenship',$editable,'110px')) ?>
              <?= cert_box('9. Citizenship of Father', cert_field($d,'wife_father_citizenship',$editable,'110px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('10. Maiden Name of Mother', cert_field($d,'husband_mother_first',$editable,'80px').' '.cert_field($d,'husband_mother_middle',$editable,'80px').' '.cert_field($d,'husband_mother_last',$editable,'90px'), true) ?>
              <?= cert_box('10. Maiden Name of Mother', cert_field($d,'wife_mother_first',$editable,'80px').' '.cert_field($d,'wife_mother_middle',$editable,'80px').' '.cert_field($d,'wife_mother_last',$editable,'90px'), true) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('11. Citizenship of Mother', cert_field($d,'husband_mother_citizenship',$editable,'110px')) ?>
              <?= cert_box('11. Citizenship of Mother', cert_field($d,'wife_mother_citizenship',$editable,'110px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('12&ndash;13. Name of Person/Wali Who Gave Consent or Advice (Relationship)', cert_field($d,'husband_consent_name',$editable,'140px').' ('.cert_field($d,'husband_consent_relationship',$editable,'100px').')', true) ?>
              <?= cert_box('12&ndash;13. Name of Person/Wali Who Gave Consent or Advice (Relationship)', cert_field($d,'wife_consent_name',$editable,'140px').' ('.cert_field($d,'wife_consent_relationship',$editable,'100px').')', true) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('14. Residence of Person Who Gave Consent or Advice', cert_field($d,'husband_consent_residence',$editable,'220px')) ?>
              <?= cert_box('14. Residence of Person Who Gave Consent or Advice', cert_field($d,'wife_consent_residence',$editable,'220px')) ?>
            </div>
          </div>

          <div class="cert-section-title">15&ndash;17. Place, Date &amp; Time of Marriage</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:2fr 1fr 1fr;">
              <?= cert_box('15. Place of Marriage (Office of the/House of/Barangay of/Church of/Mosque of, City/Municipality, Province)', cert_field($d,'place_of_marriage',$editable,'260px')) ?>
              <?= cert_box('16. Date of Marriage', cert_field($d,'date_of_marriage',$editable,'110px')) ?>
              <?= cert_box('17. Time of Marriage (am/pm)', cert_field($d,'time_of_marriage',$editable,'100px')) ?>
            </div>
          </div>

          <div class="cert-section-title">18. Certification of the Contracting Parties</div>
          <div class="affidavit-block" style="margin-bottom:10px;">
            <p style="margin:0 0 8px;">THIS IS TO CERTIFY That I, <?= cert_field($d,'husband_first',$editable,'100px') ?> <?= cert_field($d,'husband_last',$editable,'100px') ?> and I, <?= cert_field($d,'wife_first',$editable,'100px') ?> <?= cert_field($d,'wife_last',$editable,'100px') ?>, both of legal age, of our own free will and accord, and in the presence of the person solemnizing this marriage and of the witnesses named below, take each other as husband and wife and certifying further that we <?= cert_field($d,'marriage_settlement',$editable,'220px') ?> a marriage settlement.</p>
            <p style="margin:0;">IN WITNESS WHEREOF, we have signed/marked with our fingerprint this certificate in quadruplicate this <?= cert_field($d,'marriage_signing_day',$editable,'220px') ?>.</p>
          </div>

          <div class="cert-section-title">19. Certification of the Solemnizing Officer</div>
          <div class="affidavit-block" style="margin-bottom:10px;">
            <p style="margin:0;">I CERTIFY FURTHER THAT (basis): <?= cert_field($d,'legal_basis',$editable,'260px') ?></p>
          </div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('19a. Marriage License No. / Issued On / At', cert_field($d,'marriage_license_no',$editable,'120px').' &mdash; '.cert_field($d,'license_issued_on',$editable,'100px').' at '.cert_field($d,'license_issued_at',$editable,'100px'), true) ?>
              <?= cert_box('Religion/Religious Sect, Registry No. and Expiration Date (if applicable)', cert_field($d,'solemnizing_officer_religion_registry',$editable,'220px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('Signature Over Printed Name of Solemnizing Officer', cert_field($d,'solemnizing_officer_name',$editable,'220px')) ?>
              <?= cert_box('Position/Designation', cert_field($d,'solemnizing_officer_position',$editable,'180px')) ?>
            </div>
          </div>

          <div class="cert-section-title">20a. Witnesses <span style="font-weight:400;text-transform:none;">(additional at the back)</span></div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('Witness 1', cert_field($d,'witness1',$editable,'180px')) ?>
              <?= cert_box('Witness 2', cert_field($d,'witness2',$editable,'180px')) ?>
            </div>
          </div>

          <div class="cert-section-title">21&ndash;22. Received / Registered</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('21. Received By - Name in Print', cert_field($d,'received_by',$editable,'160px')) ?>
              <?= cert_box('Title or Position', cert_field($d,'received_by_title',$editable,'120px')) ?>
              <?= cert_box('Date', cert_field($d,'received_by_date',$editable,'90px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('22. Registered At the Office of the Civil Registrar - Name in Print', cert_field($d,'registered_by',$editable,'160px')) ?>
              <?= cert_box('Title or Position', cert_field($d,'registered_by_title',$editable,'120px')) ?>
              <?= cert_box('Date', cert_field($d,'registered_by_date',$editable,'90px')) ?>
            </div>
          </div>

          <div class="cert-section-title">Remarks / Annotations <span style="font-weight:400;text-transform:none;">(For LCRO/OCRG/Shari'a Circuit Registrar Use Only)</span></div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr;"><?= cert_box('Remarks', cert_field($d,'remarks',$editable,'400px')) ?></div>
          </div>

          <div class="cert-sig-row">
            <div><?= cert_field($d,'husband_first',$editable,'100px') ?> <?= cert_field($d,'husband_last',$editable,'100px') ?><br>Signature of Husband</div>
            <div><?= cert_field($d,'wife_first',$editable,'100px') ?> <?= cert_field($d,'wife_last',$editable,'100px') ?><br>Signature of Wife</div>
          </div>

        <?php else: /* birth - Certificate of Live Birth */ ?>

          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr 1fr;">
              <?= cert_box('1. Name', cert_field($d,'child_first',$editable,'80px').' '.cert_field($d,'child_middle',$editable,'80px').' '.cert_field($d,'child_last',$editable,'90px'), true) ?>
              <?= cert_box('2. Sex (Male/Female)', cert_field($d,'child_sex',$editable,'70px')) ?>
              <?= cert_box('3. Date of Birth (Day/Month/Year)', cert_field($d,'date_of_birth',$editable,'100px')) ?>
              <?= cert_box('6. Weight at Birth', cert_field($d,'birth_weight',$editable,'70px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr;"><?= cert_box('4. Place of Birth (Name of Hospital/Clinic/Institution/House No., St., Barangay / City-Municipality / Province)', cert_field($d,'place_of_birth_hospital',$editable,'160px').', '.cert_field($d,'place_of_birth_city',$editable,'120px').', '.cert_field($d,'place_of_birth_province',$editable,'120px'), true) ?></div>
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('5a. Type of Birth (Single, Twin, Triplet, etc.)', cert_field($d,'type_of_birth',$editable,'110px')) ?>
              <?= cert_box('5b. If Multiple Birth, Child was (First, Second, Third, etc.)', cert_field($d,'birth_order',$editable,'110px')) ?>
              <?= cert_box('5c. Birth Order &mdash; Live Birth and Fetal Deaths Including this Delivery (First, Second, Third, etc.)', cert_field($d,'birth_order_overall',$editable,'110px')) ?>
            </div>
          </div>

          <div class="cert-section-title">Mother</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:2fr 1fr 1fr;">
              <?= cert_box('7. Maiden Name', cert_field($d,'mother_first',$editable,'80px').' '.cert_field($d,'mother_middle',$editable,'80px').' '.cert_field($d,'mother_last',$editable,'90px'), true) ?>
              <?= cert_box('8. Citizenship', cert_field($d,'mother_citizenship',$editable,'100px')) ?>
              <?= cert_box('9. Religion / Religious Sect', cert_field($d,'mother_religion',$editable,'100px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr 1fr 1fr;">
              <?= cert_box('10a. Total No. of Children Born Alive', cert_field($d,'mother_children_born_alive',$editable,'60px')) ?>
              <?= cert_box('10b. No. of Children Still Living, Including this Birth', cert_field($d,'mother_children_still_living',$editable,'60px')) ?>
              <?= cert_box('10c. No. of Children Born Alive but are Now Dead', cert_field($d,'mother_children_now_dead',$editable,'60px')) ?>
              <?= cert_box('11. Occupation', cert_field($d,'mother_occupation',$editable,'100px')) ?>
              <?= cert_box('12. Age at the Time of this Birth (Completed Years)', cert_field($d,'mother_age',$editable,'60px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr;"><?= cert_box('13. Residence (House No., St., Barangay / City-Municipality / Province / Country)', cert_field($d,'mother_residence',$editable,'400px')) ?></div>
          </div>

          <div class="cert-section-title">Father</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:2fr 1fr 1fr;">
              <?= cert_box('14. Name', cert_field($d,'father_first',$editable,'80px').' '.cert_field($d,'father_middle',$editable,'80px').' '.cert_field($d,'father_last',$editable,'90px'), true) ?>
              <?= cert_box('15. Citizenship', cert_field($d,'father_citizenship',$editable,'100px')) ?>
              <?= cert_box('16. Religion / Religious Sect', cert_field($d,'father_religion',$editable,'100px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('17. Occupation', cert_field($d,'father_occupation',$editable,'110px')) ?>
              <?= cert_box('18. Age at the Time of this Birth (Completed Years)', cert_field($d,'father_age',$editable,'70px')) ?>
              <?= cert_box('19. Residence (House No., St., Barangay / City-Municipality / Province / Country)', cert_field($d,'father_residence',$editable,'220px')) ?>
            </div>
          </div>

          <div class="cert-section-title">20. Marriage of Parents <span style="font-weight:400;text-transform:none;">(if not married, accomplish Affidavit of Acknowledgement/Admission of Paternity at the back)</span></div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('20a. Date of Marriage (Month/Day/Year)', cert_field($d,'parents_marriage_date',$editable,'120px')) ?>
              <?= cert_box('20b. Place of Marriage (City/Municipality / Province / Country)', cert_field($d,'parents_marriage_place',$editable,'200px')) ?>
            </div>
          </div>

          <div class="cert-section-title">21a. Attendant</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr;"><?= cert_box('1. Physician / 2. Nurse / 3. Midwife / 4. Hilot (Traditional Birth Attendant) / 5. Other (Specify)', cert_field($d,'attendant_type',$editable,'260px')) ?></div>
          </div>

          <div class="cert-section-title">21b. Certification of Attendant at Birth <span style="font-weight:400;text-transform:none;">(Physician, Nurse, Midwife, Hilot, Traditional Birth Attendant, Other (Specify))</span></div>
          <div class="affidavit-block" style="margin-bottom:10px;">
            <p style="margin:0;">I hereby certify that I attended the birth of the child who was born alive at <?= cert_field($d,'attendant_time_born',$editable,'90px') ?> am/pm on the date of birth specified above.</p>
          </div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('Name in Print', cert_field($d,'attendant_name',$editable,'180px')) ?>
              <?= cert_box('Title or Position', cert_field($d,'attendant_title',$editable,'150px')) ?>
            </div>
          </div>

          <div class="cert-section-title">22. Certification of Informant</div>
          <div class="affidavit-block" style="margin-bottom:10px;">
            <p style="margin:0;">I hereby certify that all information supplied are true and correct to my own knowledge and belief.</p>
          </div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr 1fr;">
              <?= cert_box('Name in Print', cert_field($d,'informant_name',$editable,'140px')) ?>
              <?= cert_box('Relationship to the Child', cert_field($d,'informant_relationship',$editable,'110px')) ?>
              <?= cert_box('Address', cert_field($d,'informant_address',$editable,'160px')) ?>
              <?= cert_box('Date', cert_field($d,'informant_date',$editable,'90px')) ?>
            </div>
          </div>

          <div class="cert-section-title">23. Prepared By</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr 1fr;">
              <?= cert_box('Name in Print', cert_field($d,'prepared_by',$editable,'140px')) ?>
              <?= cert_box('Title or Position', cert_field($d,'prepared_by_title',$editable,'110px')) ?>
              <?= cert_box('Address', cert_field($d,'prepared_by_address',$editable,'160px')) ?>
              <?= cert_box('Date', cert_field($d,'prepared_by_date',$editable,'90px')) ?>
            </div>
          </div>

          <div class="cert-section-title">Remarks / Annotations (for LCRO/OCRG use only)</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr;"><?= cert_box('Remarks', cert_field($d,'remarks',$editable,'400px')) ?></div>
          </div>

        <?php endif;
    }
}

if (!function_exists('cert_render_back')) {
    function cert_render_back(string $type, array $d, bool $editable): void
    {
        cert_head($type, 'back');

        if ($type === 'death'): ?>

          <div class="cert-section-title">For Children Aged 0 to 7 Days (if applicable)</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr 1fr;">
              <?= cert_box('14. Age of Mother', cert_field($d,'age_of_mother',$editable,'90px')) ?>
              <?= cert_box('15. Method of Delivery', cert_field($d,'method_of_delivery',$editable,'120px')) ?>
              <?= cert_box('16. Length of Pregnancy', cert_field($d,'length_of_pregnancy',$editable,'120px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('17. Type of Birth', cert_field($d,'type_of_birth_infant',$editable,'120px')) ?>
              <?= cert_box('18. If Multiple Birth, Child was', cert_field($d,'multiple_birth_infant',$editable,'120px')) ?>
            </div>
            <div class="box-row" style="grid-template-columns:1fr;">
              <div class="box">
                <div class="bl">19a. Causes of Death (infant)</div>
                <div class="bv multi">
                  a. Main disease/condition of infant: <?= cert_field($d,'infant_cause_a',$editable,'150px') ?><br>
                  b. Other diseases/conditions of infant: <?= cert_field($d,'infant_cause_b',$editable,'150px') ?><br>
                  c. Main maternal disease/condition affecting infant: <?= cert_field($d,'infant_cause_c',$editable,'150px') ?><br>
                  d. Other maternal disease/condition affecting infant: <?= cert_field($d,'infant_cause_d',$editable,'150px') ?><br>
                  e. Other relevant circumstances: <?= cert_field($d,'infant_cause_e',$editable,'150px') ?>
                </div>
              </div>
            </div>
          </div>

          <div class="affidavit-block">
            <div class="aff-title">Postmortem Certificate of Death</div>
            <p>I HEREBY CERTIFY that I have performed an autopsy upon the body of the deceased and that the cause of death was: <?= cert_field($d,'postmortem_cause_of_death',$editable,'380px') ?></p>
            <p>Signature: <?= cert_field($d,'postmortem_signature_name',$editable,'180px') ?> &nbsp; Title/Designation: <?= cert_field($d,'postmortem_title',$editable,'160px') ?></p>
            <p>Address: <?= cert_field($d,'postmortem_address',$editable,'260px') ?> &nbsp; Date: <?= cert_field($d,'postmortem_date',$editable,'110px') ?></p>

            <div class="aff-title">Certification of Embalmer</div>
            <p>I HEREBY CERTIFY that I have embalmed <?= cert_field($d,'embalmer_deceased_name',$editable,'220px') ?> following all the regulations prescribed by the Department of Health.</p>
            <p>Signature: <?= cert_field($d,'embalmer_signature_name',$editable,'180px') ?> &nbsp; Title/Designation: <?= cert_field($d,'embalmer_title',$editable,'150px') ?> &nbsp; License No.: <?= cert_field($d,'embalmer_license_no',$editable,'120px') ?></p>
            <p>Issued on: <?= cert_field($d,'embalmer_issued_on',$editable,'110px') ?> at <?= cert_field($d,'embalmer_issued_at',$editable,'140px') ?> &nbsp; Expiry Date: <?= cert_field($d,'embalmer_expiry_date',$editable,'110px') ?></p>

            <div class="aff-title">Affidavit for Delayed Registration of Death</div>
            <p>I, <?= cert_field($d,'affiant_name',$editable,'220px') ?>, of legal age, with residence and postal address <?= cert_field($d,'affiant_address',$editable,'220px') ?>, after being duly sworn in accordance with law, do hereby depose and say:</p>
            <p>1. That <?= cert_field($d,'delayed_deceased_name',$editable,'180px') ?> died on <?= cert_field($d,'date_died',$editable,'110px') ?> in <?= cert_field($d,'place_died',$editable,'140px') ?> and was buried/cremated in <?= cert_field($d,'burial_place',$editable,'140px') ?> on <?= cert_field($d,'burial_date',$editable,'110px') ?>.</p>
            <p>2. That the deceased at the time of his/her death: ☐ was attended by <?= cert_field($d,'attended_by_name',$editable,'160px') ?> &nbsp; ☐ was not attended.</p>
            <p>3. That the cause of death of the deceased was <?= cert_field($d,'delayed_cause_of_death',$editable,'220px') ?>.</p>
            <p>4. That the reason for the delay in registering this death was due to <?= cert_field($d,'delay_reason',$editable,'220px') ?>.</p>
            <p>5. That I am executing this affidavit to attest to the truthfulness of the foregoing statements for all legal intents and purposes.</p>
            <p style="margin-top:16px;">Signature Over Printed Name of Affiant: <?= cert_field($d,'affiant_name',$editable,'220px') ?></p>
            <p>SUBSCRIBED AND SWORN to before me this <?= cert_field($d,'subscribed_day',$editable,'160px') ?>, at <?= cert_field($d,'subscribed_place',$editable,'160px') ?>, affiant who exhibited to me his/her CTC/valid ID <?= cert_field($d,'id_exhibited',$editable,'160px') ?>.</p>
          </div>

        <?php elseif ($type === 'marriage'): ?>

          <div class="cert-section-title">20b. Witnesses (continued)</div>
          <div class="box-grid">
            <div class="box-row" style="grid-template-columns:1fr 1fr;">
              <?= cert_box('Witness 3', cert_field($d,'witness3',$editable,'180px')) ?>
              <?= cert_box('Witness 4', cert_field($d,'witness4',$editable,'180px')) ?>
            </div>
          </div>

          <div class="affidavit-block">
            <div class="aff-title" style="margin-top:0;">Affidavit of Solemnizing Officer</div>
            <p>I, <?= cert_field($d,'affidavit_officer_name',$editable,'200px') ?>, of legal age, Solemnizing Officer of <?= cert_field($d,'affidavit_officer_office',$editable,'160px') ?>, with address at <?= cert_field($d,'affidavit_officer_address',$editable,'180px') ?>, after having sworn to in accordance with law, do hereby depose and say:</p>
            <p>1. That I have solemnized the marriage between <?= cert_field($d,'husband_first',$editable,'90px') ?> <?= cert_field($d,'husband_last',$editable,'90px') ?> and <?= cert_field($d,'wife_first',$editable,'90px') ?> <?= cert_field($d,'wife_last',$editable,'90px') ?>;</p>
            <p>2. ☐ a. That I have ascertained the qualifications of the contracting parties and have found no legal impediment for them to marry as required by Article 34 of the Family Code;<br>
               ☐ b. That this marriage was performed in articulo mortis or at the point of death;<br>
               ☐ c. That the contracting party/ies &hellip; being at the point of death and physically unable to sign;<br>
               ☐ d. That the residence of either party is so located that there is no means of transportation to enable the party/parties to appear personally before the civil registrar;<br>
               ☐ e. That the marriage was among Muslims or among members of the Ethnic Cultural Communities and was solemnized in accordance with their customs and practices;</p>
            <p>Applicable condition (a&ndash;e) for this record: <?= cert_field($d,'affidavit_condition',$editable,'260px') ?></p>
            <p>3. That I took the necessary steps to ascertain the ages and relationship of the contracting parties and that neither of them are under any legal impediment to marry each other;</p>
            <p>4. That I am executing this affidavit to attest to the truthfulness of the foregoing statements for all legal intents and purposes.</p>
            <p style="margin-top:16px;">Signature Over Printed Name of the Solemnizing Officer: <?= cert_field($d,'affidavit_officer_name',$editable,'220px') ?></p>
            <p>SUBSCRIBED AND SWORN to before me this <?= cert_field($d,'affidavit_subscribed_day',$editable,'160px') ?>, at <?= cert_field($d,'affidavit_subscribed_place',$editable,'160px') ?>, affiant who exhibited to me his/her CTC/valid ID <?= cert_field($d,'affidavit_id_exhibited',$editable,'160px') ?>.</p>

            <div class="aff-title">Affidavit for Delayed Registration of Marriage</div>
            <p>I, <?= cert_field($d,'delayed_affiant_name',$editable,'220px') ?>, of legal age, with residence and postal address <?= cert_field($d,'delayed_affiant_address',$editable,'220px') ?>, after having duly sworn in accordance with law do hereby depose and say:</p>
            <p>1. That I am the applicant for the delayed registration of the marriage of/between <?= cert_field($d,'delayed_marriage_between',$editable,'220px') ?> in <?= cert_field($d,'delayed_marriage_place',$editable,'160px') ?> on <?= cert_field($d,'date_of_marriage',$editable,'110px') ?>.</p>
            <p>2. That said marriage was solemnized by <?= cert_field($d,'delayed_solemnized_by',$editable,'180px') ?> under: <?= cert_field($d,'delayed_ceremony_type',$editable,'160px') ?> ceremony.</p>
            <p>3. That the marriage was solemnized: with marriage license no. <?= cert_field($d,'marriage_license_no',$editable,'140px') ?> issued on <?= cert_field($d,'license_issued_on',$editable,'110px') ?> at <?= cert_field($d,'license_issued_at',$editable,'110px') ?>; or under Article <?= cert_field($d,'delayed_article_basis',$editable,'100px') ?> (marriages of exceptional character).</p>
            <p>4. Citizenship &mdash; Husband: <?= cert_field($d,'delayed_husband_citizenship',$editable,'140px') ?> &nbsp; Wife: <?= cert_field($d,'delayed_wife_citizenship',$editable,'140px') ?>.</p>
            <p>5. That the reason for the delay in registering our/their marriage is <?= cert_field($d,'delayed_reason',$editable,'220px') ?>.</p>
            <p>6. That I am executing this affidavit to attest to the truthfulness of the foregoing statements for all legal intents and purposes.</p>
            <p style="margin-top:16px;">Signature Over Printed Name of Affiant: <?= cert_field($d,'delayed_affiant_name',$editable,'220px') ?></p>
            <p>SUBSCRIBED AND SWORN to before me this <?= cert_field($d,'delayed_subscribed_day',$editable,'160px') ?>, at <?= cert_field($d,'delayed_subscribed_place',$editable,'160px') ?>.</p>
          </div>

        <?php else: /* birth - Certificate of Live Birth, page 2 */ ?>

          <div class="affidavit-block">
            <div class="aff-title" style="margin-top:0;">Affidavit of Acknowledgement / Admission of Paternity</div>
            <p>I/We, <?= cert_field($d,'ack_parent1_name',$editable,'200px') ?> and <?= cert_field($d,'ack_parent2_name',$editable,'200px') ?>, of legal age, am/are the natural mother and/or father of <?= cert_field($d,'ack_child_name',$editable,'200px') ?>, who was born on <?= cert_field($d,'ack_child_dob',$editable,'130px') ?> at <?= cert_field($d,'ack_child_pob',$editable,'180px') ?>.</p>
            <p>I am / We are executing this affidavit to attest to the truthfulness of the foregoing statements and for purposes of acknowledging my/our child.</p>
            <p style="margin-top:16px;">Signature Over Printed Name of Father: <?= cert_field($d,'ack_father_signature',$editable,'200px') ?></p>
            <p>Signature Over Printed Name of Mother: <?= cert_field($d,'ack_mother_signature',$editable,'200px') ?></p>
            <p>SUBSCRIBED AND SWORN to before me this <?= cert_field($d,'ack_subscribed_day',$editable,'160px') ?>, by/at <?= cert_field($d,'ack_subscribed_place',$editable,'160px') ?>, who exhibited to me (his/her) Community Tax Cert. No. <?= cert_field($d,'ack_ctc_no',$editable,'120px') ?> issued on <?= cert_field($d,'ack_ctc_issued_on',$editable,'100px') ?> at <?= cert_field($d,'ack_ctc_issued_at',$editable,'120px') ?>.</p>
            <p>Signature of the Administering Officer: <?= cert_field($d,'ack_admin_officer_name',$editable,'180px') ?> &nbsp; Position/Title: <?= cert_field($d,'ack_admin_officer_position',$editable,'160px') ?></p>

            <div class="aff-title">Affidavit for Delayed Registration of Birth</div>
            <p style="font-style:italic;font-size:10.5px;">(To be accomplished by the hospital/clinic administrator, father, mother, or guardian, or the person himself if 18 years old or over.)</p>
            <p>I, <?= cert_field($d,'delayed_affiant_name',$editable,'200px') ?>, of legal age, <?= cert_field($d,'delayed_affiant_civil_status',$editable,'110px') ?>, with residence and postal address at <?= cert_field($d,'delayed_affiant_address',$editable,'220px') ?>, after having duly sworn in accordance with law do hereby depose and say:</p>
            <p>1. That I am the applicant for the delayed registration of the birth of <?= cert_field($d,'delayed_birth_of',$editable,'180px') ?> in <?= cert_field($d,'delayed_birth_place',$editable,'160px') ?> on <?= cert_field($d,'delayed_birth_date',$editable,'110px') ?>.</p>
            <p>2. That I am/he/she was attended at birth by <?= cert_field($d,'delayed_attended_by',$editable,'160px') ?> who resides at <?= cert_field($d,'delayed_attended_by_address',$editable,'180px') ?>.</p>
            <p>3. That I am/he/she is a citizen of <?= cert_field($d,'delayed_citizenship',$editable,'140px') ?>.</p>
            <p>4. That my/his/her parents were <?= cert_field($d,'delayed_parents_marriage_status',$editable,'130px') ?>, married on <?= cert_field($d,'delayed_parents_marriage_date',$editable,'110px') ?> at <?= cert_field($d,'delayed_parents_marriage_place',$editable,'140px') ?>; if not married, acknowledged by <?= cert_field($d,'delayed_acknowledged_by',$editable,'160px') ?>.</p>
            <p>That the reason for the delay in registering my/his/her birth was <?= cert_field($d,'delayed_reason',$editable,'220px') ?>.</p>
            <p>(For the applicant only) That I am married to <?= cert_field($d,'delayed_applicant_spouse',$editable,'180px') ?>.</p>
            <p>That I am executing this affidavit to attest to the truthfulness of the foregoing statements for all legal intents and purposes.</p>
            <p style="margin-top:16px;">In truth whereof, I have affixed my signature below this <?= cert_field($d,'delayed_signed_day',$editable,'160px') ?>, at <?= cert_field($d,'delayed_signed_place',$editable,'160px') ?>, Philippines.</p>
            <p>SUBSCRIBED AND SWORN to before me this <?= cert_field($d,'delayed_subscribed_day',$editable,'160px') ?>, at <?= cert_field($d,'delayed_subscribed_place',$editable,'160px') ?>, Philippines, affiant who exhibited to me his Community Tax Cert. No. <?= cert_field($d,'delayed_ctc_no',$editable,'120px') ?> issued on <?= cert_field($d,'delayed_ctc_issued_on',$editable,'100px') ?> at <?= cert_field($d,'delayed_ctc_issued_at',$editable,'120px') ?>.</p>
            <p>Signature of the Administering Officer: <?= cert_field($d,'delayed_admin_officer_name',$editable,'180px') ?> &nbsp; Position/Title: <?= cert_field($d,'delayed_admin_officer_position',$editable,'160px') ?></p>
          </div>

        <?php endif;
    }
}
