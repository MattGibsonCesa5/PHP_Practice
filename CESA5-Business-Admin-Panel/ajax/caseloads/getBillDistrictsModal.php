<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_INVOICES"))
        {
            // get the parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["quarter"]) && $_POST["quarter"] <> "") { $quarter = $_POST["quarter"]; } else { $quarter = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                if (isset($quarter) && (is_numeric($quarter) && $quarter >= 1 && $quarter <= 4))
                {
                    // get quarter statuses
                    $q1Status = checkLocked($conn, 1, $period_id);
                    $q2Status = checkLocked($conn, 2, $period_id);
                    $q3Status = checkLocked($conn, 3, $period_id);
                    $q4Status = checkLocked($conn, 4, $period_id);

                    // initialize variable to lock the bill button
                    $lock = false;

                    ?>
                        <!-- Bill Districts Modal -->
                        <div class="modal fade" tabindex="-1" role="dialog" id="billDistrictsModal" data-bs-backdrop="static" aria-labelledby="billDistrictsModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="billDistrictsModalLabel">Bill Districts</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <?php if ($quarter == 1 && $q1Status === true) { $lock = true; ?>
                                            <div class="alert alert-danger m-0">
                                                <p class="m-0">The quarter you are trying to bill for is locked.</p>
                                            </div>
                                        <?php } else if ($quarter == 2 && $q2Status === true) { $lock = true; ?>
                                            <div class="alert alert-danger m-0">
                                                <p class="m-0">The quarter you are trying to bill for is locked.</p>
                                            </div>
                                        <?php } else if ($quarter == 3 && $q3Status === true) { $lock = true; ?>
                                            <div class="alert alert-danger m-0">
                                                <p class="m-0">The quarter you are trying to bill for is locked.</p>
                                            </div>
                                        <?php } else if ($quarter == 4 && $q4Status === true) { $lock = true; ?>
                                            <div class="alert alert-danger m-0">
                                                <p class="m-0">The quarter you are trying to bill for is locked.</p>
                                            </div>
                                        <?php } else if ($quarter == 2 && $q1Status === false) { $lock = true; ?>
                                            <div class="alert alert-danger m-0">
                                                <p class="m-0">Prior quarters are still unlocked. You must lock the prior quarters before billing for this quarter.</p>
                                            </div>
                                        <?php } else if ($quarter == 3 && ($q1Status === false || $q2Status === false)) { $lock = true; ?>
                                            <div class="alert alert-danger m-0">
                                                <p class="m-0">Prior quarters are still unlocked. You must lock the prior quarters before billing for this quarter.</p>
                                            </div>
                                        <?php } else if ($quarter == 4 && ($q1Status === false || $q2Status === false || $q3Status === false)) { $lock = true; ?>
                                            <div class="alert alert-danger m-0">
                                                <p class="m-0">Prior quarters are still unlocked. You must lock the prior quarters before billing for this quarter.</p>
                                            </div>
                                        <?php } else { ?>
                                            <p>Are you sure you want to bill districts based on their existing caseload data for <?php echo $period; ?> Q<?php echo $quarter; ?>?</p>
                                            <p>Service quantities and invoice costs will only update if there was a change in units/days within the caseloads.</p>
                                            <p>Costs in locked quarters will not be impacted, and we will only update the costs for unlocked quarters.</p>
                                        <?php } ?>
                                    </div>

                                    <div class="modal-footer">
                                        <?php if ($lock === true) { ?>
                                            <button type="button" class="btn btn-danger" disabled><i class="fa-solid fa-dollar-sign"></i> Bill Districts</button>
                                        <?php } else { ?>
                                            <button type="button" class="btn btn-primary" id="billDistrictsBtn" onclick="billDistricts(<?php echo $period_id; ?>, <?php echo $quarter; ?>);"><i class="fa-solid fa-dollar-sign"></i> Bill Districts</button>
                                        <?php } ?>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Bill Districts Modal -->
                    <?php
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>