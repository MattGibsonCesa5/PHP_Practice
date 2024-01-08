<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "DELETE_EMPLOYEES"))
        {
            // get the employee ID from POST
            if (isset($_POST["employee_id"]) && $_POST["employee_id"] <> "") { $employee_id = $_POST["employee_id"]; } else { $employee_id = null; }

            if ($employee_id <> "" && $employee_id != null && $employee_id != "undefined")
            {
                ?>
                    <div class="modal fade" tabindex="-1" role="dialog" id="deleteEmployeeModal" data-bs-backdrop="static" aria-labelledby="deleteEmployeeModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="deleteEmployeeModalLabel">Delete Employee</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <p>
                                        Are you sure you want to delete this employee? We will remove this employee from all projects in the active period and departments.
                                    </p>
                                    <p>
                                        Deleting this employee could lead to historical data inaccuracies as the employee details will be deleted. 
                                        If you want to keep accurate historical data, we recommend setting this employees's status to inactive.
                                    </p>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary" onclick="deleteEmployee(<?php echo $employee_id; ?>);"><i class="fa-solid fa-trash-can"></i> Delete Employee</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>