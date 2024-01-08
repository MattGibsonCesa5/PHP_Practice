<?php
    include("header.php");

    include("underConstruction.php");
    /*
    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && ($_SESSION["role"] == 1 || $_SESSION["role"] == 2))
        {
            // include the settings
            include("getSettings.php");

            ?>
                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8"><h1 class="upload-status-header">Project Expenses Upload Status</h1></div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8 upload-status-report">
                    <?php
                        if (isset($_POST["ProjExp-Up-period_id"]))
                        {
                            // connect to the database
                            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                            // get the period ID from POST
                            $period_id = $_POST["ProjExp-BulkUp-period_id"];

                            if (verifyPeriod($conn, $period_id)) // verify the period selected exists
                            {
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

                                        // open the file for reading
                                        $handle = fopen($file, "r");

                                        while ($data = fgetcsv($handle, 1000, ",", '"'))
                                        {
                                            if (isset($data[0]) && ($data[0] != "WUFAR Codes" && $data[0] != "Fund")) // skip the first two rows by looking at cell data
                                            {
                                                if (isset($data[0]) && $data[0] <> "") { $fund = clean_data($data[0]); } else { $fund = null; } 
                                                if (isset($data[1]) && $data[1] <> "") { $loc = clean_data($data[1]); } else { $loc = null; } 
                                                if (isset($data[2]) && $data[2] <> "") { $obj = clean_data($data[2]); } else { $obj = null; } 
                                                if (isset($data[3]) && $data[3] <> "") { $func = clean_data($data[3]); } else { $func = null; } 
                                                if (isset($data[4]) && $data[4] <> "") { $proj = clean_data($data[4]); } else { $proj = null; } 
                                                if (isset($data[5]) && $data[4] <> "") { $desc = clean_data($data[5]); } else { $desc = null; } 
                                                if (isset($data[6]) && $data[4] <> "") { $cost = clean_data($data[6]); } else { $cost = 0; } 

                                                if ($fund != null && $loc != null && $obj != null && $func != null && $proj != null)
                                                {
                                                    // get the expense ID based on the fund, loc, obj, and func codes
                                                    $getExpenseID = mysqli_prepare($conn, "SELECT id, name, status FROM expenses WHERE fund_code=? AND location_code=? AND object_code=? AND function_code=? AND global=0");
                                                    mysqli_stmt_bind_param($getExpenseID, "ssss", $fund, $loc, $obj, $func);
                                                    if (mysqli_stmt_execute($getExpenseID))
                                                    {
                                                        $getExpenseIDResult = mysqli_stmt_get_result($getExpenseID);
                                                        if (mysqli_num_rows($getExpenseIDResult) > 0) // expense found; attempt to insert expense
                                                        {
                                                            // store expense details locally
                                                            $expense = mysqli_fetch_array($getExpenseIDResult);
                                                            $expense_id = $expense["id"];
                                                            $expense_name = $expense["name"];
                                                            $expense_status = $expense["status"];

                                                            if ($expense_status == 1) // status is active; continue
                                                            {
                                                                // verify the project exists
                                                                $checkProject = mysqli_prepare($conn, "SELECT code FROM projects WHERE code=?");
                                                                mysqli_stmt_bind_param($checkProject, "s", $proj);
                                                                if (mysqli_stmt_execute($checkProject))
                                                                {
                                                                    $checkProjectResult = mysqli_stmt_get_result($checkProject);
                                                                    if (mysqli_num_rows($checkProjectResult) > 0) // project exists; continue
                                                                    {
                                                                        // check to see if the amount is greater than $0.00
                                                                        if ($cost > 0)
                                                                        {
                                                                            if (verifyUserProject($conn, $_SESSION["id"], $proj)) // user has been verified to make changes to this project
                                                                            {
                                                                                // add expense to project
                                                                                $addExpense = mysqli_prepare($conn, "INSERT INTO project_expenses (project_code, expense_id, description, cost, auto, period_id) VALUES (?, ?, ?, ?, 0, ?)");
                                                                                mysqli_stmt_bind_param($addExpense, "sisdi", $proj, $expense_id, $desc, $cost, $period_id);
                                                                                if (mysqli_stmt_execute($addExpense)) // successfully added expense
                                                                                { 
                                                                                    $inserted++;
                                                                                    echo "<span class=\"log-success\">Successfully</span> added expense $expense_name to project $proj for $cost.<br>"; 

                                                                                    // edit the project last updated time
                                                                                    updateProjectEditTimestamp($conn, $proj);

                                                                                    // update the autocalculated expenses
                                                                                    recalculateAutomatedExpenses($conn, $proj, $period_id);
                                                                                } 
                                                                                else // failed to add expense
                                                                                { 
                                                                                    $errors++;
                                                                                    echo "<span class=\"log-fail\">Failed</span> to add expense $expense_name to project $proj for $cost.<br>"; 
                                                                                }
                                                                            }
                                                                            else
                                                                            {
                                                                                $errors++;
                                                                                echo "<span class=\"log-fail\">Failed</span> to add expense $expense_name to project $proj. The user does not have access to make changes to the project!<br>";
                                                                            }
                                                                        }
                                                                        else // did not add as expense was $0
                                                                        { 
                                                                            $errors++;
                                                                            echo "<span class=\"log-fail\">Failed</span> to add expense $expense_name to project $proj as the expense amount was $0.00.<br>"; 
                                                                        }
                                                                    }
                                                                    else // project does not exist; skip
                                                                    { 
                                                                        $errors++;
                                                                        echo "<span class=\"log-fail\">Failed</span> to add expense for project $proj: project does not exist!<br>"; 
                                                                    } 
                                                                }
                                                                else { $errors++; }
                                                            }
                                                            else // status is inactive; skip
                                                            { 
                                                                $errors++;
                                                                echo "<span class=\"log-fail\">Failed</span> to add expense. $expense_name is an inactive expense!<br>"; 
                                                            } 
                                                        }
                                                        else // expense does not exist
                                                        {
                                                            $errors++;
                                                            echo "<span class=\"log-fail\">Failed</span> to add expense. The expense did not exist!<br>";
                                                        } 
                                                    }
                                                    else { $errors++; } // unexpected failed query execution
                                                }
                                                else 
                                                { 
                                                    $errors++;
                                                    echo "<span class=\"log-fail\">Failed</span> to add expenses. You must assign a fund, location, object, function, and project code!<br>";
                                                }
                                            }
                                        }

                                        echo "<i class=\"fa-solid fa-check\"></i> Upload complete!";

                                        // log upload
                                        $total_successes = $inserted + $updated;
                                        $message = "Successfully uploaded $total_successes expenses to project(s). ";
                                        if ($errors > 0) { $message .= "Failed to upload $errors expenses to project(s). "; }
                                        $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                        mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                        mysqli_stmt_execute($log);
                                    }
                                    else { echo "<i class='fa-solid fa-triangle-exclamation'></i> ERROR! You must select a .csv file to upload."; }
                                }   
                                else { echo "<i class='fa-solid fa-triangle-exclamation'></i> ERROR! No upload file was found. Please select a file to upload and try again. "; }
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

                <div class="row text-center mt-3">
                    <div class="col-5"></div>
                    <div class="col-2"><button class="btn btn-primary w-100" onclick="goToBudgetProjects();">Return To Budget Projects</button></div>
                    <div class="col-5"></div>
                </div>

                <script>function goToBudgetProjects() { window.location.href = "projects_budget.php"; }</script>
            <?php
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
    */
?>