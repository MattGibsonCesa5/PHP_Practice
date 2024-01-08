<?php
    include("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get additional settings
            include("getSettings.php");

            // store the active period locally
            $period = $GLOBAL_SETTINGS["active_period"];

            ?>
                <div class="row justify-content-center text-center">
                    <div class="col-8"><h1 class="upload-status-header">Upload DPI Position Assignments Status</h1></div>
                </div>

                <div class="row justify-content-center text-center">
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
                                    // if we are in the first row (data[0] = Year), truncate the table
                                    if (isset($data[0]) && $data[0] == "Year")
                                    {
                                        $truncateDPIPositions = mysqli_query($conn, "TRUNCATE dpi_positions");
                                    }

                                    if (isset($data[0]) && $data[0] != "Year" && $data[0] <> "") // skip the first two rows by looking at cell data; also skip last row
                                    {
                                        // get and clean up the employee's data
                                        if (isset($data[1])) { $pos_code = clean_data($data[1]); } else { $pos_code = null; } // first name
                                        if (isset($data[2])) { $pos_name = clean_data($data[2]); } else { $pos_name = null; } // last name
                                        if (isset($data[3])) { $pos_desc = clean_data($data[3]); } else { $pos_desc = null; } // date of birth
                                        if (isset($data[4])) { $area_code = clean_data($data[4]); } else { $area_code = null; } // email
                                        if (isset($data[5])) { $area_name = clean_data($data[5]); } else { $area_name = null; } // phone
                                        if (isset($data[6])) { $area_desc = clean_data($data[6]); } else { $area_desc = null; } // primary department
                                        if (isset($data[8])) { $valid_staff = clean_data($data[8]); } else { $valid_staff = null; } // street
                                        if (isset($data[9])) { $assignment_type = clean_data($data[9]); } else { $assignment_type = null; } // city
                                        if (isset($data[11])) { $pos_type = clean_data($data[11]); } else { $pos_type = null; } // zip
                                        if (isset($data[12])) { $pos_class = clean_data($data[12]); } else { $pos_class = null; } // marital status
                                        if (isset($data[13])) { $license = clean_data($data[13]); } else { $license = null; } // contract days

                                        if ($license == "Y") { $license = 1; } else { $license = 0; }

                                        // create assignment strings
                                        $assignment_position = $pos_code." - ".$pos_name;
                                        $assignment_area = $area_code." - ".$area_name;

                                        $addPos = mysqli_prepare($conn, "INSERT INTO dpi_positions (position_code, position_name, position_description, area_code, area_name, area_description, valid_staff, assignment_type, position_type, position_classification, DPI_license_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                        mysqli_stmt_bind_param($addPos, "ssssssssssi", $pos_code, $pos_name, $pos_desc, $area_code, $area_name, $area_desc, $valid_staff, $assignment_type, $pos_type, $pos_class, $license);
                                        if (mysqli_stmt_execute($addPos)) { echo "Successfully added the assignment area $assignment_area to the position $assignment_position.<br>"; }
                                        else { echo "Failed to add the assignment area $assignment_area to the position $assignment_position.<br>"; }
                                    }
                                }

                                // disconnect from the database
                                mysqli_close($conn);
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

                <script>function goToSalaryComparison() { window.location.href = "salary_comparison.php"; }</script>
            <?php
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
?>