<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold data to be displayed
        $data = [];
        $active_count = 0;
        $units = 0;

        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to view caseloads
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") || checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED"))
        {
            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["caseload_id"]) && $_POST["caseload_id"] <> "") { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // verify the caseload
                if ($caseload_id != null && verifyCaseload($conn, $caseload_id))
                {
                    // verify the user has access to the caseload
                    if (verifyUserCaseload($conn, $caseload_id))
                    {
                        // get caseload settings
                        $uosEnabled = isCaseloadUOSEnabled($conn, $caseload_id);
                        $daysEnabled = isCaseloadDaysEnabled($conn, $caseload_id);

                        // get stats based on caseload category settings
                        if ($uosEnabled === true)
                        {
                            // get the total number of active students on the caseload
                            $getActive = mysqli_prepare($conn, "SELECT COUNT(id) AS active_students FROM cases WHERE period_id=? AND caseload_id=? AND active=1");
                            mysqli_stmt_bind_param($getActive, "ii", $period_id, $caseload_id);
                            if (mysqli_stmt_execute($getActive))
                            {
                                $getActiveResult = mysqli_stmt_get_result($getActive);
                                if (mysqli_num_rows($getActiveResult) > 0)
                                {
                                    $active_count = mysqli_fetch_array($getActiveResult)["active_students"];
                                }
                            }

                            // get total prorated units for all cases in the caseload
                            $getCases = mysqli_prepare($conn, "SELECT * FROM cases WHERE period_id=? AND caseload_id=?");
                            mysqli_stmt_bind_param($getCases, "ii", $period_id, $caseload_id);
                            if (mysqli_stmt_execute($getCases))
                            {
                                $getCasesResults = mysqli_stmt_get_result($getCases);
                                if (mysqli_num_rows($getCasesResults) > 0) // cases exist; continue
                                {
                                    while ($case = mysqli_fetch_array($getCasesResults))
                                    {
                                        // store caseload data locally
                                        $case_id = $case["id"];
                                        $status = $case["active"];
                                        $evaluation_method = $case["evaluation_method"];
                                        $enrollment_type = $case["enrollment_type"];
                                        $educational_plan = $case["educational_plan"];
                                        $case_units = $case["estimated_uos"];
                                        $extra_ieps = $case["extra_ieps"];
                                        $extra_evals = $case["extra_evaluations"];
                                        if (isset($case["start_date"]) && $case["start_date"] != null) { $start_date = date("n/j/Y", strtotime($case["start_date"])); } else { $start_date = "?"; }
                                        if (isset($case["end_date"]) && $case["end_date"] != null) { $end_date = date("n/j/Y",  strtotime($case["end_date"])); } else { $end_date = "?"; }
                                        $classroom_id = $case["classroom_id"];
                                        $dismissed = $case["dismissed"];

                                        // get the end of year units of service (prorated based on changes)
                                        $EOY_units = 0;
                                        if ($evaluation_method == 1) { $EOY_units = getProratedUOS($conn, $case_id); }
                                        else if ($evaluation_method == 2) { $EOY_units = 16; }

                                        // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                                        $additional_units = 0;
                                        if (is_numeric($extra_ieps) && $extra_ieps > 0) { $additional_units += (12 * $extra_ieps); }
                                        if (is_numeric($extra_evals) && $extra_evals > 0) { $additional_units += (16 * $extra_evals); }
                                        $EOY_units += $additional_units;

                                        // add end-of-year units to the total
                                        $units += $EOY_units;
                                    }
                                }
                            }
                        }
                        else if ($daysEnabled === true)
                        {
                            // get the total number of active students on the caseload
                            $getActive = mysqli_prepare($conn, "SELECT COUNT(id) AS active_students FROM cases WHERE period_id=? AND caseload_id=? AND active=1");
                            mysqli_stmt_bind_param($getActive, "ii", $period_id, $caseload_id);
                            if (mysqli_stmt_execute($getActive))
                            {
                                $getActiveResult = mysqli_stmt_get_result($getActive);
                                if (mysqli_num_rows($getActiveResult) > 0)
                                {
                                    $active_count = mysqli_fetch_array($getActiveResult)["active_students"];
                                }
                            }

                            // get total prorated units for all cases in the caseload
                            $getCases = mysqli_prepare($conn, "SELECT * FROM cases WHERE period_id=? AND caseload_id=?");
                            mysqli_stmt_bind_param($getCases, "ii", $period_id, $caseload_id);
                            if (mysqli_stmt_execute($getCases))
                            {
                                $getCasesResults = mysqli_stmt_get_result($getCases);
                                if (mysqli_num_rows($getCasesResults) > 0) // cases exist; continue
                                {
                                    while ($case = mysqli_fetch_array($getCasesResults))
                                    {
                                        // store caseload data locally
                                        $case_id = $case["id"];
                                        $membership_days = $case["membership_days"];

                                        // add membership days to toal
                                        $units += $membership_days;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // build the array of data
        $data["count"] = $active_count;
        $data["units"] = $units;

        // return the data to be printed
        echo json_encode($data);

        // disconnect from the database
        mysqli_close($conn);
    }
?>