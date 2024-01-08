<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $id = $_POST["id"]; } else { $id = null; }

            if ($code != null && $id != null)
            {
                // include additional required files
                include("../../includes/config.php");
                include("../../includes/functions.php");
                include("../../getSettings.php");

                // connect to the database
                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                if (verifyProject($conn, $code)) // verify the project exists
                {
                    // verify the test employee exists
                    $checkTestEmp = mysqli_prepare($conn, "SELECT id, costs_inclusion FROM project_employees_misc WHERE id=? AND project_code=? AND period_id=?");
                    mysqli_stmt_bind_param($checkTestEmp, "isi", $id, $code, $GLOBAL_SETTINGS["active_period"]);
                    if (mysqli_stmt_execute($checkTestEmp))
                    {
                        $checkTestEmpResult = mysqli_stmt_get_result($checkTestEmp);
                        if (mysqli_num_rows($checkTestEmpResult) > 0) // test employee exists; continue
                        {
                            // store the current cost inclusion setting
                            $cost_inclusion = mysqli_fetch_array($checkTestEmpResult)["costs_inclusion"];

                            if ($cost_inclusion == 1) // cost inclusion currentlu enabled; disable cost inclusion
                            {
                                $toggleInclusion = mysqli_prepare($conn, "UPDATE project_employees_misc SET costs_inclusion=0 WHERE id=? AND project_code=? AND period_id=?");
                                mysqli_stmt_bind_param($toggleInclusion, "isi", $id, $code, $GLOBAL_SETTINGS["active_period"]);
                                if (mysqli_stmt_execute($toggleInclusion)) { echo "Sucessfully disabled cost inclusion for this test employee."; }
                            }
                            else if ($cost_inclusion == 0) // cost inclusion currently disabled; enable cost inclusion
                            {
                                $toggleInclusion = mysqli_prepare($conn, "UPDATE project_employees_misc SET costs_inclusion=1 WHERE id=? AND project_code=? AND period_id=?");
                                mysqli_stmt_bind_param($toggleInclusion, "isi", $id, $code, $GLOBAL_SETTINGS["active_period"]);
                                if (mysqli_stmt_execute($toggleInclusion)) { echo "Sucessfully enabled cost inclusion for this test employee."; }
                            }
                        }
                    }
                }         
                
                // disconnect from the database
                mysqli_close($conn);
            }
        }
    }
?>