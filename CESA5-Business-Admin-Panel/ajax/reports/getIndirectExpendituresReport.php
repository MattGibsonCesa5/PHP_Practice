<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // initialize the array of data to send
            $masterData = [];

            // get additional required files
            include("../../includes/functions.php");
            include("../../includes/config.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // get the rates from the global_expenses table
                $getRates = mysqli_prepare($conn, "SELECT aidable_supervision, nonaidable_supervision, agency_indirect FROM global_expenses WHERE period_id=?");
                mysqli_stmt_bind_param($getRates, "i", $period_id);
                if (mysqli_stmt_execute($getRates))
                {
                    $getRatesResult = mysqli_stmt_get_result($getRates);
                    if (mysqli_num_rows($getRatesResult) > 0) // rates found
                    {
                        // get the rates
                        $rates = mysqli_fetch_array($getRatesResult);

                        // get the grant project indirect rate
                        $grant_indirect_rate = getGrantIndirectRate($conn);
                        $dpi_grant_indirect_rate = getDPIGrantIndirectRate($conn, $period_id);
                        
                        // get each indirect project
                        $getProjects = mysqli_prepare($conn, "SELECT p.code, p.name, p.supervision_costs, p.indirect_costs FROM projects p 
                                                            JOIN projects_status ps ON p.code=ps.code
                                                            WHERE ps.status=1 AND p.indirect_costs>0 AND ps.period_id=?");
                        mysqli_stmt_bind_param($getProjects, "i", $period_id);
                        if (mysqli_stmt_execute($getProjects))
                        {
                            $getProjectsResults = mysqli_stmt_get_result($getProjects);
                            if (mysqli_num_rows($getProjectsResults) > 0) // indirect projects exists; continue
                            {
                                while ($project = mysqli_fetch_array($getProjectsResults))
                                {
                                    // store project details locally
                                    $code = $project["code"];
                                    $name = $project["name"];
                                    $supervision_costs = $project["supervision_costs"];
                                    $indirect_costs = $project["indirect_costs"];

                                    // get the total expenses for the project provided
                                    $total_expenses = getProjectsTotalExpenses($conn, $code, $period_id);

                                    // initialize variables
                                    $total_compensation = 0;

                                    // get a list of the employees within the project and the sum of their total compensation
                                    $employees = getProjectEmployees($conn, $code, $period_id);
                                    for ($e = 0; $e < count($employees); $e++)
                                    {
                                        $total_compensation += getEmployeesTotalCompensation($conn, $code, $employees[$e], $period_id);
                                    }

                                    /* AIDABLE SUPERVISION */
                                    if ($supervision_costs == 1) { $aidable_supervision = $rates["aidable_supervision"] * $total_compensation; }
                                    else { $aidable_supervision = 0; }

                                    /* NON-AIDABLE SUPERVISION */
                                    if ($supervision_costs == 1) { $nonaidable_supervision = $rates["nonaidable_supervision"] * $total_compensation; }
                                    else { $nonaidable_supervision = 0; }

                                    $nonpersonnel_expenses = $total_expenses + $aidable_supervision + $nonaidable_supervision;
                                    if ($indirect_costs == 1) { $indirect_NPE = $nonpersonnel_expenses * $rates["agency_indirect"]; }
                                    else if ($indirect_costs == 2) { $indirect_NPE = $nonpersonnel_expenses * $grant_indirect_rate; } 
                                    else if ($indirect_costs == 3) { $indirect_NPE = $nonpersonnel_expenses * $dpi_grant_indirect_rate; } 

                                    $personnel_expenses = $total_compensation;
                                    if ($indirect_costs == 1) { $indirect_PE = $personnel_expenses * $rates["agency_indirect"]; }
                                    else if ($indirect_costs == 2) { $indirect_PE = $personnel_expenses * $grant_indirect_rate; } 
                                    else if ($indirect_costs == 3) { $indirect_PE = $personnel_expenses * $dpi_grant_indirect_rate; } 

                                    /* PROJECT INDIRECT */
                                    if ($indirect_costs == 1 || $indirect_costs == 2 || $indirect_costs == 3) { $project_indirect = $indirect_PE + $indirect_NPE; }
                                    else { $project_indirect = 0; }

                                    // calculate total - indirect
                                    $total_without_indirect = $total_expenses - $project_indirect;

                                    // build rate to print
                                    $print_rate = $print_label = null;
                                    if ($indirect_costs == 1) {
                                        $print_rate = $rates["agency_indirect"];
                                        $print_label = "Agency Rate";
                                    } else if ($indirect_costs == 2) {
                                        $print_rate = $grant_indirect_rate;
                                        $print_label = "Grant Rate";
                                    } else if ($indirect_costs == 3) {
                                        $print_rate = $dpi_grant_indirect_rate;
                                        $print_label = "DPI Grant Rate";
                                    }

                                    // build array of data to print
                                    $temp = [];
                                    $temp["code"] = $code;
                                    $temp["name"] = $name;
                                    $temp["rate"] = $print_rate;
                                    $temp["label"] = $print_label;
                                    $temp["total"] = printDollar($total_expenses);
                                    $temp["indirect"] = printDollar($project_indirect);
                                    $temp["diff"] = printDollar($total_without_indirect);
                                    $temp["calc_total"] = $total_expenses;
                                    $temp["calc_indirect"] = $project_indirect;
                                    $temp["calc_diff"] = $total_without_indirect;
                                    $masterData[] = $temp;
                                }
                            }
                        }
                    }
                }
            }

            // send data to be printed
            $fullData = [];
            $fullData["draw"] = 1;
            $fullData["data"] = $masterData;
            echo json_encode($fullData);

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>