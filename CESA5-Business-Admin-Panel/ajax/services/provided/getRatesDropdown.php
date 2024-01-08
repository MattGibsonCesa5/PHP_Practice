<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../../includes/config.php");
        include("../../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "ADD_INVOICES") || checkUserPermission($conn, "EDIT_INVOICES"))
        {
            if (isset($_POST["service_id"]) && $_POST["service_id"] <> "") { $service_id = $_POST["service_id"]; } else { $service_id = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($service_id != null && $period != null)
            {
                if ($period_id = getPeriodID($conn, $period))
                {
                    ?>
                        <select class="form-select w-100" id="provide-rate" name="provide-rate" required>
                            <option></option>
                            <?php
                                // verify the service exists and is a rate-based cost
                                $checkService = mysqli_prepare($conn, "SELECT id FROM services WHERE id=? AND cost_type=4");
                                mysqli_stmt_bind_param($checkService, "s", $service_id);
                                if (mysqli_stmt_execute($checkService))
                                {
                                    $checkServiceResult = mysqli_stmt_get_result($checkService);
                                    if (mysqli_num_rows($checkServiceResult) > 0) // service exists; continue
                                    {
                                        // get each rate the service has
                                        $getRates = mysqli_prepare($conn, "SELECT variable_order, cost FROM costs WHERE service_id=? AND period_id=? AND cost_type=4");
                                        mysqli_stmt_bind_param($getRates, "si", $service_id, $period_id);
                                        if (mysqli_stmt_execute($getRates))
                                        {
                                            $getRatesResults = mysqli_stmt_get_result($getRates);
                                            if (mysqli_num_rows($getRatesResults) > 0) // rates found
                                            {
                                                // for each rate found, create a dropdown option
                                                while ($rate = mysqli_fetch_array($getRatesResults))
                                                {
                                                    $tier = $rate["variable_order"];
                                                    $cost = $rate["cost"];
                                                    echo "<option value='".$tier."'>Tier ".$tier." - ".printDollar($cost)."</option>";
                                                }
                                            }
                                        }
                                    }
                                }
                            ?>
                        </select>
                    <?php
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>