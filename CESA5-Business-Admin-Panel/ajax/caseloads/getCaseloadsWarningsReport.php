<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold caseloads to be displayed
        $caseloads = [];
        
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // get period name from POST
        if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

        // verify the period was set
        if ($period != null)
        {
            if ($period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
            {
                if (checkUserPermission($conn, "VIEW_THERAPISTS") && checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
                {
                    // get all cases for existing students
                    $getCaseloads = mysqli_prepare($conn, "SELECT c.*, s.fname, s.lname, s.date_of_birth, s.status AS student_status FROM cases c
                                                        JOIN caseload_students s ON c.student_id=s.id
                                                        JOIN caseloads cl ON c.caseload_id=cl.id
                                                        JOIN caseload_categories cc ON cl.category_id=cc.id
                                                        WHERE c.period_id=? AND cc.uos_enabled=1 AND uos_required=1");
                    mysqli_stmt_bind_param($getCaseloads, "i", $period_id);
                    if (mysqli_stmt_execute($getCaseloads))
                    {
                        $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                        if (mysqli_num_rows($getCaseloadsResults) > 0) // caseloads exist; continue
                        {
                            while ($case = mysqli_fetch_array($getCaseloadsResults))
                            {
                                // store caseload data locally
                                $case_id = $case["id"];
                                $caseload_id = $case["caseload_id"];
                                $student_id = $case["student_id"];
                                $fname = $case["fname"];
                                $lname = $case["lname"];
                                $caseload_status = $case["active"];
                                $student_status = $case["student_status"];
                                if (isset($case["date_of_birth"])) { $date_of_birth = date("n/j/Y", strtotime($case["date_of_birth"])); } else { $date_of_birth = "?"; }
                                $grade_level = $case["grade_level"];
                                $evaluation_method = $case["evaluation_method"];
                                $units = $case["estimated_uos"];
                                $extra_ieps = $case["extra_ieps"];
                                $extra_evals = $case["extra_evaluations"];

                                // get caseload category ID
                                $category_id = getCaseloadCategory($conn, $caseload_id);
                                $category_name = getCaseloadCategoryName($conn, $category_id);

                                // get the end of year units of service (prorated based on changes)
                                $EOY_units = 0;
                                if ($evaluation_method == 1) { $EOY_units = getProratedUOS($conn, $case_id); }
                                else if ($evaluation_method == 2) { $EOY_units = 16; }

                                // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                                $additional_units = 0;
                                if (is_numeric($extra_ieps) && $extra_ieps > 0) { $additional_units += (12 * $extra_ieps); }
                                if (is_numeric($extra_evals) && $extra_evals > 0) { $additional_units += (16 * $extra_evals); }
                                $EOY_units += $additional_units;

                                if ($EOY_units >= $GLOBAL_SETTINGS["caseloads_units_warning"] || $EOY_units < 12)
                                {
                                    // create the student's display name
                                    $display_name = $lname.", ".$fname;

                                    // get student's age
                                    $age = getAge($date_of_birth);

                                    // build the student display
                                    $student_display = "<button class='btn btn-caseload-student_details w-100 py-1 px-2' type='button' onclick='getViewStudentModal(".$case_id.", ".$student_id.");'>";
                                        if ($student_status == 1) { $student_display .= "<div class='my-1'><span class='text-nowrap'><b>Name:</b> $display_name</span><div class='active-div text-center px-3 py-1 float-end'>Active</div></div>"; }
                                        else { $student_display .= "<div class='my-1'><span class='text-nowrap'><b>Name:</b> $display_name</span><div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div></div>"; } 
                                        $student_display .= "<div class='my-1 text-nowrap'><b>Date Of Birth:</b> ".$date_of_birth." (".$age." years old)</div>";
                                        $student_display .= "<div class='my-1'><b>Grade:</b> ".printGradeLevel($grade_level)."</div>";
                                    $student_display .= "</button>";

                                    // get the caseload display name
                                    $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                                    // build the name and status column
                                    $caseload_div = ""; // initialize div
                                    $caseload_div .= "<div class='my-1'>
                                        <form class='w-100' method='POST' action='caseload.php'>
                                            <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                            <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                            <button class='btn btn-therapist_caseload w-100' type='submit'>
                                                <span class='text-nowrap float-start'>$caseload_name</span>";
                                                if ($caseload_status == 1) { $caseload_div .= "<div class='active-div text-center px-3 py-1 float-end'>Active</div>"; }
                                                else { $caseload_div .= "<div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div>"; } 
                                            $caseload_div .= "</button>
                                        </form>
                                    </div>";

                                    // build the temporary array of data
                                    $temp = [];
                                    $temp["caseload"] = $caseload_div;
                                    $temp["student"] = $student_display;
                                    $temp["units"] = $EOY_units;
                                    $temp["category"] = $category_name;

                                    // add the temporary array to the master list
                                    $caseloads[] = $temp;
                                }
                            }
                        }
                    }
                }
            }
        }

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $caseloads;
        echo json_encode($fullData);
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>