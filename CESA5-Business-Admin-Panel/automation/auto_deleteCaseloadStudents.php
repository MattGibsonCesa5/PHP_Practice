<?php
    include("../includes/config.php");

    // connect to the database
    $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

    // initialize variable to count number of delete students
    $deleted = 0;

    // get a list of all students older than 27 years old
    $getStudents = mysqli_query($conn, "SELECT id FROM caseload_students WHERE date_of_birth < NOW() - INTERVAL 27 YEAR");
    if (mysqli_num_rows($getStudents) > 0)
    {
        // for each student, delete all of their associated data
        while ($student = mysqli_fetch_array($getStudents))
        {
            // store student ID locally
            $student_id = $student["id"];

            $getStudentCaseloads = mysqli_prepare($conn, "SELECT id FROM cases WHERE student_id=?");
            mysqli_stmt_bind_param($getStudentCaseloads, "i", $student_id);
            if (mysqli_stmt_execute($getStudentCaseloads))
            {
                $getStudentCaseloadsResults = mysqli_stmt_get_result($getStudentCaseloads);
                if (mysqli_num_rows($getStudentCaseloadsResults) > 0)
                {
                    while ($caseload = mysqli_fetch_array($getStudentCaseloadsResults))
                    {
                        // store caseload ID locally
                        $case_id = $caseload["id"];

                        // delete all cases for this student
                        $deleteFromCaseloads = mysqli_prepare($conn, "DELETE FROM cases WHERE id=?");
                        mysqli_stmt_bind_param($deleteFromCaseloads, "i", $case_id);
                        if (!mysqli_stmt_execute($deleteFromCaseloads))
                        {
                            // TODO - alert failed deletion
                        }

                        // delete caseload changes
                        $deleteFromCaseloadChanges = mysqli_prepare($conn, "DELETE FROM case_changes WHERE case_id=?");
                        mysqli_stmt_bind_param($deleteFromCaseloadChanges, "i", $case_id);
                        if (!mysqli_stmt_execute($deleteFromCaseloadChanges))
                        {
                            // TODO - alert failed deletion
                        }
                    }
                }
            }

            // delete student
            $deleteStudent = mysqli_prepare($conn, "DELETE FROM caseload_students WHERE id=?");
            mysqli_stmt_bind_param($deleteStudent, "i", $student_id);
            if (!mysqli_stmt_execute($deleteStudent))
            {
                // TODO - alert failed deletion
            }
            else { $deleted++; }
        }
    }

    // disconnect from the database
    mysqli_close($conn);
?>