<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize return array
        $returnArray = [];
        $returnArray["showFTEBreakdown"] = 0;

        // get the required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ALL") || checkUserPermission($conn, "VIEW_PROJECT_BUDGETS_ASSIGNED"))
        {
            // get the parameters from POST
            if (isset($_POST["code"]) && $_POST["code"] <> "") { $code = $_POST["code"]; } else { $code = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($code != null && $period != null)
            {
                if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
                {
                    if (verifyProject($conn, $code)) // verify the project exists
                    {
                        // check to see if we should calculate the FTE breakdown
                        $checkCalcFTE = mysqli_prepare($conn, "SELECT calc_fte FROM projects WHERE code=?");
                        mysqli_stmt_bind_param($checkCalcFTE, "s", $code);
                        if (mysqli_stmt_execute($checkCalcFTE))
                        {
                            $checkCalcFTEResult = mysqli_stmt_get_result($checkCalcFTE);
                            if (mysqli_num_rows($checkCalcFTEResult) > 0)
                            {
                                $showFTEBreakdown = mysqli_fetch_array($checkCalcFTEResult)["calc_fte"];
                                if ($showFTEBreakdown == 1)
                                {
                                    // get the project's "leave time" days
                                    $projectDays = getProjectLeaveTimeDays($conn, $code);

                                    // get the total number of days in the project
                                    $daysInProject = 0;
                                    $getDaysInProject = mysqli_prepare($conn, "SELECT SUM(project_days) AS daysInProject FROM project_employees WHERE project_code=? AND period_id=?");
                                    mysqli_stmt_bind_param($getDaysInProject, "si", $code, $period_id);
                                    if (mysqli_stmt_execute($getDaysInProject))
                                    {
                                        $getDaysInProjectResult = mysqli_stmt_get_result($getDaysInProject);
                                        $daysInProject = mysqli_fetch_array($getDaysInProjectResult)["daysInProject"];
                                    }

                                    // get the project's total expenses
                                    $projectExpenses = getProjectsTotalExpenses($conn, $code, $period_id);

                                    // calculate the project's daily rate
                                    if ($daysInProject > 0) { $dailyRate = $projectExpenses / $daysInProject; } else { $dailyRate = 0; }

                                    // calculate the FTE in project
                                    if ($projectDays["FTE_days"] > 0) { $projectFTE = $daysInProject / $projectDays["FTE_days"]; } else { $projectFTE = 0; }

                                    // calculate leave time
                                    $leaveTimeDays = $projectFTE * $projectDays["leave_time"];
                                    $leaveTimeCost = $leaveTimeDays * $dailyRate;

                                    // calculate prep days
                                    $prepDays = $projectFTE * $projectDays["prep_work"];
                                    $prepCost = $prepDays * $dailyRate;

                                    // calculate personal development days
                                    $pdDays = $projectFTE * $projectDays["personal_development"];
                                    $pdCost = $pdDays * $dailyRate;

                                    // calculate the total number of unbillable days and cost of those days
                                    $unbillableDays = $leaveTimeDays + $prepDays + $pdDays;
                                    $unbillableCost = $leaveTimeCost + $prepCost + $pdCost;

                                    // calculate the unbillable days FTE
                                    if ($projectFTE > 0) { $unbillableFTE = $unbillableDays / $projectFTE; } else { $unbillableFTE = 0; }

                                    // calculate the total number of billable days
                                    $billableDays = $daysInProject - ($unbillableFTE * $projectFTE);

                                    // calculate the percentage of billable days
                                    if ($daysInProject > 0) { $billablePercentage = $billableDays / $daysInProject; } else { $billablePercentage = 0; }

                                    // calculate the daily rate with unbilled days
                                    if (($daysInProject - ($projectFTE * $unbillableFTE)) > 0) { $dailyRateUnbilled = $projectExpenses / ($daysInProject - ($projectFTE * $unbillableFTE)); } else { $dailyRateUnbilled = 0; }

                                    // build the return array
                                    $returnArray["showFTEBreakdown"] = 1;
                                    $returnArray["dailyRate"] = printDollar($dailyRate);
                                    $returnArray["projectFTE"] = round($projectFTE, 2);
                                    $returnArray["leaveTimeDays"] = round($leaveTimeDays, 2);
                                    $returnArray["leaveTimeCost"] = printDollar($leaveTimeCost);
                                    $returnArray["prepDays"] = round($prepDays, 2);
                                    $returnArray["prepCost"] = printDollar($prepCost);
                                    $returnArray["pdDays"] = round($pdDays, 2);
                                    $returnArray["pdCost"] = printDollar($pdCost);
                                    $returnArray["unbillableCost"] = printDollar($unbillableCost);
                                    $returnArray["unbillableDays"] = round($unbillableDays, 2);
                                    $returnArray["unbillableFTE"] = round($unbillableFTE, 2);
                                    $returnArray["daysInProject"] = $daysInProject;
                                    $returnArray["billableDays"] = round($billableDays, 2);
                                    $returnArray["billablePercentage"] = $billablePercentage;
                                    $returnArray["dailyRateUnbilled"] = printDollar($dailyRateUnbilled);
                                    $returnArray["projectExpenses"] = printDollar($projectExpenses);
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // send back the total expenses
        echo json_encode($returnArray);
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>