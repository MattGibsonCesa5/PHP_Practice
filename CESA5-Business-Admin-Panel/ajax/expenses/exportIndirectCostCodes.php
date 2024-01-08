<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");
            include("../../getSettings.php");

            // get the parameters from POST
            if (isset($_POST["IC-export-period"]) && $_POST["IC-export-period"] <> "") { $period = $_POST["IC-export-period"]; } else { $period = null; }

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // create export file name
                $exportFile = "BAP - Indirect Costs Codes - $period.csv";

                // open export file
                header("Content-Type: text/csv; charset=utf-8");
                header("Content-Disposition: attachment; filename=\"$exportFile\"");
                $output = fopen("php://output", "w");

                if (verifyPeriod($conn, $period_id))
                {
                    // TODO - in the future, pull fund code from project_expenses instead of project

                    $getCodes = mysqli_prepare($conn, "SELECT p.fund_code, e.location_code, e.object_code, pe.function_code, pe.project_code, pe.cost FROM project_expenses pe 
                                                        JOIN expenses e ON pe.expense_id=e.id
                                                        JOIN projects p ON pe.project_code=p.code
                                                        WHERE e.object_code=825 AND pe.period_id=?");
                    mysqli_stmt_bind_param($getCodes, "i", $period_id);
                    if (mysqli_stmt_execute($getCodes))
                    {
                        $getCodesResults = mysqli_stmt_get_result($getCodes);
                        if (mysqli_num_rows($getCodesResults) > 0)
                        {
                            while ($entry = mysqli_fetch_array($getCodesResults))
                            {
                                // store entry details locally
                                $fund = $entry["fund_code"];
                                $loc = $entry["location_code"];
                                $obj = $entry["object_code"];
                                $func = $entry["function_code"];
                                $proj = $entry["project_code"];
                                $cost = round($entry["cost"], 2);

                                // build the wufar code string
                                $codeString = $fund." E ".$loc." ".$obj." ".$func." ".$proj;

                                if ($cost != 0)
                                {
                                    fputcsv($output, [$codeString, $cost]);
                                }
                            }
                        }
                    }
                }
            }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>