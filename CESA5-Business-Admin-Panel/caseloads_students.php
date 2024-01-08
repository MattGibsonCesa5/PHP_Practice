<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_STUDENTS_ALL"]) || isset($PERMISSIONS["VIEW_STUDENTS_ASSIGNED"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize an array to store all periods; then get all periods and store in the array
            $periods = [];
            $getPeriods = mysqli_query($conn, "SELECT id, name, active, start_date, end_date FROM `periods` ORDER BY active DESC, name ASC");
            if (mysqli_num_rows($getPeriods) > 0) // periods exist
            {
                while ($period = mysqli_fetch_array($getPeriods))
                {
                    // store period's data in array
                    $periods[] = $period;

                    // store the acitve period's name
                    if ($period["active"] == 1) 
                    { 
                        $active_period_label = $period["name"]; 
                        $active_start_date = date("m/d/Y", strtotime($period["start_date"]));
                        $active_end_date = date("m/d/Y", strtotime($period["end_date"])); 
                    }
                }
            }

            ?>
                <script>
                    /** function to add a new student */
                    function addStudent()
                    {
                        // get student information form fields
                        let fname = document.getElementById("add-fname").value;
                        let lname = document.getElementById("add-lname").value;
                        let date_of_birth = document.getElementById("add-date_of_birth").value;
                        let status = document.getElementById("add-status").value;
                        let sendString = "fname="+fname+"&lname="+lname+"&date_of_birth="+date_of_birth+"&status="+status;

                        // send the data to process the add student request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/addStudent.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Student Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addStudentModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to delete the student */
                    function deleteStudent(id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/deleteStudent.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Delete Student Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#deleteStudentModal").modal("hide");
                            }
                        };
                        xmlhttp.send("student_id="+id);
                    }

                    /** function to get the delete student modal */
                    function getDeleteStudentModal(id)
                    {
                        // send the data to create the delete student modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getDeleteStudentModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the delete student modal
                                document.getElementById("delete-student-modal-div").innerHTML = this.responseText;     
                                $("#deleteStudentModal").modal("show");
                            }
                        };
                        xmlhttp.send("student_id="+id);
                    }

                    /** function to edit the student */
                    function editStudent(id)
                    {
                        // get student information form fields
                        let fname = document.getElementById("edit-fname").value;
                        let lname = document.getElementById("edit-lname").value;
                        let date_of_birth = document.getElementById("edit-date_of_birth").value;
                        let status = document.getElementById("edit-status").value;
                        let sendString = "id="+id+"&fname="+fname+"&lname="+lname+"&date_of_birth="+date_of_birth+"&status="+status;

                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/editStudent.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Edit Student Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#editStudentModal").modal("hide");
                            }
                        };
                        xmlhttp.send(sendString);
                    }

                    /** function to get the edit student modal */
                    function getEditStudentModal(id)
                    {
                        // send the data to create the edit student modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getEditStudentModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the edit student modal
                                document.getElementById("edit-student-modal-div").innerHTML = this.responseText;
                                $("#editStudentModal").modal("show");

                                $(function() {
                                    $("#edit-date_of_birth").datepicker();
                                });
                            }
                        };
                        xmlhttp.send("student_id="+id);
                    }

                    /** function to update the status element */
                    function updateStatus(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to inactive
                        {
                            // update status to active
                            element.value = 1;
                            element.innerHTML = "Active";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
                        else // currently set to active, or other?
                        {
                            // update status to inactive
                            element.value = 0;
                            element.innerHTML = "Inactive";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                    }

                    /** function to display the student's age */
                    function updateAge(value, origin)
                    {
                        let age = getAge(value);
                        document.getElementById(origin+"-age").value = age;
                    }

                    /** function to get the age of a student */
                    function getAge(dateOfBirth)
                    {
                        var today = new Date();
                        var birthDate = new Date(dateOfBirth);
                        var age = today.getFullYear() - birthDate.getFullYear();
                        var month = today.getMonth() - birthDate.getMonth();
                        if (month < 0 || (month === 0 && today.getDate() < birthDate.getDate()))
                        {
                            age--;
                        }
                        return age;
                    }
                </script>

                <div class="report">
                    <div class="row report-body m-0">
                        <!-- Page Header -->
                        <div class="table-header p-0">
                            <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3">
                                <!-- Period & Filters-->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                    <div class="row px-3">
                                        <!-- Period Selection -->
                                        <div class="col-9 p-0">
                                            <div class="input-group h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                </div>
                                                <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                                <select class="form-select" id="search-period" name="search-period" onchange="showStudents();">
                                                    <?php
                                                        for ($p = 0; $p < count($periods); $p++)
                                                        {
                                                            echo "<option value='".$periods[$p]["name"]."'>".$periods[$p]["name"]."</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

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

                                                    <!-- Filter By Status -->
                                                    <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                        <div class="col-4 ps-0 pe-1">
                                                            <label for="search-status">Status:</label>
                                                        </div>

                                                        <div class="col-8 ps-1 pe-0">
                                                            <select class="form-select w-100" id="search-status" name="search-status">
                                                                <option value="">Show All</option>
                                                                <option value="Active" style="background-color: #006900; color: #ffffff;" selected>Active</option>
                                                                <option value="Inactive" style="background-color: #e40000; color: #ffffff;">Inactive</option>
                                                            </select>
                                                        </div>
                                                    </div>

                                                    <!-- Clear Filters -->
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
                                    <h1 class="m-0">Students</h1>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                    <?php if (isset($PERMISSIONS["ADD_STUDENTS"])) { ?>
                                        <button class="btn btn-primary px-5 py-2 float-end" type="button" data-bs-toggle="modal" data-bs-target="#addStudentModal">Add Student</button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>

                        <table id="students" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Last Name</th>
                                    <th class="text-center py-1 px-2">First Name</th>
                                    <th class="text-center py-1 px-2">Date Of Birth</th>
                                    <th class="text-center py-1 px-2">Age</th>
                                    <th class="text-center py-1 px-2"><span id="th-period-caseloads"></span> Caseloads</th>
                                    <th class="text-center py-1 px-2"><span id="th-period-uos"></span> Total UOS</th>
                                    <th class="text-center py-1 px-2"></th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("students", "BAP_ManageStudents_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add Student Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addStudentModal" data-bs-backdrop="static" aria-labelledby="addStudentModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addStudentModalLabel">Add Student</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- First Name -->
                                    <div class="form-group col-5">
                                        <label for="add-fname"><span class="required-field">*</span> First Name:</label>
                                        <input type="text" class="form-control w-100" id="add-fname" name="add-fname" autocomplete="off" required>
                                    </div>

                                    <!-- Divider -->
                                    <div class="form-group col-1 p-0"></div>

                                    <!-- Last Name -->
                                    <div class="form-group col-5">
                                        <label for="add-lname"><span class="required-field">*</span> Last Name:</label>
                                        <input type="text" class="form-control w-100" id="add-lname" name="add-lname" autocomplete="off" required>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Date Of Birth -->
                                    <div class="form-group col-5">
                                        <label for="add-date_of_birth"><span class="required-field">*</span> Date Of Birth:</label>
                                        <input type="text" class="form-control w-100" id="add-date_of_birth" name="add-date_of_birth" onchange="updateAge(this.value, 'add');" autocomplete="off">
                                    </div>

                                    <!-- Divider -->
                                    <div class="form-group col-1 p-0"></div>
                                    
                                    <!-- Age -->
                                    <div class="form-group col-5">
                                        <label for="add-age">Age:</label>
                                        <input type="number" class="form-control w-100" id="add-age" name="add-age" value="0" disabled readonly>
                                    </div>
                                </div>

                                <div class="form-row d-flex justify-content-center align-items-center my-3">
                                    <!-- Status -->
                                    <div class="form-group col-11">
                                        <label for="add-status"><span class="required-field">*</span> Status:</label>
                                        <button class="btn btn-success w-100" id="add-status" name="add-status" value=1 onclick="updateStatus('add-status');">Active</button>
                                    </div>
                                </div>

                                <!-- Required Field Indicator -->
                                <div class="row justify-content-center">
                                    <div class="col-11 text-center fst-italic">
                                        <span class="required-field">*</span> indicates a required field
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addStudent();"><i class="fa-solid fa-floppy-disk"></i> Add Student</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Student Modal -->

                <!-- Edit Students Modal -->
                <div id="edit-student-modal-div"></div>
                <!-- End Edit Students Modal -->

                <!-- Delete Students Modal -->
                <div id="delete-student-modal-div"></div>
                <!-- End Delete Students Modal -->

                <script>
                    // initialize variable to state if we've drawn the table or not
                    var drawn = 0; // assume we have not drawn the table (0)

                    // get the current active period
                    let active_period = "<?php echo $active_period_label; ?>"; 

                    // set the search filters to values we have saved in storage
                    if (sessionStorage["BAP_CaseloadStudents_Search_Period"] != "" && sessionStorage["BAP_CaseloadStudents_Search_Period"] != null && sessionStorage["BAP_CaseloadStudents_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_CaseloadStudents_Search_Period"]); }
                    else { $('#search-period').val(active_period); } // no period set; default to active period 

                    // set page length to prior saved state
                    let saved_page_length = sessionStorage["BAP_ManageStudents_PageLength"];
                    if (saved_page_length != "" && saved_page_length != null && saved_page_length != undefined)
                    {
                        $("#DT_PageLength").val(sessionStorage["BAP_ManageStudents_PageLength"]);
                    }

                    // initialize date of birth field
                    $(function() {
                        $("#add-date_of_birth").datepicker();
                    });

                    /** function to show student data for the selected period */
                    function showStudents()
                    {
                        // get the value of the period we are searching
                        var period = document.getElementById("search-period").value;

                        if (period != "" && period != null && period != undefined)
                        {
                            // update session storage stored search parameter
                            sessionStorage["BAP_CaseloadStudents_Search_Period"] = period;

                            // update table headers
                            document.getElementById("th-period-caseloads").innerHTML = period;
                            document.getElementById("th-period-uos").innerHTML = period;

                            // if we have already drawn the table, destroy existing table
                            if (drawn == 1) { $("#students").DataTable().destroy(); }

                            // initialize the students table
                            var students = $("#students").DataTable({
                                ajax: {
                                    url: "ajax/caseloads/getStudents.php",
                                    type: "POST",
                                    data: {
                                        period: period
                                    }
                                },
                                autoWidth: false,
                                async: false,
                                processing: true,
                                pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                columns: [
                                    // display columns
                                    { data: "id", orderable: true, width: "7.5%" },
                                    { data: "lname", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "fname", orderable: true, width: "12.5%", className: "text-center" },
                                    { data: "date_of_birth", orderable: true, width: "10%", className: "text-center" },
                                    { data: "age", orderable: true, width: "5%", className: "text-center" },
                                    { data: "active_caseloads", orderable: true, width: "27.5%", className: "text-center" },
                                    { data: "total_units", orderable: true, width: "15%", className: "text-center" },
                                    { data: "actions", orderable: true, width: "10%" },
                                    { data: "status", orderable: true, visible: false },
                                ],
                                dom: 'rt',
                                order: [
                                    [ 2, "asc" ],
                                    [ 1, "asc" ]
                                ],
                                language: {
                                    search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                    lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                    info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                },
                                rowCallback: function (row, data, index)
                                {
                                    updatePageSelection("students");
                                }
                            });

                            // mark that we have drawn the table
                            drawn = 1;

                            // search table by custom search filter
                            $('#search-all').keyup(function() {
                                students.search($(this).val()).draw();
                                sessionStorage["BAP_ManageStudents_Search_All"] = $(this).val();
                            });

                            // search table by student status
                            $('#search-status').change(function() {
                                sessionStorage["BAP_ManageStudents_Search_Status"] = $(this).val();
                                if ($(this).val() != "") { students.columns(8).search("^" + $(this).val() + "$", true, false, true).draw(); }
                                else { students.columns(8).search("").draw(); }
                            });

                            // function to clear search filters
                            $('#clearFilters').click(function() {
                                sessionStorage["BAP_ManageStudents_Search_All"] = "";
                                $('#search-all').val("");
                                students.search("").columns().search("").draw();
                            });

                            // redraw table with current search fields
                            if ($('#search-all').val() != "") { students.search($('#search-all').val()).draw(); }
                            if ($('#search-status').val() != "") { students.columns(8).search("^" + $('#search-status').val() + "$", true, false, true).draw(); }
                        }
                    }

                    // call the function to show students for the default paraments
                    showStudents();
                </script>
            <?php 

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>