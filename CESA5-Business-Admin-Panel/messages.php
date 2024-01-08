<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (!isset($_SESSION["masquerade"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>
                <style>
                    .choices
                    {
                        width: 100% !important;
                    }

                    .choices__list--multiple .choices__item 
                    {
                        background-color: #00376d !important;
                    }
                </style>

                <script>
                    /** function to send a message */
                    function sendMessage()
                    {
                        // get the values from the "Compose Message" modal
                        let recipients = $("#recipients").val();
                        let subject = $("#subject").val();
                        let message = $("#message").val();

                        // create the string of data to send
                        let sendString = "recipients="+JSON.stringify(recipients)+"&subject="+subject+"&message="+message;

                        <?php if ($_SESSION["role"] == 1) { // allow admins to mark a message as important ?>
                            let important = $("#important").val();
                            sendString += "&important="+important;
                        <?php } ?>

                        // send the data to send the message
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/messages/sendMessage.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // initialize the status header 
                                let status_title = "Send Message Status";

                                // initialize the status body
                                let status_body = "";

                                // get the status
                                let status = this.responseText;
                                if (status == 0) // success code - refresh page when status modal closes
                                {
                                    // create and display the status modal
                                    status_body = "Successfully sent the message to all recipients!";
                                    createStatusModal("refresh", status_title, status_body);
                                }
                                else // fail code - do not refresh the page when status modal closes - re-open compose message modal
                                {
                                    if (status == 1) 
                                    { 
                                        // create and display status modal
                                        status_body = "Failed to send the message. You must select at least 1 recipient to send the message to.<br>";
                                        createStatusModal("alert", status_title, status_body);
                                    }
                                    else if (status == 2) 
                                    { 
                                        // create and display status modal
                                        status_body = "Failed to send the message. You must provide a subject for the message!<br>";
                                        createStatusModal("alert", status_title, status_body);
                                    }
                                    else if (status == 3) 
                                    { 
                                        // create and display status modal
                                        status_body = "Failed to send the message. You message cannot be blank!<br>";
                                        createStatusModal("alert", status_title, status_body);
                                    }
                                    else if (status == 4) 
                                    { 
                                        // create and display status modal
                                        status_body = "No messages sent. An unknown errors has occurred! Please try again later.<br>";
                                        createStatusModal("refresh", status_title, status_body);
                                    }
                                    else
                                    {
                                        // create and display the status modal
                                        status_body = status;
                                        createStatusModal("refresh", status_title, status_body);
                                    }
                                }

                                // hide the current modal
                                $("#composeMessageModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    <?php if ($_SESSION["role"] == 1) { // allow admins to mark a message as important ?>
                        /** function to toggle a message as important */
                        function toggleImportant()
                        {
                            // store the important element
                            let element = document.getElementById("important");

                            // get the current value of the button
                            let current = element.value;

                            // if the button is currently set as important
                            if (current == 0) // set to important
                            {
                                element.classList.remove("btn-secondary");
                                element.classList.add("btn-danger");
                                element.classList.add("fw-bold");
                                element.value = 1;
                            }
                            else // otherwise set to not important
                            {
                                element.classList.remove("btn-secondary");
                                element.classList.remove("btn-danger");
                                element.classList.remove("fw-bold");
                                element.classList.add("btn-secondary");
                                element.value = 0;
                            }
                        }
                    <?php } ?>
                </script>

                <!-- Body -->
                <div class="container-fluid">
                    <!-- Buttons & Search -->
                    <div class="row my-2">
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-3 col-xxl-2 my-1">
                            <button class="btn btn-primary btn-lg w-100 p-3" type="button" data-bs-toggle="modal" data-bs-target="#composeMessageModal"><i class="fa-solid fa-pen"></i> Compose Message</button>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-8 col-xl-9 col-xxl-10 my-1">
                            <!-- Search Inboxes -->
                            <div class="input-group w-100 h-100" id="search-inboxes-div">
                                <div class="input-group-prepend">
                                    <span class="input-group-text h-100"><i class="fa-solid fa-magnifying-glass"></i></span>
                                </div>
                                <input class="form-control" id="search-inboxes" name="search-inboxes" placeholder="Search inboxes">
                            </div>
                        </div>
                    </div>

                    <!-- Inbox -->
                    <div class="row mb-3">
                        <div class="col-12 col-sm-12 col-md-2 col-lg-2 col-xl-2 col-xxl-2">
                            <button class="btn btn-secondary w-100 text-start my-1 active" id="messages-btn" onclick="show('messages');"><i class="fa-solid fa-envelope me-2"></i> Inbox</button>
                            <button class="btn btn-secondary w-100 text-start my-1" id="sent-btn" onclick="show('sent');"><i class="fa-solid fa-paper-plane me-2"></i> Sent</button>
                        </div> 

                        <div class="col-12 col-sm-12 col-md-10 col-lg-10 col-xl-10 col-xxl-10">
                            <!-- Messages Received -->
                            <div id="messages-table-div">
                                <table class="inbox-table w-100" id="messages">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>ID</th>
                                            <th>From</th>
                                            <?php if ($_SESSION["id"] == 0) { ?><th>To</th><?php } ?>
                                            <th>Subject</th>
                                            <th>Message</th>
                                            <th>Time</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>

                            <!-- Messages Sent -->
                            <div class="d-none" id="sent-table-div">
                                <table class="inbox-table w-100" id="sent">
                                    <thead>
                                        <tr>
                                            <th></th>
                                            <th>ID</th>
                                            <th>To</th>
                                            <th>Subject</th>
                                            <th>Message</th>
                                            <th>Time</th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!--
                    MODALS
                -->
                <!-- Compose Message Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="composeMessageModal" data-bs-backdrop="static" aria-labelledby="composeMessageModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="composeMessageModalLabel">Compose Message</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-row mb-3">
                                    <label for="recipients" class="form-label m-0">Recipient(s):</label>
                                    <div class="input-group flex-nowrap">
                                        <span class="input-group-text" id="recipient-icon"><i class="fa-solid fa-users"></i></span>
                                        <select id="recipients" name="recipients[]" class="form-select w-100" aria-label="recipients" aria-describedby="recipient-icon" style="height: 72px" multiple>
                                            <?php
                                                // get a list of all active users
                                                $getUsers = mysqli_query($conn, "SELECT id, fname, lname, email FROM users WHERE status=1 ORDER BY fname ASC, lname ASC");
                                                if (mysqli_num_rows($getUsers) > 0)
                                                {
                                                    while ($user = mysqli_fetch_array($getUsers))
                                                    {
                                                        // store user details locally
                                                        $id = $user["id"];
                                                        $fname = $user["fname"];
                                                        $lname = $user["lname"];
                                                        $email = $user["email"];

                                                        // create the string to display as the option
                                                        $option_str = $fname . " " . $lname . " (" . $email . ")";

                                                        // display the option with employee ID as the value
                                                        echo "<option value='".$id."'>".$option_str."</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row mb-3">
                                    <label for="recipients" class="form-label m-0">Subject:</label>
                                    <div class="input-group flex-nowrap">
                                        <span class="input-group-text" id="subject-icon"><i class="fa-solid fa-star"></i></span>
                                        <input id="subject" name="subject" class="form-control" type="text" aria-label="subject" aria-describedby="subject-icon" >
                                    </div>
                                </div>

                                <div class="form-row mb-3">
                                    <label for="recipients" class="form-label m-0">Message:</label>
                                    <div class="input-group flex-nowrap">
                                        <span class="input-group-text" id="message-icon"><i class="fa-solid fa-comment"></i></span>
                                        <textarea id="message" name="message" class="form-control" type="text" aria-label="message" aria-describedby="message-icon" rows="8"></textarea>
                                    </div>
                                </div>

                                <?php if ($_SESSION["role"] == 1) { // allow admins to mark a message as important ?>
                                    <div class="form-row">
                                        <button class="btn btn-secondary btn-sm w-100 p-2" id="important" name="important" value="0" onclick="toggleImportant();">
                                            <div class="row">
                                                <div class="col-1 text-start"><i class="fa-solid fa-triangle-exclamation"></i></div> 
                                                <div class="col-10">Mark Message As Important</div>
                                                <div class="col-1"></div>
                                            </div>
                                        </button>
                                    </div>
                                <?php } ?>
                            </div>

                            <div class="modal-footer">
                                <button type="submit" class="btn btn-primary" onclick="sendMessage();"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Compose Message Modal -->

                <!-- View Message Modal -->
                <div id="message-modal-div"></div>
                <!-- End View Message Modal -->

                <!-- ViewSent  Message Modal -->
                <div id="sent-message-modal-div"></div>
                <!-- End View Sent Message Modal -->
                <!--
                    END MODALS
                -->

                <script>
                    var secondElement = new Choices('#recipients', 
                        { 
                            allowSearch: true,
                            removeItemButton: true,
                        }
                    );

                    // initialize the message recieved table
                    var messages = $("#messages").DataTable({
                        ajax: {
                            url: "ajax/messages/getMessages.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            <?php if ($_SESSION["id"] == 0) { // super admin messages ?> 
                                { data: "important", orderable: false, width: "2.5%" },
                                { data: "id", orderable: false, width: "5%" },
                                { data: "from", orderable: false, width: "12.5%" },
                                { data: "to", orderable: false, width: "12.5%" },
                                { data: "subject", orderable: false, width: "20%", render: $.fn.dataTable.render.ellipsis(32) },
                                { data: "message", orderable: false, width: "30%", render: $.fn.dataTable.render.ellipsis(64) },
                                { data: "time", orderable: false, width: "10%" },
                                { data: "actions", orderable: false, width: "10%" },
                            <?php } else { ?>
                                { data: "important", orderable: false, width: "2.5%" },
                                { data: "id", orderable: false, visible: false },
                                { data: "from", orderable: false, width: "15%" },
                                { data: "subject", orderable: false, width: "25%", render: $.fn.dataTable.render.ellipsis(32) },
                                { data: "message", orderable: false, width: "37.5%", render: $.fn.dataTable.render.ellipsis(64) },
                                { data: "time", orderable: false, width: "10%" },
                                { data: "actions", orderable: false, width: "10%", className: "td-disable-click" },
                            <?php } ?>
                        ],
                        dom: 'lrtip',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        order: [], // disable default sort
                        rowCallback: function (row, data, index)
                        {
                            // set the status box to failed if inactive
                            if (data["read_by_recipient"] == 0) { $(row).addClass("unread"); }
                        }
                    });

                    // initialize the sent message table
                    var sent = $("#sent").DataTable({
                        ajax: {
                            url: "ajax/messages/getSent.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            <?php if ($_SESSION["id"] == 0) { // super admin messages ?> 
                                { data: "important", orderable: false, width: "2.5%" },
                                { data: "id", orderable: false, width: "5%" },
                                { data: "to", orderable: false, width: "12.5%" },
                                { data: "subject", orderable: false, width: "25%", render: $.fn.dataTable.render.ellipsis(32) },
                                { data: "message", orderable: false, width: "45%", render: $.fn.dataTable.render.ellipsis(64) },
                                { data: "time", orderable: false, width: "10%" },
                            <?php } else { ?>
                                { data: "important", orderable: false, width: "2.5%" },
                                { data: "id", orderable: false, visible: false },
                                { data: "to", orderable: false, width: "15%" },
                                { data: "subject", orderable: false, width: "25%", render: $.fn.dataTable.render.ellipsis(32) },
                                { data: "message", orderable: false, width: "47.5%", render: $.fn.dataTable.render.ellipsis(64) },
                                { data: "time", orderable: false, width: "10%" },
                            <?php } ?>
                        ],
                        dom: 'lrtip',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        order: [], // disable default sort
                    });

                    // view message if row is clicked
                    $("#messages tbody").on("click", "tr", function (event) {
                        var data = messages.row(this).data();
                        var message_id = data["id"];
                        getViewMessageModal(message_id, messages);
                    });

                    // view sent message if row is clicked
                    $("#sent tbody").on("click", "tr", function () {
                        var data = sent.row(this).data();
                        var message_id = data["id"];
                        getViewSentMessageModal(message_id);
                    });

                    /** function to create the view message modal based on message ID */
                    function getViewMessageModal(message_id, message)
                    {
                        // send the data to send the message
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/messages/getMessageModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // set the modal div
                                document.getElementById("message-modal-div").innerHTML = this.responseText;

                                // show the message modal
                                $("#messageModal").modal("show");
                                
                                // reload the messages table
                                messages.ajax.reload();
                            }
                        };
                        xmlhttp.send("message_id="+message_id);
                    }

                    /** function to create the view message modal based on message ID */
                    function getViewSentMessageModal(message_id)
                    {
                        // send the data to send the message
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/messages/getViewSentMessageModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // set the modal div
                                document.getElementById("sent-message-modal-div").innerHTML = this.responseText;

                                // show the message modal
                                $("#sentMessageModal").modal("show");
                            }
                        };
                        xmlhttp.send("message_id="+message_id);
                    }

                    /** function to show different inboxes */
                    function show(type)
                    {
                        // hide all inboxes
                        document.getElementById("messages-table-div").classList.add("d-none");
                        document.getElementById("sent-table-div").classList.add("d-none");

                        // deactivate sidebar buttons
                        document.getElementById("messages-btn").classList.remove("active");
                        document.getElementById("sent-btn").classList.remove("active");

                        // display the inbox selected
                        document.getElementById(type+"-table-div").classList.remove("d-none");

                        // activate the selected sidebar button
                        document.getElementById(type+"-btn").classList.add("active");
                    }

                    // create the custom search filters
                    $("#search-inboxes").keyup(function() {
                        messages.search($(this).val()).draw();
                        sent.search($(this).val()).draw();
                    });

                    /** function to delete a message */
                    function deleteMessage(message_id)
                    {
                        // send the data to send the message
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/messages/deleteMessage.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // close the message modal
                                $("#messageModal").modal("hide");

                                // reload the messages table
                                messages.ajax.reload();
                            }
                        };
                        xmlhttp.send("message_id="+message_id);
                    }
                </script>
            <?php

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
    
    include_once("footer.php"); 
?>