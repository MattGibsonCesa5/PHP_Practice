<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // override server settings
            ini_set("max_execution_time", 600); // cap to 10 minutes
            ini_set("memory_limit", "256M"); // cap to 256 MB

            // bring in required additional files
            include("../../includes/functions.php");
            include("../../includes/config.php");
            include("../../getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get the parameters from POST
            if (isset($_POST["from"]) && is_numeric($_POST["from"])) { $from = $_POST["from"]; } else { $from = 0; }
            if (isset($_POST["to"]) && is_numeric($_POST["to"])) { $to = $_POST["to"]; } else { $to = 0; }

            if ($from != 0 && $to != 0) // both from and to periods selected; continue
            {
                // verify the from period exists
                $checkFrom = mysqli_prepare($conn, "SELECT id, name FROM periods WHERE id=?");
                mysqli_stmt_bind_param($checkFrom, "i", $from);
                if (mysqli_stmt_execute($checkFrom))
                {
                    $checkFromResult = mysqli_stmt_get_result($checkFrom);
                    if (mysqli_num_rows($checkFromResult) > 0) // period exists; continue
                    {
                        // store from period name locally
                        $from_label = mysqli_fetch_array($checkFromResult)["name"];

                        // verify the to period exists
                        $checkTo = mysqli_prepare($conn, "SELECT id, name FROM periods WHERE id=?");
                        mysqli_stmt_bind_param($checkTo, "i", $to);
                        if (mysqli_stmt_execute($checkTo))
                        {
                            $checkToResult = mysqli_stmt_get_result($checkTo);
                            if (mysqli_num_rows($checkToResult) > 0) // period exists; continue
                            {
                                // store the period details locally
                                $toPeriodDetails = mysqli_fetch_array($checkToResult);
                                $to_label = $toPeriodDetails["name"];

                                // clear out existiing employee details
                                $clearEmployeeDetails = mysqli_prepare($conn, "DELETE FROM employee_compensation WHERE period_id=?");
                                mysqli_stmt_bind_param($clearEmployeeDetails, "i", $to);
                                if (mysqli_stmt_execute($clearEmployeeDetails)) // successfully cleared employee compensation in the to period
                                {
                                    // copy employee benefits and compensation
                                    $copyEmployeeCompensation = mysqli_prepare($conn, "INSERT INTO employee_compensation (employee_id, yearly_rate, contract_days, contract_type, health_insurance, dental_insurance, wrs_eligible, assignment_position, sub_assignment, experience, highest_degree, period_id) SELECT employee_id, yearly_rate, contract_days, contract_type, health_insurance, dental_insurance, wrs_eligible, assignment_position, sub_assignment, experience, highest_degree, ? FROM employee_compensation WHERE period_id=?");
                                    mysqli_stmt_bind_param($copyEmployeeCompensation, "ii", $to, $from);
                                    if (mysqli_stmt_execute($copyEmployeeCompensation)) { echo "<span class=\"log-success\">Successfully</span> copied employee details from $from_label to $to_label.<br>"; }
                                    else { echo "<span class=\"log-fail\">Failed</span> to copy employee details from $from_label to $to_label.<br>"; }
                                }
                                else { echo "<span class=\"log-fail\">Failed</span> to copy employee details. We failed to clear out the details in the period we tried copying details into. Please try again later.<br>"; }
                            }
                            else { echo "<span class=\"log-fail\">Failed</span> to copy employee details. The period you are trying to copy details into does not exist!<br>"; }
                        }
                        else { echo "<span class=\"log-fail\">Failed</span> to copy employee details. An unexpected error has occurred! Please try again later.<br>"; }
                    }
                    else { echo "<span class=\"log-fail\">Failed</span> to copy employee details. The period you are trying to copy details from does not exist!<br>"; }
                }
                else { echo "<span class=\"log-fail\">Failed</span> to copy employee details. An unexpected error has occurred! Please try again later.<br>"; }
            }
            else { echo "<span class=\"log-fail\">Failed</span> to copy employee details. You must select both a period to copy details from and a period to copy details to.<br>"; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>