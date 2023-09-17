<?php
namespace Mi2\SeeYourselfCampaign;
require_once($GLOBALS['srcdir'] . "/pnotes.inc");
use OpenEMR\Common\Csrf\CsrfUtils;
use OpenEMR\Common\ORDataObject\ORDataObject;
use OpenEMR\Common\Session\SessionUtil;
use OpenEMR\Core\Header;

class PrintoutReport extends ORDataObject
{


    function __construct()
    {

        //Check if table exists,  if not create it.
        $query = " Create table if not exists `printout_log` " .
            " ( `id` int(11) NOT NULL AUTO_INCREMENT,  " .
            "`pid` int(11) NOT NULL,  " .
            "`doc_id` int(11),  " .
            "`encounter` int(11),  " .
            "`form_name` varchar(256) NOT NULL,  " .
            "`user` varchar(256),  " .
            "`printed` int(1),  " .
            "`print_date` timestamp NOT NULL ON UPDATE CURRENT_TIMESTAMP,  " .
            " PRIMARY KEY (`id`) ) " .
            " ENGINE=InnoDB ";

        sqlQuery($query);



    }

    public static function logPrintout($pid,  $user,  $encounter,  $filename)
    {
        $sql = "Insert into printout_log (pid,  doc_id,  encounter,  form_name,  user,  print_date) " .
            "Values (?, ?, ?, ?, ?, ?) ";
        $date = date('Y-m-d h:i:s');
        $res = sqlQuery($sql,  array($pid,  '',  $encounter,  $filename,  $user,  $date ));
    }


    private static function parseFilename($filename)
    {
        $filename = str_replace("/var/pdfgeneration/",  '',  $filename );
        $filename = substr($filename,  0,  strpos($filename,  "_"));
        return $filename;
    }

    public static function getReportByDate($from_date,  $to_date)
    {

        $printouts_response['data'] = array();
        $sql = "Select pl.pid as pid,  pl.print_date,  pl.form_name as printout_name,  user,  " .
            " CONCAT(lname,  ',  ',  fname) as pt_name,  pd.pubpid,  encounter from printout_log pl " .
            " join patient_data pd on pl.pid = pd.pid " .
            " where date(print_date) >= ? and date(print_date) <= ? ";
        $res = sqlStatement($sql,  array($from_date,  $to_date));
        while($row = sqlFetchArray($res)){
            $row['printout_name'] = self::parseFilename($row['printout_name']);
            array_push($printouts_response['data'],  $row);
        }

        return $printouts_response;
    }
}



