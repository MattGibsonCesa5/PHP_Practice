<?php
    include("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_PROJECTS_ALL"]))
        {
            ?>
                <div class="row text-center">
                    <div class="col-2"></div>
                    <div class="col-8"><h1 class="upload-status-header">Projects Upload Status</h1></div>
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

                                // open the file for reading
                                $handle = fopen($file, "r");

                                while ($data = fgetcsv($handle, 1000, ",", '"'))
                                {
                                    if (isset($data[0]) && ($data[0] != "Project Information" && $data[0] != "Project Code")) // skip the first two rows by looking at cell data
                                    {
                                        if (isset($data[0]) && $data[0] <> "") { $code = clean_data($data[0]); } else { $code = null; } // project code
                                        if (isset($data[1]) && $data[1] <> "") { $name = clean_data($data[1]); } else { $name = null; } // project name
                                        if (isset($data[2]) && $data[2] <> "") { $desc = clean_data($data[2]); } else { $desc = null; } // project desc
                                        if (isset($data[3]) && $data[3] <> "") { $dept = clean_data($data[3]); } else { $dept = null; } // project dept
                                        if (isset($data[4]) && $data[4] <> "") { $status = clean_data($data[4]); } else { $status = null; } // project status

                                        // verify and convert data from upload to database values if necessary
                                        if ($status == "Inactive") { $DB_status = 0; } else if ($status == "Active") { $DB_status = 1; } else { $DB_status = 0; }

                                        if ($code != null && $name != null)
                                        {    
                                            // get department ID
                                            $getDepartment = mysqli_prepare($conn, "SELECT id FROM departments WHERE name=?");
                                            mysqli_stmt_bind_param($getDepartment, "s", $dept);
                                            if (mysqli_stmt_execute($getDepartment))
                                            {
                                                $getDepartmentResult = mysqli_stmt_get_result($getDepartment);
                                                if (mysqli_num_rows($getDepartmentResult) > 0) // department found; get ID
                                                {
                                                    $department_id = mysqli_fetch_array($getDepartmentResult)["id"];
                                                }
                                                else { $department_id = null; } // no department found, set ID to null
                                            }

                                            // check to see if the project exists already
                                            $checkProject = mysqli_prepare($conn, "SELECT code FROM projects WHERE code=?");
                                            mysqli_stmt_bind_param($checkProject, "s", $code);
                                            if (mysqli_stmt_execute($checkProject))
                                            {
                                                $checkProjectResult = mysqli_stmt_get_result($checkProject);
                                                if (mysqli_num_rows($checkProjectResult) == 0) // project does not exist; create new project
                                                {
                                                    $createProject = mysqli_prepare($conn, "INSERT INTO projects (code, name, description, department_id, status) VALUES (?, ?, ?, ?, ?)");
                                                    mysqli_stmt_bind_param($createProject, "sssii", $code, $name, $desc, $department_id, $DB_status);
                                                    if (mysqli_stmt_execute($createProject)) // successfully created the new project
                                                    { 
                                                        $inserted++;
                                                        echo "<span class=\"log-success\">Successfully</span> created the project $name ($code).<br>"; 
                                                    }
                                                    else // failed to create the new project
                                                    { 
                                                        $errors++;
                                                        echo "<span class=\"log-fail\">Failed</span> to create the project $name ($code).<br>"; 
                                                    }
                                                }
                                                else // project already exists; update current project details
                                                {
                                                    $updateProject = mysqli_prepare($conn, "UPDATE projects SET name=?, description=?, department_id=?, status=? WHERE code=?");
                                                    mysqli_stmt_bind_param($updateProject, "ssiis", $name, $desc, $department_id, $DB_status, $code);
                                                    if (mysqli_stmt_execute($updateProject)) // successfully updated the project
                                                    { 
                                                        $updated++;
                                                        echo "<span class=\"log-success\">Successfully</span> updated the project $name ($code).<br>"; 
                                                    }
                                                    else // failed to update the project
                                                    { 
                                                        $errors++;
                                                        echo "<span class=\"log-fail\">Failed</span> to update the project $name ($code).<br>"; 
                                                    }
                                                }
                                            }
                                            else { $errors++; } // failed to check if project already exists
                                        }
                                        else // missing project code and project name
                                        {
                                            $errors++;
                                            echo "<span class=\"log-fail\">Failed</span> to upload a project. You must provide both a project code and project name!<br>";
                                        }
                                    }
                                }

                                echo "<i class=\"fa-solid fa-check\"></i> Upload complete!";

                                // log upload
                                $total_successes = $inserted + $updated;
                                $message = "Successfully uploaded $total_successes projects ($inserted inserts; $updated updates). ";
                                if ($errors > 0) { $message .= "Failed to upload $errors projects. "; }
                                $log = mysqli_prepare($conn, "INSERT INTO log (user_id, message) VALUES (?, ?)");
                                mysqli_stmt_bind_param($log, "is", $_SESSION["id"], $message);
                                mysqli_stmt_execute($log);

                                // disconnect from the database
                                mysqli_close($conn);
                            }
                            else { echo "ERROR! You must select a .csv file to upload."; }
                        }   
                        else { echo "ERROR! No upload file was found. Please select a file to upload and try again. "; }
                    ?>
                    </div>
                    <div class="col-2"></div>
                </div>

                <div class="row text-center mt-3">
                    <div class="col-5"></div>
                    <div class="col-2"><button class="btn btn-primary w-100" onclick="goToManageProjects();">Return To Manage Projects</button></div>
                    <div class="col-5"></div>
                </div>

                <script>function goToManageProjects() { window.location.href = "projects_manage.php"; }</script>
            <?php
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
?>