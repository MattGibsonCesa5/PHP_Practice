<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "EDIT_REVENUES"))
        {
            // get revenue ID POST
            if (isset($_POST["revenue_id"]) && $_POST["revenue_id"] <> "") { $revenue_id = $_POST["revenue_id"]; } else { $revenue_id = null; }

            // get source from POST
            if (isset($_POST["source"]) && $_POST["source"] <> "") { $source = $_POST["source"]; } else { $source = 0; }

            // verify the revenue was set
            if ($revenue_id != null)
            {
                // verify the revenue exists; if exists get details
                $checkRevenue = mysqli_prepare($conn, "SELECT * FROM revenues WHERE id=?");
                mysqli_stmt_bind_param($checkRevenue, "i", $revenue_id);
                if (mysqli_stmt_execute($checkRevenue))
                {
                    $checkRevenueResult = mysqli_stmt_get_result($checkRevenue);
                    if (mysqli_num_rows($checkRevenueResult) > 0) // revenue exists; continue
                    {
                        // store existiing revenue details
                        $revenue = mysqli_fetch_array($checkRevenueResult);

                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="cloneRevenueModal" data-bs-backdrop="static" aria-labelledby="cloneRevenueModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="cloneRevenueModalLabel">Clone Revenue</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        
                                        <div class="modal-body px-5 py-4">
                                            <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                                <!-- Revenue Name -->
                                                <div class="form-group col px-1">
                                                    <label for="clone-revenue-name"><span class="required-field">*</span> Revenue Name:</label>
                                                    <input type="text" class="form-control w-100" id="clone-revenue-name" name="clone-revenue-name" value="<?php echo $revenue["name"]; ?>" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                                <!-- Description -->
                                                <div class="form-group col px-1">
                                                    <label for="clone-revenue-desc">Description:</label>
                                                    <textarea class="form-control w-100" id="clone-revenue-desc" name="clone-revenue-desc"><?php echo $revenue["description"]; ?></textarea>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                                <!-- Date Provided -->
                                                <div class="form-group col px-1">
                                                    <label for="clone-revenue-date"><span class="required-field">*</span> Date Provided:</label>
                                                    <input type="text" class="form-control w-100" id="clone-revenue-date" name="clone-revenue-date"  value="<?php if (isset($revenue["date"])) { echo date("m/d/Y", strtotime($revenue["date"])); } else { echo date("m/d/Y"); } ?>" required>
                                                </div>
                                            </div>

                                            <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                                <!-- Revenue Amount -->
                                                <div class="form-group col px-1">
                                                    <label for="clone-revenue-cost"><span class="required-field">*</span> Revenue Amount:</label>
                                                    <input type="number" class="form-control w-100" id="clone-revenue-cost" name="clone-revenue-cost" value="<?php echo $revenue["total_cost"]; ?>" required>
                                                </div>

                                                <!-- Quantity -->
                                                <div class="form-group col px-1">
                                                    <label for="clone-revenue-qty"><span class="required-field">*</span> Quantity:</label>
                                                    <input type="number" class="form-control w-100" id="clone-revenue-qty" name="clone-revenue-qty" value="<?php echo $revenue["quantity"]; ?>" required>
                                                </div>
                                            </div>

                                            <h3 class="text-center m-0"><span class="required-field">*</span> WUFAR Codes</h3>
                                            <div class="form-row d-flex justify-content-center align-items-center mb-2">
                                                <!-- Fund Code -->
                                                <div class="form-group col px-1">
                                                    <label for="clone-revenue-fund">Fund:</label>
                                                    <input type="text" class="form-control w-100" id="clone-revenue-fund" name="clone-revenue-fund" value="<?php echo $revenue["fund_code"]; ?>" required>
                                                </div>

                                                <!-- Location Code -->
                                                <div class="form-group col px-1">
                                                    <label for="clone-revenue-loc">Location:</label>
                                                    <input type="text" class="form-control w-100" id="clone-revenue-loc" name="clone-revenue-loc" value="<?php echo $revenue["location_code"]; ?>" required>
                                                </div>

                                                <!-- Source Code -->
                                                <div class="form-group col px-1">
                                                    <label for="clone-revenue-src">Source:</label>
                                                    <input type="text" class="form-control w-100" id="clone-revenue-src" name="clone-revenue-src" value="<?php echo $revenue["source_code"]; ?>" required>
                                                </div>

                                                <!-- Function Code -->
                                                <div class="form-group col px-1">
                                                    <label for="clone-revenue-func">Function:</label>
                                                    <input type="text" class="form-control w-100" id="clone-revenue-func" name="clone-revenue-func" value="<?php echo $revenue["function_code"]; ?>" required>
                                                </div>

                                                <!-- Project Code -->
                                                <div class="form-group col px-1">
                                                    <label for="clone-revenue-proj">Project:</label>
                                                    <?php if ($source == 1) { // can't update project if editing from the project's budget ?>
                                                        <input type="text" class="form-control w-100" id="clone-revenue-proj" name="clone-revenue-proj" value="<?php echo $revenue["project_code"]; ?>"  required disabled readonly>
                                                    <?php } else { ?>
                                                        <select class="form-select w-100" id="clone-revenue-proj" name="clone-revenue-proj" required>
                                                            <option></option>
                                                            <?php
                                                                $getProjectCodes = mysqli_query($conn, "SELECT code, name FROM projects ORDER BY code ASC");
                                                                if (mysqli_num_rows($getProjectCodes) > 0) // projects found; continue
                                                                {
                                                                    while ($project = mysqli_fetch_array($getProjectCodes))
                                                                    {
                                                                        $code = $project["code"];
                                                                        $name = $project["name"];
                                                                        $display = $code . " - " . $name;
                                                                        if ($code == $revenue["project_code"]) { echo "<option value='".$code."' selected>".$display."</option>"; } 
                                                                        else { echo "<option value='".$code."'>".$display."</option>"; }
                                                                    }
                                                                }
                                                            ?>
                                                        </select>
                                                    <?php } ?>
                                                </div>
                                            </div>

                                            <!-- Required Field Indicator -->
                                            <div class="row justify-content-center">
                                                <div class="col text-center fst-italic">
                                                    <span class="required-field">*</span> indicates a required field
                                                </div>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="cloneRevenue();"><i class="fa-solid fa-plus"></i> Add Revenue</button>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php
                    }
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>