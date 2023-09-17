<?php
// library/ajax/sms_notification_log_report_ajax.php
// Copyright (C) 2017 -Daniel Pflieger
// daniel@growlingflea.com daniel@mi-squared.com
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.

// This is a reporting tool that shows all sent notifications and their status.


require_once("../interface/globals.php");
require_once("$srcdir/patient.inc.php");
require_once("$srcdir/formatting.inc.php");
require_once("$webserver_root/library/globals.inc.php");
require_once("{$GLOBALS['srcdir']}/sql.inc.php");
use OpenEMR\Core\Header;
use Mi2\SeeYourselfCampaign\Bootstrap;



$lname = $_POST['lname'] ?? null;
$fname = $_POST['fname'] ?? null;
$pid = $_POST['pid'] ?? null;

?>
<html>
<head>

    <link rel="stylesheet" href="<?php echo $GLOBALS['css_header'];?>" type="text/css">
    <link rel="stylesheet" href="../assets/sms_log_report.css" type="text/css">
    <title><?php xl('Call List: ','e'); ?></title>
    <?php Header::setupHeader(['opener', 'report-helper', 'datatables', 'datatables-buttons',
        'datatables-buttons-html5', 'datetime-picker', 'dialog', 'jquery', 'common']);
    echo '';
    ?>



    <script>
        //This is for the child row that displays the response
        function format ( d ) {
            // `d` is the original data object for the row
            var rowColor;



            var rel1color ="rgb(133, 66, 4)";
            var rel2color ="rgba(6, 6, 172, 0.83)";
            var rel3color="rgba(13, 71, 10, 0.83)";
            var rel4color ="rgba(246, 23, 216, 0.83)";
            var defaultcolor = "Black";



            var response =
                '<table id="main_table" class="main" align="center">' +
                '<tr><td>' +
                '<div id="contact_list_div">' +
                '<table class="formtable sms_report contact-list-table" id="contact_list">' +
                '<caption><h3>Contact List</h3></caption>' +
                '<tr>' +
                '<th>Relation</th>' +
                '<th>Name</th>' +
                '<th>HIPAA</th>' +
                '<th>Contact #</th>' +
                '</tr>' +
                '<tr class="default-color">' +
                '<td>Patient</td>' +
                '<td>' + d.fname + ' ' + d.lname + '</td>' +
                '<td>' + d.hipaa_allowsms + '</td>' +
                '<td>' + d.phone_cell + '</td>' +
                '</tr>' +
                '<tr class="rel1-color">' +
                '<td>Rel 1</td>' +
                '<td>' + d.relation1name + '</td>' +
                '<td>' + d.relation1sms + '</td>' +
                '<td>' + d.relation1cell + '</td>' +
                '</tr>' +
                '<tr class="rel2-color">' +
                '<td>Rel 2</td>' +
                '<td>' + d.relation2name + '</td>' +
                '<td>' + d.relation2sms + '</td>' +
                '<td>' + d.relation2cell + '</td>' +
                '</tr>' +
                '<tr class="rel3-color">' +
                '<td>Rel 3</td>' +
                '<td>' + d.relation3name + '</td>' +
                '<td>' + d.relation3sms + '</td>' +
                '<td>' + d.relation3cell + '</td>' +
                '</tr>' +
                '<tr class="rel4-color">' +
                '<td>Rel 4</td>' +
                '<td>' + d.relation4name + '</td>' +
                '<td>' + d.relation4sms + '</td>' +
                '<td>' + d.relation4cell + '</td>' +
                '</tr>' +
                '</table>' +
                '</div>';

            response +=
                '<table class="formtable compact sms_report response-list-table" id="response_list">' +
                '<caption><h3>Recent Messages</h3></caption>' +
                '<tr>' +
                '<th>Date</th>' +
                '<th>From</th>' +
                '<th>To</th>' +
                '<th>Message</th>' +
                '<th></th>' +
                '</tr>';
            ;

            //here we insert the responses:
            var textResponse = d.messages;

            textResponse.forEach(function(item) {
                var decoded;

                if(/^\d+$/.test(item.from) && /^\d+$/.test(item.to)) {
                    var a = item.from;
                    item.from = [a.slice(0, 1) + '-', a.slice(1, 4) + '-', a.slice(4, 7) + '-', a.slice(7, 11)].join()
                        .replace(/,/g, '');

                    a = item.to;
                    item.to = [a.slice(0, 1) + '-', a.slice(1, 4) + '-', a.slice(4, 7) + '-', a.slice(7, 11)].join().replace(/,/g, '');
                }else{


                }

                try {

                    var str = item.text;
                    var uri_encoded = str.replace(/%([^\d].)/, "%25$1");

                    var text = decodeURIComponent(uri_encoded);
                    console.log("dec" + text);
                }catch{
                    var text = item.text


                    text =    text.replace(/%00/g, '');
                    text =    text.replace(/%20/g, ' ');

                    text = "Error parsing message, message may not be complete <br>" + text;

                }




                if(item.to.includes("1sms")){

                    rowColor=rel1color;

                }else if(item.to.includes("2sms")){

                    rowColor=rel2color;

                }else if(item.to.includes("3sms")){

                    rowColor=rel3color;
                }else if(item.to.includes("4sms")){

                    rowColor=rel4color;

                }else{

                    rowColor = defaultcolor;
                }





                response += '' +
                    '<tr role="row" class="shown" style="color:' + rowColor + '">' +
                    '<td class="smsdateTime">' + item.time + ' </td>' +
                    '<td class="smsfrom" > ' + item.from + ' </td>' +
                    '<td class="smsto"> ' + item.to + ' </td>' +
                    '<td class="smsmessage"> ' + text + ' </td> ' +
                    '<td class="smsmessageID" hidden>' + item.messageId +'</td>' +
                    '<td class="smsreplyMessageID" hidden>' + item.replyMessageId +'</td>' +
                    '<td class="smsptname" hidden>' + d.fname + " " + d.lname +'</td>' +
                    '<td class="pid" hidden>' + d.pid +'</td>' +
                    '<td>';
                if(! item.from.match(/[a-z]/i)) {

                    response += '<button style="padding: 4px 10px;" align="left" class="reply"> Reply </button>';
                }

                response += '</td>' + '</tr>';





            });






            response +='</table>';
            '</td></table>';




            return response;

        }


        $(document).ready(function() {

            var oTable;

            oTable=$('#show_sms_table').DataTable({
                dom: 'Bfrtip',
                autoWidth: true,
                scrollY: false,
                fixedHeader: true,
                buttons: [
                    'copy', 'excel', 'pdf', 'csv'
                ],
                ajax:{ type: "POST",
                    url: "../library/ajax/recruitment_call_list_ajax.php",
                    data: {
                        func:"show_list",
                        lname:"<?php echo $lname; ?>",
                        fname:"<?php echo $fname; ?>",
                        pid:"<?php echo $pid; ?>"


                    },

                },

                columns:[

                    { 'data': 'lastUpdated' },
                    { 'data': 'pid'        },
                    { 'data': 'full_name',
                        "render": function(data, type, row, meta){
                            if(type === 'display'){
                                data = '<a>' + data + '</a>';
                            }

                            return data;
                        }
                    },
                    { 'data': 'phone_cell'}

                ],

                "columnDefs": [
                    { className: "details-control", targets: "_all" },
                ],
                "rowCallback": function( row, data ) {




                },
                "iDisplayLength": 100,
                "select":true,
                "searching":true,
                "retrieve" : true


            });



            $('#column0_lastUpdate_search_show_sms_table').on( 'keyup', function () {
                oTable
                    .columns( 1 )
                    .search( this.value)
                    .draw();
            } );

            $('#column1_pid_search_show_sms_table').on( 'keyup', function () {
                oTable
                    .columns( 2 )
                    .search( this.value )
                    .draw();
            } );

            $('#column2_eventDate_search_show_sms_table').on( 'keyup', function () {
                oTable
                    .columns( 3 )
                    .search( this.value )
                    .draw();
            } );

            $('#column3_pcTitle_search_show_sms_table').on( 'keyup', function () {
                oTable
                    .columns( 4 )
                    .search( this.value )
                    .draw();
            } );



            $("button.dt-button").css('color', 'black !important');

            // Event listener for opening and closing responses
            // Todo: make this open always
            // Todo: add ability to respond
            // Todo: make sure patient notes includes these interactions, which I think they already do
            $('#show_sms_table tbody').on('click', 'td.details-control', function () {
                var tr = $(this).closest('tr');
                var row = oTable.row( tr );

                if ( row.child.isShown() ) {
                    // This row is already open - close it
                    row.child.hide();
                    tr.removeClass('shown');
                }
                else {
                    // Open this row
                    row.child( format(row.data()) ).show();



                    $('.reply').click(function(){

                        var messageId = $(this).closest('tr').find('.smsmessageID').text();
                        var replyMessageId = $(this).closest('tr').find('.smsreplyMessageID').text();
                        var patient=$(this).closest('tr').find('.smsptname').text();

                        //pull the number we are sending to from the 'from' field
                        var sendto=$(this).closest('tr').find('.smsfrom').text();
                        var pid=$(this).closest('tr').find('.pid').text();

                        var URL = encodeURI( "<?php echo $GLOBALS['webroot'] ?>" +
                            "/modules/sms_email_reminder/views/sms_respond_view.php?" +
                            "patient="+patient+"&to="+sendto+";"+"&replyID="+ replyMessageId +
                            "&pid=" + pid);
                        dlgopen(URL, '_blank', 800, 400);





                    });
                }
            } );


            $('#show_sms_table tbody').on('click', 'td.ptN', function () {
                var tr = $(this).closest('tr');
                var row = oTable.row( tr );
                var data = oTable.row( $(this).parents('tr') ).data();
                var next = this.className;

                var newpid =data['pid'];

                if (newpid.length===0)
                {
                    return;
                }
                if (0) {
                    openNewTopWindow(newpid);
                }
                else {
                    top.restoreSession();
                    top.RTop.location = "<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/summary/demographics.php?set_pid=" + newpid;
                    top.left_nav.closeErx();
                }


            } );

            function openNewTopWindow(pid) {
                document.fnew.patientID.value = pid;
                top.restoreSession();
                document.fnew.submit();
            }




        });





    </script>
