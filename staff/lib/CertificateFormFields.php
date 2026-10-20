<?php
/**
 * CertificateFormFields
 * ----------------------
 * Single source of truth for the field layout of each official MCRO form:
 *   - birth    -> TEMPORARY placeholder layout (approximate Municipal Form No. 102 /
 *                 Certificate of Live Birth). Swap this out once the exact
 *                 birth-certificate images/text are provided - everything that
 *                 reads getSections('birth') will automatically follow.
 *   - marriage -> Municipal Form No. 97, Certificate of Marriage (Revised Aug 2016)
 *   - death    -> Municipal Form No. 103, Certificate of Death (Revised Aug 2016)
 *
 * Used by:
 *   - civil-registry-scan.php   (renders the "Verify Scanned Information" form)
 *   - civil-registry-print.php  (renders the exact-format printable certificate)
 *
 * Keeping the field list in ONE place means the scan-review inputs and the
 * printed certificate can never drift out of sync with each other.
 */

if (!function_exists('cert_form_sections')) {
    function cert_form_sections(string $type): array
    {
        $shared = [
            'title' => 'Registry Information',
            'fields' => [
                'registry_number'   => 'Registry No.',
                'page_no'           => 'Page No.',
                'province'          => 'Province',
                'city_municipality' => 'City / Municipality',
                'date_prepared'     => 'Date Prepared',
            ],
        ];
        // NOTE: the official Municipal Form (Birth 102 / Marriage 97 / Death
        // 103) has NO standalone "Barangay" box at the top - only
        // Province / City-Municipality / Registry No. "Barangay" only ever
        // appears INSIDE the Place of Birth / Residence address lines. We
        // still collect it below as a separate GeoGene-only system field
        // (not part of the original certificate) because the Geographic
        // Map feature groups records by barangay.
        $systemFields = [
            'title' => 'GeoGene System Field (not on the original certificate)',
            'fields' => [
                'barangay' => 'Barangay (used for the Geographic Map feature)',
            ],
        ];

        if ($type === 'birth') {
            return [
                $shared,
                $systemFields,
                [
                    'title' => '1-2. Name / Sex',
                    'fields' => [
                        'child_last'   => '1. Last Name',
                        'child_first'  => '1. First Name',
                        'child_middle' => '1. Middle Name',
                        'child_sex'    => '2. Sex (Male/Female)',
                    ],
                ],
                [
                    'title' => '3-4. Date & Place of Birth',
                    'fields' => [
                        'date_of_birth'           => '3. Date of Birth (Day/Month/Year)',
                        'place_of_birth_hospital' => '4. Place of Birth - Name of Hospital/Clinic/Institution/House No., St., Barangay',
                        'place_of_birth_city'     => '4. Place of Birth - City/Municipality',
                        'place_of_birth_province' => '4. Place of Birth - Province',
                    ],
                ],
                [
                    'title' => '5-6. Type / Order / Weight',
                    'fields' => [
                        'type_of_birth'         => '5a. Type of Birth (Single, Twin, Triplet, etc.)',
                        'birth_order'           => '5b. If Multiple Birth, Child was (First, Second, Third, etc.)',
                        'birth_order_overall'   => '5c. Birth Order - Live Birth and Fetal Deaths Including this Delivery (First, Second, Third, etc.)',
                        'birth_weight'          => '6. Weight at Birth',
                    ],
                ],
                [
                    'title' => 'Mother',
                    'fields' => [
                        'mother_last'        => '7. Maiden Name - Last Name',
                        'mother_first'       => '7. Maiden Name - First Name',
                        'mother_middle'      => '7. Maiden Name - Middle Name',
                        'mother_citizenship' => '8. Citizenship',
                        'mother_religion'    => '9. Religion / Religious Sect',
                        'mother_children_born_alive'  => '10a. Total Number of Children Born Alive',
                        'mother_children_still_living'=> '10b. No. of Children Still Living, Including this Birth',
                        'mother_children_now_dead'    => '10c. No. of Children Born Alive but are Now Dead',
                        'mother_occupation'  => '11. Occupation',
                        'mother_age'         => '12. Age at the Time of this Birth (Completed Years)',
                        'mother_residence'   => '13. Residence (House No., St., Barangay / City-Municipality / Province / Country)',
                    ],
                ],
                [
                    'title' => 'Father',
                    'fields' => [
                        'father_last'        => '14. Name - Last Name',
                        'father_first'       => '14. Name - First Name',
                        'father_middle'      => '14. Name - Middle Name',
                        'father_citizenship' => '15. Citizenship',
                        'father_religion'    => '16. Religion / Religious Sect',
                        'father_occupation'  => '17. Occupation',
                        'father_age'         => '18. Age at the Time of this Birth (Completed Years)',
                        'father_residence'   => '19. Residence (House No., St., Barangay / City-Municipality / Province / Country)',
                    ],
                ],
                [
                    'title' => '20. Marriage of Parents (if not married, accomplish Affidavit of Acknowledgement/Admission of Paternity at the back)',
                    'fields' => [
                        'parents_marriage_date'  => '20a. Date of Marriage (Month/Day/Year)',
                        'parents_marriage_place' => '20b. Place of Marriage (City/Municipality / Province / Country)',
                    ],
                ],
                [
                    'title' => '21. Attendant & Certification of Attendant at Birth',
                    'fields' => [
                        'attendant_type'      => '21a. Attendant (1. Physician / 2. Nurse / 3. Midwife / 4. Hilot (Traditional Birth Attendant) / 5. Other, Specify)',
                        'attendant_time_born' => '21b. "...born alive at ___" (Time, am/pm)',
                        'attendant_name'      => '21b. Name in Print',
                        'attendant_title'     => '21b. Title or Position',
                    ],
                ],
                [
                    'title' => '22. Certification of Informant',
                    'fields' => [
                        'informant_name'         => 'Name in Print',
                        'informant_relationship' => 'Relationship to the Child',
                        'informant_address'      => 'Address',
                        'informant_date'         => 'Date',
                    ],
                ],
                [
                    'title' => '23. Prepared By',
                    'fields' => [
                        'prepared_by'         => 'Name in Print',
                        'prepared_by_title'   => 'Title or Position',
                        'prepared_by_address' => 'Address',
                        'prepared_by_date'    => 'Date',
                    ],
                ],
                [
                    'title' => 'Remarks / Annotations (for LCRO/OCRG use only)',
                    'fields' => [
                        'remarks' => 'Remarks / Annotations',
                    ],
                ],
                // --- BACK bond-paper page (Certificate of Live Birth, page 2) ---
                [
                    'title' => 'Affidavit of Acknowledgement / Admission of Paternity',
                    'fields' => [
                        'ack_parent1_name'   => 'I/We (Parent 1 Name)',
                        'ack_parent2_name'   => 'and (Parent 2 Name)',
                        'ack_child_name'     => 'Natural Mother and/or Father of (Child Name)',
                        'ack_child_dob'      => 'Who was Born On',
                        'ack_child_pob'      => 'At (Place)',
                        'ack_father_signature' => 'Signature Over Printed Name of Father',
                        'ack_mother_signature' => 'Signature Over Printed Name of Mother',
                        'ack_subscribed_day'   => 'Subscribed and Sworn - Day/Month/Year',
                        'ack_subscribed_place' => 'Subscribed and Sworn - Place/By Whom',
                        'ack_ctc_no'           => 'Community Tax Cert. No.',
                        'ack_ctc_issued_on'    => 'CTC Issued On',
                        'ack_ctc_issued_at'    => 'CTC Issued At',
                        'ack_admin_officer_name'     => 'Signature of the Administering Officer',
                        'ack_admin_officer_position' => 'Position/Title/Designation',
                    ],
                ],
                [
                    'title' => 'Affidavit for Delayed Registration of Birth',
                    'fields' => [
                        'delayed_affiant_name'        => 'Affiant Name',
                        'delayed_affiant_civil_status'=> 'Affiant Civil Status (Single/Married/Divorced/Widow/Widower)',
                        'delayed_affiant_address'     => 'Affiant Residence and Postal Address',
                        'delayed_birth_of'            => 'Applicant for Delayed Registration of Birth Of',
                        'delayed_birth_place'         => 'Place of Birth',
                        'delayed_birth_date'          => 'Date of Birth',
                        'delayed_attended_by'         => 'Attended At Birth By',
                        'delayed_attended_by_address' => "Attendant's Address",
                        'delayed_citizenship'         => 'Citizen Of',
                        'delayed_parents_marriage_status' => "Parents' Marriage Status (Married/Not Married)",
                        'delayed_parents_marriage_date'   => 'Parents Married On',
                        'delayed_parents_marriage_place'  => 'Parents Married At',
                        'delayed_acknowledged_by'     => 'If Not Married, Acknowledged By (Father)',
                        'delayed_reason'              => 'Reason for the Delay in Registering this Birth',
                        'delayed_applicant_spouse'    => '(For the Applicant Only) Married To',
                        'delayed_signed_day'          => 'Signed This Day/Month/Year',
                        'delayed_signed_place'        => 'Signed At',
                        'delayed_subscribed_day'      => 'Subscribed and Sworn - Day/Month/Year',
                        'delayed_subscribed_place'    => 'Subscribed and Sworn - Place',
                        'delayed_ctc_no'              => 'Community Tax Cert. No.',
                        'delayed_ctc_issued_on'       => 'CTC Issued On',
                        'delayed_ctc_issued_at'       => 'CTC Issued At',
                        'delayed_admin_officer_name'     => 'Signature of the Administering Officer',
                        'delayed_admin_officer_position' => 'Position/Title/Designation',
                    ],
                ],
            ];
        }

        if ($type === 'marriage') {
            $person = function (string $prefix): array {
                return [
                    "{$prefix}_last"    => 'Last Name',
                    "{$prefix}_first"   => 'First Name',
                    "{$prefix}_middle"  => 'Middle Name',
                    "{$prefix}_dob"     => '2a. Date of Birth (Day/Month/Year)',
                    "{$prefix}_age"     => '2b. Age',
                    "{$prefix}_pob_city" => '3. Place of Birth - City/Municipality',
                    "{$prefix}_pob_province" => '3. Place of Birth - Province',
                    "{$prefix}_pob_country"  => '3. Place of Birth - Country',
                    "{$prefix}_sex"          => '4a. Sex',
                    "{$prefix}_citizenship"  => '4b. Citizenship',
                    "{$prefix}_residence"    => '5. Residence (House No., St., Barangay, City/Municipality, Province, Country)',
                    "{$prefix}_religion"     => '6. Religion / Religious Sect',
                    "{$prefix}_civil_status" => '7. Civil Status',
                    "{$prefix}_father_last"   => "8. Name of Father - Last Name",
                    "{$prefix}_father_first"  => "8. Name of Father - First Name",
                    "{$prefix}_father_middle" => "8. Name of Father - Middle Name",
                    "{$prefix}_father_citizenship" => '9. Citizenship of Father',
                    "{$prefix}_mother_last"   => "10. Maiden Name of Mother - Last Name",
                    "{$prefix}_mother_first"  => "10. Maiden Name of Mother - First Name",
                    "{$prefix}_mother_middle" => "10. Maiden Name of Mother - Middle Name",
                    "{$prefix}_mother_citizenship" => '11. Citizenship of Mother',
                    "{$prefix}_consent_name"  => '12. Name of Person/Wali Who Gave Consent or Advice',
                    "{$prefix}_consent_relationship" => '13. Relationship',
                    "{$prefix}_consent_residence"    => '14. Residence of Person Who Gave Consent or Advice',
                ];
            };

            return [
                $shared,
                $systemFields,
                ['title' => '1. Husband', 'fields' => $person('husband')],
                ['title' => '2. Wife',    'fields' => $person('wife')],
                [
                    'title' => '15-17. Place, Date & Time of Marriage',
                    'fields' => [
                        'place_of_marriage'   => '15. Place of Marriage (Office of the/House of/Barangay of/Church of/Mosque of, City/Municipality, Province)',
                        'date_of_marriage'    => '16. Date of Marriage (Day/Month/Year)',
                        'time_of_marriage'    => '17. Time of Marriage (am/pm)',
                    ],
                ],
                [
                    'title' => '18. Certification of the Contracting Parties',
                    'fields' => [
                        'marriage_settlement' => 'Marriage Settlement (Entered / Not Entered into a Marriage Settlement)',
                        'marriage_signing_day'=> 'Signed/Marked with Fingerprint this Day of___,___ (Date Signed)',
                    ],
                ],
                [
                    'title' => '19. Certification of the Solemnizing Officer',
                    'fields' => [
                        'legal_basis'            => 'Basis (a. Marriage License / b. No License Necessary, Art.___ of EO 209 / c. Solemnized under PD 1083)',
                        'marriage_license_no'    => '19a. Marriage License No.',
                        'license_issued_on'      => '19a. License Issued On',
                        'license_issued_at'      => '19a. License Issued At',
                        'solemnizing_officer_name'     => 'Signature Over Printed Name of Solemnizing Officer',
                        'solemnizing_officer_position' => 'Position / Designation',
                        'solemnizing_officer_religion_registry' => 'Religion/Religious Sect, Registry No. and Expiration Date (if applicable)',
                    ],
                ],
                [
                    'title' => '20a-20b. Witnesses',
                    'fields' => [
                        'witness1' => 'Witness 1',
                        'witness2' => 'Witness 2',
                        'witness3' => 'Witness 3 (at the back)',
                        'witness4' => 'Witness 4 (at the back)',
                    ],
                ],
                [
                    'title' => '21-22. Received / Registered',
                    'fields' => [
                        'received_by'        => '21. Name in Print (Received By)',
                        'received_by_title'  => '21. Title or Position',
                        'received_by_date'   => '21. Date',
                        'registered_by'       => '22. Name in Print (Registered At the Office of the Civil Registrar)',
                        'registered_by_title' => '22. Title or Position',
                        'registered_by_date'  => '22. Date',
                    ],
                ],
                [
                    'title' => 'Remarks / Annotations (For LCRO/OCRG/Shari\'a Circuit Registrar Use Only)',
                    'fields' => [
                        'remarks' => 'Remarks / Annotations',
                    ],
                ],
                // --- BACK bond-paper page (Municipal Form 97, page 2) ---
                [
                    'title' => 'Affidavit of Solemnizing Officer',
                    'fields' => [
                        'affidavit_officer_name'    => 'Solemnizing Officer (Affiant)',
                        'affidavit_officer_office'  => 'Office/Church/Mosque of',
                        'affidavit_officer_address' => 'Address',
                        'affidavit_condition'       => 'Applicable Condition (a-e, Art. 34 Family Code etc.)',
                        'affidavit_subscribed_day'  => 'Subscribed - Day/Month/Year',
                        'affidavit_subscribed_place'=> 'Subscribed - Place',
                        'affidavit_id_exhibited'    => 'CTC/Valid ID Exhibited',
                    ],
                ],
                [
                    'title' => 'Affidavit for Delayed Registration of Marriage',
                    'fields' => [
                        'delayed_affiant_name'      => 'Affiant Name',
                        'delayed_affiant_address'   => 'Affiant Residence/Postal Address',
                        'delayed_marriage_between'  => 'Marriage Of / Between',
                        'delayed_marriage_place'    => 'Where Marriage Took Place',
                        'delayed_solemnized_by'     => 'Solemnized By',
                        'delayed_ceremony_type'     => 'Ceremony Type (Religious/Civil/Muslim/Tribal)',
                        'delayed_article_basis'     => 'Article Basis (if no license)',
                        'delayed_husband_citizenship' => "Husband's Citizenship",
                        'delayed_wife_citizenship'  => "Wife's Citizenship",
                        'delayed_reason'            => 'Reason for Delay',
                        'delayed_subscribed_day'    => 'Subscribed - Day/Month/Year',
                        'delayed_subscribed_place'  => 'Subscribed - Place',
                    ],
                ],
            ];
        }

        // death
        return [
            $shared,
            $systemFields,
            [
                'title' => '1. Name of Deceased',
                'fields' => [
                    'last_name'  => 'Last Name',
                    'first_name' => 'First Name',
                    'middle_name'=> 'Middle Name',
                    'sex'        => '2. Sex (Male/Female)',
                    'civil_status' => '7. Civil Status (Single, Married, Widower, Widow, Annulled, Divorced)',
                ],
            ],
            [
                'title' => '3-5. Death, Birth & Age Details',
                'fields' => [
                    'date_of_death' => '3. Date of Death (Day/Month/Year)',
                    'date_of_birth' => '4. Date of Birth (Day/Month/Year)',
                    'age_years'   => '5a. If 1 Year or Above - Completed Years',
                    'age_months'  => '5b. If Under 1 Year - Months',
                    'age_days'    => '5b. If Under 1 Year - Days',
                    'age_hours'   => '5c. If Under 24 Hrs - Hours',
                    'age_minutes' => '5c. If Under 24 Hrs - Min/Sec',
                    'place_of_death' => '6. Place of Death (Name of Hospital/Clinic/Institution/House No., St., Barangay, City/Municipality, Province)',
                ],
            ],
            [
                'title' => '8-13. Personal Information',
                'fields' => [
                    'religion'    => '8. Religion / Religious Sect',
                    'citizenship' => '9. Citizenship',
                    'residence'   => '10. Residence (House No., St., Barangay, City/Municipality, Province, Country)',
                    'occupation'  => '11. Occupation',
                    'father_name' => '12. Name of Father (First, Middle, Last)',
                    'mother_maiden_name' => '13. Maiden Name of Mother (First, Middle, Last)',
                ],
            ],
            [
                'title' => 'Medical Certificate - 19b. Causes of Death (Aged 8 Days and Over)',
                'fields' => [
                    'immediate_cause'  => 'I(a). Immediate Cause',
                    'immediate_cause_interval'  => 'I(a). Interval Between Onset and Death',
                    'antecedent_cause' => 'I(b). Antecedent Cause',
                    'antecedent_cause_interval' => 'I(b). Interval Between Onset and Death',
                    'underlying_cause' => 'I(c). Underlying Cause',
                    'underlying_cause_interval' => 'I(c). Interval Between Onset and Death',
                    'other_conditions' => 'II. Other Significant Conditions Contributing to Death',
                ],
            ],
            [
                'title' => '19c. Maternal Condition (if Deceased is Female Aged 15-49)',
                'fields' => [
                    'maternal_condition' => '19c. Maternal Condition (Pregnant not in Labour / Pregnant, in Labour / Less than 42 Days After Delivery / 42 Days to 1 Year After Delivery / None)',
                ],
            ],
            [
                'title' => '19d-20. Death by External Causes / Autopsy',
                'fields' => [
                    'manner_of_death'      => '19d(a). Manner of Death (Homicide, Suicide, Accident, Legal Intervention, etc.)',
                    'place_of_occurrence'  => '19d(b). Place of Occurrence of External Cause (Home, Farm, Factory, Street, Sea, etc.)',
                    'autopsy' => '20. Autopsy (Yes/No)',
                ],
            ],
            [
                'title' => '21. Attendant',
                'fields' => [
                    'attendant_type' => '21a. Attendant (1. Private Physician / 2. Public Health Officer / 3. Hospital Authority / 4. None / 5. Others, Specify)',
                    'attended_from'  => '21b. If Attended, State Duration - From (mm/dd/yy)',
                    'attended_to'    => '21b. If Attended, State Duration - To (mm/dd/yy)',
                ],
            ],
            [
                'title' => '22. Certification of Death',
                'fields' => [
                    'certifying_attended'  => 'Have Attended / Have Not Attended the Deceased',
                    'time_of_death'        => 'Death Occurred At (Time, am/pm)',
                    'certifying_physician' => 'Name in Print',
                    'certifying_title'     => 'Title of Position',
                    'certifying_address'   => 'Address',
                    'certifying_date'      => 'Date',
                    'reviewed_by_health_officer' => 'Reviewed By - Signature Over Printed Name of Health Officer',
                    'reviewed_by_date'           => 'Reviewed By - Date',
                ],
            ],
            [
                'title' => '23-25. Disposal & Permits',
                'fields' => [
                    'corpse_disposal'   => '23. Corpse Disposal (Burial, Cremation, Others - Specify)',
                    'burial_permit_no'  => '24a. Burial/Cremation Permit No.',
                    'burial_permit_date'=> '24a. Date Issued',
                    'transfer_permit_no'   => '24b. Transfer Permit No.',
                    'transfer_permit_date' => '24b. Date Issued',
                    'cemetery_name'     => '25. Name and Address of Cemetery/Crematory',
                ],
            ],
            [
                'title' => '26-29. Informant & Registration',
                'fields' => [
                    'informant_name'         => '26. Name in Print (Informant)',
                    'informant_relationship' => '26. Relationship to the Deceased',
                    'informant_address'      => '26. Address',
                    'informant_date'         => '26. Date',
                    'prepared_by'            => '27. Name in Print (Prepared By)',
                    'prepared_by_title'      => '27. Title or Position',
                    'prepared_by_date'       => '27. Date',
                    'received_by'            => '28. Name in Print (Received By)',
                    'received_by_title'      => '28. Title or Position',
                    'received_by_date'       => '28. Date',
                    'registered_by'          => '29. Name in Print (Registered At the Office of the Civil Registrar)',
                    'registered_by_title'    => '29. Title or Position',
                    'registered_by_date'     => '29. Date',
                ],
            ],
            [
                'title' => 'Remarks / Annotations (for LCRO/OCRG Use Only)',
                'fields' => [
                    'remarks' => 'Remarks / Annotations',
                ],
            ],
            // --- BACK bond-paper page (Municipal Form 103, page 2) ---
            [
                'title' => 'For Children Aged 0-7 Days (Back Page)',
                'fields' => [
                    'age_of_mother'        => '14. Age of Mother',
                    'method_of_delivery'   => '15. Method of Delivery (Normal Spontaneous Vertex, if Others Specify)',
                    'length_of_pregnancy'  => '16. Length of Pregnancy (in Completed Weeks)',
                    'type_of_birth_infant' => '17. Type of Birth (Single, Twin, Triplet, etc.)',
                    'multiple_birth_infant'=> '18. If Multiple Birth, Child was (First, Second, Third, etc.)',
                    'infant_cause_a'       => '19a(a). Main Disease/Condition of Infant',
                    'infant_cause_b'       => '19a(b). Other Diseases/Conditions of Infant',
                    'infant_cause_c'       => '19a(c). Main Maternal Disease/Condition Affecting Infant',
                    'infant_cause_d'       => '19a(d). Other Maternal Disease/Condition Affecting Infant',
                    'infant_cause_e'       => '19a(e). Other Relevant Circumstances',
                ],
            ],
            [
                'title' => 'Postmortem Certificate of Death',
                'fields' => [
                    'postmortem_cause_of_death' => 'Cause of Death (Autopsy Finding)',
                    'postmortem_signature_name' => 'Name in Print',
                    'postmortem_title'          => 'Title/Designation',
                    'postmortem_address'        => 'Address',
                    'postmortem_date'           => 'Date',
                ],
            ],
            [
                'title' => 'Certification of Embalmer',
                'fields' => [
                    'embalmer_deceased_name' => 'Name of Person Embalmed',
                    'embalmer_signature_name'=> 'Name in Print',
                    'embalmer_title'         => 'Title/Designation',
                    'embalmer_license_no'    => 'License No.',
                    'embalmer_issued_on'     => 'Issued On',
                    'embalmer_issued_at'     => 'Issued At',
                    'embalmer_expiry_date'   => 'Expiry Date',
                ],
            ],
            [
                'title' => 'Affidavit for Delayed Registration of Death',
                'fields' => [
                    'affiant_name'          => 'Affiant Name',
                    'affiant_address'       => 'Affiant Residence/Postal Address',
                    'delayed_deceased_name' => 'Name of Deceased',
                    'date_died'             => 'Date Died',
                    'place_died'            => 'Place Died',
                    'burial_place'          => 'Buried/Cremated At',
                    'burial_date'           => 'Burial/Cremation Date',
                    'attended_by_name'      => 'Attended By',
                    'delayed_cause_of_death'=> 'Cause of Death',
                    'delay_reason'          => 'Reason for Delay',
                    'subscribed_day'        => 'Subscribed - Day/Month/Year',
                    'subscribed_place'      => 'Subscribed - Place',
                    'id_exhibited'          => 'CTC/Valid ID Exhibited',
                ],
            ],
        ];
    }
}

