<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // initialize array to hold students to be displayed
        $students = [];

        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");
        include("../../getSettings.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_STUDENTS_ALL") || checkUserPermission($conn, "VIEW_STUDENTS_ASSIGNED"))
        {
            // get the period from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the period; get period ID
            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // store user permissions locally
                $can_user_edit = checkUserPermission($conn, "EDIT_STUDENTS");
                $can_user_delete = checkUserPermission($conn, "DELETE_STUDENTS");

                // store user permissions for viewing caseloads
                $can_user_view_all = checkUserPermission($conn, "VIEW_CASELOADS_ALL");
                $can_user_view_assigned = checkUserPermission($conn, "VIEW_CASELOADS_ASSIGNED");

                // build and prepare the query to get students
                if (checkUserPermission($conn, "VIEW_STUDENTS_ALL"))
                {
                    $getStudents = mysqli_prepare($conn, "SELECT * FROM caseload_students");
                }
                else if (checkUserPermission($conn, "VIEW_STUDENTS_ASSIGNED"))
                {
                    $getStudents = mysqli_prepare($conn, "SELECT DISTINCT cs.* FROM caseload_students cs
                                                            JOIN cases c ON cs.id=c.student_id
                                                            JOIN caseloads cl ON c.caseload_id=cl.id
                                                            WHERE cl.employee_id=? AND c.period_id=?");
                    mysqli_stmt_bind_param($getStudents, "ii", $_SESSION["id"], $period_id);
                }

                // execute the query to get students
                if (mysqli_stmt_execute($getStudents))
                {
                    $getStudentsResults = mysqli_stmt_get_result($getStudents);
                    if (mysqli_num_rows($getStudentsResults) > 0) // students exist; continue
                    {
                        while ($student = mysqli_fetch_array($getStudentsResults))
                        {
                            // store student data locally
                            $id = $student["id"];
                            $fname = $student["fname"];
                            $lname = $student["lname"];
                            $status = $student["status"];
                            if (isset($student["date_of_birth"])) { $date_of_birth = date("n/j/Y", strtotime($student["date_of_birth"])); } 
                            else { $date_of_birth = "<span class='badge badge-pill bg-danger text-center p-2' title='This student is missing their date of birth. Before further evaluation, the date of birth is required.'><i class='fa-solid fa-triangle-exclamation fa-lg'></i></span>"; }
                            $gender = $student["gender"];

                            // get student's age
                            $age = getAge($date_of_birth);

                            // build the ID / status column
                            $id_div = ""; // initialize div
                            if ($status == 1) { $id_div .= "<div class='my-1'><span class='text-nowrap'>$id</span><div class='active-div text-center px-3 py-1 float-end'>Active</div></div>"; }
                            else { $id_div .= "<div class='my-1'><span class='text-nowrap'>$id</span><div class='inactive-div text-center px-3 py-1 float-end'>Inactive</div></div>"; } 

                            // build columns that display caseload/case data
                            $caseloads_display = "";
                            $total_units = 0;
                            $getCases = mysqli_prepare($conn, "SELECT id, caseload_id, evaluation_method, estimated_uos, extra_ieps, extra_evaluations, membership_days FROM cases WHERE period_id=? AND student_id=?");
                            mysqli_stmt_bind_param($getCases, "ii", $period_id, $id);
                            if (mysqli_stmt_execute($getCases))
                            {
                                $getCasesResults = mysqli_stmt_get_result($getCases);
                                if (mysqli_num_rows($getCasesResults) > 0) // cases exist; continue
                                {
                                    while ($case = mysqli_fetch_array($getCasesResults))
                                    {
                                        // store case data locally
                                        $case_id = $case["id"];
                                        $caseload_id = $case["caseload_id"];
                                        $evaluation_method = $case["evaluation_method"];
                                        $units = $case["estimated_uos"];
                                        $extra_ieps = $case["extra_ieps"];
                                        $extra_evals = $case["extra_evaluations"];
                                        $days = $case["membership_days"];

                                        // get the caseload therapist and category
                                        $caseload_therapist_id = getCaseloadTherapist($conn, $caseload_id);
                                        $caseload_therapist_name = getUserDisplayName($conn, $caseload_therapist_id);
                                        $caseload_category_id = getCaseloadCategory($conn, $caseload_id);
                                        $caseload_category_name = getCaseloadCategoryName($conn, $caseload_category_id);

                                        // get caseload category types
                                        $category_settings = getCaseloadCategorySettings($conn, $caseload_category_id);

                                        // build displays based on category type
                                        if (isset($category_settings) && isset($category_settings["is_classroom"]) && $category_settings["is_classroom"] == 1)
                                        {
                                            // build the form to go to the caseload
                                            if (($can_user_view_all === true || $can_user_view_assigned === true) && checkUserPermission($conn, "VIEW_STUDENTS_ALL")) 
                                            { 
                                                $caseloads_display .= "<div class='my-1'>
                                                    <form class='w-100' method='POST' action='caseload.php'>
                                                        <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                                        <input type='hidden' id='therapist_id' name='therapist_id' value='".$caseload_therapist_id."' aria-hidden='true'>
                                                        <input type='hidden' id='category_id' name='category_id' value='".$caseload_category_id."' aria-hidden='true'>
                                                        <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                                        <button class='btn btn-therapist_caseload w-100' type='submit'>
                                                            <span class='text-nowrap float-start'>$caseload_therapist_name ($caseload_category_name)</span>
                                                            <span class='text-nowrap float-end'>".$days." days</span>
                                                        </button>
                                                    </form>
                                                </div>";
                                            } else {
                                                $caseloads_display .= "<div class='my-1'>
                                                    <span class='text-nowrap float-start'>$caseload_therapist_name ($caseload_category_name)</span>
                                                    <span class='text-nowrap float-end'>".$days." days</span>
                                                </div>";
                                            }
                                        }
                                        else if (isset($category_settings) && isset($category_settings["uos_enabled"]) && $category_settings["uos_enabled"] == 1)
                                        {
                                            // get the end of year units of service (prorated based on changes)
                                            $EOY_units = 0;
                                            if ($evaluation_method == 1) { $EOY_units = getProratedUOS($conn, $case_id); }
                                            else if ($evaluation_method == 2) { $EOY_units = 16; }

                                            // calculate the number of additional units based on extra IEPs or evaluations, then add to the EOY unit total
                                            $additional_units = 0;
                                            if (is_numeric($extra_ieps) && $extra_ieps > 0) { $additional_units += (12 * $extra_ieps); }
                                            if (is_numeric($extra_evals) && $extra_evals > 0) { $additional_units += (16 * $extra_evals); }
                                            $EOY_units += $additional_units;

                                            // add the cases EOY units to the count
                                            $total_units += $EOY_units;

                                            // build the form to go to the caseload
                                            if (($can_user_view_all === true || $can_user_view_assigned === true) && checkUserPermission($conn, "VIEW_STUDENTS_ALL")) 
                                            { 
                                                $caseloads_display .= "<div class='my-1'>
                                                    <form class='w-100' method='POST' action='caseload.php'>
                                                        <input type='hidden' id='caseload_id' name='caseload_id' value='".$caseload_id."' aria-hidden='true'>
                                                        <input type='hidden' id='therapist_id' name='therapist_id' value='".$caseload_therapist_id."' aria-hidden='true'>
                                                        <input type='hidden' id='category_id' name='category_id' value='".$caseload_category_id."' aria-hidden='true'>
                                                        <input type='hidden' id='period_id' name='period_id' value='".$period_id."' aria-hidden='true'>
                                                        <button class='btn btn-therapist_caseload w-100' type='submit'>
                                                            <span class='text-nowrap float-start'>$caseload_therapist_name ($caseload_category_name)</span>
                                                            <span class='text-nowrap float-end'>".$EOY_units." UOS</span>
                                                        </button>
                                                    </form>
                                                </div>";
                                            } else {
                                                $caseloads_display .= "<div class='my-1'>
                                                    <span class='text-nowrap float-start'>$caseload_therapist_name ($caseload_category_name)</span>
                                                    <span class='text-nowrap float-end'>".$EOY_units." UOS</span>
                                                </div>";
                                            }
                                        }
                                    }
                                }
                            }

                            // build the actions column
                            $actions = "<div class='d-flex justify-content-end'>";
                                if ($can_user_edit === true) { $actions .= "<button class='btn btn-primary btn-sm mx-1' type='button' onclick='getEditStudentModal(".$id.");'><i class='fa-solid fa-pencil'></i></button>"; }
                                if ($can_user_delete === true) { $actions .= "<button class='btn btn-danger btn-sm mx-1' type='button' onclick='getDeleteStudentModal(".$id.");'><i class='fa-solid fa-trash-can'></i></button>"; }
                            $actions .= "</div>";

                            // build the status column to be filtered by
                            $filter_status = "";
                            if ($status == 1) { $filter_status = "Active"; }
                            else { $filter_status = "Inactive"; }

                            // build the temporary array of data
                            $temp = [];
                            $temp["id"] = $id_div;
                            $temp["fname"] = $fname;
                            $temp["lname"] = $lname;
                            $temp["date_of_birth"] = $date_of_birth;
                            $temp["age"] = $age;
                            $temp["active_caseloads"] = $caseloads_display;
                            $temp["total_units"] = $total_units;
                            $temp["status"] = $filter_status;
                            $temp["actions"] = $actions;

                            // add the temporary array to the master list
                            $students[] = $temp;
                        }
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);

        // return data
        $fullData = [];
        $fullData["draw"] = 1;
        $fullData["data"] = $students;
        echo json_encode($fullData);
    }
?>