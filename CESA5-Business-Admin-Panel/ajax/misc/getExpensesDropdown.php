<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && ($_SESSION["role"] == 1 || $_SESSION["role"] == 2))
        {
            // get additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>
                <option></option>
                <?php
                    // create a dropdown of all active expenses
                    $getExpenses = mysqli_query($conn, "SELECT id, name, object_code FROM expenses WHERE status=1 AND global=0 ORDER BY name ASC");
                    while ($expense = mysqli_fetch_array($getExpenses))
                    {
                        // store expense details locally
                        $id = $expense["id"];
                        $name = $expense["name"];
                        $obj = $expense["object_code"];

                        // build option display
                        $display = $name;
                        if (isset($obj) && trim($obj) <> "") { $display .= " (".$obj.")"; }

                        // create option
                        echo "<option value=".$id.">".$display."</option>";
                    }
                ?>
            <?php

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>