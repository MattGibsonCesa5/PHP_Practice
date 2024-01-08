<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // include config
            include("../../includes/config.php");
            include("../../includes/functions.php");

            // initialize counters
            $errors = 0;

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            if (isset($_POST["type"]) && $_POST["type"] <> "") { $type = $_POST["type"]; } else { $type = null; }

            if ($type != null)
            {
                if ($type == "health" || $type == "dental")
                {
                    $indicator = "";
                    if ($type == "health") { $indicator = "Health"; }
                    else if ($type == "dental") { $indicator = "Dental"; }

                    if (isset($_POST["none"]) && $_POST["none"] <> "")
                    {
                        $code = clean_data($_POST["none"]);

                        // update insurance code for no coverage
                        $updateCode = mysqli_prepare($conn, "UPDATE `codes` SET code=? WHERE indicator=? AND plan='None'");
                        mysqli_stmt_bind_param($updateCode, "ss", $code, $indicator);
                        if (!mysqli_stmt_execute($updateCode)) { echo "<span class=\"log-fail\">Failed</span> to update the code for $indcator None. "; $errors++; }
                    }
                    
                    if (isset($_POST["single"]) && $_POST["single"] <> "")
                    {
                        $code = clean_data($_POST["single"]);

                        // update insurance code for no coverage
                        $updateCode = mysqli_prepare($conn, "UPDATE `codes` SET code=? WHERE indicator=? AND plan='Single'");
                        mysqli_stmt_bind_param($updateCode, "ss", $code, $indicator);
                        if (!mysqli_stmt_execute($updateCode)) { echo "<span class=\"log-fail\">Failed</span> to update the code for $indcator Single. "; $errors++; }
                    }

                    if (isset($_POST["family"]) && $_POST["family"] <> "")
                    {
                        $code = clean_data($_POST["family"]);

                        // update insurance code for no coverage
                        $updateCode = mysqli_prepare($conn, "UPDATE `codes` SET code=? WHERE indicator=? AND plan='Family'");
                        mysqli_stmt_bind_param($updateCode, "ss", $code, $indicator);
                        if (!mysqli_stmt_execute($updateCode)) { echo "<span class=\"log-fail\">Failed</span> to update the code for $indcator Family. "; $errors++; }
                    }
                }
                else if ($type == "wrs")
                {
                    $indicator = "WRS";

                    if (isset($_POST["yes"]) && $_POST["yes"] <> "")
                    {
                        $code = clean_data($_POST["yes"]);

                        // update insurance code for no coverage
                        $updateCode = mysqli_prepare($conn, "UPDATE `codes` SET code=? WHERE indicator=? AND plan='Yes'");
                        mysqli_stmt_bind_param($updateCode, "ss", $code, $indicator);
                        if (!mysqli_stmt_execute($updateCode)) { echo "<span class=\"log-fail\">Failed</span> to update the code for $indcator Eligible. "; $errors++; }
                    }

                    if (isset($_POST["no"]) && $_POST["no"] <> "")
                    {
                        $code = clean_data($_POST["no"]);

                        // update insurance code for no coverage
                        $updateCode = mysqli_prepare($conn, "UPDATE `codes` SET code=? WHERE indicator=? AND plan='No'");
                        mysqli_stmt_bind_param($updateCode, "ss", $code, $indicator);
                        if (!mysqli_stmt_execute($updateCode)) { echo "<span class=\"log-fail\">Failed</span> to update the code for $indcator Ineligible. "; $errors++; }
                    }
                }
                else if ($type == "gender")
                {
                    $indicator = "Gender";

                    if (isset($_POST["male"]) && $_POST["male"] <> "")
                    {
                        $code = clean_data($_POST["male"]);

                        // update male code
                        $updateCode = mysqli_prepare($conn, "UPDATE `codes` SET code=? WHERE indicator=? AND plan='Male'");
                        mysqli_stmt_bind_param($updateCode, "ss", $code, $indicator);
                        if (!mysqli_stmt_execute($updateCode)) { echo "<span class=\"log-fail\">Failed</span> to update the code for $indcator Male. "; $errors++; }
                    }

                    if (isset($_POST["female"]) && $_POST["female"] <> "")
                    {
                        $code = clean_data($_POST["female"]);

                        // update female code
                        $updateCode = mysqli_prepare($conn, "UPDATE `codes` SET code=? WHERE indicator=? AND plan='Female'");
                        mysqli_stmt_bind_param($updateCode, "ss", $code, $indicator);
                        if (!mysqli_stmt_execute($updateCode)) { echo "<span class=\"log-fail\">Failed</span> to update the code for $indcator Female. "; $errors++; }
                    }
                }
                else if ($type == "marital_status")
                {
                    $indicator = "Marital Status";

                    if (isset($_POST["single"]) && $_POST["single"] <> "")
                    {
                        $code = clean_data($_POST["single"]);

                        // update single code
                        $updateCode = mysqli_prepare($conn, "UPDATE `codes` SET code=? WHERE indicator=? AND plan='Single'");
                        mysqli_stmt_bind_param($updateCode, "ss", $code, $indicator);
                        if (!mysqli_stmt_execute($updateCode)) { echo "<span class=\"log-fail\">Failed</span> to update the code for $indcator Single. "; $errors++; }
                    }

                    if (isset($_POST["married"]) && $_POST["married"] <> "")
                    {
                        $code = clean_data($_POST["married"]);

                        // update married code
                        $updateCode = mysqli_prepare($conn, "UPDATE `codes` SET code=? WHERE indicator=? AND plan='Married'");
                        mysqli_stmt_bind_param($updateCode, "ss", $code, $indicator);
                        if (!mysqli_stmt_execute($updateCode)) { echo "<span class=\"log-fail\">Failed</span> to update the code for $indcator Married. "; $errors++; }
                    }
                }
                else if ($type == "address_type")
                {
                    $indicator = "Address Type";

                    if (isset($_POST["street"]) && $_POST["street"] <> "")
                    {
                        $code = clean_data($_POST["street"]);

                        // update Street code
                        $updateCode = mysqli_prepare($conn, "UPDATE `codes` SET code=? WHERE indicator=? AND plan='Street'");
                        mysqli_stmt_bind_param($updateCode, "ss", $code, $indicator);
                        if (!mysqli_stmt_execute($updateCode)) { echo "<span class=\"log-fail\">Failed</span> to update the code for $indcator Street. "; $errors++; }
                    }

                    if (isset($_POST["po"]) && $_POST["po"] <> "")
                    {
                        $code = clean_data($_POST["po"]);

                        // update PO code
                        $updateCode = mysqli_prepare($conn, "UPDATE `codes` SET code=? WHERE indicator=? AND plan='PO'");
                        mysqli_stmt_bind_param($updateCode, "ss", $code, $indicator);
                        if (!mysqli_stmt_execute($updateCode)) { echo "<span class=\"log-fail\">Failed</span> to update the code for $indcator PO. "; $errors++; }
                    }
                }
            }

            if ($errors == 0) { echo 1; }

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>