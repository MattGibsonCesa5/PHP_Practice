<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_PROJECTS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECTS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"]))
        {
            ?> 
                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="dashboard.php" title="Return to the dashboard."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Projects</div>
                    </h1>
                </div>

                <!-- Body -->
                <div class="row d-flex justify-content-center align-items-around m-0">
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="projects_manage.php">Manage Projects</a>
                    </div>

                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="projects_budget.php">Budget Projects</a>
                    </div>
                </div>
            <?php
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
    
    include_once("footer.php"); 
?>