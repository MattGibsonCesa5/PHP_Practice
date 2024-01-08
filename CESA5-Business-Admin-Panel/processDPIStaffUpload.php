<?php
    include("header.php");

    // override server settings
    ini_set("max_execution_time", 900); // cap to 15 minutes
    ini_set("memory_limit", "1024M"); // cap to 1024 MB (1 GB)

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            ?>
                <div class='row justify-content-center text-center'>
                    <div class='col-8'><h1 class='upload-status-header'>Upload Public DPI Employee Status</h1></div>
                </div>

                <div class='row justify-content-center text-center'>
                    <div class='col-8 upload-status-report'>
                    <?php
                        if (isset($_FILES["fileToUpload"])) 
                        {
                            // get and open the file
                            $file = $_FILES['fileToUpload']['tmp_name'];
                            $file_type = $_FILES["fileToUpload"]["type"];

                            // verify the file is set and it is a .csv file
                            if (isset($file) && (isset($file_type) && $file_type == "text/csv"))
                            {
                                // start the timer
                                $start_time = microtime(true);
                                $line1000_start_time = microtime(true);

                                // initialze the line counter
                                $line = $rows1000_counter = 0;

                                // get number of lines in file
                                $fp = file($file);
                                $rows = count($fp);
                                $rows1000 = floor($rows / 1000);

                                // add the progress bar
                                include("ajax/misc/uploadProcessingSpinner.php");
                                ob_flush(); 
                                flush();
                                
                                // initialize variables
                                $successes = $errors = 0;

                                // connect to the database
                                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                                // open the file for reading
                                $handle = fopen($file, "r");

                                while ($data = fgetcsv($handle, 1000, ",", '"'))
                                {
                                    // if we are in the first row (data[0] = Research Id), truncate the table
                                    if (isset($data[0]) && $data[0] == "Research Id")
                                    {
                                        $truncateDPIEmplyoees = mysqli_query($conn, "TRUNCATE dpi_employees");
                                    }

                                    if (isset($data[0]) && $data[0] != "Research Id") // skip the first two rows by looking at cell data
                                    {
                                        // get and clean up the employee's data
                                        if (isset($data[0])) { $research_id = clean_data($data[0]); } else { $research_id = null; }
                                        if (isset($data[1])) { $year = clean_data($data[1]); } else { $year = null; }
                                        if (isset($data[2])) { $lname = clean_data($data[2]); } else { $lname = null; }
                                        if (isset($data[3])) { $fname = clean_data($data[3]); } else { $fname = null; }
                                        if (isset($data[4])) { $entity_id = clean_data($data[4]); } else { $entity_id = null; }
                                        if (isset($data[5])) { $gender = clean_data($data[5]); } else { $gender = null; }
                                        if (isset($data[6])) { $race = clean_data($data[6]); } else { $race = null; }
                                        if (isset($data[7])) { $contract_hire_agency = clean_data($data[7]); } else { $contract_hire_agency = null; }
                                        if (isset($data[8])) { $contract_high_degree = clean_data($data[8]); } else { $contract_high_degree = null; }
                                        if (isset($data[9])) { $contract_days = clean_data($data[9]); } else { $contract_days = null; }
                                        if (isset($data[10])) { $local_exp = clean_data($data[10]); } else { $local_exp = null; }
                                        if (isset($data[11])) { $total_exp = clean_data($data[11]); } else { $total_exp = null; }
                                        if (isset($data[12])) { $salary = clean_data($data[12]); } else { $salary = null; }
                                        if (isset($data[13])) { $fringe = clean_data($data[13]); } else { $fringe = null; }
                                        if (isset($data[14])) { $hire_agency = clean_data($data[14]); } else { $hire_agency = null; }
                                        if (isset($data[15])) { $hire_type = clean_data($data[15]); } else { $hire_type = null; }
                                        if (isset($data[16])) { $work_agency = clean_data($data[16]); } else { $work_agency = null; }
                                        if (isset($data[17])) { $work_type = clean_data($data[17]); } else { $work_type = null; }
                                        if (isset($data[18])) { $work_location = clean_data($data[18]); } else { $work_location = null; }
                                        if (isset($data[19])) { $work_cesa = clean_data($data[19]); } else { $work_cesa = null; }
                                        if (isset($data[20])) { $work_county = clean_data($data[20]); } else { $work_county = null; }
                                        if (isset($data[21])) { $work_level = clean_data($data[21]); } else { $work_level = null; }
                                        if (isset($data[22])) { $position = clean_data($data[22]); } else { $position = null; }
                                        if (isset($data[23])) { $area = clean_data($data[23]); } else { $area = null; }
                                        if (isset($data[24])) { $cat = clean_data($data[24]); } else { $cat = null; }
                                        if (isset($data[25])) { $class = clean_data($data[25]); } else { $class = null; }

                                        if ($gender == "F") { $gender = 2; } else if ($gender == "M") { $gender = 1; }

                                        $addEmp = mysqli_prepare($conn, "INSERT INTO dpi_employees (year, lname, fname, research_id, entity_id, gender, race, contract_hire_agency, contract_high_degree, contract_days, local_experience, total_experience, total_salary, total_fringe, hire_agency, hire_agency_type, work_agency, work_type, work_location, work_cesa, work_county, work_level, assignment_position, assignment_area, assignment_staff_type, position_classification) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                        mysqli_stmt_bind_param($addEmp, "issiiisssiddddsssssissssss", $year, $lname, $fname, $research_id, $entity_id, $gender, $race, $contract_hire_agency, $contract_high_degree, $contract_days, $local_exp, $total_exp, $salary, $fringe, $hire_agency, $hire_type, $work_agency, $work_type, $work_location, $work_cesa, $work_county, $work_level, $position, $area, $cat, $class);
                                        if (mysqli_stmt_execute($addEmp)) { $successes++; } else { $errors++; }

                                        // every 1000 lines, calculate estimated time remaining
                                        if (($line % 1000) == 0)
                                        {
                                            // end the timer to get the time it took for the last 1000 rows
                                            $line1000_end_time = microtime(true);

                                            // calculate stimated time remaining based on how many sets of 1000 rows we have left, based on the time it took for the prior 1000 rows in seconds
                                            $rows1000_remaining = $rows1000 - $rows1000_counter;
                                            $line1000_exec_time = $line1000_end_time - $line1000_start_time;
                                            $estimated_time_remaining_seconds = $line1000_exec_time * $rows1000_remaining;

                                            // convert the estimated time remaining to minutes and seconds 
                                            $estimated_time_in_minutes = $estimated_time_remaining_seconds / 60;
                                            $estimated_minutes_remaining = date("i", floor($estimated_time_remaining_seconds));
                                            $estimated_seconds_remainder = round(($estimated_time_in_minutes - $estimated_minutes_remaining) * 60);
                                            $estimated_seconds_remaining = date("s", $estimated_seconds_remainder);

                                            // print the estimated time remanining
                                            $estimated_time_remaining_str = floor($estimated_minutes_remaining)." minutes and ".round($estimated_seconds_remaining)." seconds";
                                            ?> <script>$("#estimated_time_remaining").html("Estimated time remaining: <?php echo $estimated_time_remaining_str; ?>");</script> <?php
                                            ob_flush();
                                            flush();

                                            // start the timer for the next 1000 rows
                                            $line1000_start_time = microtime(true);

                                            // increment the 1000th row we've imported
                                            $rows1000_counter++;
                                        }

                                        // increment the line we've imported
                                        $line++;
                                    }
                                }

                                // disconnect from the database
                                mysqli_close($conn);

                                // end the timer
                                $end_time = microtime(true);
                                $execution_time = $end_time - $start_time; // in seconds

                                // calculate the time completed in minutes and seconds
                                $time_in_minutes = $execution_time / 60;
                                $execution_minutes = date("i", floor($execution_time));
                                $execution_seconds_remaining = round(($time_in_minutes - $execution_minutes) * 60);
                                $execution_seconds = date("s", $execution_seconds_remaining);

                                echo "<i class=\"fa-solid fa-check\"></i> Upload completed in ".round($execution_minutes)." minutes and ".round($execution_seconds)." seconds.<br>";
                                echo "Successfully uploaded ".number_format($successes)." public DPI employees.<br>";
                                if ($errors > 0) { echo "Failed to upload $errors recorded DPI employees.<br>"; }
                            }
                            else { echo "ERROR! You must select a .csv file to upload."; }
                        }   
                        else { echo "ERROR! No upload file was found. Please select a file to upload and try again. "; }
                    ?>
                    </div>
                </div>

                <div class="row justify-content-center mt-3">
                    <div class="col-2"><button class="btn btn-primary w-100" onclick="goToSalaryComparison();">Return To Salary Comparison</button></div>
                </div>

                <script>
                    function goToSalaryComparison() { window.location.href = "salary_comparison.php"; }
                    $("#upload-processing-spinner").html("");
                    $("#estimated_time_remaining").html("");
                </script>
            <?php
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
?>