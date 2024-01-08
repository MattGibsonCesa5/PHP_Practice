<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?> 
                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="dashboard.php" title="Return to the dashboard."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Manage</div>
                    </h1>
                </div>

                <!-- Body -->
                <div class="row d-flex align-items-around m-0">
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="users.php">Accounts</a>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="admin.php">Admin</a>
                    </div>
                    
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="automation.php">Automation</a>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="periods.php">Periods</a>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="codes.php">Codes</a>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="clear.php">Clear</a>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="roles.php">Roles & Permissions</a>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="log.php">Log</a>
                    </div>
                </div>
            <?php

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include_once("footer.php"); 
?>