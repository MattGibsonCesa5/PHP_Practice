<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) || isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_STUDENTS_ALL"]) || isset($PERMISSIONS["VIEW_STUDENTS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_THERAPISTS"]))
        {
            // get addtional settings and functions
            include("getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?> 
                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="dashboard.php" title="Return to the dashboard."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Caseloads</div>
                    </h1>
                </div>
                

                <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) && isset($PERMISSIONS["VIEW_STUDENTS_ALL"]) && isset($PERMISSIONS["VIEW_THERAPISTS"])) { 
                    // get active period label
                    $active_label = getActivePeriodLabel($conn);

                    ///////////////////////////////////////////////////////////////////////////////
                    //
                    //  Admin SPED Dashboard
                    //
                    /////////////////////////////////////////////////////////////////////////////// ?>
                    <!-- Body -->
                    <div class="row d-flex justify-content-center m-0">
                        <div class="col-12 col-lg-6 col-xl-3">
                            <!-- Period -->
                            <div class="col-12 px-3 py-2">
                                <div class="card bg-primary text-white h-100">
                                    <div class="card-body d-flex justify-content-center align-items-center">
                                        <i class="fa-solid fa-calendar-days fa-3x mx-3"></i>
                                        <h1 class="card-title text-center mx-3 my-0" style="font-size: 32px !important;"><?php echo $active_label; ?></h1>
                                    </div>
                                </div>
                            </div>

                            <?php for ($c = 0; $c < count($CASELOAD_CATEGORIES); $c++) { ?>
                                <!-- <?php echo $CASELOAD_CATEGORIES[$c]["name"]; ?> -->
                                <div class="col-12 px-3 py-2">
                                    <div class="card bg-secondary text-white h-100" id="tile-div-<?php echo $CASELOAD_CATEGORIES[$c]["id"]; ?>">
                                        <div class="card-body" id="tile-body-<?php echo $CASELOAD_CATEGORIES[$c]["id"]; ?>">
                                            <div class="text-center">
                                                <i class='fa-solid fa-spinner fa-spin-pulse fa-3x'></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>
                        </div>

                        <div class="col-12 col-lg-6 col-xl-9">
                            <!--
                                --
                                -- MANAGEMENT
                                -- 
                            -->
                            <div class="row d-flex justify-content-end align-items-around m-0">
                                <!-- Section Header -->
                                <div class="col-12 px-3">
                                    <h2 class="sped_dash-subheader text-end mb-0">Manage</h2>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_manage.php">Caseloads</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_students.php">Students</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_assistants.php">Assistants</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_categories.php">Categories</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_classrooms.php">Classrooms</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_coordinators.php">Coordinators</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="schools.php">Schools</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_transfers.php">Transfer Requests</a>
                                </div>
                            </div>
                            <hr>

                            <!--
                                --
                                -- Reports
                                -- 
                            -->
                            <div class="row d-flex justify-content-end align-items-around m-0">
                                <!-- Section Header -->
                                <div class="col-12 px-3">
                                    <h2 class="sped_dash-subheader text-end mb-0">Reports</h2>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_billing.php">Billing Summary</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_billing_quarterly.php">Quarterly Billing</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_medicaid_billing.php">Medicaid Billing</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_start_end_changes.php">Start-End Changes</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="caseloads_warnings.php">Unit Warnings</a>
                                </div>
                            </div>
                            <hr>

                            <!--
                                --
                                -- Miscellaneous
                                -- 
                            -->
                            <div class="row d-flex justify-content-end align-items-around m-0">
                                <!-- Section Header -->
                                <div class="col-12 px-3">
                                    <h2 class="sped_dash-subheader text-end mb-0">Miscellaneous</h2>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="uos_calculator.php">UOS Calculator</a>
                                </div>

                                <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 d-flex justify-content-center px-3 py-2">
                                    <a class="btn-dash btn-dash-sped d-flex w-100 h-100 justify-content-center align-items-center px-5 py-4" href="uos_quotes.php">UOS Quotes</a>
                                </div>
                            </div>
                            <hr>
                        </div>
                    </div>

                    <script>
                        var caseload_categories = [];
                        caseload_categories = <?php echo json_encode($CASELOAD_CATEGORIES); ?>;
                        for (let c = 0; c < caseload_categories.length; c++)
                        {
                            // store category details locally
                            let category_id = caseload_categories[c].id;
                            let category_name = caseload_categories[c].name;

                            // send request to generate the tile
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/dashboard/getCaseloadCategoryTile.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // store resposne as a JSON element
                                    let response = JSON.parse(this.responseText);
                                    
                                    // build tile based on response status
                                    if (response.code == 1) 
                                    {
                                        $("#tile-div-"+category_id).removeClass("bg-secondary");
                                        $("#tile-div-"+category_id).addClass("bg-primary");
                                        $("#tile-body-"+category_id).html(decodeURIComponent(response.body));
                                    } 
                                    else 
                                    {
                                        $("#tile-div-"+category_id).removeClass("bg-secondary");
                                        $("#tile-div-"+category_id).addClass("bg-danger");
                                        $("#tile-body-"+category_id).html("<h3 class=\"card-title card-title-sped_dash m-0\">"+category_name+"</h3><p>Failed to load tile.</p>");
                                    }
                                }
                            }
                            xmlhttp.send("category_id="+category_id);
                        }
                    </script>
                <?php } else { 
                    ///////////////////////////////////////////////////////////////////////////////
                    //
                    //  Regular SPED Dashboard
                    //
                    /////////////////////////////////////////////////////////////////////////////// ?>
                    <!-- Body -->
                    <div class="row d-flex justify-content-center align-items-around m-0">
                        <?php if (isset($PERMISSIONS["VIEW_STUDENTS_ALL"]) || isset($PERMISSIONS["VIEW_STUDENTS_ASSIGNED"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="caseloads_students.php">
                                <?php 
                                    if (isset($PERMISSIONS["VIEW_STUDENTS_ALL"])) { echo "Student Management"; }
                                    else { echo "My Students"; }
                                ?>
                            </a>
                        </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"])) { ?>
                            <?php if (isset($PERMISSIONS["VIEW_THERAPISTS"])) { ?>
                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                                <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="caseloads_manage.php">Caseload Management</a>
                            </div>

                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                                <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="caseloads_assistants.php">Caseload Assistants</a>
                            </div>

                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                                <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="caseloads_categories.php">Caseload Categories</a>
                            </div>

                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                                <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="caseloads_classrooms.php">Caseload Classrooms</a>
                            </div>

                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                                <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="caseloads_coordinators.php">Caseload Coordinators</a>
                            </div>

                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                                <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="caseloads_reports.php">Caseload Reports</a>
                            </div>
                            <?php } ?>

                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                                <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="schools.php">School Management</a>
                            </div>
                        <?php } else if (isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"])) { ?>
                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                                <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="my-caseloads.php">My Caseloads</a>
                            </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"]) && verifyCoordinator($conn, $_SESSION["id"])) { ?>
                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                                <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="caseloads_billing.php">Billing Summary</a>
                            </div>
                        <?php } ?>

                        <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) && isset($PERMISSIONS["VIEW_THERAPISTS"])) { ?>
                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                                <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="caseloads_transfers.php">Transfer Requests</a>
                            </div>
                        <?php } ?>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="uos_calculator.php">UOS Calculator</a>
                        </div>

                        <?php if (isset($PERMISSIONS["VIEW_THERAPISTS"]) && isset($PERMISSIONS["VIEW_CASELOADS_ALL"])) { ?>
                            <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                                <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="uos_quotes.php">UOS Quotes</a>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            <?php 

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
    
    include_once("footer.php"); 
?>