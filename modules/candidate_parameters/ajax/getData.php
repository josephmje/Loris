<?php
/**
 * Candidate parameters data fetch
 *
 * Gets data to populate the front-end form fields
 *
 * PHP Version 5
 *
 * @category Loris
 * @package  Media
 * @author   Stella L. <slee.mcin@gmail.com>
 * @license  Loris license
 * @link     https://github.com/aces/Loris-Trunk
 */
if (isset($_GET['data'])) {
    $data = $_GET['data'];
    if ($data == "candidateInfo") {
        echo json_encode(getCandInfoFields());
    } else if ($data == "probandInfo") {
        echo json_encode(getProbandInfoFields());
    } else if ($data == "familyInfo") {
        echo json_encode(getFamilyInfoFields());
    } else if ($data == "participantStatus") {
        echo json_encode(getParticipantStatusFields());
    } else if ($data == "consentStatus") {
        echo json_encode(getConsentStatusFields());
    } else {
        header("HTTP/1.1 404 Not Found");
        exit;
    }
}

/**
 * Handles the fetching of Candidate Info fields
 *
 * @throws DatabaseException
 *
 * @return array
 */
function getCandInfoFields()
{
    $candID = $_GET['candID'];

    $db =& Database::singleton();

    // get caveat options
    $caveat_options = [];
    $options        = $db->pselect(
        "SELECT ID, Description FROM caveat_options",
        array()
    );
    foreach ($options as $row) {
        $caveat_options[$row['ID']] = $row['Description'];
    }

    // get pscid
    $pscid = $db->pselectOne(
        'SELECT PSCID FROM candidate WHERE CandID = :candid',
        array('candid' => $candID)
    );

    $flag = $db->pselectOne(
        'SELECT flagged_caveatemptor FROM candidate WHERE CandID = :candid',
        array('candid' => $candID)
    );

    $reason = $db->pselectOne(
        'SELECT flagged_reason FROM candidate WHERE CandID = :candid',
        array('candid' => $candID)
    );

    $other = $db->pselectOne(
        'SELECT flagged_other FROM candidate WHERE CandID = :candid',
        array('candid' => $candID)
    );

    $extra_parameters = $db->pselect(
        "SELECT CONCAT('PTID', pt.ParameterTypeID) AS ParameterTypeID, pt.Name, 
        pt.Type, pt.Description 
        FROM parameter_type pt
        JOIN parameter_type_category_rel ptcr USING (ParameterTypeID) 
        JOIN parameter_type_category ptc USING (ParameterTypeCategoryID)
        WHERE ptc.Name='Candidate Parameters'
        ORDER BY pt.ParameterTypeID, pt.name ASC",
        array()
    );

    $fields = $db->pselect(
        "SELECT CONCAT('PTID', ParameterTypeID) AS ParameterTypeID, Value 
        FROM parameter_candidate WHERE CandID=:cid",
        array('cid' => $candID)
    );

    $parameter_values = [];
    foreach ($fields as $row) {
        $parameter_values[$row['ParameterTypeID']] = $row['Value'];
    }

    $result = [
               'pscid'                => $pscid,
               'candID'               => $candID,
               'caveatReasonOptions'  => $caveat_options,
               'flagged_caveatemptor' => $flag,
               'flagged_reason'       => $reason,
               'flagged_other'        => $other,
               'extra_parameters'     => $extra_parameters,
               'parameter_values'     => $parameter_values,
              ];

    return $result;
}

/**
 * Handles the fetching of Proband Info fields
 *
 * @throws DatabaseException
 *
 * @return array
 */
