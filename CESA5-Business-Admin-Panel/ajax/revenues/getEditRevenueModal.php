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
            if (isset($_POST["id"]) && $_POST["id"] <> "") { $id = $_POST["id"]; } else { $id = null; }

            // get source from POST
            if (isset($_POST["source"]) && $_POST["source"] <> "") { $source = $_POST["source"]; } else { $source = 0; }

            // verify the revenue exists; if exists get details
            $checkRevenue = mysqli_prepare($conn, "SELECT * FROM revenues WHERE id=?");
            mysqli_stmt_bind_param($checkRevenue, "i", $id);
            if (mysqli_stmt_execute($checkRevenue))
            {
                $checkRevenueResult = mysqli_stmt_get_result($checkRevenue);
                if (mysqli_num_rows($checkRevenueResult) > 0) // revenue exists; continue
                {
                    $revenue = mysqli_fetch_array($checkRevenueResult);

                    ?>
                        <div class="modal fade" tabindex="-1" role="dialog" id="editRevenueModal" data-bs-backdrop="static" aria-labelledby="editRevenueModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="editRevenueModalLabel">Edit Revenue</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    
                                    <div class="modal-body px-5 py-4">
                                        <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                            <!-- Revenue Name -->
                                            <div class="form-group col px-1">
                                                <label for="edit-revenue-name"><span class="required-field">*</span> Revenue Name:</label>
                                                <input type="text" class="form-control w-100" id="edit-revenue-name" name="edit-revenue-name" value="<?php echo $revenue["name"]; ?>" required>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                            <!-- Description -->
                                            <div class="form-group col px-1">
                                                <label for="edit-revenue-desc">Description:</label>
                                                <textarea class="form-control w-100" id="edit-revenue-desc" name="edit-revenue-desc"><?php echo $revenue["description"]; ?></textarea>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                            <!-- Date Provided -->
                                            <div class="form-group col px-1">
                                                <label for="edit-revenue-date"><span class="required-field">*</span> Date Provided:</label>
                                                <input type="text" class="form-control w-100" id="edit-revenue-date" name="edit-revenue-date"  value="<?php if (isset($revenue["date"])) { echo date("m/d/Y", strtotime($revenue["date"])); } else { echo date("m/d/Y"); } ?>" required>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                            <!-- Revenue Amount -->
                                            <div class="form-group col px-1">
                                                <label for="edit-revenue-total"><span class="required-field">*</span> Revenue Amount:</label>
                                                <input type="number" class="form-control w-100" id="edit-revenue-total" name="edit-revenue-total" value="<?php echo $revenue["total_cost"]; ?>" required>
                                            </div>

                                            <!-- Quantity -->
                                            <div class="form-group col px-1">
                                                <label for="edit-revenue-qty"><span class="required-field">*</span> Quantity:</label>
                                                <input type="number" class="form-control w-100" id="edit-revenue-qty" name="edit-revenue-qty" value="<?php echo $revenue["quantity"]; ?>" required>
                                            </div>
                                        </div>

                                        <h3 class="text-center m-0"><span class="required-field">*</span> WUFAR Codes</h3>
                                        <div class="form-row d-flex justify-content-center align-items-center mb-2">
                                            <!-- Fund Code -->
                                            <div class="form-group col px-1">
                                                <label for="edit-revenue-fund">Fund:</label>
                                                <input type="text" class="form-control w-100" id="edit-revenue-fund" name="edit-revenue-fund" value="<?php echo $revenue["fund_code"]; ?>" required>
                                            </div>

                                            <!-- Location Code -->
                                            <div class="form-group col px-1">
                                                <label for="edit-revenue-loc">Location:</label>
                                                <input type="text" class="form-control w-100" id="edit-revenue-loc" name="edit-revenue-loc" value="<?php echo $revenue["location_code"]; ?>" required>
                                            </div>

                                            <!-- Source Code -->
                                            <div class="form-group col px-1">
                                                <label for="edit-revenue-src">Source:</label>
                                                <input type="text" class="form-control w-100" id="edit-revenue-src" name="edit-revenue-src" value="<?php echo $revenue["source_code"]; ?>" required>
                                            </div>

                                            <!-- Function Code -->
                                            <div class="form-group col px-1">
                                                <label for="edit-revenue-func">Function:</label>
                                                <input type="text" class="form-control w-100" id="edit-revenue-func" name="edit-revenue-func" value="<?php echo $revenue["function_code"]; ?>" required>
                                            </div>

                                            <!-- Project Code -->
                                            <div class="form-group col px-1">
                                                <label for="edit-revenue-proj">Project:</label>
                                                <?php if ($source == 1) { // can't update project if editing from the project's budget ?>
                                                    <input type="text" class="form-control w-100" id="edit-revenue-proj" name="edit-revenue-proj" value="<?php echo $revenue["project_code"]; ?>" required readonly disabled>
                                                <?php } else { ?>
                                                    <select class="form-select w-100" id="edit-revenue-proj" name="edit-revenue-proj" required>
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
                                        <button type="button" class="btn btn-primary" onclick="editRevenue(<?php echo $id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Revenue</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>