<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["CREATE_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["BUILD_SERVICE_CONTRACTS"]) || 
            isset($PERMISSIONS["VIEW_QUARTERLY_INVOICES"]) || isset($PERMISSIONS["CREATE_QUARTERLY_INVOICES"]) || isset($PERMISSIONS["BUILD_QUARTERLY_INVOICES"]) || 
            isset($PERMISSIONS["EXPORT_INVOICES"]))
        {
            ?>  
                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="services.php" title="Return to Services."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Contracts</div>
                    </h1>
                </div>

                <!-- Body -->
                <div class="row d-flex justify-content-center align-items-around m-0">
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="contract_creator.php">Contract Creator</a>
                    </div>

                    <?php if (isset($PERMISSIONS["VIEW_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["VIEW_QUARTERLY_INVOICES"])) { ?>
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="customer_files.php">View Contracts</a>
                    </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["CREATE_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["CREATE_QUARTERLY_INVOICES"])) { ?>
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="contracts_create.php">Create Contracts</a>
                    </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["BUILD_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["BUILD_QUARTERLY_INVOICES"])) { ?>
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="contracts_builder.php">Build Contracts</a>
                    </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["EXPORT_INVOICES"])) { ?>
                    <div class="col-12 col-sm-12 col-md-6 col-lg-4 col-xl-4 col-xxl-3 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="invoices_export.php">Export Invoices</a>
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