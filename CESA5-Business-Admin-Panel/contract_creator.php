<?php 
    // include the autoloader
    require_once("vendor/autoload.php");

    // include the PDF creation tool
    use mikehaertl\wkhtmlto\Pdf;

    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if ($_SESSION["role"] == 1)
        {
            // include additional settings
            include("getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>
                <script>
                    function checkType(type)
                    {
                        // service contract selected
                        if (type == 1)
                        {
                            // display the quantity period and make it a required field
                            $("#qty_period-div").removeClass("d-none");
                            $("#qty_period").attr("required", true);
                        } else {
                            // hide the quantity period and make it a non-required field
                            $("#qty_period-div").addClass("d-none");
                            $("#qty_period").attr("required", false);
                        }
                    }
                </script>

                <div class="container py-3">
                    <form action="createContracts.php" method="POST">
                        <!-- Basic Information -->
                        <h2>Basic Information</h2>
                        <div class="form-floating mb-3">
                            <select type="text" class="form-select" id="type" name="type" onchange="checkType(this.value);" required>
                                <option></option>
                                <?php
                                    // get contract types
                                    $getTypes = mysqli_query($conn, "SELECT id, name FROM contract_types ORDER BY name ASC");
                                    if (mysqli_num_rows($getTypes) > 0)
                                    {
                                        while ($type = mysqli_fetch_assoc($getTypes)) 
                                        {
                                            echo "<option value='".$type["id"]."'>".$type["name"]."</option>";
                                        }
                                    }
                                ?>
                            </select>
                            <label for="type">Contract Type</label>
                        </div>

                        <div class="form-floating mb-3">
                            <select type="text" class="form-select" id="contract_period" name="contract_period" required>
                                <option></option>
                                <?php
                                    // get periods
                                    $getPeriods = mysqli_query($conn, "SELECT id, name FROM periods ORDER BY end_date DESC");
                                    if (mysqli_num_rows($getPeriods) > 0)
                                    {
                                        while ($period = mysqli_fetch_assoc($getPeriods)) 
                                        {
                                            echo "<option value='".$period["id"]."'>".$period["name"]."</option>";
                                        }
                                    }
                                ?>
                            </select>
                            <label for="contract_period">Contract Period</label>
                        </div>

                        <div class="form-floating d-none mb-3" id="qty_period-div">
                            <select type="text" class="form-select" id="qty_period" name="qty_period">
                                <option></option>
                                <?php
                                    // get periods
                                    $getPeriods = mysqli_query($conn, "SELECT id, name FROM periods ORDER BY end_date DESC");
                                    if (mysqli_num_rows($getPeriods) > 0)
                                    {
                                        while ($period = mysqli_fetch_assoc($getPeriods)) 
                                        {
                                            echo "<option value='".$period["id"]."'>".$period["name"]."</option>";
                                        }
                                    }
                                ?>
                            </select>
                            <label for="qty_period">Quantity Period</label>
                        </div>

                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="name" name="name" required>
                            <label for="name">Contract Name</label>
                        </div>

                        <div class="form-floating mb-3">
                            <select id="customers" name="customers[]" class="form-select" style="height: 250px" multiple required>
                                <?php
                                    // create a dropdown list of all customers who have been enabled to build service contracts for
                                    $getCustomers = mysqli_query($conn, "SELECT id, name FROM customers WHERE build_service_contract=1 AND active=1 ORDER BY name ASC");
                                    while ($customers = mysqli_fetch_array($getCustomers)) { echo "<option value='".$customers["id"]."'>".$customers["name"]."</option>"; }
                                ?>
                            </select>
                            <label for="customers">Customers</label>
                        </div>

                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="overwrite" name="overwrite">
                            <label class="form-check-label" for="overwrite">Overwrite existing file?</label>
                        </div>

                        <div class="text-center w-100">
                            <button type="submit" class="btn btn-primary btn-lg"><i class="fa-solid fa-file-signature"></i> Create Contracts</button>
                        </div>
                    </form>
                </div>
            <?php

            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>