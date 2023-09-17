<?php
/**
 * library/ajax/sms_notification_log_report_ajax.php .
 *
 * Copyright (C) 2012 Medical Information Integration <info@mi-squared.com>
 * Copyright (c) 2018 Growlingflea Software <daniel@growlingflea.com>
 * Copyright (c) 2019 Growlingflea Software <daniel@growlingflea.com>
 *
 * LICENSE: This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 3
 * of the License, or (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://opensource.org/licenses/mpl-license.php>;.
 *
 */
$fake_register_globals=false;
$sanitize_all_escapes=true;
$testing = false;

require_once("../../interface/globals.php");
require_once("$srcdir/sql.inc.php");
require_once("$srcdir/formatting.inc.php");
require_once("$srcdir/patient.inc.php");

$DateFormat = DateFormatRead();
//make sure to get the dates

// Comparison function
function date_compare($element1, $element2) {
    $datetime1 = strtotime($element1['timestamp']);
    $datetime2 = strtotime($element2['timestamp']);
    return $datetime2 - $datetime1;
}

function phone_number_format($number) {
    // Allow only Digits, remove all other characters.
    $original = $number;
    $number = preg_replace("/^(\D+)(\d{1})(\D+)/","", $number);
    $number = preg_replace("/[^\d]/","", $number);

    // get number length.
    $length = strlen($number);



    // if number = 10
    if ($length == 10) {
        $number = preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $number);
    }else if ($length == 11){

        $number = ltrim($number, '1');
        $number = preg_replace("/^1?(\d{3})(\d{3})(\d{4})$/", "$1-$2-$3", $number);

    }

    return $number;

}

//capture the date and correct it


$DateFormat = "Y-m-d";

// Check and set the 'from_date' variable
$from_date = isset($_POST['from_date']) ? fixDate($_POST['from_date']) : fixDate(date($DateFormat));

// Check and set the 'to_date' variable
$to_date = isset($_POST['to_date']) ? fixDate($_POST['to_date']) : fixDate(date($DateFormat));

if($_POST['func'] === "show_list"){
    $query = "SELECT 'TBD' as lastUpdated, pd.pid as pid, concat(lname, ', ', fname) as full_name, pd.phone_cell from patient_data pd where 1";

    $res = sqlStatement($query);
    while($row = sqlFetchArray($res)){
        $data['data'][]=$row;
    }

    echo json_encode($data);
}





