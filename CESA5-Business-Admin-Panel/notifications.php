<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?> 
                <!-- Page Specific Styling -->
                <style>
                    .accordion-header, .accordion-button
                    {
                        font-size: 20px !important;
                        font-weight: 500 !important;
                    }

                    <?php if (isset($USER_SETTINGS) && $USER_SETTINGS["dark_mode"] == 1) { ?>
                        .accordion-header, .accordion-button
                        {
                            background-color: #1c1c1c !important;
                            color: #ffffff !important;
                        }

                        .accordion-item
                        {
                            background-color: #1c1c1c !important;
                            color: #ffffff !important;
                        }
                    <?php } ?>
                </style>

                <script>
                    // initialize array to store all tables 
                    var recipients = [];
                    
                    /** function to toggle notification for the setting clicked */
                    function toggleNotification(element_id)
                    {
                        let element = document.getElementById(element_id);
                        let value = element.value;
                        
                        if (value == 0)
                        {
                            element.classList.remove("btn-outline-secondary");
                            element.classList.remove("switch-inactive");
                            element.classList.add("btn-success");
                            element.classList.add("switch-active");
                            element.value = 1;
                        }
                        else
                        {
                            element.classList.remove("btn-success");
                            element.classList.remove("switch-active");
                            element.classList.add("btn-outline-secondary");
                            element.classList.add("switch-inactive");
                            element.value = 0;
                        }
                    }

                    /** function to save notification settings */
                    function saveNotifications()
                    {
                        // initialize status body
                        let status_body = "";

                        // save settings for each notification
                        for (let n = 0; n < recipients.length; n++)
                        {
                            // initialize notification ID
                            let id = $("#notification_id-"+n).val();

                            // get status of notification
                            let status = $("#noti-"+n+"-switch").val();

                            // get the employees selected to be in the department
                            let recipients_table = $('#recipients-'+n).DataTable();
                            let count = recipients_table.rows({ selected: true }).count();
                            let recipients = [];
                            for (let r = 0; r < count; r++) { recipients.push(recipients_table.rows({ selected: true }).data()[r]["user_id"]); }

                            // build string of data to send
                            let sendString = "notification_id="+id+"&status="+status+"&recipients="+JSON.stringify(recipients);

                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/notifications/editNotification.php", false);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    status_body += this.responseText;
                                }
                            };
                            xmlhttp.send(sendString);
                        }

                        // create the status modal
                        let status_title = "Saving Notifications Status";
                        createStatusModal("refresh", status_title, status_body);
                    }
                </script>

                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="manage.php" title="Return to Manage."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Notifications</div>
                    </h1>
                </div>

                <div class="alert alert-danger text-center mx-3 mb-3" role="alert">
                    <i class="fa-solid fa-triangle-exclamation"></i> <b>THIS PAGE IS CURRENTLY UNDERGOING DEVELOPMENT. NOTIFICATION FEATURES WILL NOT ALL FUNCTION YET.</b>
                </div>

                <?php
                    $getNotificationTypes = mysqli_query($conn, "SELECT * FROM email_types ORDER BY type ASC");
                    if (mysqli_num_rows($getNotificationTypes) > 0)
                    {
                        $index = 0;
                        while ($notification_type = mysqli_fetch_array($getNotificationTypes))
                        {
                            // store notification details locally
                            $id = $notification_type["id"];
                            $label = $notification_type["type"];
                            $desc = $notification_type["description"];
                            $active = $notification_type["active"];

                            ?>
                                <!-- <?php echo $label; ?> -->
                                <div class="row d-flex justify-content-center align-items-top mb-3 mx-0">
                                    <div class="col-2 col-sm-2 col-md-2 col-lg-1 col-xl-1 col-xxl-1 btn-toggle-switch">
                                        <?php if ($active == 1) { ?>
                                            <button class="btn btn-success switch-active w-100 h-100" id="noti-<?php echo $index; ?>-switch" value="1" onclick="toggleNotification('noti-<?php echo $index; ?>-switch');"><i class="fa-solid fa-power-off fa-lg"></i></button>
                                        <?php } else { ?>
                                            <button class="btn btn-outline-secondary switch-inactive w-100 h-100" id="noti-<?php echo $index; ?>-switch" value="0" onclick="toggleNotification('noti-<?php echo $index; ?>-switch');"><i class="fa-solid fa-power-off fa-lg"></i></button>
                                        <?php } ?>
                                    </div>

                                    <div class="col-10 col-sm-10 col-md-10 col-lg-11 col-xl-11 col-xxl-11">
                                        <div class="accordion" id="accordionExample">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="false" aria-controls="collapse<?php echo $index; ?>">
                                                        <?php echo $label; ?>
                                                    </button>
                                                </h2>
                                                <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#accordionExample">
                                                    <div class="accordion-body">
                                                        <p><?php echo $desc; ?></p>

                                                        <input type="hidden" id="notification_id-<?php echo $index; ?>" value="<?php echo $id; ?>">
                                                        <table id="recipients-<?php echo $index; ?>" class="report_table w-100">
                                                            <thead>
                                                                <tr>
                                                                    <th class="text-center py-1 px-2">User ID</th>
                                                                    <th class="text-center py-1 px-2">User Email</th>
                                                                    <th class="text-center py-1 px-2">Last Name</th>
                                                                    <th class="text-center py-1 px-2">First Name</th>
                                                                    <th class="text-center py-1 px-2">Subscribed</th>
                                                                    <th class="text-center py-1 px-2">Frequency</th>
                                                                </tr>
                                                            </thead>
                                                        </table>
                                                        <?php createTableFooterV2("recipients-".$index, "BAP_Notifications".$index."_PageLength", $USER_SETTINGS["page_length"], true, true); ?>

                                                        <script>
                                                            // initialize the edit department members table                  
                                                            $(document).ready(function () {
                                                                recipients.push($("#recipients-<?php echo $index; ?>").DataTable({
                                                                    ajax: {
                                                                        url: "ajax/notifications/getNotificationRecipients.php",
                                                                        type: "POST",
                                                                        data: {
                                                                            notification_id: <?php echo $id; ?>
                                                                        }
                                                                    },
                                                                    autoWidth: false,
                                                                    pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                                                    lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                                                    columns: [
                                                                        { data: "user_id", orderable: true, width: "10%", className: "text-center" },
                                                                        { data: "email", orderable: true, width: "25%", className: "text-center" },
                                                                        { data: "lname", orderable: true, width: "20%", className: "text-center" },
                                                                        { data: "fname", orderable: true, width: "20%", className: "text-center" },
                                                                        { data: "subscribed", orderable: true, width: "12.5%", className: "text-center" },
                                                                        { data: "frequency", orderable: true, width: "12.5%", className: "text-center" },
                                                                    ],
                                                                    select: {
                                                                        style: "multi"
                                                                    },
                                                                    dom: 'frt',
                                                                    language: {
                                                                        search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                                                        lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                                                        info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                                                    },
                                                                    order: [
                                                                        [ 4, "desc" ],
                                                                        [ 2, "asc" ],
                                                                        [ 3, "asc" ]
                                                                    ],
                                                                    rowCallback: function (row, data, index)
                                                                    {
                                                                        updatePageSelection("recipients-<?php echo $index; ?>");
                                                                    },
                                                                    initComplete: function () {
                                                                        // pre-select rows of employees that are already within the department
                                                                        let data = recipients[<?php echo $index; ?>].rows().data();
                                                                        for (let r = 0; r < data.length; r++) { if (data[r]["subscribed"] == 1) { recipients[<?php echo $index; ?>].row(":eq("+r+")").select(); } }
                                                                    }
                                                                }));
                                                            });
                                                        </script>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php

                            // increment index
                            $index++;
                        }

                        ?>
                            <!-- Save Button -->
                            <div class="row d-flex justify-content-center position-sticky bottom-0 mx-auto mt-3 mb-1">
                                <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-4 col-xxl-4">
                                    <button class="btn btn-primary btn-lg px-5 py-3 w-100" type="button" onclick="saveNotifications();">
                                        <i class="fas fa-save"></i> Save Notification Settings
                                    </button>
                                </div>
                            </div>
                        <?php
                    }
                ?>
            <?php
            
            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include_once("footer.php"); 
?>