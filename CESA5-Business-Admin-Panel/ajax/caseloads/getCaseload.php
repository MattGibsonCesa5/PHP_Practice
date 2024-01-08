<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get today's date
        $today = date("Y-m-d");

        // initialize array to hold students to be displayed
        $students = [];

        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to view caseloads
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") || checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED"))
        {
            // store user permissions locally
            $can_user_edit = checkUserPermission($conn, "EDIT_CASELOADS");
            $can_user_delete = checkUserPermission($conn, "DELETE_CASELOADS");
            $view_all = checkUserPermission($conn, "VIEW_CASELOADS_ALL");

            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }
            if (isset($_POST["caseload_id"]) && $_POST["caseload_id"] <> "") { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id = null; }

            // verify the caseload
            if ($caseload_id != null && verifyCaseload($conn, $caseload_id))
            {
                // disable edit, transfer, and delete buttons for coordinators; unless the coordinator is the case manager 
                if (verifyCoordinator($conn, $_SESSION["id"]) && !isCaseloadAssigned($conn, $_SESSION["id"], $caseload_id)) 
                { 
                    $can_user_edit = false; 
                    $can_user_delete = false;
                }

                // verify the user has access to the caseload
                if (verifyUserCaseload($conn, $caseload_id))
                {
                    // get the category ID
                    $category_id = getCaseloadCategory($conn, $caseload_id);

                    // get if the caseload is a classroom-based caseload or not
                    $isClassroom = isCaseloadClassroom($conn, $caseload_id);

                    // verify the period exists; if it exists, store the period ID
                    if ($period != null && $period_id = getPeriodID($conn, $period))
                    {
                        // store if the period is editable
                        $is_editable = isPeriodEditable($conn, $period_id);
                        
                        // override is_editable if user can only view assigned and the caseload category is locked
                        if (!$view_all && isCaseloadLocked($conn, $caseload_id)) { 
                            $is_editable = false;
                        }

                        // get all cases for existing students
                        $getCases = mysqli_prepare($conn, "SELECT c.*, s.fname, s.lname, s.date_of_birth FROM cases c
                                                            JOIN caseload_students s ON c.student_id=s.id
                                                            WHERE c.period_id=? AND c.caseload_id=?");
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
                                    $student_id = $case["student_id"];
                                    $fname = $case["fname"];
                                    $lname = $case["lname"];
                                    $status = $case["active"];
                                    if (isset($case["date_of_birth"])) { $date_of_birth = date("n/j/Y", strtotime($case["date_of_birth"])); } else { $date_of_birth = "?"; }
                                    $grade_level = $case["grade_level"];
                                    $evaluation_method = $case["evaluation_method"];
                                    $enrollment_type = $case["enrollment_type"];
                                    $educational_plan = $case["educational_plan"];
                                    $residency_id = $case["residency"];
                                    $district_id = $case["district_attending"];
                                    $school_id = $case["school_attending"];
                                    $bill_to = $case["bill_to"];
                                    $billing_notes = $case["billing_notes"];
                                    $frequency = $case["frequency"];
                                    $units = $case["estimated_uos"];
                                    $extra_ieps = $case["extra_ieps"];
                                    $extra_evals = $case["extra_evaluations"];
                                    $membership_days = $case["membership_days"];
                                    if (isset($case["start_date"]) && $case["start_date"] != null) { $start_date = date("n/j/Y", strtotime($case["start_date"])); } else { $start_date = "?"; }
                                    if (isset($case["end_date"]) && $case["end_date"] != null) { $end_date = date("n/j/Y",  strtotime($case["end_date"])); } else { $end_date = "?"; }
                                    $classroom_id = $case["classroom_id"];
                                    $dismissed = $case["dismissed"];
                                    $assistant_id = $case["assistant_id"];

                                    // get the school name
                                    $school_name = getSchoolName($conn, $school_id);
                                    if ($school_name == "")
                                    {
                                        $school_name = "<span class='badge badge-pill bg-danger' title='This student is missing their school! Please select an existing school for this student.'><i class='fa-solid fa-triangle-exclamation'></i></span>";
                                    }

                                    // get end of year frequency
                                    $numOfCaseChanges = 0;
                                    $EOY_frequency = ""; // initialize end of year frequency
                                    $getEndOfYearFrequency = mysqli_prepare($conn, "SELECT frequency FROM case_changes WHERE case_id=? AND start_date<=? ORDER BY start_date DESC LIMIT 1");
                                    mysqli_stmt_bind_param($getEndOfYearFrequency, "is", $case_id, $today);
                                    if (mysqli_stmt_execute($getEndOfYearFrequency))
                                    {
                                        $getEndOfYearFrequencyResult = mysqli_stmt_get_result($getEndOfYearFrequency);
                                        if (($numOfCaseChanges = mysqli_num_rows($getEndOfYearFrequencyResult)) > 0)
                                        {
                                            $EOY_frequency = mysqli_fetch_array($getEndOfYearFrequencyResult)["frequency"];
                                        }
                                    }
                                    if ($numOfCaseChanges == 0) { $EOY_frequency = $frequency; }

                                    // get the end of year units of service (prorated based on changes)
                                    $EOY_units = 0;
                                    if ($evaluation_method == 1) { $EOY_units = getProratedUOS($conn, $case_id); }
                                    else if ($evaluation_method == 2) { $EOY_units = 16; }

                                    // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                                    $additional_units = 0;
                                    if (is_numeric($extra_ieps) && $extra_ieps > 0) { $additional_units += (12 * $extra_ieps); }
                                    if (is_numeric($extra_evals) && $extra_evals > 0) { $additional_units += (16 * $extra_evals); }
                                    $EOY_units += $additional_units;

                                    // create the student's display name
                                    $display_name = $lname.", ".$fname;

                                    // get student's age
                                    $age = getAge($date_of_birth);

                                    // get the residency name
                                    $residency = ""; 
                                    $residency_details = getCustomerDetails($conn, $residency_id);
                                    if (is_array($residency_details)) { $residency = $residency_details["name"]; }   

                                    // get the district's name
                                    $district = ""; 
                                    $district_details = getCustomerDetails($conn, $district_id);
                                    if (is_array($district_details)) { $district = $district_details["name"]; }  

                                    // calculate the difference in SOY UOS and EOY UOS
                                    $UOS_diff = $EOY_units - $units;

                                    // build evaluation method filter
                                    $filter_evaluation_method = "";
                                    // if ($evaluation_method == 0) { $filter_evaluation_method = "Pending Evaluation"; }
                                    if ($evaluation_method == 1) { $filter_evaluation_method = "Regular"; }
                                    if ($evaluation_method == 2) { $filter_evaluation_method = "Evaluation Only"; } 

                                    // build the student display
                                    $student_display = "<button class='btn btn-caseload-student_details w-100 py-1 px-2' type='button' onclick='getViewStudentModal(".$case_id.", ".$student_id.");'>";
                                        if ($status == 1) { $student_display .= "<div class='my-1'><span class='text-nowrap'><b>Name:</b> $display_name</span><div class='active-div text-center px-3 py-1 float-end'>Active</div></div>"; }
                                        else { $student_display .= "<div class='my-1'><span class='text-nowrap'><b>Name:</b> $display_name</span><div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div></div>"; } 
                                        $student_display .= "<div class='my-1 text-nowrap'><b>Date Of Birth:</b> ".$date_of_birth." (".$age." years old)</div>";
                                        $student_display .= "<div class='my-1'><b>Grade:</b> ".printGradeLevel($grade_level)."</div>
                                    </button>";

                                    // get the classroom name
                                    $classroom_name = "";
                                    if (isset($classroom_id) && $classroom_id != null && is_numeric($classroom_id))
                                    {
                                        $classroom_name = getCaseloadClassroomName($conn, $classroom_id);
                                    }

                                    // build the attending column
                                    $location_column = $residency_billing_div = $district_billing_div = "";
                                    if ($bill_to == 1) { $residency_billing_div .= "<div class='billing_to-div d-inline text-center px-2 py-1' title='Billing to residency.'><i class='fa-solid fa-money-bill-transfer'></i></div>"; }
                                    if ($bill_to == 2) { $district_billing_div .= "<div class='billing_to-div d-inline text-center px-2 py-1' title='Billing to district.'><i class='fa-solid fa-money-bill-transfer'></i></div>"; }
                                    $location_column .= "<div class='my-1'>$residency_billing_div <b>Resides:</b> $residency</div>";
                                    $location_column .= "<div class='my-1'>$district_billing_div <b>District:</b> $district</div>";
                                    $location_column .= "<div class='my-1'><b>School:</b> $school_name</div>";
                                    if ($isClassroom === true) 
                                    {
                                        $location_column .= "<div class='my-1'><b>Classroom:</b> ";
                                        if ($classroom_name <> "") { $location_column .= $classroom_name; } else { $location_column .= "<span class='missing-field'>Missing/unknown</span>"; }
                                        $location_column .= "</div>";
                                    }

                                    // build the school year column
                                    $daterange = "";
                                    if ($evaluation_method == 0) { $daterange = ""; }
                                    else if ($evaluation_method == 1) { $daterange .= "<b>Start:</b> ".$start_date."<br><b>End:</b> ".$end_date; }
                                    else if ($evaluation_method == 2) { $daterange .= "<b>Evaluated:</b> ".$start_date; }

                                    // build the actions column
                                    $actions = "<div class='d-flex justify-content-end'>";
                                        if ($evaluation_method == 1) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getViewCaseChangesModal(".$case_id.");' title='View & Add Changes'><i class='fa-solid fa-eye'></i></button>"; }
                                        if (($can_user_edit && $is_editable && $dismissed == 0) || $view_all === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditCaseModal(".$case_id.");' title='Edit Case'><i class='fa-solid fa-pencil'></i></button>"; }
                                        if ($view_all && $is_editable && $dismissed == 0 && $caseload_id > 0) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getRequestCaseloadTransferModal(".$case_id.");' title='Request Student Transfer'><i class='fa-solid fa-right-left'></i></button>"; }
                                        if ($view_all && $is_editable && $dismissed == 1 && $caseload_id > 0) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getUndoCaseDismissalModal(".$case_id.");' title='Undo Dismissal'><i class=\"fa-solid fa-rotate-left\"></i></button>"; }
                                        if ($can_user_delete && $is_editable) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteCaseModal(".$case_id.");' title='Delete Case'><i class='fa-solid fa-trash-can'></i></button>"; }
                                    $actions .= "</div>";

                                    // build the status column to be filtered by
                                    $filter_status = "";
                                    if ($status == 1) { $filter_status = "Active"; }
                                    else { $filter_status = "Inactive"; }

                                    // if the user was dismissed, override current frequency
                                    if ($dismissed == 1) { $EOY_frequency = "<span class=\"missing-field fw-bold\">Dismissed</span>"; }

                                    // get assistant name
                                    $assistant_name = getAssistantName($conn, $assistant_id);

                                    // build the temporary array of data
                                    $temp = [];
                                    $temp["student"] = $student_display;
                                    $temp["location"] = $location_column;
                                    $temp["daterange"] = $daterange;
                                    $temp["assistant"] = $assistant_name;
                                    $temp["SOY-frequency"] = $frequency;
                                    $temp["SOY-UOS"] = $units;
                                    $temp["EOY-frequency"] = $EOY_frequency;
                                    $temp["additional-UOS"] = $additional_units;
                                    $temp["EOY-UOS"] = $EOY_units;
                                    $temp["UOS_change"] = $UOS_diff;
                                    $temp["billing_notes"] = $billing_notes;
                                    $temp["actions"] = $actions;
                                    $temp["attending_id"] = $district_id;
                                    $temp["status"] = $filter_status;
                                    $temp["evaluation_method"] = $filter_evaluation_method;
                                    $temp["grade_id"] = $grade_level;
                                    $temp["membership_days"] = $membership_days;

                                    // add to temporary array columns build for exports
                                    $temp["export-student_name"] = $display_name;
                                    $temp["export-student_dob"] = $date_of_birth;
                                    $temp["export-student_grade"] = printGradeLevel($grade_level);
                                    $temp["export-residency"] = $residency;
                                    $temp["export-attending"] = $district;
                                    $temp["export-school"] = $school_name;
                                    $temp["export-classroom"] = $classroom_name;
                                    $temp["export-start_date"] = $start_date;
                                    $temp["export-end_date"] = $end_date;
                                    $temp["export-frequency"] = $EOY_frequency;
                                    $temp["export-additional_uos"] = $additional_units;
                                    $temp["export-uos_to_bill"] = $EOY_units;
                                    $temp["export-membership_days"] = $membership_days;

                                    // add the temporary array to the master list
                                    $students[] = $temp;
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
        $fullData["data"] = $students;
        echo json_encode($fullData);

        // disconnect from the database
        mysqli_close($conn);
    }
?>