<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_SERVICES_ALL"]) || isset($PERMISSIONS["VIEW_SERVICES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_INVOICES_ALL"]) || isset($PERMISSIONS["VIEW_INVOICES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_OTHER_SERVICES"]) || isset($PERMISSIONS["VIEW_REVENUES_ALL"]) || isset($PERMISSIONS["VIEW_REVENUES_ASSIGNED"]))
        {
            ?>  
                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="dashboard.php" title="Return to the dashboard."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Services</div>
                    </h1>
                </div>

                <!-- Body -->
                <div class="row d-flex justify-content-center align-items-around m-0">
                    <?php if (isset($PERMISSIONS["VIEW_SERVICES_ALL"]) || isset($PERMISSIONS["VIEW_SERVICES_ASSIGNED"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="services_manage.php">Manage Services</a>
                        </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_INVOICES_ALL"]) || isset($PERMISSIONS["VIEW_INVOICES_ASSIGNED"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="services_billed.php">Services Billed</a>
                        </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_REVENUES_ALL"]) || isset($PERMISSIONS["VIEW_REVENUES_ASSIGNED"])) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="revenues.php">Other Revenues</a>
                        </div>
                    <?php } ?>

                    <?php if ($_SESSION["role"] == 1) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="contracts.php">Contracts</a>
                        </div>
                    <?php } ?>

                    <?php if ($_SESSION["role"] == 4) { ?>
                        <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                            <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="customer_files.php">View Contracts</a>
                        </div>
                    <?php } ?>
                </div>
            <?php 
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include_once("footer.php"); 
?>