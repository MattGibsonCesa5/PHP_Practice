<?php
    include("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && ($_SESSION["role"] == 1 || $_SESSION["role"] == 2))
        {
            // include additonal files
            include("getSettings.php");

            ?>
                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8"><h1 class="upload-status-header">Project Employees Upload Status</h1></div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8 upload-status-report">
                    <?php
                        if (isset($_POST["ProjExp-BulkUp-period_id"]))
                        {
                            // connect to the database
                            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                            // get the period ID from POST
                            $period_id = $_POST["ProjEmp-BulkUp-period_id"];

                            if (verifyPeriod($conn, $period_id)) // verify the period selected exists
                            {
                                if (isset($_FILES["files"]))
                                {
                                    // initialize variables
                                    $updated = $inserted = $errors = $files_counter = 0;

                                    // connect to the database
                                    $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                                    // go through the folder and transfer student folders to server
                                    foreach ($_FILES["files"]["name"] as $i => $name)
                                    {
                                        // verify the file type is valid
                                        if ($_FILES["files"]["type"][$i] == "text/csv")
                                        {
                                            if (strlen($_FILES["files"]["name"][$i]) > 1)
                                            {
                                                // get and open the file for reading
                                                $file = $_FILES['files']['tmp_name'][$i];
                                                $handle = fopen($file, "r");

                                                // increment the files counter
                                                $files_counter++;

                                                echo "#===============# PROCESSING FILE ".$_FILES["files"]["name"][$i]." #===============#<br>";

                                                while ($data = fgetcsv($handle, 1000, ",", '"'))
                                                {
                                                    if (isset($data[0]) && $data[0] != "Employee ID") // skip the first row by looking at cell data
                                                    {
                                                        if (isset($data[0]) && $data[0] <> "") { $employee = clean_data($data[0]); } else { $employee = null; } 
                                                        if (isset($data[1]) && $data[1] <> "") { $project = clean_data($data[1]); } else { $project = null; } 
                                                        if (isset($data[2]) && $data[2] <> "") { $days = clean_data($data[2]); } else { $days = null; } 
                                                        if (isset($data[3]) && $data[3] <> "") { $obj = clean_data($data[3]); } else { $obj = null; } 
                                                        if (isset($data[4]) && $data[4] <> "") { $func = clean_data($data[4]); } else { $func = null; } 

                                                        if ($employee != null && $project != null && $days != null && $obj != null && $func != null)
                                                        {
                                                            if (is_numeric($days))
                                                            {
                                                                if (verifyUserProject($conn, $_SESSION["id"], $project)) // user has been verified to make changes to this project
                                                                {
                                                                    // check to see if the employee exists
                                                                    $checkEmployee = mysqli_prepare($conn, "SELECT id, fname, lname FROM employees WHERE id=?");
                                                                    mysqli_stmt_bind_param($checkEmployee, "i", $employee);
                                                                    if (mysqli_stmt_execute($checkEmployee))
                                                                    {
                                                                        $checkEmployeeResult = mysqli_stmt_get_result($checkEmployee);
                                                                        if (mysqli_num_rows($checkEmployeeResult) > 0) // employee exists; continue
                                                                        {
                                                                            // get and store employee name
                                                                            $employee_details = mysqli_fetch_array($checkEmployeeResult);
                                                                            $fname = $employee_details["fname"];
                                                                            $lname = $employee_details["lname"];
                                                                            
                                                                            // check to see if the project exists
                                                                            $checkProject = mysqli_prepare($conn, "SELECT code FROM projects WHERE code=?");
                                                                            mysqli_stmt_bind_param($checkProject, "s", $project);
                                                                            if (mysqli_stmt_execute($checkProject))
                                                                            {
                                                                                $checkProjectResult = mysqli_stmt_get_result($checkProject);
                                                                                if (mysqli_num_rows($checkProjectResult) > 0) // project exists; continue
                                                                                {
                                                                                    if (verifyUserEmployee($conn, $_SESSION["id"], $employee))
                                                                                    {   
                                                                                        // check to see if the employee is already in the project
                                                                                        $checkBudgeted = mysqli_prepare($conn, "SELECT id FROM project_employees WHERE employee_id=? AND project_code=?");
                                                                                        mysqli_stmt_bind_param($checkBudgeted, "is", $employee, $project);
                                                                                        if (mysqli_stmt_execute($checkBudgeted))
                                                                                        {
                                                                                            $checkBudgetedResult = mysqli_stmt_get_result($checkBudgeted);
                                                                                            if (mysqli_num_rows($checkBudgetedResult) > 0) // employee is already assigned to the project; update current entry
                                                                                            {
                                                                                                $updateBudget = mysqli_prepare($conn, "UPDATE project_employees SET project_days=?, object_code=?, function_code=? WHERE employee_id=? AND project_code=? AND period_id=?");
                                                                                                mysqli_stmt_bind_param($updateBudget, "issisi", $days, $obj, $func, $employee, $project, $period_id);
                                                                                                if (mysqli_stmt_execute($updateBudget)) // successfully updated entry
                                                                                                { 
                                                                                                    $updated++;
                                                                                                    echo "<span class=\"log-success\">Successfully</span> updated $fname $lname (ID: $employee) in the Project $project budget.<br>"; 
                
                                                                                                    // edit the project last updated time
                                                                                                    updateProjectEditTimestamp($conn, $project);
                
                                                                                                    // update the autocalculated expenses
                                                                                                    recalculateAutomatedExpenses($conn, $project, $period_id);
                                                                                                }
                                                                                                else // failed to update entry 
                                                                                                { 
                                                                                                    $errors++;
                                                                                                    echo "<span class=\"log-fail\">Failed</span> to update $fname $lname (ID: $employee) in the Project $project budget.<br>"; 
                                                                                                }
                                                                                            }
                                                                                            else // employee is not assigned to the project; insert new entry
                                                                                            {
                                                                                                $insertBudget = mysqli_prepare($conn, "INSERT INTO project_employees (project_code, employee_id, project_days, object_code, function_code, period_id) VALUES (?, ?, ?, ?, ?, ?)");
                                                                                                mysqli_stmt_bind_param($insertBudget, "siissi", $project, $employee, $days, $obj, $func, $period_id);
                                                                                                if (mysqli_stmt_execute($insertBudget)) // successfully inserted new entry 
                                                                                                {
                                                                                                    $inserted++;
                                                                                                    echo "<span class=\"log-success\">Successfully</span> added $fname $lname (ID: $employee) to the Project $project budget.<br>"; 
                
                                                                                                    // edit the project last updated time
                                                                                                    updateProjectEditTimestamp($conn, $project);
                
                                                                                                    // update the autocalculated expenses
                                                                                                    recalculateAutomatedExpenses($conn, $project, $period_id);
                                                                                                }
                                                                                                else // failed to insert new entry 
                                                                                                { 
                                                                                                    $errors++;
                                                                                                    echo "<span class=\"log-fail\">Failed</span> to add $fname $lname (ID: $employee) to the Project $project budget.<br>"; 
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                        else // failed to check the budget
                                                                                        { 
                                                                                            $errors++;
                                                                                            echo "<span class=\"log-fail\">Failed</span> to add/update $fname $lname (ID: $employee) in the Project $project budget. An unexpected error has occurred!<br>"; 
                                                                                        }    
                                                                                    }
                                                                                    else
                                                                                    {
                                                                                        $errors++;
                                                                                        echo "<span class=\"log-fail\">Failed</span> to add/update $fname $lname (ID: $employee) in the Project $project budget. The user does not have access to add this employee to the project.<br>";
                                                                                    }
                                                                                }
                                                                                else // project does not exist 
                                                                                { 
                                                                                    $errors++;
                                                                                    echo "<span class=\"log-fail\">Failed</span> to add/update $fname $lname (ID: $employee) in the Project $project budget. The project does not exist!<br>"; 
                                                                                }
                                                                            }
                                                                            else // failed to verify the project
                                                                            { 
                                                                                $errors++;
                                                                                echo "<span class=\"log-fail\">Failed</span> to add/update $fname $lname (ID: $employee) in the Project $project budget. An unexpected error has occurred!<br>"; 
                                                                            } 
                                                                        }
                                                                        else 
                                                                        { 
                                                                            $errors++;
                                                                            echo "<span class=\"log-fail\">Failed</span> to add the employee with ID $employee to the Project $project budget. The employee does not exist!<br>"; 
                                                                        }
                                                                    }
                                                                    else 
                                                                    { 
                                                                        $errors++;
                                                                        echo "<span class=\"log-fail\">Failed</span> to add/update the employee with ID $employee to the Project $project budget. An unexpected error has occurred!<br>"; 
                                                                    }
                                                                }
                                                                else
                                                                {
                                                                    $errors;
                                                                    echo "<span class=\"log-fail\">Failed</span> to add/update the employee with ID $employee to the Project $project budget. The user does not have access to make changes to this project.<br>";
                                                                }
                                                            }
                                                            else 
                                                            { 
                                                                $errors++;
                                                                echo "<span class=\"log-fail\">Failed</span> to add/update the employee with ID $employee to the Project $project budget. The days in project must be a valid number!<br>"; 
                                                            }
                                                        }
                                                        else 
                                                        { 
                                                            $errors++;
                                                            echo "You must provide an employee ID, the project code, and days in the project."; 
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        else { echo "<i class='fa-solid fa-triangle-exclamation'></i> ERROR! The file ".$_FILES["files"]["name"][$i]." is not a csv. Skipping file.<br>"; }
                                    }                                    

                                    echo "<i class=\"fa-solid fa-check\"></i> Upload complete!";

                                    // log upload
                                    $total_successes = $inserted + $updated;
                                    $message = "Successfully bulk uploaded $total_successes project employees ($files_counter files). ";
                                    if ($errors > 0) { $message .= "Failed to upload $errors project employees. "; }
                                    $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                    mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                    mysqli_stmt_execute($log);
                                }   
                                else { echo "<i class='fa-solid fa-triangle-exclamation'></i> ERROR! No upload folder was found. Please select a folder of files to upload and try again. "; }
                            }
                            else { echo "<i class='fa-solid fa-triangle-exclamation'></i> ERROR! The period selected was invalid. Please try again. "; }
                        
                            // disconnect from the database
                            mysqli_close($conn);
                        }
                        else { echo "<i class='fa-solid fa-triangle-exclamation'></i> ERROR! No period was selected. Please select a period and try again. "; }
                    ?>
                    </div>
                    <div class="col-2"></div>
                </div>

                <div class="row justify-content-center text-center my-3">
                    <div class="col-2"><button class="btn btn-primary w-100" onclick="goToBudgetProjects();">Return To Budget Projects</button></div>
                </div>

                <script>function goToBudgetProjects() { window.location.href = "projects_budget.php"; }</script>
            <?php
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
?>