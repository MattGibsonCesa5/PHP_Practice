<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // initialize grade to 0 (Kindergarten)
        $grade = 0;

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        // verify the user has permission
        if (checkUserPermission($conn, "ADD_CASELOADS"))
        {
            // get the parameters from POST
            if (isset($_POST["student_id"]) && $_POST["student_id"] <> "") { $student_id = $_POST["student_id"]; } else { $student_id = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            // verify the student exists
            if ($student_id != null && verifyStudent($conn, $student_id))
            {
                // get and verify the period
                if ($period != null && $period_id = getPeriodID($conn, $period))
                {
                    // see if the student is in any caseload this period; if so, assume that grade level
                    $checkCases = mysqli_prepare($conn, "SELECT grade_level FROM cases WHERE student_id=? AND period_id=? LIMIT 1");
                    mysqli_stmt_bind_param($checkCases, "ii", $student_id, $period_id);
                    if (mysqli_stmt_execute($checkCases))
                    {
                        // get the query result
                        $checkCasesResult = mysqli_stmt_get_result($checkCases);

                        // student is in a caseload this period; get current grade level
                        if (mysqli_num_rows($checkCasesResult) > 0)
                        {
                            // get the grade level from the case
                            $grade = mysqli_fetch_array($checkCasesResult)["grade_level"];
                        }
                        // student is not in a caseload this period; attempt to find student in the prior period
                        else
                        {
                            $checkPriorCases = mysqli_prepare($conn, "SELECT grade_level FROM cases WHERE student_id=? AND period_id!=? ORDER BY period_id DESC LIMIT 1");
                            mysqli_stmt_bind_param($checkPriorCases, "ii", $student_id, $period_id);
                            if (mysqli_stmt_execute($checkPriorCases))
                            {
                                // get the query result
                                $checkPriorCasesResult = mysqli_stmt_get_result($checkPriorCases);
                                
                                // student was in a prior case, add grade level to 1
                                if (mysqli_num_rows($checkPriorCasesResult) > 0)
                                {
                                    // get the grade level from the case
                                    $prior_grade = mysqli_fetch_array($checkCasesResult)["grade_level"];

                                    // add a year to the most recently found grade
                                    $grade = $prior_grade + 1;
                                }
                                // student was not in a prior case, attempt to estimate grade level based on date of birth
                                else
                                {
                                    // get the student's date of birth
                                    $getDOB = mysqli_prepare($conn, "SELECT date_of_birth FROM caseload_students WHERE id=?");
                                    mysqli_stmt_bind_param($getDOB, "i", $student_id);
                                    if (mysqli_stmt_execute($getDOB))
                                    {
                                        $getDOBResult = mysqli_stmt_get_result($getDOB);
                                        if (mysqli_num_rows($getDOBResult) > 0)
                                        {
                                            // store the date of birth
                                            $dob = mysqli_fetch_array($getDOBResult);

                                            // get the student's age
                                            $age = date("Y", time() - strtotime($dob)) - 1970;

                                            // estimate the grade level based on age - 5 (5 year old = Kindergarten (5 - 5 = 0))
                                            $grade = $age - 5;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // return the grade level
        echo $grade;

        // disconnect from the database
        mysqli_close($conn);
    }
?>