function getProbandInfoFields()
{
    $candID = $_GET['candID'];

    $db =& Database::singleton();

    // get pscid
    $pscid = $db->pselectOne(
        'SELECT PSCID FROM candidate where CandID = :candid',
        array('candid' => $candID)
    );

    $gender = $db->pselectOne(
        'SELECT ProbandGender FROM candidate where CandID = :candid',
        array('candid' => $candID)
    );

    $dob = $db->pselectOne(
        'SELECT ProbandDoB FROM candidate where CandID = :candid',
        array('candid' => $candID)
    );

    $extra_parameters = $db->pselect(
        "SELECT CONCAT('PTID', pt.ParameterTypeID) AS ParameterTypeID, pt.Name, 
        pt.Type, pt.Description 
        FROM parameter_type pt
        JOIN parameter_type_category_rel ptcr USING (ParameterTypeID) 
        JOIN parameter_type_category ptc USING (ParameterTypeCategoryID)
        WHERE ptc.Name='Candidate Parameters Proband'
        ORDER BY pt.ParameterTypeID, pt.name ASC",
        array()
    );

    $fields = $db->pselect(
        "SELECT ParameterTypeID, Value FROM parameter_candidate WHERE CandID=:cid",
        array('cid' => $candID)
    );

    $parameter_values = [];
    foreach ($fields as $row) {
        $parameter_values[$row['ParameterTypeID']] = $row['Value'];
    }

    // Calculate age difference
    $ageDifference = "Could not calculate age";
    $candidateDOB  = $db->pselectOne(
        "SELECT DoB FROM candidate WHERE CandID=:CandidateID",
        array('CandidateID' => $candID)
    );
    if (!empty($candidateDOB) && !empty($dob)) {
        $age = Utility::calculateAge($dob, $candidateDOB);

        if ($age !== null) {
            $ageDifference = $age['year'] * 12
                + $age['mon']
                + round($age['day'] / 30, 2);
        }
    }

    $result = [
               'pscid'            => $pscid,
               'candID'           => $candID,
               'ProbandGender'    => $gender,
               'ProbandDoB'       => $dob,
               'ageDifference'    => $ageDifference,
               'extra_parameters' => $extra_parameters,
               'parameter_values' => $parameter_values,
              ];

    return $result;
}

/**
 * Handles the fetching of Family Info fields
 *
 * @throws DatabaseException
 *
 * @return array
 */
function getFamilyInfoFields()
{
    $candID = $_GET['candID'];

    $db =& Database::singleton();

    // get pscid
    $pscid = $db->pselectOne(
        'SELECT PSCID FROM candidate where CandID = :candid',
        array('candid' => $candID)
    );

    $candidatesList = $db->pselect(
        "SELECT CandID FROM candidate ORDER BY CandID",
        array()
    );

    $siblingsList = $db->pselect(
        "SELECT f1.CandID 
        FROM family f1 JOIN family f2
        ON f1.FamilyID=f2.FamilyID WHERE f2.CandId=:candid GROUP BY f1.CandID",
        array('candid' => $candID)
    );

    $siblings = array();
    foreach ($siblingsList as $key => $siblingArray) {
        foreach ($siblingArray as $ID) {
            array_push($siblings, $ID);
        }
    }

    $candidates = array();
    // Remove own ID and sibling IDs from list of possible family members
    foreach ($candidatesList as $key => $candidate) {
        foreach ($candidate as $ID) {
            if ($ID == $candID || in_array($ID, $siblings)) {
                unset($candidatesList[$key]);
            } else {
                $candidates[$ID] = $ID;
            }
        }
    }

    $familyMembers = $db->pselect(
        "SELECT f1.CandID as FamilyCandID, f1.Relationship_type 
        FROM family f1 JOIN family f2 ON f1.FamilyID=f2.FamilyID
        WHERE f2.CandID = :candid AND f1.CandID <> :candid2 
          ORDER BY f1.CandID",
        array(
         'candid'  => $candID,
         'candid2' => $candID,
        )
    );

    $result = [
               'pscid'                 => $pscid,
               'candID'                => $candID,
               'candidates'            => $candidates,
               'existingFamilyMembers' => $familyMembers,
              ];

    return $result;
}

/**
 * Handles the fetching of Participant Status fields
 *
 * @throws DatabaseException
 *
 * @return array
 */
function getParticipantStatusFields()
{
    include_once __DIR__
        . "/../../candidate_parameters/php/"
        . "NDB_Form_candidate_parameters.class.inc";

    $candID = $_GET['candID'];

    $db =& Database::singleton();

    // get pscid
    $pscid = $db->pselectOne(
        'SELECT PSCID FROM candidate where CandID = :candid',
        array('candid' => $candID)
    );

    $statusOptions = NDB_Form_candidate_parameters::getParticipantStatusOptions();
    $reasonOptions = array();

    $required    = $db->pselect(
        'SELECT ID from participant_status_options where Required=1',
        array()
    );
    $parentIDs   = $db->pselect(
        'SELECT distinct(parentID) from participant_status_options',
        array()
    );
    $parentIDMap = array();

    foreach ($parentIDs as $ID) {
        $reasonOptions = array();
        foreach ($ID as $parentID) {
            if ($parentID != null) {
                $options = $db->pselect(
                    "SELECT ID, Description 
                    FROM participant_status_options 
                    WHERE parentID=:pid",
                    array('pid' => $parentID)
                );
                foreach ($options as $option) {
                    $reasonOptions[$option['ID']] = $option['Description'];
                }
                $parentIDMap[$parentID] = $reasonOptions;
            }
        }
    }

    $query = "SELECT participant_status, participant_suboptions, 
    reason_specify FROM participant_status WHERE CandID=:candid";
    $row   = $db->pselectRow($query, ['candid' => $candID]);

    $status    = !empty($row['participant_status']) ? $row['participant_status']
        : null;
    $suboption = !empty($row['participant_suboptions'])
        ? $row['participant_suboptions'] : null;
    $reason    = !empty($row['reason_specify']) ? $row['reason_specify'] : null;

    $history = getParticipantStatusHistory($candID);

    $result = [
               'pscid'                 => $pscid,
               'candID'                => $candID,
               'statusOptions'         => $statusOptions,
               'required'              => $required,
               'reasonOptions'         => $reasonOptions,
               'parentIDs'             => $parentIDMap,
               'participantStatus'     => $status,
               'participantSuboptions' => $suboption,
               'reasonSpecify'         => $reason,
               'history'               => $history,
              ];

    return $result;
}

    /**
     * Handles the fetching of Participant Status History
     *
     * @param int $candID current candidate's ID
     *
     * @throws DatabaseException
     *
     * @return array
     */
