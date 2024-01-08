<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // initialize grade to 0 (Kindergarten)
        $residency = $district_attending = $school_attending = null;

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
                    $checkCases = mysqli_prepare($conn, "SELECT residency, district_attending, school_attending FROM cases WHERE student_id=? AND period_id=? LIMIT 1");
                    mysqli_stmt_bind_param($checkCases, "ii", $student_id, $period_id);
                    if (mysqli_stmt_execute($checkCases))
                    {
                        // get the query result
                        $checkCasesResult = mysqli_stmt_get_result($checkCases);

                        // student is in a caseload this period; get current locations
                        if (mysqli_num_rows($checkCasesResult) > 0)
                        {
                            // store locations
                            $locations = mysqli_fetch_assoc($checkCasesResult);
                            $residency = $locations["residency"];
                            $district_attending = $locations["district_attending"];
                            $school_attending = $locations["school_attending"];
                        }
                        // student is not in a caseload this period; check prior periods if possible
                        else
                        {
                            $checkPriorCases = mysqli_prepare($conn, "SELECT residency, district_attending, school_attending FROM cases WHERE student_id=? AND period_id!=? ORDER BY period_id DESC LIMIT 1");
                            mysqli_stmt_bind_param($checkPriorCases, "ii", $student_id, $period_id);
                            if (mysqli_stmt_execute($checkPriorCases))
                            {
                                // get the query result
                                $checkPriorCasesResult = mysqli_stmt_get_result($checkPriorCases);
                                if (mysqli_num_rows($checkPriorCasesResult) > 0)
                                {
                                    // store locations
                                    $locations = mysqli_fetch_assoc($checkPriorCasesResult);
                                    $residency = $locations["residency"];
                                    $district_attending = $locations["district_attending"];
                                    $school_attending = $locations["school_attending"];
                                }
                            }
                        }
                    }
                }
            }
        }
        
        // build and return locations array
        $locations = [];
        $locations["residency"] = $residency;
        $locations["district_attending"] = $district_attending;
        $locations["school_attending"] = $school_attending;
        echo json_encode($locations);

        // disconnect from the database
        mysqli_close($conn);
    }
?>