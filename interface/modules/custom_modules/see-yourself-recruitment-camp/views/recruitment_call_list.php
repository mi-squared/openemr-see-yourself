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
            let rowColor;



            let rel1color ="rgb(133, 66, 4)";
            let rel2color ="rgba(6, 6, 172, 0.83)";
            let rel3color="rgba(13, 71, 10, 0.83)";
            let rel4color ="rgba(246, 23, 216, 0.83)";
            let defaultcolor = "Black";
          ;


            let response = `
    <div class="ptinfo">
        <!-- First Row -->
        <div class="grid-item">
            <button type="button" style="padding: 4px 10px;" align="left" id="call_button" class="call_button"> Call </button>
        </div>
        <div class="grid-item">
            <label for="callStatus" class="dropdown-label">Call Status</label>
        </div>
        <div class="grid-item">
            <label for="qualifications" class="dropdown-label">Qualifications</label>
        </div>
        <div class="grid-item">
            <label for="Notes" class="label">Notes</label>
        </div>
        <div class="grid-item">
            <label for="meetsQual" class="dropdown-label">Meets Qualifications</label>
        </div>
        <div class="grid-item">
            <label for="nextStep" class="dropdown-label">Next Step</label>
        </div>
        <div class="grid-item">
            <button type="button" class="save_button" style="padding: 4px 10px;" align="left" id="save_record"> Save </button>
        </div>
        <div class="grid-item">
            <button type="button" class="undo_button" style="padding: 4px 10px;" align="left" id="UnDo"> Undo </button>
        </div>

        <!-- Second Row -->
        <div class="grid-item"></div>
        <div class="grid-item">
            <select id="callStatus" name="callStatus">
                <option value="na">N/A</option>
                <option value="LVM">LVM</option>
                <option value="Contact">Contact</option>
                <option value="Refused">Refused</option>
            </select>
        </div>
        <div class="grid-item">
            <select id="qualifications" name="qualifications">
                <option value="q1">Interest</option>
                <option value="q2">Willing and Able</option>
                <option value="q3">Meets Health Criteria</option>
                <option value="q4">Commits to Program</option>
            </select>
        </div>
        <div class="grid-item">
            <input type="text" class="form_name" name="notes" id="notes" placeholder="Last" value="">
        </div>
        <div class="grid-item">
            <select id="meetsQual" name="meetsQual">
                <option value="q1">Yes</option>
                <option value="q2">No</option>
                <option value="q3">No Decision Yet</option>
            </select>
        </div>
        <div class="grid-item">
            <select id="nextStep" name="nextStep">
                <option value="q1">Move to Program</option>
                <option value="q2">Wait for more info</option>
                <option value="q3">Patient is DQ'd</option>
            </select>
        </div>
        <div class="grid-item"></div>
        <div class="grid-item"></div>


        <div class="ptCallHist">
            <div class="title" style="grid-column: 1 / span 4; text-align: center;">Call History</div>
            <div class="title">Date</div>
            <div class="title">Agent</div>
            <div class="title">Result</div>
            <div class="title">Status</div>
            <div class="data">Date</div>
            <div class="data">Agent</div>
            <div class="data">Result</div>
            <div class="data">Status</div>
            <div class="data">Date</div>
            <div class="data">Agent</div>
            <div class="data">Result</div>
            <div class="data">Status</div>
            <div class="data">Date</div>
            <div class="data">Agent</div>
            <div class="data">Result</div>
            <div class="data">Status</div>
        </div>

        <div class="ptQual">
            <div class="title" style="grid-column: 1 / span 4; text-align: center;">Qualifying Criteria</div>
            <div class = "data">
                <ul>
                    <li><input type="checkbox" disabled checked> Criteria 1</li>
                    <li><input type="checkbox" disabled checked> Criteria 2</li>
                    <li><input type="checkbox" disabled checked> Criteria 3</li>
                </ul>
            </div>
        </div>
         <div class="ptNotes">
            <div class="title" style="grid-column: 1 / span 6; text-align: center;">Notes</div>
            <div> Clicking on the patients name will open their demographics window.  If
                you would like we can automatically open a pts window using the call button
            </div>
        </div>

          <div class="ptCallHist">
            <div class="title" style="grid-column: 1 / span 4; text-align: center;">More Stuff</div>
            <div class = "data">
            <br>???<br>???<br>???
            </div>
        </div>

        <div class="ptQual">
            <div class="title" style="grid-column: 1 / span 6; text-align: center;">More Stuff</div>
            <div class = "data">
            <br>???<br>???<br>???
            </div>
        </div>



   </div>
`;

            response += '</div>'; //end of ptinfo grid:Parent grid

            return response;

        }


        $(document).ready(function() {

            let oTable;

            oTable=$('#show_sms_table').DataTable({
                dom: 'Bfrtip',
                autoWidth: true,
                scrollY: false,
                fixedHeader: true,
                stripeClasses: ['odd-row', 'even-row'],
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
                    { className: "ptN compact details-control", "targets": [ 2 ] },
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
                    .columns( 0 )
                    .search( this.value)
                    .draw();
            } );

            $('#column1_pid_search_show_sms_table').on( 'keyup', function () {
                oTable
                    .columns( 1 )
                    .search( this.value )
                    .draw();
            } );

            $('#column2_name_search_show_sms_table').on( 'keyup', function () {
                oTable
                    .columns( 2 )
                    .search( this.value )
                    .draw();
            } );

            $('#column3_cell_search_show_sms_table').on( 'keyup', function () {
                oTable
                    .columns( 3 )
                    .search( this.value )
                    .draw();
            } );



            $("button.dt-button").css('color', 'black !important');

            // Event listener for opening and closing responses
            // Todo: make this open always
            // Todo: add ability to respond
            // Todo: make sure patient notes includes these interactions, which I think they already do
            $('#show_sms_table tbody').on('click', 'td.details-control', function () {
                let tr = $(this).closest('tr');
                let row = oTable.row(tr);
                let rowIndex = tr.index(); // Get the index of the clicked row

                // Close all other rows and reset their CSS properties
                $('#show_sms_table tbody dt-hasChild tr.shown').each(function () {
                    let otherRow = oTable.row(this);
                    otherRow.child.hide();
                    $(this).removeClass('shown');
                    // Reset CSS properties for other rows
                    $(this).css({
                        'background-color': '',    // Reset background color
                        'margin-bottom': ''        // Reset margin
                    });
                });

                if (row.child.isShown()) {
                    console.log("Clicked is shown");
                    // This row is already open - close it and reset CSS properties
                    row.child.hide();
                    tr.removeClass('shown');
                    tr.css({
                        'background-color': '',    // Reset background color
                        'margin-bottom': ''        // Reset margin
                    });

                    // Reset the top margin for the following row
                    let nextRow = $('#show_sms_table tbody tr:eq(' + (rowIndex + 1) + ')');
                    nextRow.css('margin-top', '');
                } else {
                    // Open this row and apply CSS properties using inline styles
                    row.child(format(row.data())).show();
                    tr.addClass('shown');
                    // Add CSS properties to the opened row using inline styles


                    // Set the top margin for the following row
                    let nextRow = $('#show_sms_table tbody tr:eq(' + (rowIndex + 1) + ')');
                    nextRow.css('margin-top', '3em');
                }
            });





            $('#show_sms_table tbody').on('click', 'td.ptN', function () {
                console.log("Clicked on the name!");
                let tr = $(this).closest('tr');
                let data = oTable.row(tr).data();
                console.log(data);
                if (!data || typeof data !== 'object' || !data['pid']) {
                    console.error('Invalid data for PID.');
                    return;
                }

                let newpid = data['pid'];

                if (newpid.length === 0) {
                    return;
                }

                // Update your condition here to control the behavior
                if (0) {
                    openNewTopWindow(newpid);
                } else {
                    top.restoreSession();
                    top.RTop.location = "<?php echo $GLOBALS['webroot']; ?>/interface/patient_file/summary/demographics.php?set_pid=" + newpid;
                    top.left_nav.closeErx();
                }
            });
;

            function openNewTopWindow(pid) {
                document.fnew.patientID.value = pid;
                top.restoreSession();
                document.fnew.submit();
            }

            $('.call_button').on('click', '' , function () {
                    alert("Pressing this button will update the call history");

            } );

            $('.save_button').on('click', '' , function () {
                alert("Pressing this button will update patient record with new info");

            } );

            $('.undo_button').on('click', '' , function () {
                alert("Unsure if we want this button.  There is probably a better solutions");

            } );


        });





    </script>
</head>
<body class="body_top formtable">
<span class='title'><?php xl('Report','e'); ?> - <?php xl('Recruitment Campaign','e');  ?></span>
<div class="position-static">
    <div class="content">

        <form name="theform" id="theform" method="post" action="./recruitment_call_list.php" onsubmit="return top.restoreSession()">
            <div id="report_parameters" class="grid-form">
            <!-- First Row Patient Name-->
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

                <!-- Second Row - PID and Cellphone -->
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

                <!-- Third Row -->

                <div class="grid-item">
                    <input value="<?php echo htmlspecialchars(xl('Submit')) ?> " type="submit" id="submit_selector" name="sms_submit" ><?php ?>
                    <input hidden id = 'submit_button' value = '<?php $_POST['sms_submit']  ?? '' ?>'>
                </div>
                <div class="grid-item">

                </div>
                <div class="grid-item">

                </div>
                <div class="grid-item">

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


        $("#clear").on('click', function(){

            $('#clear_app').trigger('click');
            $('#clear_name').trigger('click');
            $('#clear_pid').trigger('click');
            $('#clear_sent').trigger('click');

        });

        $("#clear_app, #clear_name, #clear_pid, #clear_sent").on('click', function(){

            let id = this.id;
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