function getParticipantStatusHistory($candID)
{
    $db =& Database::singleton();
    $unformattedComments = $db->pselect(
        "SELECT entry_staff, data_entry_date,
            (SELECT Description 
              FROM participant_status_options pso 
              WHERE ID=psh.participant_status) AS status, 
            (SELECT Description from participant_status_options pso 
              WHERE ID=psh.participant_subOptions) 
              AS suboption,  reason_specify 
            FROM participant_status_history psh WHERE CandID=:cid",
        array('cid' => $candID)
    );

    return $unformattedComments;
}


/**
 * Handles the fetching of Consent Status fields
 *
 * @throws DatabaseException
 *
 * @return array
 */
function getConsentStatusFields()
{
    $candID = $_GET['candID'];

    $db =& Database::singleton();

    // get pscid
    $pscid = $db->pselectOne(
        'SELECT PSCID FROM candidate where CandID = :candid',
        array('candid' => $candID)
    );

    $config        =& NDB_Config::singleton();
    $consent       = $config->getSetting('ConsentModule');
    $consents      = [];
    $consentStatus = [];
    $date          = [];
    $withdrawal    = [];

    $consent_details =Utility::asArray($consent['Consent']);
    if (!$consent_details[0]) {
        // If only one consent, need to put in an array
        $temp            = array();
        $temp[]          = $consent_details;
        $consent_details = $temp;
    }

    foreach ($consent_details as $consentType) {
        $name           = $consentType['name'];
        $consentDate    = $name . '_date';
        $withdrawalDate = $name . '_withdrawal';

        $query = "SELECT 
                {$db->escape($name)}, 
                {$db->escape($consentDate)},
                {$db->escape($withdrawalDate )}         
                FROM participant_status WHERE CandID=:candid";

        $row = $db->pselectRow($query, ['candid' => $candID]);

        $consents[$name]      = $consentType['label'];
        $consentStatus[$name] = !empty($row[$name]) ? $row[$name] : null;
        $date[$name]          = !empty($row[$consentDate]) ?
            $row[$consentDate] : null;
        $withdrawal[$name]    = !empty($row[$withdrawalDate]) ?
            $row[$withdrawalDate] : null;
    }

    $history = getConsentStatusHistory($candID, $consents);

    $result = [
               'pscid'           => $pscid,
               'candID'          => $candID,
               'consentStatuses' => $consentStatus,
               'consentDates'    => $date,
               'withdrawals'     => $withdrawal,
               'consents'        => $consents,
               'history'         => $history,
              ];

    return $result;
}

/**
 * Handles the fetching of Consent Status history
 *
 * @param int   $candID   current candidate's ID
 * @param array $consents consent values
 *
 * @throws DatabaseException
 *
 * @return array
 */
function getConsentStatusHistory($candID, $consents)
{
    $db =& Database::singleton();

    $commentHistory = array();

    foreach ($consents as $consent => $label) {
        $unformattedComments = $db->pselect(
            "SELECT entry_staff, data_entry_date, "
            . $db->escape($consent) . ", "
            . $db->escape($consent . '_date') . ", "
            . $db->escape($consent . '_withdrawal')
            ." FROM consent_info_history WHERE $consent IS NOT NULL and CandID=:cid",
            array('cid' => $candID)
        );

        $unformattedComments['label']       = $label;
        $unformattedComments['consentType'] = $consent;

        array_push($commentHistory, $unformattedComments);
    }

    return $commentHistory;
}
