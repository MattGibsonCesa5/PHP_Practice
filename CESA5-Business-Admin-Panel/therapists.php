<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    {             
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
        
        // verify the user has permission
        if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) && isset($PERMISSIONS["EDIT_EMPLOYEES"]))
        {
            ?>
                <div class="report">
                    <!-- Page Header -->
                    <div class="table-header p-0">
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
                                <h2 class="m-0">Therapists</h2>
                            </div>

                            <!-- Page Management Dropdown -->
                            <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                <button class="btn btn-primary float-end" type="button" data-bs-toggle="modal" data-bs-target="#addTherapistModal">Add Therapist</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="report-body p-0">
                        <!-- Therapists Table -->
                        <table id="therapists" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("therapists", "BAP_Therapists_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!--
                    ### MODALS ###
                -->
                <!-- Add Therapist Modal -->
                <div class="modal fade" tabindex="-1" role="dialog" id="addTherapistModal" data-bs-backdrop="static" aria-labelledby="addTherapistModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header primary-modal-header">
                                <h5 class="modal-title primary-modal-title" id="addTherapistModalLabel">Add Therapist</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>

                            <div class="modal-body">
                                <div class="row align-items-center my-2">
                                    <div class="col-3 text-end"><label for="add-therapist_id">Therapist:</label></div>
                                    <div class="col-9">
                                        <select class="form-select w-100" id="add-therapist_id" name="add-therapist_id">
                                            <option></option>
                                            <?php
                                                // populate a list of all active users that can be assigned as a therapist
                                                $getUsers = mysqli_query($conn, "SELECT id FROM users ORDER BY fname ASC, lname ASC");
                                                if (mysqli_num_rows($getUsers) > 0) // there are valid therapists; populate list
                                                {
                                                    while ($user = mysqli_fetch_array($getUsers))
                                                    {
                                                        $user_id = $user["id"];
                                                        $user_name = getUserDisplayName($conn, $user_id);
                                                        echo "<option value=".$user_id.">".$user_name."</option>";
                                                    }
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" class="btn btn-primary" onclick="addTherapist();"><i class="fa-solid fa-floppy-disk"></i> Add Therapist</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End Add Department Modal -->

                <!-- Remove Therapist Modal -->
                <div id="remove-therapist-modal-div"></div>
                <!-- End Remove Therapist Modal -->
                <!--
                    ### END MODALS ###
                -->

                <script>
                    // initialize the departments table
                    var therapists = $("#therapists").DataTable({
                        ajax: {
                            url: "ajax/therapists/getTherapists.php",
                            type: "POST"
                        },
                        autoWidth: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            { data: "name", orderable: true, width: "50%" },
                            { data: "actions", orderable: false, width: "50%" },
                        ],
                        dom: 'rt',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        rowCallback: function (row, data, index)
                        {
                            updatePageSelection("therapists");
                        },
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        therapists.search($(this).val()).draw();
                    });

                    /** function to add a therapist */
                    function addTherapist()
                    {
                        // get the form values
                        let therapist_id = document.getElementById("add-therapist_id").value;

                        // send the data to process the add department request
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/therapists/addTherapist.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Add Therapist Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#addTherapistModal").modal("hide");
                            }
                        };
                        xmlhttp.send("therapist_id="+therapist_id);
                    }

                    /** function to remove a therapist */
                    function removeTherapist(therapist_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/therapists/removeTherapist.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create the status modal
                                let status_title = "Remove Therapist Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#removeTherapistModal").modal("hide");
                            }
                        };
                        xmlhttp.send("therapist_id="+therapist_id);
                    }

                    /** function to get the remove therapist modal */
                    function getRemoveTherapistModal(therapist_id)
                    {
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/therapists/getRemoveTherapistModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("remove-therapist-modal-div").innerHTML = this.responseText;     
                                $("#removeTherapistModal").modal("show");
                            }
                        };
                        xmlhttp.send("therapist_id="+therapist_id);
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