if (!function_exists('cert_flatten_fields')) {
    /** All field keys => labels for a type, ignoring section grouping. */
    function cert_flatten_fields(string $type): array
    {
        $flat = [];
        foreach (cert_form_sections($type) as $section) {
            $flat += $section['fields'];
        }
        return $flat;
    }
}

if (!function_exists('cert_map_to_legacy_columns')) {
    /**
     * Maps the detailed certificate_data fields onto the existing (simpler)
     * civil_registry_records columns, so the record list, search, family
     * tree, and map view - which all read first_name/last_name/father_name/
     * mother_name/etc. directly - keep working exactly as before, whether a
     * record was entered via the plain Add Record form or via Scan & Upload.
     */
    function cert_map_to_legacy_columns(string $type, array $d): array
    {
        if ($type === 'birth') {
            return [
                'first_name'  => $d['child_first']  ?? '',
                'middle_name' => $d['child_middle'] ?? '',
                'last_name'   => $d['child_last']   ?? '',
                'sex'         => strtolower($d['child_sex'] ?? '') === 'female' ? 'female' : 'male',
                'civil_status'=> null,
                'date_of_event' => $d['date_of_birth'] ?? null,
                // Place of birth is rebuilt from its three boxed columns
                // (hospital/barangay, city/municipality, province) so the
                // record's Place field fills in from the scan too, instead
                // of being left for staff to retype.
                'place_of_event' => implode(', ', array_filter([
                    trim((string) ($d['place_of_birth_hospital'] ?? '')),
                    trim((string) ($d['place_of_birth_city'] ?? '')),
                    trim((string) ($d['place_of_birth_province'] ?? '')),
                ], fn($v) => $v !== '')),
                'father_name' => formatFullName($d['father_last'] ?? '', $d['father_first'] ?? '', $d['father_middle'] ?? ''),
                'mother_name' => formatFullName($d['mother_last'] ?? '', $d['mother_first'] ?? '', $d['mother_middle'] ?? ''),
                'spouse_name' => null,
            ];
        }

        if ($type === 'marriage') {
            // Husband is stored as the record's primary person; Wife goes into
            // spouse_name - same simplification the plain Add Record form uses.
            return [
                'first_name'  => $d['husband_first']  ?? '',
                'middle_name' => $d['husband_middle'] ?? '',
                'last_name'   => $d['husband_last']   ?? '',
                'sex'         => 'male',
                'civil_status'=> 'married',
                'date_of_event' => $d['date_of_marriage'] ?? null,
                'father_name' => formatFullName($d['husband_father_last'] ?? '', $d['husband_father_first'] ?? '', $d['husband_father_middle'] ?? ''),
                'mother_name' => formatFullName($d['husband_mother_last'] ?? '', $d['husband_mother_first'] ?? '', $d['husband_mother_middle'] ?? ''),
                'spouse_name' => formatFullName($d['wife_last'] ?? '', $d['wife_first'] ?? '', $d['wife_middle'] ?? ''),
                'wife_father_name' => formatFullName($d['wife_father_last'] ?? '', $d['wife_father_first'] ?? '', $d['wife_father_middle'] ?? ''),
                'wife_mother_name' => formatFullName($d['wife_mother_last'] ?? '', $d['wife_mother_first'] ?? '', $d['wife_mother_middle'] ?? ''),
            ];
        }

        // death
        return [
            'first_name'  => $d['first_name']  ?? '',
            'middle_name' => $d['middle_name'] ?? '',
            'last_name'   => $d['last_name']   ?? '',
            'sex'         => strtolower($d['sex'] ?? '') === 'female' ? 'female' : 'male',
            'civil_status'=> $d['civil_status'] ?: null,
            'date_of_event' => $d['date_of_death'] ?? null,
            'father_name' => $d['father_name'] ?? '',
            'mother_name' => $d['mother_maiden_name'] ?? '',
            'spouse_name' => null,
            'date_of_birth'  => $d['date_of_birth'] ?? null,
            'age_at_death'   => $d['age_years'] ?? '',
            'place_of_event' => $d['place_of_death'] ?? '',
            'cause_of_death' => $d['immediate_cause'] ?? '',
        ];
    }
}