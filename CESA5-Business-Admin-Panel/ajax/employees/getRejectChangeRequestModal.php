<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_EMPLOYEES_ALL") && checkUserPermission($conn, "EDIT_EMPLOYEES"))
        {
            // get the request ID from POST
            if (isset($_POST["request_id"]) && $_POST["request_id"] <> "") { $request_id = $_POST["request_id"]; } else { $request_id = null; }

            if ($request_id != null)
            {
                // get request details
                $getRequest = mysqli_prepare($conn, "SELECT employee_id, requested_by FROM employee_compensation_change_requests WHERE id=?");
                mysqli_stmt_bind_param($getRequest, "i", $request_id);
                if (mysqli_stmt_execute($getRequest))
                {
                    $getRequestResult = mysqli_stmt_get_result($getRequest);
                    if (mysqli_num_rows($getRequestResult) > 0) // request exists; continue
                    {
                        // store request details
                        $request_details = mysqli_fetch_array($getRequestResult);
                        $employee_id = $request_details["employee_id"];
                        $requester_id = $request_details["requested_by"];

                        // get the employee's display name
                        $employee_name = getEmployeeDisplayName($conn, $employee_id);
                        $requester_name = getUserDisplayName($conn, $requester_id);

                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="rejectChangeRequestModal" data-bs-backdrop="static" aria-labelledby="rejectChangeRequestModalLabel" aria-hidden="true">
                                <div class="modal-dialog" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="rejectChangeRequestModalLabel">Reject Change Request</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <p>
                                                Are you sure you want to reject the employee compensation change request for <?php echo $employee_name; ?>, 
                                                that was requested by <?php echo $requester_name; ?>?
                                            </p>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-danger" onclick="rejectChangeRequest(<?php echo $request_id; ?>);"><i class="fa-solid fa-xmark"></i> Reject Request</button>
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