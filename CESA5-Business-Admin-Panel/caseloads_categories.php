<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the active period label
            $active_period_label = getActivePeriodLabel($conn);

            ?>
                <script>
                    /** function to get the modal to edit the selected category */
                    function getEditCategoryModal(category_id)
                    {
                        // send the data to create the delete student modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/getEditCategoryModal.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // display the edit category modal
                                document.getElementById("edit-category-modal-div").innerHTML = this.responseText;     
                                $("#editCategoryModal").modal("show");
                            }
                        };
                        xmlhttp.send("category_id="+category_id+"&period=2023-2024");
                    }

                    /** function to edit a category */
                    function editCategory(category_id)
                    {
                        // get form data
                        let name = document.getElementById("edit-name").value;
                        let locked = document.getElementById("edit-locked").value;

                        // send the data to create the delete student modal
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/caseloads/editCategory.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                // create and display the status modal
                                let status_title = "Edit Category Status";
                                let status_body = this.responseText;
                                createStatusModal("refresh", status_title, status_body);

                                // hide the edit category modal
                                $("#editCategoryModal").modal("hide");
                            }
                        };
                        xmlhttp.send("category_id="+category_id+"&name="+encodeURIComponent(name)+"&locked="+locked);
                    }

                    /** function to update the locked element */
                    function toggleLocked(id)
                    {
                        // get current status of the element
                        let element = document.getElementById(id);
                        let status = element.value;

                        if (status == 0) // currently set to unlocked
                        {
                            // update status to locked
                            element.value = 1;
                            element.innerHTML = "<i class='fa-solid fa-lock'></i> Locked";
                            element.classList.remove("btn-success");
                            element.classList.add("btn-danger");
                        }
                        else // currently set to locked, or other?
                        {
                            // update status to unlocked
                            element.value = 0;
                            element.innerHTML = "<i class='fa-solid fa-lock-open'></i> Unlocked";
                            element.classList.remove("btn-danger");
                            element.classList.add("btn-success");
                        }
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
                                    <h1 class="m-0">Caseload Categories</h1>
                                </div>

                                <!-- Page Management Dropdown -->
                                <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0"></div>
                            </div>
                        </div>

                        <table id="caseloads_categories" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th class="text-center py-1 px-2" rowspan="2">Name</th>
                                    <th class="text-center py-1 px-2" rowspan="2"># of Caseloads</th>
                                    <th class="text-center py-1 px-2" rowspan="2"># of <?php echo $active_period_label; ?> Students</th>
                                    <th class="text-center py-1 px-2" rowspan="2">Subcategories</th>
                                    <th class="text-center py-1 px-2" colspan="2">Service To Bill</th>
                                    <th class="text-center py-1 px-2" rowspan="2"></th>
                                </tr>

                                <tr>
                                    <th class="text-center py-1 px-2">ID</th>
                                    <th class="text-center py-1 px-2">Name</th>
                                </tr>
                            </thead>
                        </table>
                        <?php createTableFooterV2("caseloads_categories", "BAP_CaseloadCategories_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                    </div>
                </div>

                <!-- Edit Category Modal -->
                <div id="edit-category-modal-div"></div>
                <!-- End Edit Category Modal -->

                <script>
                    // initialize the caseloads_categories table
                    var caseloads_categories = $("#caseloads_categories").DataTable({
                        ajax: {
                            url: "ajax/caseloads/getCaseloadCategories.php",
                            type: "POST",
                        },
                        autoWidth: false,
                        async: false,
                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                        columns: [
                            // display columns
                            { data: "name", orderable: true, width: "20%", className: "text-center" },
                            { data: "therapist_count", orderable: true, width: "10%", className: "text-center" },
                            { data: "student_count", orderable: true, width: "10%", className: "text-center" },
                            { data: "subcategories", orderable: true, width: "20%", className: "text-center" },
                            { data: "service_id", orderable: true, width: "5%", className: "text-center" },
                            { data: "service_name", orderable: true, width: "15%", className: "text-center" },
                            { data: "actions", orderable: false, width: "20%" }
                        ],
                        dom: 'rt',
                        language: {
                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                        },
                        saveState: false,
                        rowCallback: function (row, data, index)
                        {
                            updatePageSelection("caseloads_categories");
                        }
                    });

                    // search table by custom search filter
                    $('#search-all').keyup(function() {
                        caseloads_categories.search($(this).val()).draw();
                        sessionStorage["BAP_CaseloadCategories_Search_All"] = $(this).val();
                    });

                    // function to clear search filters
                    $('#clearFilters').click(function() {
                        sessionStorage["BAP_CaseloadCategories_Search_All"] = "";
                        $('#search-all').val("");
                        caseloads_categories.search("").columns().search("").draw();
                    });
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