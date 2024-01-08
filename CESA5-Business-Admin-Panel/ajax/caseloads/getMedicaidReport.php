<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold students to be displayed
        $students = [];

        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission to view caseloads
        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL"))
        {
            // get parameters from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // get all caseloads for the period that are enrolled for Medicaid
                $getCaseloads = mysqli_prepare($conn, "SELECT c.id FROM caseloads c
                                                        JOIN caseloads_status cs ON c.id=cs.caseload_id
                                                        JOIN caseload_categories cc ON c.category_id=cc.id
                                                        WHERE cs.period_id=? AND cs.status=1 AND cc.medicaid=1");
                mysqli_stmt_bind_param($getCaseloads, "i", $period_id);
                if (mysqli_stmt_execute($getCaseloads))
                {
                    $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                    if (mysqli_num_rows($getCaseloadsResults) > 0) // caseloads found
                    {
                        while ($caseload = mysqli_fetch_array($getCaseloadsResults))
                        {
                            // store caseload details locally
                            $caseload_id = $caseload["id"];
                            
                            // store if the caseload is classroom-based
                            $isClassroom = isCaseloadClassroom($conn, $caseload_id);

                            // store if the period is editable
                            $is_editable = isPeriodEditable($conn, $period_id);

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
                                        $caseload_id = $case["caseload_id"];
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
                                        $medicaid_billing_done = $case["medicaid_billing_done"];
                                        $medicaid_billed = $case["medicaid_billed"];
                                        $evaluation_month = $case["medicaid_evaluation_month"];

                                        // create the student's display name
                                        $display_name = $lname.", ".$fname;

                                        // get the school name
                                        $school_name = getSchoolName($conn, $school_id);
                                        if ($school_name == "")
                                        {
                                            $school_name = "<span class='badge badge-pill bg-danger' title='This student is missing their school! Please select an existing school for this student.'><i class='fa-solid fa-triangle-exclamation'></i></span>";
                                        }

                                        // get the residency name
                                        $residency = ""; 
                                        $residency_details = getCustomerDetails($conn, $residency_id);
                                        if (is_array($residency_details)) { $residency = $residency_details["name"]; }   

                                        // get the district's name
                                        $district = ""; 
                                        $district_details = getCustomerDetails($conn, $district_id);
                                        if (is_array($district_details)) { $district = $district_details["name"]; }  
                                        
                                        // get the classroom name
                                        $classroom_name = "";
                                        if (isset($classroom_id) && $classroom_id != null && is_numeric($classroom_id))
                                        {
                                            $classroom_name = getCaseloadClassroomName($conn, $classroom_id);
                                        }

                                        // build evaluation method filter
                                        $filter_evaluation_method = "";
                                        // if ($evaluation_method == 0) { $filter_evaluation_method = "Pending Evaluation"; }
                                        if ($evaluation_method == 1) { $filter_evaluation_method = "Regular"; }
                                        if ($evaluation_method == 2) { $filter_evaluation_method = "Evaluation Only"; } 

                                        // evaluation method column
                                        $display_evaluation_method = "";
                                        if ($evaluation_method == 0) { $display_evaluation_method = "<span title='Pending Evaluation'>Pending Eval.</span>"; }
                                        if ($evaluation_method == 1) { $display_evaluation_method = "Regular"; }
                                        if ($evaluation_method == 2) { $display_evaluation_method = "<span title='Evaluation Only'>Eval. Only</span>"; }

                                        // build the enrollment type column
                                        $display_enrollment_type = "";
                                        if ($enrollment_type == 1) { $display_enrollment_type = "Resident"; }
                                        if ($enrollment_type == 2) { $display_enrollment_type = "<span title='Open Enrolled'>Open Enr.</span>"; }
                                        if ($enrollment_type == 3) { $display_enrollment_type = "Placed"; }
                                        if ($enrollment_type == 4) { $display_enrollment_type = "66.0301"; }
                                        if ($enrollment_type == 5) { $display_enrollment_type = "Other"; }

                                        // build the educational plan column
                                        $display_educational_plan = "";
                                        if ($educational_plan == 1) { $display_educational_plan = "504"; }
                                        if ($educational_plan == 2) { $display_educational_plan = "IEP"; }
                                        if ($educational_plan == 3) { $display_educational_plan = "ISP"; }
                                        if ($educational_plan == 4) { $display_educational_plan = "Other"; }

                                        // get student's age
                                        $age = getAge($date_of_birth);

                                        // build the student display
                                        $student_display = "<button class='btn btn-caseload-student_details w-100 py-1 px-2' type='button' onclick='getViewStudentModal(".$case_id.", ".$student_id.");'>";
                                            if ($status == 1) { $student_display .= "<div class='my-1'><span class='text-nowrap'><b>Name:</b> $display_name</span><div class='active-div text-center px-3 py-1 float-end'>Active</div></div>"; }
                                            else { $student_display .= "<div class='my-1'><span class='text-nowrap'><b>Name:</b> $display_name</span><div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div></div>"; } 
                                            $student_display .= "<div class='my-1 text-nowrap'><b>Date Of Birth:</b> ".$date_of_birth." (".$age." years old)</div>";
                                            $student_display .= "<div class='my-1'><b>Grade:</b> ".printGradeLevel($grade_level)."</div>";
                                            $student_display .= "<div class='row my-1'>";
                                                if ($display_evaluation_method <> "") { $student_display .= "<div class='col-12 col-sm-12 col-md-12 col-lg-12 col-xl-4 col-xxl-4 p-1'><div class='evaluation_method-div d-flex justify-content-center align-items-center text-center p-1 w-100 h-100'>".$display_evaluation_method."</div></div>"; } else { $student_display .= "<div class='col-12 col-sm-12 col-md-12 col-lg-12 col-xl-4 col-xxl-4 p-1'><div class='evaluation_method-div text-center p-1 w-100'>-</div></div>"; }
                                                if ($display_enrollment_type <> "") { $student_display .= "<div class='col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-1'><div class='enrollment_type-div d-flex justify-content-center align-items-center text-center p-1 w-100 h-100'>".$display_enrollment_type."</div></div>"; } else { $student_display .= "<div class='col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-1'><div class='enrollment_type-div text-center p-1 w-100'>-</div></div>"; }
                                                if ($display_educational_plan <> "") { $student_display .= "<div class='col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-1'><div class='educational_plan-div d-flex justify-content-center align-items-center text-center p-1 w-100 h-100'>".$display_educational_plan."</div></div>"; } else { $student_display .= "<div class='col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-1'><div class='educational_plan-div text-center p-1 w-100'>-</div></div>"; }
                                            $student_display .= "</div>
                                        </button>";

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
                                        if ($evaluation_method == 0) { 
                                            $start_date = ""; $end_date = ""; 
                                        } else if ($evaluation_method == 2) { 
                                            $end_date = $start_date; 
                                        }

                                        // build the status column to be filtered by
                                        $filter_status = "";
                                        if ($status == 1) { $filter_status = "Active"; }
                                        else { $filter_status = "Inactive"; }

                                        // build evaluation month
                                        $display_evaluation_month = "";
                                        if (is_numeric($evaluation_month) && ($evaluation_month >= 1 && $evaluation_month <= 12)) { $display_evaluation_month = date("F", mktime(0, 0, 0, $evaluation_month, 10)); }
                                        else { $display_evaluation_month = "<span class='missing-field'>Missing</span>"; }

                                        // build assistant column
                                        $assistant_name = getAssistantName($conn, $assistant_id);
                                        $assistant = $assistant_name;

                                        // get therapist name
                                        $therapist_id = getCaseloadTherapist($conn, $caseload_id);
                                        $therapist_name = getUserDisplayName($conn, $therapist_id);

                                        // build the name and status column
                                        $therapist_div = "<div class='my-1'>
                                            <form class='w-100' method='POST' action='caseload.php'>
                                                <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                                <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                                <button class='btn btn-therapist_caseload text-center w-100' type='submit'>
                                                    <span class='text-nowrap'>$therapist_name</span>
                                                </button>
                                            </form>
                                        </div>";

                                        // build the actions column
                                        $actions = "<div class=\"d-flex flex-column justify-content-center\">
                                            <div class=\"form-check\">";
                                                if ($medicaid_billing_done == 1) { 
                                                    $actions .= "<span data-bs-toggle=\"tooltip\" data-bs-placement=\"bottom\" title=\"To be checked if medicaid billing is completed for the student.\"><label class=\"form-check-label\" for=\"mbd-".$case_id."\">Medicaid Billing Done?</label></span>
                                                        <input class=\"form-check-input\" type=\"checkbox\" id=\"mbd-".$case_id."\" onchange=\"toggleMedicaidBillingDone(".$case_id.", this.checked);\" checked>";
                                                } else {
                                                    $actions .= "<span data-bs-toggle=\"tooltip\" data-bs-placement=\"bottom\" title=\"To be checked if medicaid billing is completed for the student.\"><label class=\"form-check-label\" for=\"mbd-".$case_id."\">Medicaid Billing Done?</label></span>
                                                        <input class=\"form-check-input\" type=\"checkbox\" id=\"mbd-".$case_id."\" onchange=\"toggleMedicaidBillingDone(".$case_id.", this.checked);\">";
                                                }
                                            $actions .= "</div>
                                            <div class=\"form-check\">";
                                                if ($medicaid_billed == 1) { 
                                                    $actions .= "<span data-bs-toggle=\"tooltip\" data-bs-placement=\"bottom\" title=\"To be checked after medicaid has been entered into the system to be billed.\"><label class=\"form-check-label\" for=\"mb-".$case_id."\">Updated Caseload</label></span>
                                                        <input class=\"form-check-input\" type=\"checkbox\" id=\"mb-".$case_id."\" onchange=\"toggleMedicaidBilled(".$case_id.", this.checked);\" checked>";
                                                } else {
                                                    $actions .= "<span data-bs-toggle=\"tooltip\" data-bs-placement=\"bottom\" title=\"To be checked after medicaid has been entered into the system to be billed.\"><label class=\"form-check-label\" for=\"mb-".$case_id."\">Updated Caseload</label></span>
                                                        <input class=\"form-check-input\" type=\"checkbox\" id=\"mb-".$case_id."\" onchange=\"toggleMedicaidBilled(".$case_id.", this.checked);\">";
                                                }
                                            $actions .= "</div>
                                        </div>";

                                        // get caseload category name
                                        $category_id = getCaseloadCategory($conn, $caseload_id);
                                        $category_name = getCaseloadCategoryName($conn, $category_id);

                                        // create bill to district filter
                                        $bill_to_district = "";
                                        if ($bill_to == 1) { $bill_to_district = $residency; }
                                        else if ($bill_to == 2) { $bill_to_district = $district; }

                                        // build the temporary array of data
                                        $temp = [];
                                        $temp["student"] = $student_display;
                                        $temp["location"] = $location_column;
                                        $temp["start"] = $start_date;
                                        $temp["end"] = $end_date;
                                        $temp["month"] = $display_evaluation_month;
                                        $temp["therapist"] = $therapist_div;
                                        $temp["assistant"] = $assistant;
                                        $temp["actions"] = $actions;
                                        $temp["filter_district"] = $bill_to_district;
                                        $temp["filter_category"] = $category_name;
                                        $temp["filter_therapist"] = $therapist_name;

                                        // add the temporary array to the master list
                                        $students[] = $temp;
                                    }
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