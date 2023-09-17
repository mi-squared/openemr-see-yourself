<?php
namespace Mi2\SeeYourselfCampaign;

use OpenEMR\Common\ORDataObject\ORDataObject;

require_once(__DIR__ . "/../interface/globals.php");
require_once($GLOBALS['srcdir']."/sql.inc.php");
require_once($GLOBALS['srcdir']."/patient.inc.php");
require_once($GLOBALS['srcdir']."/formatting.inc.php");

class PrintoutHelper extends ORDataObject
{

    //Returns Patient Name and DOB.
    public static function getPatientNameDOB($pid)
    {
        return sqlQuery("select CONCAT(fname, ' ', lname) as pname, dob from patient_data where pid = ?", array($pid));

    }

    //Returns
    public static function getEyeData($pid)
    {
        return sqlQuery("SELECT fpr.* FROM patient_data pd
                        JOIN forms f USING (pid)
                        JOIN form_snellen fpr ON f.form_id = fpr.id and pd.pid = fpr.pid
                        WHERE fpr.pid = ? AND f.deleted = 0
                        ORDER BY fpr.date DESC limit 1", array($pid));
    }

    //We can put in "Allergy"
    public static function getIssues($pid, $issue): array
    {
        $count =sqlQuery("select count(*) as count from lists where type = ? and pid = ? ", array($issue, $pid))['count'];
        if ($count > 0) {
            $issues_array = array();
            $res = sqlStatement("Select * from lists where pid = ? and type = ? and enddate is null ", array($pid, $issue));
            while ($row = sqlFetchArray($res)) {
                $issues_array[] = $row;
            }
            return $issues_array;

        } else{
            return array('title' => 'Issue does not exist.  ');

        }
    }


    public static function getLastFormLBF($pid, $formdir)
    {
       $form_data =  sqlQuery("SELECT ld.form_id, f.date as date FROM patient_data pd
                join forms f ON f.pid = pd.pid
                join lbf_data ld ON ld.form_id = f.form_id
                WHERE pd.pid = ? AND f.deleted = 0
                AND f.formdir = ? ORDER BY f.date DESC limit 1 ", array($pid, $formdir));


        $res = sqlStatement("Select * from lbf_data where form_id = ? ", array($form_data['form_id']));

        $data['result'] = array();
        while ($row = sqlFetchArray($res)){
            $data['result'][$row['field_id']] = $row['field_value'];
        }

        if(empty($data['result'])) {
            $data['result']['date'] = "";
            $data['result']['data'] = "No data exists";
            return $data['result'];
        }

        $data['result']['date'] = $form_data['date'];

        return $data['result'];


    }

    //break up notes into number of lines for printing to a form
    //return note array, if note is too big return note but give error.
    public static function handleNotes($text, $numLines) {


    }

    public static function formatEyeData($array): array
    {
        if (!$array){
            $response['date'] = "No record Exists";

            return $response;
        }

        $vision = "Uncorrected Vision: Left: 20/{$array['left_1']} Right: 20/{$array['right_1']} \n";
        //if there is corrected vision, we must print this to the form
        if ($array['left_2'] != '' || $array['right_2'] != '' ) {
            $vision .= "Corrected Vision: Left: 20/{$array['left_2']} Right: 20/{$array['right_2']} \n";
        }

        $response['data'] =  $vision . $array['notes'];
        $response['date']=$array['date'];
        return $response;
    }

    public static function formatMedicalIssues($array): string
    {
        $string = '';
        foreach($array as $datum){
            $string .= "  " . $datum['title'] != NULL ? $datum['title'] . ', ' : ' ';

            }
        return rtrim($string, ', ');;

        }



    public static function formatHearing($array) : array {


        if (!$array['date']) {
            $array['data'] = "";
            $array['date'] = 'No data exists';
            return $array;
        }

        if ($array['Left_Ear'] === "Passed" && $array['Right_Ear'] === "Passed"){
            $array['data'] =  "Passed Hearing Screening";

        } elseif (str_contains($array['Left_Ear'], 'unco')){
            $array['data'] = $array['Left_Ear'] . " " . $array['Right_Ear'];

        }

        $array['date'] = date('Y-m-d', strtotime($array['date']));
        return $array;
    }

    public static function getReportData($pid, $report) {

        return sqlQuery("select *, fe.date as encounterDate, fpr.date as dateFormCompleted from forms f
    join $report fpr on f.form_id = fpr.id
    join form_encounter fe on fe.encounter = f.encounter
    where deleted = 0 and fpr.pid = ? " .
            "order by fpr.date desc limit 1", array($pid) );




    }

    public static function formatTBRisk($array) :  array
    {
        if($array['date'] === null) {
            $response['data'] = "No data exists";
            $response['date'] = "";
            return $response;
        }
        //$response['date'] = $array['date'];
        if (strtolower($array['TB_Risk']) == 'no') {
            $response['data'] = 0;
        } else {
            $response['data'] = 1;
        }
        $response['date'] = substr($array['date'], 0, 10);
        return $response;

    }

    public static function formatSpeech($array) : array {


        $response['data'] =  array();

        if($array['date'] === ''){
            $response['data'][] = "No Data Exists";
            return $response;

        }

        if($array['AudiologyRefer'] == "Yes"){
            $response['data'][] = "Referred to Audiology.";
        }

        if($array['SpeechTx'] == "Yes") {
            $response['data'][] = "Referred to Speech Therapy.";
        }

        //Go to the fee sheet to get diag codes.

        $response['date'] = substr($array['date'], 0, 10);
        $query = "Select code_type, code, encounter, code_text from billing where date like ?
                and activity = 1";

        $res = sqlStatement($query, array($response['date'] . "%"));
        while($row = sqlFetchArray($res)) {
            $response['data'][] = $row['code_type'] . " " . $row['code'] . " " . $row['code_text'];
        }

        return $response;
    }

    public static function getEmergencyContact($pid) {

        $sql = "Select mothersname, relation1name, relation1home, relation1cell, fathersname, " .
            "relation2name, relation2cell, relation2home, guardiansname, relation3name," .
            " relation3cell, relation3home from patient_data where pid = ?";
        $res = sqlQuery($sql, array($pid));

        return $res;


    }

    public static function formatNoteField($string, $length = 90): array {
        $str = wordwrap($string, $length, '<br>');
        $str = explode("<br>", $str);
        return $str;
    }






    public static function generate_checkbox($parameter, $class, $title): string {
        if($parameter == 1) {
            $status = "checked";
        }else{
            $status = '';
        }

        $string = '<div class="'.$class.'"><label for="tbRisk"><span>'.$title.'</span>
                <input type="checkbox" id="tbRisk" name = "tbRisk" value="1" onclick="return false;" '.$status.'>
            </label></div>';

        //if the checkbox is checked we can print it.  if not we send an empty string
        if($status === "checked") {
            return $string;

        }else {
            return '';

        }
    }

    public static function generate_line($title, $class, $value): string {
        $string = '<div class="'.$class.' "><label><span>'.$title.':</span></label>  '.$value.'</div>';
        if ($value !== '') {
            return $string;
        } else {
            return '';
        }

    }

    public static function generate_line_title_val($title, $value): string {
        $string = '<div class="measurement_title">'.$title.'</div><div class="value">'.$value.'</div>';
        if ($value !== '') {
            return $string;
        } else {
            return '';
        }


    }

    public static function getProviders(): array {
        $sql = "select id, CONCAT(fname, ' ', mname, ' ', lname) as providername from users where authorized = 1
              and federaltaxid != '' and calendar = 1 order by id asc";
        $res = sqlStatement($sql);
        $array = array();
        while ($row = sqlFetchArray($res)){
            $array[] = $row;

        }

        return $array;

    }

    public static function getLastWellCheckAppointment($pid) {

        $sql = "select date(date) as date, encounter, opc.pc_catid, opc.pc_catname"
            ." from form_encounter fe "
            ."join openemr_postcalendar_categories opc on opc.pc_catid = fe.pc_catid "
            ." where pid = ? and pc_catname like ? order by date desc limit 1";

        return sqlQuery($sql, array($pid, "%Well%"));


    }


    public static function printMessage($class, $message){
        echo "<div class='$class' >$message</div>";

    }

    public static function generate_value($class, $value): string {
        $string = '<div class="'.$class.' ">'.htmlspecialchars($value).'</div>';
        if ($value !== '') {
            return $string;
        } else {
            return '<div class="'.$class.' "></div>';
        }

    }

    //We use this function to add lines to the diag picker.
    public static function generateRow($type = '', $diag = '', $title = '')
    {
        return array('type' => $type, 'diag' => $diag, 'title' => $title);

    }

    //This is the function we use to print to the pdf.  Older versions used pdf clown which saw pdfs at a different
    //size.  The new one sees the size differently.  More documentation to come
    public static function addTextField(&$pdf, $xPx, $yPx, $widthPx, $heightPx, $text, $font = '',$textSize = 12, $pdfClown = false) {

        if($pdfClown) {
            $xPx = $xPx * .352777;
            $yPx = $yPx * .35777;
        }
        $pdf->SetFont('Arial', $font, $textSize);
        $pdf->SetXY($xPx, $yPx);
        $pdf->Cell($widthPx, $heightPx, $text);
    }



}

//This is kind of OK - but not the best OOP practices.
$data = array();
$data['id'] = $_POST['id'] ?? null ;
if (isset($_POST['func']) && $_POST['func'] == 'getIssues' && $_POST['pid']) {

    $res = sqlStatement("SELECT DISTINCT type, diagnosis, title, short_desc, REPLACE(diagnosis, 'ICD10:', '') AS diag
            FROM lists
            LEFT JOIN icd10_dx_order_code
            ON REPLACE(diagnosis, 'ICD10:', '') = formatted_dx_code
            WHERE pid = ?
            AND type = ?
            AND (activity = 1 OR enddate IS NULL)", array($_POST['pid'], "medical_problem"));
    while($row = sqlFetchArray($res)) {
        array_push($data, $row);
    }
    $data['type'] = "problems";
    $data[] = PrintoutHelper::generateRow('problems', 'None', '');
    $data['title'] = $_POST['title'] ?? "Medical Issues";

    echo json_encode($data);
}

if (isset($_POST['func']) && $_POST['func'] === 'getAllergies' ) {


    $res = sqlStatement("Select title, type from lists where pid = ? and type = ? ", array($_POST['pid'], "allergy"));
    while($row = sqlFetchArray($res)) {
        array_push($data, $row);
    }
    $data['type'] = "allergies";
    $data['title'] = $_POST['title'] ?? "Allergies";
    echo json_encode($data);

}

if (isset($_POST['func']) && $_POST['func'] === 'getDental' ) {


    $res = sqlStatement("Select diagnosis, title, type from lists where pid = ? and type = ? and activity = 1", array($_POST['pid'], "dental"));
    while($row = sqlFetchArray($res)) {
        $data[] = $row;
    }
    $data['title'] = $_POST['title'] ?? "Dental Issues";
    $data['type'] = "Dental Issues";
    echo json_encode($data);

}



if (isset($_POST['func'])  && $_POST['func'] === 'getSpeech'  ) {

    //get the last encounter and formID of the last Speech Delay form
    $query = sqlQuery("select encounter, form_id, date from forms where formdir = ? and pid = ? and deleted = 0 order by id desc ", array("LBF_SpeechDelay", $_POST['pid']));
    $data['encounter'] = $query['encounter'];
    $data['date'] = substr($query['date'], 0, 10);

    //Get the values from the LBF Form

//    $query2 = sqlStatement("Select title, field_value as diag, lbf_data.field_id from lbf_data
//    join layout_options on lbf_data.field_id = layout_options.field_id where lbf_data.form_id = ? ", array($query['form_id']));
//    while ($row = sqlFetchArray($query2)){
//        $data[] = $row;
//    }

    $query3 = sqlStatement("Select code_text as title, code as diag from billing where encounter = ?
              and code_type like '%ICD%' and pid = ? and code_text like ? group by code ",
               array($data['encounter'], $pid, "%speech%") );
    while ($row = sqlFetchArray($query3)) {
        $row['type'] = 'speechIssues';
        $data[] = $row;
    }
    $data['title'] = $_POST['title'] ?? "Speech Issues";
    $data['type'] = 'speechIssues';
    echo json_encode($data);

}

if (isset($_POST['func'])  && $_POST['func'] === 'getMeds'  ) {
    $query = sqlStatement("SELECT drug_id, drug as diag, drug_info_erx as title, MAX(date_added) AS max_date
            FROM prescriptions
             WHERE patient_id = ? AND date_added >= DATE_SUB(CURDATE(), INTERVAL 2 YEAR)
                 GROUP BY drug_id, drug, drug_info_erx" , array($pid));
    while ($row = sqlFetchArray($query)) {
        $data[] = $row;
    }

    $data['type'] = 'meds';
    $data['title'] = $_POST['title'] ?? 'Medications Prescribed in the Last 2 years';
    echo json_encode($data);
}

if (isset($_POST['func']) && $_POST['func'] === 'getContacts'  ) {
    $data[] = PrintoutHelper::getEmergencyContact($_POST['pid']);
    $data['type'] = 'contacts';
    $data['title'] = $_POST['title'] ?? 'Emergency Contact List';
    $data['mother']['diag'] = "Mother";
    $data['mother']['title'] = "Name: " . $data[0]['relation1name'] . " Home: " . $data[0]['relation1home'] . "Cell: " . $data[0]['relation1cell'];
    $data['father']['diag'] = "Father";
    $data['father']['title'] = "Name: " . $data[0]['relation2name'] . " Home: " . $data[0]['relation2home']  . "Cell: " . $data[0]['relation2cell'];;
    $data['rel3']['diag'] = "Other Contact:";
    $data['rel3']['title'] = "Name: " . $data[0]['relation3name'] . " Home: " . $data[0]['relation3home']  . "Cell: " . $data[0]['relation3cell'];;
    $data['rel4']['diag'] = "Other Contact";
    $data['rel4']['title'] = "Name: " . $data[0]['relation4name'] . " Home: " . $data[0]['relation4home']  . "Cell: " . $data[0]['relation4cell'];;
    unset($data[0]);
    echo json_encode($data);

}

if (isset($_POST['func'])  && $_POST['func'] === 'getHearing'  ) {


    $data[] = PrintoutHelper::getLastFormLBF($_POST['pid'], "LBFHearingSCN");
    $data['type'] = 'hearing';
    $data['title'] = $_POST['title'] ?? "Hearing Results";
    $data['date'] = date('Y-m-d', strtotime($data[0]['date']));
    //here we put the results into a format the diag picker can read
    //get the Left Ear
    $data['leftEar']['title'] = "Left Ear";
    $data['rightEar']['title'] = "Right Ear";
    $data['leftEar']['diag'] = sqlQuery("select title from list_options where option_id = ? and list_id = ?" , array($data[0]['Left_Ear'], "Pass_Fail"))['title'];
    $data['rightEar']['diag'] = sqlQuery("select title from list_options where option_id = ? and list_id = ?" , array($data[0]['Right_Ear'], "Pass_Fail"))['title'];

    unset($data[0]);
    $data[] = PrintoutHelper::generateRow('hearing', 'Passed', 'Hearing Screening');
    $data[] = PrintoutHelper::generateRow('hearing', 'Referred', 'To Audiology');
    echo json_encode($data);

}

if (isset($_POST['func']) && $_POST['func'] === 'getAsthma'  ) {
    //we check all Health Issues that are related to Asthma
    $res = sqlStatement("SELECT DISTINCT type, diagnosis, title, short_desc, REPLACE(diagnosis, 'ICD10:', '') AS diag
            FROM lists
            LEFT JOIN icd10_dx_order_code
            ON REPLACE(diagnosis, 'ICD10:', '') = formatted_dx_code
            WHERE pid = ?
            AND type = ?
            AND (activity = 1 OR enddate IS NULL)
            AND title like ? ", array($_POST['pid'], "medical_problem", "%asthma%"));
    while($row = sqlFetchArray($res)) {
        $data[] = $row;
    }
    $data[] = PrintoutHelper::generateRow("asthma", "Patient", "has been prescribed Albuterol, Xopenex, Proair, or Ventolin ");
    $data[] = PrintoutHelper::generateRow("asthma", "No", "known issues related to Asthma");



    $data['type'] = "problems";
    $data['title'] = $_POST['title'] ?? "Health Problems Related to Asthma";

    echo json_encode($data);

}
if (isset($_POST['func'])  && $_POST['func'] === 'snellenNotes'  ) {

    $data[] = PrintoutHelper::generateRow('hearing', 'Referred', 'to Optometry');
    $data[] = PrintoutHelper::generateRow('hearing', 'Has', 'Other Provider');
    $data[] = PrintoutHelper::generateRow('hearing', 'Passed', 'Eye Exam');

    $data['title'] = $_POST['title'] ?? "Snellen Exam Notes";
    echo json_encode($data);

}


if (isset($_POST['func'])  && $_POST['func'] === 'getInsectStings'  ) {
    $data[] = PrintoutHelper::generateRow('insectStings', 'None', 'known');
    $data[] = PrintoutHelper::generateRow('insectStings', 'Epinephrine', 'provided in case of sting.');


    $data['title'] = $_POST['title'] ?? "Insect Stings";
    echo json_encode($data);

}
