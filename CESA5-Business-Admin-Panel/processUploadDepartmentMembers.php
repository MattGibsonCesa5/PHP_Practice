<?php
    include("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["ADD_DEPARTMENTS"]))
        {
            ?>
                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8"><h1 class="upload-status-header">Department Members Upload Status</h1></div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8 upload-status-report">
                    <?php
                        if (isset($_FILES["fileToUpload"])) 
                        {
                            // get and open the file
                            $file = $_FILES['fileToUpload']['tmp_name'];
                            $file_type = $_FILES["fileToUpload"]["type"];

                            // verify the file is set and it is a .csv file
                            if (isset($file) && (isset($file_type) && $file_type == "text/csv"))
                            {                   
                                // initialize variables 
                                $updated = $inserted = $errors = 0;
                                
                                // connect to the database
                                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                                // open the file
                                $handle = fopen($file, "r");

                                while ($data = fgetcsv($handle, 1000, ",", '"'))
                                {
                                    if (isset($data[0]) && $data[0] != "Department Name") // skip the first row by looking at cell data
                                    {
                                        // get and clean up the upload's data
                                        if (isset($data[0]) && $data[0] <> "") { $dept_name = clean_data($data[0]); } else { $dept_name = null; } // department name
                                        if (isset($data[1]) && $data[1] <> "") { $emp_id = clean_data($data[1]); } else { $emp_id = null; } // employee id
                                        if (isset($data[2])) { $isPrimary = clean_data($data[2]); } else { $isPrimary = null; } // primary department

                                        // build the database var for primary department
                                        $DB_isPrimary = 0; // assume dept is not primary
                                        if ($isPrimary == "Yes") { $DB_isPrimary = 1; }
                                        else if ($isPrimary == "No") { $DB_isPrimary = 0; }
                                        
                                        if ($dept_name != null && $emp_id != null)
                                        {
                                            // check to see if the department exists
                                            $checkDept = mysqli_prepare($conn, "SELECT id FROM departments WHERE name=?");
                                            mysqli_stmt_bind_param($checkDept, "s", $dept_name);
                                            if (mysqli_stmt_execute($checkDept))
                                            {
                                                $checkDeptResult = mysqli_stmt_get_result($checkDept);
                                                if (mysqli_num_rows($checkDeptResult) > 0) // department exists; continue
                                                {
                                                    // store the department ID locally
                                                    $dept_id = mysqli_fetch_array($checkDeptResult)["id"];

                                                    // check to see if the employee exists
                                                    $checkEmp = mysqli_prepare($conn, "SELECT id, fname, lname, status FROM employees WHERE id=?");
                                                    mysqli_stmt_bind_param($checkEmp, "i", $emp_id);
                                                    if (mysqli_stmt_execute($checkEmp))
                                                    {
                                                        $checkEmpResult = mysqli_stmt_get_result($checkEmp);
                                                        if (mysqli_num_rows($checkEmpResult) > 0) // employee exists; continue
                                                        {
                                                            // store the employee's data
                                                            $emp_data = mysqli_fetch_array($checkEmpResult);
                                                            $fname = $emp_data["fname"];
                                                            $lname = $emp_data["lname"];
                                                            $emp_status = $emp_data["status"];

                                                            // verify that the employee is active
                                                            if ($emp_status == 1)
                                                            {
                                                                // check to see if the employee is already in the department
                                                                $checkDeptStatus = mysqli_prepare($conn, "SELECT id, is_primary FROM department_members WHERE department_id=? AND employee_id=?");
                                                                mysqli_stmt_bind_param($checkDeptStatus, "ii", $dept_id, $emp_id);
                                                                if (mysqli_stmt_execute($checkDeptStatus))
                                                                {
                                                                    $checkDeptStatusResult = mysqli_stmt_get_result($checkDeptStatus);
                                                                    if (mysqli_num_rows($checkDeptStatusResult) == 0) // employee is not in department; add employee to department
                                                                    {
                                                                        $addEmp = mysqli_prepare($conn, "INSERT INTO department_members (department_id, employee_id, is_primary) VALUES (?, ?, ?)");
                                                                        mysqli_stmt_bind_param($addEmp, "iii", $dept_id, $emp_id, $DB_isPrimary);
                                                                        if (mysqli_stmt_execute($addEmp)) // successfully added the employee to the department
                                                                        {
                                                                            // log status
                                                                            echo "<span class=\"log-success\">Successfully</span> added $fname $lname to $dept_name.<br>";

                                                                            // if setting this as primary department, remove current primary department if set
                                                                            if ($DB_isPrimary == 1)
                                                                            {
                                                                                // log primary dept status
                                                                                echo "Set $dept_name as $fname $lname's primary department.<br>";

                                                                                // remove other primary dept(s) if set
                                                                                $removePrimary = mysqli_prepare($conn, "UPDATE department_members SET is_primary=0 WHERE employee_id=? AND department_id!=?");
                                                                                mysqli_stmt_bind_param($removePrimary, "ii", $emp_id, $dept_id);
                                                                                if (!mysqli_stmt_execute($removePrimary)) { echo "<span class=\"log-fail\">Failed</span> to remove $fname $lname's existing primary department.<br>"; }
                                                                            }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to add $fname $lname to $dept_name.<br>"; } 
                                                                    }
                                                                    else // employee is already in department; update existing entry
                                                                    {
                                                                        // store current entry ID
                                                                        $entry_id = mysqli_fetch_array($checkDeptStatusResult)["id"];

                                                                        // update existing entry
                                                                        $updateEmp = mysqli_prepare($conn, "UPDATE department_members SET is_primary=? WHERE id=?");
                                                                        mysqli_stmt_bind_param($updateEmp, "ii", $DB_isPrimary, $entry_id);
                                                                        if (mysqli_stmt_execute($updateEmp)) // successfully updated the department members entry
                                                                        {
                                                                            // if setting this as primary department, remove current primary department if set
                                                                            if ($DB_isPrimary == 1)
                                                                            {
                                                                                // log primary dept status
                                                                                echo "Set $dept_name as $fname $lname's primary department.<br>";

                                                                                // remove other primary dept(s) if set
                                                                                $removePrimary = mysqli_prepare($conn, "UPDATE department_members SET is_primary=0 WHERE id!=? AND employee_id=?");
                                                                                mysqli_stmt_bind_param($removePrimary, "ii", $entry_id, $emp_id);
                                                                                if (!mysqli_stmt_execute($removePrimary)) { echo "<span class=\"log-fail\">Failed</span> to remove $fname $lname's existing primary department.<br>"; }
                                                                            }
                                                                        }
                                                                        else { echo "<span class=\"log-fail\">Failed</span> to update $fname $lname department status for $dept_name.<br>"; } 
                                                                    }
                                                                }
                                                                
                                                            }
                                                            else { echo "<span class=\"log-fail\">Failed</span> to add $fname $lname to $dept_name. Employee is inactive.<br>"; } 
                                                        }
                                                        else { echo "<span class=\"log-fail\">Failed</span> to add employee with ID $emp_id to $dept_name. The employee does not exist!<br>"; } 
                                                    }
                                                    else { echo "<span class=\"log-fail\">Failed</span> to add employee with ID $emp_id to $dept_name. An unexpected error has occurred.<br>"; }
                                                }
                                                else { echo "<span class=\"log-fail\">Failed</span> to add employee with ID $emp_id to $dept_name. The department does not exist!<br>"; }
                                            }
                                            else { echo "<span class=\"log-fail\">Failed</span> to add employee with ID $emp_id to $dept_name. An unexpected error has occurred.<br>"; }
                                        }
                                    }
                                }

                                echo "<i class=\"fa-solid fa-check\"></i> Upload complete!";

                                // log upload
                                $message = "Successfully uploaded department members.";
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // disconnect from the database
                                mysqli_close($conn);
                            }
                            else { echo "ERROR! You must select a .csv file to upload.<br>"; }
                        }   
                        else { echo "ERROR! No upload file was found. Please select a file to upload and try again.<br>"; }
                    ?>
                    </div>
                    <div class="col-2"></div>
                </div>

                <div class="row justify-content-center text-center mt-3">
                    <div class="col-2"><button class="btn btn-primary w-100" onclick="goToDepartments();">Return To Departments</button></div>
                </div>

                <script>function goToDepartments() { window.location.href = "departments.php"; }</script>
            <?php
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
?>