</head>
<body class="body_top formtable">
<span class='title'><?php xl('Report','e'); ?> - <?php xl('Recruitment Campaign','e');  ?></span>
<div class="position-static">
    <div class="content">

        <form name="theform" id="theform" method="post" action="./recruitment_call_list.php" onsubmit="return top.restoreSession()">
            <div id="report_parameters" class="grid-form">
                <!-- First Row Appointments -->
                <div class="grid-item">
                    <span class="grid-title"><?php xl('Appointments','e'); ?>:</span>
                </div>
                <div class="grid-item">
                    <input type='text' name='form_from_date' id='form_from_date'
                           class="form_date" value='<?php echo $form_from_date ?>' placeholder="From" >
                    <input type='text' name='form_to_date' id='form_to_date'
                           class="form_date" value='<?php echo $form_to_date ?>' placeholder="To">
                </div>
                <div class="grid-item">

                </div>
                <div class="grid-item">
                    <button type="button" class="clear-button" id="clear_app">Clear</button>
                </div>

                <!-- Second Row Date Message Sent -->
                <div class="grid-item">
                    <span class="grid-title"><?php xl('Message Sent','e'); ?></span>
                </div>
                <div class="grid-item">
                    <input type='text' name='form_from_date_sent' id='form_from_date_sent' class="form_date"
                           value='<?php echo $form_from_date_sent ?>' placeholder="From" >
                    <input type='text' name='form_to_date_sent' id='form_to_date_sent' class="form_date"
                           value='<?php echo $form_to_date_sent ?>' placeholder="To">
                </div>
                <div class="grid-item">

                </div>
                <div class="grid-item">
                    <button type="button" style="padding: 4px 10px;" align="left"  id="clear_sent"> Clear </button>
                </div>

                <!-- Third Row Patient Name-->
                <div class="grid-item">
                    <span class="grid-title"><?php xl('Name','e'); ?>:</span>
                </div>
                <div class="grid-item">
                    <input type='text' class="form_name" name='lname' id='lname'  placeholder="Last" value='<?php echo $lname ?>' >
                </div>
                <div class="grid-item">
                    <input type='text' class="form_name" name='fname' id='fname'  placeholder="First" value='<?php echo $fname ?>'>
                </div>
                <div class="grid-item">
                    <button type="button" style="padding: 4px 10px;" align="left"  id="clear_name"> Clear </button>
                </div>

                <!-- Fourth Row -->
                <div class="grid-item">
                    <span class="grid-title"><?php xl('PID','e'); ?>:</span>
                </div>
                <div class="grid-item">
                    <input type='text' name='pid' id='pid' class="form_pid" placeholder="PID" value='<?php echo $pid; ?>'>
                </div>
                <div class="grid-item">
                    <span class="grid-title"><?php xl('Cellphone','e'); ?>:</span>
                    <span>
                        <input type='text' name='ptcell' id='ptcell' class="form_pid" placeholder="Cell" value=''>
                        </span>
                </div>
                <div class="grid-item">
                    <button type="button" style="padding: 4px 10px;" align="left"  id="clear_pid"> Clear </button>
                </div>

                <!-- Fifth Row -->

                <div class="grid-item">
                    <input value="<?php echo htmlspecialchars(xl('Submit')) ?> " type="submit" id="submit_selector" name="sms_submit" ><?php ?>
                    <input hidden id = 'submit_button' value = '<?php $_POST['sms_submit']  ?? '' ?>'>
                </div>
                <div class="grid-item">
                    <button type="button" class="clear-form-button" id="clear">Clear Form</button>
                </div>
                <div class="grid-item">
                </div>
                <div class="grid-item">
                    <button type="button" class="send-unsent-button" id="send_sms">Send Unsent</button>
                </div>
            </div>
        </form>

    </div>

