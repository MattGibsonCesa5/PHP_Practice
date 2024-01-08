<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"]))
        {
            ?> 
                <div class="report">
                    <div class="row justify-content-center report-header mb-3 mx-0"> 
                        <div class="col-sm-12 col-md-8 col-lg-8 col-xl-6 col-xxl-6 p-0">
                            <fieldset class="border p-2">
                                <legend class="float-none w-auto px-4 py-0 m-0"><h1 class="report-title m-0">Budgeted Test Employees</h1></legend>
                                <div class="report-description">
                                    This report displays a list of all test employees that admins and/or directors have added to their projects.
                                </div>

                                <div class="row report-header justify-content-center mx-0"> 
                                    <div class="col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-5 p-2">
                                        <button class="btn btn-primary w-100" type="button" data-bs-toggle="modal" data-bs-target="#includeTestsModal">Include All Test Employees In Costs</button>
                                    </div>

                                    <div class="col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-5 p-2">
                                        <button class="btn btn-primary w-100" type="button" data-bs-toggle="modal" data-bs-target="#excludeTestsModal">Exclude All Test Employees In Costs</button>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    </div>

                    <div class="row report-body m-0">
                        <table id="report_table" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Label</th>
                                    <th>Project Code</th>
                                    <th>Project Name</th>
                                    <th>Days In Project</th>
                                    <th>Included In Counts?</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <!-- MODALS -->
                <div id="remove-employee_from_project-modal-div"></div>

                <div class="modal fade" tabindex="-1" role="dialog" id="includeTestsModal" aria-labelledby="includeTestsModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="includeTestsModalLabel">Include All Test Employees In Costs</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <p>Are you sure you want to include all test employees in the active period to all cost calculations?</p>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="includeAll();">Include All</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" tabindex="-1" role="dialog" id="excludeTestsModal" aria-labelledby="excludeTestsModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="excludeTestsModalLabel">Exclude All Test Employees In Costs</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <p>Are you sure you want to exclude all test employees in the active period from all cost calculations?</p>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-danger" onclick="excludeAll();">Exclude All</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- END MODALS -->

                <script>
                    var table = $("#report_table").DataTable({
                        ajax: {
                            url: "ajax/reports/getTestEmployees.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "id", width: "10%", orderable: true },
                            { data: "label", width: "20%", orderable: true },
                            { data: "project_code", width: "10%", orderable: true },
                            { data: "project_name", width: "20%", orderable: true },
                            { data: "project_days", width: "15%", orderable: true },
                            { data: "inclusion", width: "12.5%", orderable: true },
                            <?php if (isset($PERMISSIONS["BUDGET_PROJECTS_ALL"]) || isset($PERMISSIONS["BUDGET_PROJECTS_ASSIGNED"])) { ?>
                            { data: "actions", width: "12.5%", orderable: false }
                            <?php } else { ?>
                            { data: "actions", orderable: false, visible: false }
                            <?php } ?>
                        ],
                        dom: 'lfrtip',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        paging: true,
                        rowCallback: function (row, data, index)
                        {
                            // display inclusion status
                            if (data["inclusion"] == 0) 
                            { 
                                $("td:eq(5)", row).addClass("period-inactive text-center m-0 p-0"); 
                                $("td:eq(5)", row).html("<i class=\"fa-solid fa-xmark\"></i>");
                            }
                            else if (data["inclusion"] == 1) 
                            { 
                                $("td:eq(5)", row).addClass("period-active text-center m-0 p-0"); 
                                $("td:eq(5)", row).html("<i class=\"fa-solid fa-check\"></i>");
                            }
                        }
                    });

                    /** function to get the delete department modal */
                    function getRemoveTestEmployeeFromProjectModal(employee_id, project_code)
                    {
                        // send the data to create the delete department modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/getRemoveTestEmployeeFromProjectModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("remove-employee_from_project-modal-div").innerHTML = this.responseText;     
                                $("#removeTestEmployeeFromProjectModal").modal("show");
                            }
                        };
                        xmlhttp.send("code="+project_code+"&id="+employee_id);
                    }

                    /** function to remove a test employee from the project */
                    function removeTestEmployeeFromProject(id, code)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/removeTestEmployeeFromProject.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Remove Test Employee From Project Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#removeTestEmployeeFromProjectModal").modal("hide");
                            }
                        };
                        xmlhttp.send("code="+code+"&id="+id);
                    }

                    /** function to toggle the cost inclusion setting for the test project employee */
                    function toggleInclusion(id, code)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/toggleCostInclusion.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal only if success
                                if (this.responseText != "" || this.responseText != null || this.responseText != undefined)
                                {
                                    let status_title = "Update Include Costs Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);
                                }
                            }
                        };
                        xmlhttp.send("code="+code+"&id="+id);
                    }

                    /** function to include all test employees in cost calculations */
                    function includeAll()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/includeAllTestEmployeesInCosts.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Include All Test Employees In Costs Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide include all modal
                                $("#includeTestsModal").modal("hide");
                            }
                        };
                        xmlhttp.send();
                    }

                    /** function to exclude all test employees from cost calculations */
                    function excludeAll()
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/projects/excludeAllTestEmployeesInCosts.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Exclude All Test Employees In Costs Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide exclude all modal
                                $("#excludeTestsModal").modal("hide");
                            }
                        };
                        xmlhttp.send();
                    }
                </script>
            <?php 
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>