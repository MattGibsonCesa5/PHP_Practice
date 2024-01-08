<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"]))
        {
            ?> 
                <div class="report">
                    <div class="table-header">
                        <div class="row d-flex justify-content-center align-items-center text-center p-2">
                            <div class="col-12 col-sm-12 col-md-8 col-lg-6 col-xl-6 col-xxl-6">
                                <h1 class="report-title m-0">Employee Changes</h1>
                                <p class="report-description m-0">This report displays all <b>marked</b> employee changes.</p>
                            </div>
                        </div>
                    </div>

                    <div class="table-header">
                        <div class="row d-flex justify-content-center align-items-center text-center p-2">
                             <!-- Page Length -->
                             <div class="col-12 col-sm-12 col-md-12 col-lg-3 col-xl-3 col-xxl-3">
                                <?php createPageLengthContainer("report_table", "BAP_MisbudgetedDaysReport_PageLength", $USER_SETTINGS["page_length"]); ?>
                            </div>

                            <!-- Filters -->
                            <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6">
                                <div class="row justify-content-center">
                                    <!-- Search All -->
                                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6 h-100 px-2">
                                        <div class="input-group h-auto">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text h-100" id="nav-search-icon">
                                                    <label for="search-all"><i class="fa-solid fa-magnifying-glass"></i></label>
                                                </span>
                                            </div>
                                            <input class="form-control" type="text" placeholder="Search table..." id="search-all" name="search-all" autocomplete="off">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Table Buttons -->
                            <div class="col-12 col-sm-12 col-md-12 col-lg-3 col-xl-3 col-xxl-3">
                                <span class="d-flex justify-content-end" id="report-buttons"></span>
                            </div>
                        </div>
                    </div> 

                    <div class="row report-body m-0">
                        <table id="report_table" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th>Employee ID</th>
                                    <th>First Name</th>
                                    <th>Last Name</th>
                                    <th>Field Changed</th>
                                    <th>Changed From</th>
                                    <th>Notes</th>
                                    <th>Changed By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooter("report_table"); ?>
                    </div>
                </div>

                <!-- MODALS -->
                <div id="remove-marked_changed-modal-div"></div>
                <!-- END MODALS -->

                <script>
                    var table = $("#report_table").DataTable({
                        ajax: {
                            url: "ajax/reports/getEmployeeChanges.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "id", orderable: true, width: "10%" },
                            { data: "fname", orderable: true, width: "12.5%" },
                            { data: "lname", orderable: true, width: "12.5%" },
                            { data: "field_changed", orderable: true, width: "12.5%" },
                            { data: "changed_from", orderable: true, width: "12.5%" },
                            { data: "notes", orderable: true, width: "22.5%" },
                            { data: "changed_by", orderable: true, width: "10%" },
                            <?php if (isset($PERMISSIONS["EDIT_EMPLOYEES"])) { ?>
                            { data: "actions", orderable: false, width: "7.5%" }
                            <?php } else { ?>
                            { data: "actions", orderable: false, visible: false }
                            <?php } ?>
                        ],
                        dom: 'rt',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        rowCallback: function (row, data, index)
                        {
                            updatePageSelection("report_table");
                        },
                    });

                    /** function to get the modal to delete a marked employee change */
                    function getDeleteMarkedChangeModal(id)
                    {
                        // send the data to create the edit employee modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/getDeleteMarkedChangeModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the edit employee modal
                                document.getElementById("remove-marked_changed-modal-div").innerHTML = this.responseText;
                                $("#removeMarkedChangeModal").modal("show");
                            }
                        }
                        xmlhttp.send("change_id="+id);
                    }

                    /** function to remove the marked change */
                    function removeMarkedChange(id)
                    {
                        // send the data to create the edit employee modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/employees/removeMarkedChange.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Remove Marked Change Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#removeMarkedChangeModal").modal("hide");
                            }
                        }
                        xmlhttp.send("change_id="+id);
                    }
                </script>
            <?php 
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>