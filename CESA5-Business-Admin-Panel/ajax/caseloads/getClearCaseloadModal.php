<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if ($_SESSION["role"] == 1)
        {
            // get the caseload ID from POST
            if (isset($_POST["caseload_id"]) && $_POST["caseload_id"] <> "") { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the caseload exists
            if (verifyCaseload($conn, $caseload_id))
            {
                if ($caseload_id <> "" && $caseload_id != null && $caseload_id != "undefined")
                {
                    if ($period != null && $period_id = getPeriodID($conn, $period))
                    {
                        // get the caseload name
                        $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="clearCaseloadModal" data-bs-backdrop="static" aria-labelledby="clearCaseloadModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="clearCaseloadModalLabel">Clear Caseload</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <p>Are you sure you want to clear all students from the <?php echo $caseload_name; ?> for the <?php echo $period; ?> year? This action is irreversible!</p>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-danger" onclick="clearCaseload(<?php echo $caseload_id; ?>);"><i class="fa-solid fa-trash-can"></i> Clear Caseload</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>