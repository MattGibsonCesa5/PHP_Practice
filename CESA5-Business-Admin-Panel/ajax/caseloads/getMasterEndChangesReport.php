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

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") || checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period exists; if it exists, store the period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // get all caseloads
                $getCaseloads = mysqli_prepare($conn, "SELECT cl.id, cc.id AS category_id, cc.name AS category_name, u.lname, u.fname FROM caseloads cl
                                                        JOIN caseload_categories cc ON cl.category_id=cc.id
                                                        JOIN users u ON cl.employee_id=u.id");
                if (mysqli_stmt_execute($getCaseloads))
                {
                    $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                    if (mysqli_num_rows($getCaseloadsResults) > 0)
                    {
                        while ($caseload = mysqli_fetch_array($getCaseloadsResults))
                        {
                            // store caseload details locally
                            $caseload_id = $caseload["id"];
                            $category_id = $caseload["category_id"];
                            $category_name = $caseload["category_name"];
                            $lname = $caseload["lname"];
                            $fname = $caseload["fname"];

                            // build the caseload div
                            $caseload_div = ""; // initialize div
                            $caseload_div .= "<div class='my-1'>
                                <form class='w-100' method='POST' action='caseload.php'>
                                    <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                    <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                    <button class='btn btn-therapist_caseload w-100' type='submit'>
                                        <div class='text-center my-1'>$lname, $fname</div>
                                        <div class='text-center my-1'>$category_name</div>
                                    </button>
                                </form>
                            </div>";

                            // verify the user has access to this caseload
                            if (verifyUserCaseload($conn, $caseload_id))
                            {
                                // get all cases for exisiting students within this caseload for the period provided
                                $getCases = mysqli_prepare($conn, "SELECT c.*, cl.employee_id, s.fname, s.lname, s.date_of_birth FROM cases c
                                                                    JOIN caseloads cl ON c.caseload_id=cl.id
                                                                    JOIN caseload_students s ON c.student_id=s.id
                                                                    WHERE c.period_id=? AND c.caseload_id=? AND c.end_date<(SELECT p.caseload_term_end FROM periods p WHERE p.id=?) AND c.evaluation_method=1");
                                mysqli_stmt_bind_param($getCases, "iii", $period_id, $caseload_id, $period_id);
                                if (mysqli_stmt_execute($getCases))
                                {
                                    $getCasesResults = mysqli_stmt_get_result($getCases);
                                    if (mysqli_num_rows($getCasesResults) > 0) // cases exist; continue
                                    {
                                        while ($case = mysqli_fetch_array($getCasesResults))
                                        {
                                            // store caseload data locally
                                            $case_id = $case["id"];
                                            $caseload_id = $case["caseload_id"];
                                            $student_id = $case["student_id"];
                                            $therapist_id = $case["employee_id"];
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
                                            if (isset($case["start_date"]) && $case["start_date"] != null) { $start_date = date("n/j/Y", strtotime($case["start_date"])); } else { $start_date = "?"; }
                                            if (isset($case["end_date"]) && $case["end_date"] != null) { $end_date = date("n/j/Y",  strtotime($case["end_date"])); } else { $end_date = "?"; }
                                            $assistant_id = $case["assistant_id"];
                                            $evaluation_month = $case["medicaid_evaluation_month"];
                                            $medicaid_billing_done = $case["medicaid_billing_done"];
                                            
                                            // get the school name
                                            $school_name = getSchoolName($conn, $school_id);
                                            if ($school_name == "")
                                            {
                                                $school_name = "<span class='badge badge-pill bg-danger' title='This student is missing their school! Please select an existing school for this student.'><i class='fa-solid fa-triangle-exclamation'></i></span>";
                                            }

                                            // create the student's display name
                                            $display_name = $lname.", ".$fname;

                                            // get therapists display name
                                            $therapist = getUserDisplayName($conn, $therapist_id);

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

                                            // build the student display
                                            $student_display = "<button class='btn btn-caseload-student_details w-100 py-1 px-2' type='button' onclick='getViewStudentModal(".$case_id.", ".$student_id.");'>";
                                                if ($status == 1) { $student_display .= "<div class='my-1'><span class='text-nowrap'><b>Name:</b> $display_name</span><div class='active-div text-center px-3 py-1 float-end'>Active</div></div>"; }
                                                else { $student_display .= "<div class='my-1'><span class='text-nowrap'><b>Name:</b> $display_name</span><div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div></div>"; } 
                                                $student_display .= "<div class='my-1 text-nowrap'><b>Date Of Birth:</b> ".$date_of_birth." (".$age." years old)</div>";
                                                $student_display .= "<div class='my-1'><b>Grade:</b> ".printGradeLevel($grade_level)."</div>
                                            </button>";

                                            // build the attending column
                                            $location_column = $residency_billing_div = $district_billing_div = "";
                                            if ($bill_to == 1) { $residency_billing_div .= "<div class='billing_to-div d-inline text-center px-2 py-1' title='Billing to residency.'><i class='fa-solid fa-money-bill-transfer'></i></div>"; }
                                            if ($bill_to == 2) { $district_billing_div .= "<div class='billing_to-div d-inline text-center px-2 py-1' title='Billing to district.'><i class='fa-solid fa-money-bill-transfer'></i></div>"; }
                                            $location_column .= "<div class='my-1'>$residency_billing_div <b>Resides:</b> $residency</div>";
                                            $location_column .= "<div class='my-1'>$district_billing_div <b>District:</b> $district</div>";
                                            $location_column .= "<div class='my-1'><b>School:</b> $school_name</div>";

                                            // build the assistant column
                                            $assistant_name = getAssistantName($conn, $assistant_id);

                                            // build the status column to be filtered by
                                            $filter_status = "";
                                            if ($status == 1) { $filter_status = "Active"; }
                                            else { $filter_status = "Inactive"; }

                                            // build evaluation month
                                            $display_evaluation_month = "";
                                            if (is_numeric($evaluation_month) && ($evaluation_month >= 1 && $evaluation_month <= 12)) { $display_evaluation_month = date("F", mktime(0, 0, 0, $evaluation_month, 10)); }
                                            else if (is_numeric($evaluation_month) && $evaluation_month == 0) { $display_evaluation_month = "N/A"; }
                                            else { $display_evaluation_month = "<span class='missing-field'>Missing</span>"; }

                                            // build the actions column
                                            $actions = "<div class=\"d-flex justify-content-center\">
                                                <div class=\"form-check\">";
                                                    if ($medicaid_billing_done == 1) { 
                                                        $actions .= "<span data-bs-toggle=\"tooltip\" data-bs-placement=\"bottom\" title=\"To be checked if medicaid billing is completed for the student.\"><label class=\"form-check-label\" for=\"mbd-".$case_id."\">Medicaid Billing Done?</label></span>
                                                            <input class=\"form-check-input\" type=\"checkbox\" id=\"mbd-".$case_id."\" onchange=\"toggleMedicaidBillingDone(".$case_id.", this.checked);\" checked>";
                                                    } else {
                                                        $actions .= "<span data-bs-toggle=\"tooltip\" data-bs-placement=\"bottom\" title=\"To be checked if medicaid billing is completed for the student.\"><label class=\"form-check-label\" for=\"mbd-".$case_id."\">Medicaid Billing Done?</label></span>
                                                            <input class=\"form-check-input\" type=\"checkbox\" id=\"mbd-".$case_id."\" onchange=\"toggleMedicaidBillingDone(".$case_id.", this.checked);\">";
                                                    }
                                                $actions .= "</div>
                                            </div>";

                                            // build the temporary array of data
                                            $temp = [];
                                            $temp["therapist"] = $caseload_div;
                                            $temp["category"] = $category_name;
                                            $temp["student"] = $student_display;
                                            $temp["location"] = $location_column;
                                            $temp["start_date"] = $start_date;
                                            $temp["end_date"] = $end_date;
                                            $temp["month"] = $display_evaluation_month;
                                            $temp["assistant"] = $assistant_name;
                                            $temp["actions"] = $actions;
                                            $temp["status"] = $filter_status;
                                            $temp["district"] = $district_id;
                                            $temp["grade"] = $grade_level;

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