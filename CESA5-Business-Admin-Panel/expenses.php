<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (isset($PERMISSIONS["VIEW_PROJECT_EXPENSES"]) || isset($PERMISSIONS["VIEW_EMPLOYEE_EXPENSES"]))
        {
            ?> 
                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="dashboard.php" title="Return to the dashboard."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Expenses</div>
                    </h1>
                </div>

                <!-- Body -->
                <div class="row d-flex justify-content-center align-items-around m-0">
                    <?php if (isset($PERMISSIONS["VIEW_PROJECT_EXPENSES"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="expenses_manage.php">Project Expenses</a>
                        </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_EMPLOYEE_EXPENSES"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="expenses_global.php">Employee Expenses</a>
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