</div>

<table class="display formtable session_table cell-border compact" id="show_sms_table">


    <thead>

    <tr>
        <th><input  id = 'column0_lastUpdate_search_show_sms_table' class="date_search"></th>
        <th><input  id = 'column1_pid_search_show_sms_table' class="pid_search"></th>
        <th><input  id = 'column2_eventDate_search_show_sms_table' class="date_search"></th>
        <th><input  id = 'column3_pcTitle_search_show_sms_table' class="pc_title_search"></th>
   </tr>

    <tr>
        <th> <?php xl('Last  Updated','e'); ?> </th>
        <th > <?php xl('Patient  ID','e'); ?> </th>
        <th > <?php xl('Patient  Name','e'); ?> </th>
        <th > <?php xl('Contact  Number','e'); ?> </th>
    </tr>

    </thead>
    <tbody id="users_list" >
    </tbody>

</table>


</body>


<script>
    $(document).ready(function() {
        $("#form_from_date").datetimepicker({
            timepicker: false,
            format: "Y-m-d"

        });
        $("#form_to_date").datetimepicker({
            timepicker: false,
            format: "Y-m-d"
        });

        $("#form_from_date_sent").datetimepicker({
            timepicker: false,
            format: "Y-m-d"

        });

        $("#form_to_date_sent").datetimepicker({
            timepicker: false,
            format: "Y-m-d"

        });

        $("#clear").on('click', function(){

            $('#clear_app').trigger('click');
            $('#clear_name').trigger('click');
            $('#clear_pid').trigger('click');
            $('#clear_sent').trigger('click');

        });

        $("#clear_app, #clear_name, #clear_pid, #clear_sent").on('click', function(){

            var id = this.id;
            if (id == "clear_app"){
                $('#form_from_date').val('');
                $('#form_to_date').val('');

            }else if(id == "clear_name"){
                $('#fname').val('');
                $('#lname').val('');

            }else if (id == "clear_pid" ){
                $('#pid').val('');

            }else if (id == "clear_sent"){

                $('#form_from_date_sent').val('');
                $('#form_to_date_sent').val('');
            }
        });



    });


</script>


</html>
