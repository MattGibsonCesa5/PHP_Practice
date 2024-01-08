<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_EMPLOYEES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_DEPARTMENTS_ALL"]) || isset($PERMISSIONS["VIEW_DEPARTMENTS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_STATE"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ALL"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"]) || isset($PERMISSIONS["VIEW_RAISE_PROJECTION"]))
        {
            ?> 
                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="dashboard.php" title="Return to the dashboard."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Employees</div>
                    </h1>
                </div>

                <!-- Body -->
                <div class="row d-flex justify-content-center align-items-around m-0">
                    <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_EMPLOYEES_ASSIGNED"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="employees_list.php">Employees List</a>
                        </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_DEPARTMENTS_ALL"]) || isset($PERMISSIONS["VIEW_DEPARTMENTS_ASSIGNED"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="departments.php">Departments</a>
                        </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) && isset($PERMISSIONS["EDIT_EMPLOYEES"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="directors.php">Directors & Supervisors</a>
                        </div>

                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="therapists.php">Therapists</a>
                        </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) && isset($PERMISSIONS["ADD_EMPLOYEES"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="employees_titles.php">Position Titles</a>
                        </div>
                    <?php } ?>

                    <?php if ((isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) || isset($PERMISSIONS["EDIT_EMPLOYEES"])) || isset($PERMISSIONS["VIEW_EMPLOYEES_ASSIGNED"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="employees_change_requests.php">Change Requests</a>
                        </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_SALARY_COMPARISON_STATE"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ALL"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"]) || isset($PERMISSIONS["VIEW_RAISE_PROJECTION"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="salary_comparison.php">Salary Comparison</a>
                        </div>
                    <?php } ?>
                </div>
            <?php
        }
        else { denyAccess(); }

        // disconnect from the database
        mysqli_close($conn);
    }
    else { goToLogin(); }
    
    include_once("footer.php"); 
?>