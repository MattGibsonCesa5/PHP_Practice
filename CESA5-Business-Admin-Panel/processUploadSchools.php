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
                    <div class='col-8'><h1 class='upload-status-header'>Upload Schools</h1></div>
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
                                // connect to the database
                                $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

                                // open the file for reading
                                $handle = fopen($file, "r");

                                while ($data = fgetcsv($handle, 1000, ",", '"'))
                                {
                                    // if we are in the first row (data[0] = SCHOOL_YEAR), truncate the table
                                    if (isset($data[0]) && $data[0] == "SCHOOL_YEAR")
                                    {
                                        $truncateDPIEmplyoees = mysqli_query($conn, "TRUNCATE schools");
                                    }

                                    if (isset($data[0]) && $data[0] != "SCHOOL_YEAR") // skip the first two rows by looking at cell data
                                    {
                                        // get and clean up the employee's data
                                        if (isset($data[14])) { $district_name = clean_data($data[14]); } else { $district_name = null; }
                                        if (isset($data[16])) { $school_name = clean_data($data[16]); } else { $school_name = null; }
                                        if (isset($data[17])) { $grade_group = clean_data($data[17]); } else { $grade_group = null; }

                                        // get customer ID based on district name
                                        $getDistrictID = mysqli_prepare($conn, "SELECT id FROM customers WHERE name=?");
                                        mysqli_stmt_bind_param($getDistrictID, "s", $district_name);
                                        if (mysqli_stmt_execute($getDistrictID))
                                        {
                                            $getDistrictIDResult = mysqli_stmt_get_result($getDistrictID);
                                            if (mysqli_num_rows($getDistrictIDResult) > 0) // district found
                                            {
                                                // store district ID locally
                                                $district_id = mysqli_fetch_array($getDistrictIDResult)["id"];

                                                // add school 
                                                $addSchool = mysqli_prepare($conn, "INSERT INTO schools (name, grade_group, district_id) VALUES (?, ?, ?)");
                                                mysqli_stmt_bind_param($addSchool, "ssi", $school_name, $grade_group, $district_id);
                                                if (mysqli_stmt_execute($addSchool)) { echo "<span style='color: #008000;'><b>Successfully</b></span> added $school_name to the district $district_name.<br>"; }
                                            }
                                            else { echo "<span style='color: #ff0000;'><b>Failed</b></span> to import $school_name for the district $district_name. The district does not exist as an existing customer within the BAP!<br>"; }
                                        }
                                    }
                                }

                                // disconnect from the database
                                mysqli_close($conn);

                                echo "<i class=\"fa-solid fa-check\"></i> Upload complete!<br>";
                            }
                            else { echo "ERROR! You must select a .csv file to upload."; }
                        }   
                        else { echo "ERROR! No upload file was found. Please select a file to upload and try again. "; }
                    ?>
                    </div>
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