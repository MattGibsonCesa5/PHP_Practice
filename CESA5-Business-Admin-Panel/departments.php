<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    {             
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
        
        if (isset($PERMISSIONS["VIEW_DEPARTMENTS_ALL"]) || isset($PERMISSIONS["VIEW_DEPARTMENTS_ASSIGNED"]))
        {
            ?>
                <div class="report">
                    <div class="row report-body m-0">
                        <!-- Page Header -->
                        <div class="table-header sticky-top p-0">
                            <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3">
                                <!-- Period & Filters-->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                    <div class="row px-3">
                                        <!-- Filters -->
                                        <div class="col-3 ps-2 py-0">
                                            <div class="dropdown float-start">
                                                <button class="btn btn-primary" type="button" id="filtersMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="fa-solid fa-magnifying-glass"></i>
                                                </button>
                                                <div class="dropdown-menu filters-menu px-2" aria-labelledby="filtersMenu" style="width: 288px;">
                                                    <!-- Search Table -->
                                                    <div class="row mx-0 mt-0 mb-2">
                                                        <div class="input-group h-auto p-0">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon">
                                                                    <label for="search-all"><i class="fa-solid fa-magnifying-glass"></i></label>
                                                                </span>
                                                            </div>
                                                            <input class="form-control" type="text" placeholder="Search table" id="search-all" name="search-all" autocomplete="off">
                                                        </div>
                                                    </div>

                                                    <!-- Filter By Role -->
                                                    <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                        <div class="col-4 ps-0 pe-1">
                                                            <label for="search-director">Director:</label>
                                                        </div>

                                                        <div class="col-8 ps-1 pe-0">
                                                            <select class="form-select" id="search-director" name="search-director">
                                                                <option></option>
                                                                <?php
                                                                    // populate a list of all active directors that can be assigned as the department director
                                                                    $getDirectors = mysqli_query($conn, "SELECT DISTINCT u.id FROM users u
                                                                                                        JOIN directors d ON u.id=d.user_id
                                                                                                        ORDER BY u.lname ASC, u.fname ASC");
                                                                    if (mysqli_num_rows($getDirectors) > 0) // there are valid directors; populate list
                                                                    {
                                                                        while ($director = mysqli_fetch_array($getDirectors))
                                                                        {
                                                                            $director_id = $director["id"];
                                                                            $director_name = getUserDisplayName($conn, $director_id);
                                                                            echo "<option value=".$director_name.">".$director_name."</option>";
                                                                        }
                                                                    }
                                                                ?>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <div class="row m-0">
                                                        <button class="btn btn-secondary w-100" id="clearFilters"><i class="fa-solid fa-xmark"></i> Clear Filters</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Page Header -->
                                <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-8 col-xxl-8 p-0">
                                    <h2 class="m-0">Departments</h2>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                    <?php if (isset($PERMISSIONS["ADD_DEPARTMENTS"])) { ?>
                                        <div class="dropdown float-end">
                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                Manage Departments
                                            </button>
                                            <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#addDepartmentModal">Add Department</button></li>
                                                <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" data-bs-toggle="modal" data-bs-target="#uploadDepartmentMembersModal">Upload Department Members</button></li>
                                            </ul>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <!-- Departments Table -->
                        <table id="departments" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Directors</th>
                                    <th>Department Members</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("departments", "BAP_Departments_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <?php if (isset($PERMISSIONS["ADD_DEPARTMENTS"])) { ?>
                <!-- Add Department Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addDepartmentModal" data-bs-backdrop="static" aria-labelledby="addDepartmentModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addDepartmentModalLabel">Add Department</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <!-- Department Details -->
                                <fieldset class="form-group border p-3 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Department Details</legend>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-name"><span class="required-field">*</span> Department Name:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-name" name="add-name" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-desc">Description:</label></div>
                                        <div class="col-9"><input type="text" class="form-control w-100" id="add-desc" name="add-desc" required></div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-name">Primary Director:</label></div>
                                        <div class="col-9">
                                            <select class="form-select w-100" id="add-director" name="add-director">
                                                <option></option>
                                                <?php
                                                    // populate a list of all active directors that can be assigned as the department director
                                                    $getDirectors = mysqli_query($conn, "SELECT u.id FROM users u 
                                                                                        JOIN directors d ON u.id=d.user_id
                                                                                        WHERE u.status=1 ORDER BY u.fname ASC, u.lname ASC");
                                                    if (mysqli_num_rows($getDirectors) > 0) // there are valid directors; populate list
                                                    {
                                                        while ($director = mysqli_fetch_array($getDirectors))
                                                        {
                                                            $director_id = $director["id"];
                                                            $director_name = getUserDisplayName($conn, $director_id);
                                                            echo "<option value=".$director_id.">".$director_name."</option>";
                                                        }
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row align-items-center my-2">
                                        <div class="col-3 text-end"><label for="add-name">Secondary Director:</label></div>
                                        <div class="col-9">
                                            <select class="form-select w-100" id="add-secondary_director" name="add-secondary_director">
                                                <option></option>
                                                <?php
                                                    // populate a list of all active directors that can be assigned as the department director
                                                    $getDirectors = mysqli_query($conn, "SELECT u.id FROM users u 
                                                                                        JOIN directors d ON u.id=d.user_id
                                                                                        WHERE u.status=1 ORDER BY u.fname ASC, u.lname ASC");
                                                    if (mysqli_num_rows($getDirectors) > 0) // there are valid directors; populate list
                                                    {
                                                        while ($director = mysqli_fetch_array($getDirectors))
                                                        {
                                                            $director_id = $director["id"];
                                                            $director_name = getUserDisplayName($conn, $director_id);
                                                            echo "<option value=".$director_id.">".$director_name."</option>";
                                                        }
                                                    }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </fieldset>

                                <!-- Department Members -->
                                <fieldset class="form-group border p-3 mb-3">
                                    <legend class="w-auto px-2 m-0 float-none fieldset-legend">Department Members</legend>

                                    <table id="add-department_members" class="report_table w-100">
                                        <thead>
                                            <tr>
                                                <th>Employee ID</th>
                                                <th>First Name</th>
                                                <th>Last Name</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </fieldset>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addDepartment();"><i class="fa-solid fa-floppy-disk"></i> Save New Department</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Department Modal -->

                <!-- Upload Employees Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="uploadDepartmentMembersModal" data-bs-backdrop="static" aria-labelledby="uploadDepartmentMembersModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="uploadDepartmentMembersModalLabel">Upload Department Members</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <form action="processUploadDepartmentMembers.php" method="POST" enctype="multipart/form-data">
                                <div class="modal-body">
                                    <p><label for="fileToUpload">Select a CSV file following the <a class="template-link" href="https://docs.google.com/spreadsheets/d/1MJv-A-ec7qTu5CsDvVY5Q0VJq4lRPyv1_cSerWPZOJU/copy" target="_blank">correct upload template</a> to upload...</label></p>
                                    <input type="file" name="fileToUpload" id="fileToUpload">
                                </div>

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Department Members</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- End Upload Employees Modal -->
                <?php } ?>

                <?php if (isset($PERMISSIONS["EDIT_DEPARTMENTS"])) { ?>
                <!-- Edit Department Modal -->
                <div id="edit-department-modal-div"></div>
                <!-- End Edit Department Modal -->
                <?php } ?> 

                <?php if (isset($PERMISSIONS["DELETE_DEPARTMENTS"])) { ?>
                <!-- Delete Department Modal -->
                <div id="delete-department-modal-div"></div>
                <!-- End Delete Department Modal -->
                <?php } ?>

                <!-- View Department Modal -->
                <div id="view-department-modal-div"></div>
                <!-- End View Department Modal -->
                <!--
                    ### END MODALS ###
                -->

                <script>
                    // set the search filters to values we have saved in storage
                    $('#search-all').val(sessionStorage["BAP_Departments_Search_All"]);
                    <?php if (isset($PERMISSIONS["VIEW_DEPARTMENTS_ALL"])) { ?>
                    $('#search-director').val(sessionStorage["BAP_Departments_Search_Director"]);
                    <?php } ?>

                    // set page length to prior saved state
                    let saved_page_length = sessionStorage["BAP_Departments_PageLength"];
                    if (saved_page_length != "" && saved_page_length != null && saved_page_length != undefined)
                    {
                        $("#departments-DT_PageLength").val(sessionStorage["BAP_Departments_PageLength"]);
                    }

                    // initialize the departments table
                    var departments = $("#departments").DataTable({
                        ajax: {
                            url: "ajax/departments/getDepartments.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "name", orderable: true, width: "22.5%" },
                            { data: "description", orderable: true, width: "20%" },
                            { data: "directors", orderable: true, width: "20%" },
                            { data: "view_employees", orderable: true, width: "15%" },
                            { data: "actions", orderable: false, width: "22.5%" },
                        ],
                        dom: 'rt',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        stateSave: true,
                        rowCallback: function (row, data, index)
                        {
                            updatePageSelection("departments");
                        },
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        departments.search($(this).val()).draw();
                        sessionStorage["BAP_Departments_Search_All"] = $(this).val();
                    });

                    <?php if (isset($PERMISSIONS["VIEW_DEPARTMENTS_ALL"])) { ?>
                    // search table by employee primary department
                    $('#search-director').change(function() {
                        departments.columns(2).search($(this).val()).draw();
                        sessionStorage["BAP_Departments_Search_Director"] = $(this).val();
                    });
                    <?php } ?>

                    // function to clear search filters
                    $('#clearFilters').click(function() {
                        sessionStorage["BAP_Departments_Search_Director"] = "";
                        sessionStorage["BAP_Departments_Search_All"] = "";
                        $('#search-all').val("");
                        $('#search-director').val("");
                        departments.search("").columns().search("").draw();
                    });

                    <?php if (isset($PERMISSIONS["ADD_DEPARTMENTS"])) { ?>
                    /** function to add a new department */
                    function addDepartment()
                    {
                        // get the form values
                        let name = encodeURIComponent(document.getElementById("add-name").value);
                        let desc = encodeURIComponent(document.getElementById("add-desc").value);
                        let director = document.getElementById("add-director").value;
                        let secondary_director = document.getElementById("add-secondary_director").value;

                        // get the employees selected to be in the department
                        let members_table = $('#add-department_members').DataTable();
                        let count = members_table.rows({ selected: true }).count();
                        let employees = [];
                        for (let e = 0; e < count; e++) { employees.push(members_table.rows({ selected: true }).data()[e]["id"]); }

                        // create the string of data to send
                        let sendString = "name="+name+"&desc="+desc+"&director_id="+director+"&secondary_director="+secondary_director+"&employees="+JSON.stringify(employees);

                        // send the data to process the add department request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/departments/addDepartment.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Department Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addDepartmentModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** create the department members table in the add department modal */
                    var department_members = $("#add-department_members").DataTable({
                        ajax: {
                            url: "ajax/departments/getAddDepartmentEmployees.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "id_display", orderable: true, width: "30%" },
                            { data: "fname", orderable: true, width: "35%" },
                            { data: "lname", orderable: true, width: "35%" },
                        ],
                        select: {
                            style: "multi"
                        },
                        order: [
                            [ 2, "asc" ],
                            [ 1, "asc" ]
                        ],
                        dom: 'lfrtip',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        }
                    });
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["DELETE_DEPARTMENTS"])) { ?>
                    /** function to delete the department */
                    function deleteDepartment(id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/departments/deleteDepartment.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Department Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteDepartmentModal").modal("hide");
                            }
                        };
                        xmlhttp.send("department_id="+id);
                    }

                    /** function to get the delete department modal */
                    function getDeleteDepartmentModal(id)
                    {
                        // send the data to create the delete department modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/departments/getDeleteDepartmentModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete department modal
                                document.getElementById("delete-department-modal-div").innerHTML = this.responseText;     
                                $("#deleteDepartmentModal").modal("show");
                            }
                        };
                        xmlhttp.send("department_id="+id);
                    }
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["EDIT_DEPARTMENTS"])) { ?>
                    /** function to edit the department */
                    function editDepartment(id)
                    {
                        // get the form values
                        let name = encodeURIComponent(document.getElementById("edit-name").value);
                        let desc = encodeURIComponent(document.getElementById("edit-desc").value);
                        let director = encodeURIComponent(document.getElementById("edit-director").value);
                        let secondary_director = encodeURIComponent(document.getElementById("edit-secondary_director").value);

                        // get the employees selected to be in the department
                        let members_table = $('#edit-department_members').DataTable();
                        let count = members_table.rows({ selected: true }).count();
                        let employees = [];
                        for (let e = 0; e < count; e++) { employees.push(members_table.rows({ selected: true }).data()[e]["id"]); }

                        // create the string of data to send
                        let sendString = "department_id="+id+"&name="+name+"&desc="+desc+"&director_id="+director+"&secondary_director="+secondary_director+"&employees="+JSON.stringify(employees);

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/departments/editDepartment.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Department Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editDepartmentModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the edit department modal */
                    function getEditDepartmentModal(id)
                    {
                        // send the data to create the edit department modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/departments/getEditDepartmentModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {          
                                // initialize the edit department members table                  
                                $(document).ready(function () {
                                    var edit_department_members = $("#edit-department_members").DataTable({
                                        ajax: {
                                            url: "ajax/departments/getEditDepartmentEmployees.php",
                                            type: "POST",
                                            data: {
                                                department_id: id
                                            }
                                        },
                                        autoWidth: false,
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        columns: [
                                            { data: "id_display", orderable: true, width: "30%" },
                                            { data: "fname", orderable: true, width: "35%" },
                                            { data: "lname", orderable: true, width: "35%" },
                                            { data: "isMember", orderable: true, visible: false }
                                        ],
                                        select: {
                                            style: "multi"
                                        },
                                        dom: 'lfrtip',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        order: [
                                            [ 3, "desc" ],
                                            [ 1, "asc" ]
                                        ],
                                        initComplete: function () {
                                            // pre-select rows of employees that are already within the department
                                            let data = edit_department_members.rows().data();
                                            for (let r = 0; r < data.length; r++) { if (data[r]["isMember"] == 1) { edit_department_members.row(":eq("+r+")").select(); } }
                                        }
                                    });
                                });

                                // display the edit department modal
                                document.getElementById("edit-department-modal-div").innerHTML = this.responseText;   
                                $("#editDepartmentModal").modal("show");
                            }
                        };
                        xmlhttp.send("department_id="+id);
                    }
                    <?php } ?>

                    /** function to create the view department members modal */
                    function getViewDepartmentModal(id)
                    {
                        // send the data to create the delete department modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/departments/getViewDepartmentModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // initialize the view department members table                  
                                $(document).ready(function () {
                                    var edit_department_members = $("#view-department_members").DataTable({
                                        autoWidth: false,
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        columns: [
                                            { orderable: true, width: "12.5%" },
                                            { orderable: true, width: "22.5%" },
                                            { orderable: true, width: "22.5%" },
                                            { orderable: true, width: "30%" },
                                            { orderable: true, width: "12.5%" }
                                        ],
                                        dom: 'rt',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        order: [
                                            [ 2, "asc" ],
                                            [ 1, "asc" ],
                                            [ 0, "asc" ]
                                        ],
                                        rowCallback: function (row, data, index)
                                        {
                                            updatePageSelection("view-department_members");
                                        },
                                    });
                                });

                                // display the delete department modal
                                document.getElementById("view-department-modal-div").innerHTML = this.responseText;     
                                $("#viewDepartmentModal").modal("show");
                            }
                        };
                        xmlhttp.send("department_id="+id);
                    }
                </script>
            <?php
        }
        else { denyAccess(); }             
        
        // disconnect from the database
        mysqli_close($conn);
    }
    else { goToLogin(); }

    include("footer.php"); 
?>