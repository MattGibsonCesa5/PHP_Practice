<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get the period ID from POST
            if (isset($_POST["period_id"]) && $_POST["period_id"] <> "") { $period_id = $_POST["period_id"]; } else { $period_id = null; }

            // get additional required files
            include("../../includes/config.php");
            include("../../includes/functions.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            if ($period_id != null && verifyPeriod($conn, $period_id))
            {
                // get the current period details
                $getDetails = mysqli_prepare($conn, "SELECT * FROM periods WHERE id=?");
                mysqli_stmt_bind_param($getDetails, "i", $period_id);
                if (mysqli_stmt_execute($getDetails))
                {
                    $getDetailsResult = mysqli_stmt_get_result($getDetails);
                    if (mysqli_num_rows($getDetailsResult)) // period details exist; create modal
                    {
                        $period = mysqli_fetch_array($getDetailsResult);

                        // validate database values
                        if (isset($period["name"]) && $period["name"] != null) { $name = $period["name"]; } else { $name = ""; }
                        if (isset($period["description"]) && $period["description"] != null) { $description = $period["description"]; } else { $description = ""; }
                        if (isset($period["start_date"]) && $period["start_date"] != null) { $start_date = $period["start_date"]; } else { $start_date = "1999-12-31"; }
                        if (isset($period["end_date"]) && $period["end_date"] != null) { $end_date = $period["end_date"]; } else { $end_date = "2000-01-01"; }
                        if (isset($period["caseload_term_start"]) && $period["caseload_term_start"] != null) { $caseload_term_start = $period["caseload_term_start"]; } else { $caseload_term_start = "1999-12-31"; }
                        if (isset($period["caseload_term_end"]) && $period["caseload_term_end"] != null) { $caseload_term_end = $period["caseload_term_end"]; } else { $caseload_term_end = "2000-01-01"; }
                        if (isset($period["active"]) && $period["active"] != null) { $active = $period["active"]; } else { $active = 0; }
                        if (isset($period["comparison"]) && $period["comparison"] != null) { $comparison = $period["comparison"]; } else { $comparison = 0; }
                        if (isset($period["next"]) && $period["next"] != null) { $next = $period["next"]; } else { $next = 0; }
                        if (isset($period["editable"]) && $period["editable"] != null) { $editable = $period["editable"]; } else { $editable = 0; }

                        // convert the database dates to the correct display format
                        $display_start = date("m/d/Y", strtotime($start_date));
                        $display_end = date("m/d/Y", strtotime($end_date));
                        $display_caseload_start = date("m/d/Y", strtotime($caseload_term_start));
                        $display_caseload_end = date("m/d/Y", strtotime($caseload_term_end));

                        // get period quarter details
                        $quarters = [];
                        $getQuarters = mysqli_prepare($conn, "SELECT * FROM quarters WHERE period_id=? ORDER BY quarter ASC");
                        mysqli_stmt_bind_param($getQuarters, "i", $period_id);
                        if (mysqli_stmt_execute($getQuarters))
                        {
                            $getQuartersResults = mysqli_stmt_get_result($getQuarters);
                            if (mysqli_num_rows($getQuartersResults) > 0)
                            {
                                while ($quarter = mysqli_fetch_array($getQuartersResults))
                                {
                                    $quarter_num = $quarter["quarter"];
                                    $quarter_label = $quarter["label"];
                                    $quarter_status = $quarter["locked"];
                                    $quarters[$quarter_num]["label"] = $quarter_label;
                                    $quarters[$quarter_num]["status"] = $quarter_status;
                                }
                            }
                        }

                        ?>
                            <div class="modal fade" tabindex="-1" role="dialog" id="editPeriodModal" data-bs-backdrop="static" aria-labelledby="editPeriodModalLabel" aria-hidden="true">
                                <div class="modal-dialog modal-lg" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header primary-modal-header">
                                            <h5 class="modal-title primary-modal-title" id="editPeriodModalLabel">Edit Period</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>

                                        <div class="modal-body">
                                            <div class="form-group mb-3">
                                                <label for="edit-name"><span class="required-field">*</span> Period Name:</label>
                                                <input type="text" class="form-control w-100" id="edit-name" name="edit-name" value="<?php echo $name; ?>" required>
                                            </div>

                                            <div class="form-group mb-3">
                                                <label for="edit-desc">Description:</label>
                                                <input type="text" class="form-control w-100" id="edit-desc" name="edit-desc" value="<?php echo $description; ?>">
                                            </div>

                                            <!-- Quarters -->
                                            <div class="form-group mb-3">
                                                <fieldset class="border p-2">
                                                    <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h4 class="mb-0">Quarters</h4></legend>
                                                    
                                                    <table class="report_table w-100">
                                                        <thead>
                                                            <tr>
                                                                <th class="text-center w-25" id="Q1-header">Q1</th>
                                                                <th class="text-center w-25" id="Q2-header">Q2</th>
                                                                <th class="text-center w-25" id="Q3-header">Q3</th>
                                                                <th class="text-center w-25" id="Q4-header">Q4</th>
                                                            </tr>
                                                        </thead>

                                                        <tbody>
                                                            <!-- Labels -->
                                                            <tr>
                                                                <td><input type="text" class="form-control" id="edit-q1-label" name="edit-q1-label" aria-labelledby="#Q1-header" placeholder="Q1 Label" value="<?php if (isset($quarters[1]["label"])) { echo $quarters[1]["label"]; } ?>"></td>
                                                                <td><input type="text" class="form-control" id="edit-q2-label" name="edit-q2-label" aria-labelledby="#Q2-header" placeholder="Q2 Label" value="<?php if (isset($quarters[2]["label"])) { echo $quarters[2]["label"]; } ?>"></td>
                                                                <td><input type="text" class="form-control" id="edit-q3-label" name="edit-q3-label" aria-labelledby="#Q3-header" placeholder="Q3 Label" value="<?php if (isset($quarters[3]["label"])) { echo $quarters[3]["label"]; } ?>"></td>
                                                                <td><input type="text" class="form-control" id="edit-q4-label" name="edit-q4-label" aria-labelledby="#Q4-header" placeholder="Q4 Label" value="<?php if (isset($quarters[4]["label"])) { echo $quarters[4]["label"]; } ?>"></td>
                                                            </tr>

                                                            <!-- Lock Status -->
                                                            <tr>
                                                                <td>
                                                                    <?php if (isset($quarters[1]["status"]) && $quarters[1]["status"] == 0) { ?>
                                                                        <button class="btn btn-success btn-sm w-100" id="edit-q1-status" value="0" onclick="toggleStatus('edit', 1);" aria-labelledby="#Q1-header"><i class="fa-solid fa-lock-open"></i></button>
                                                                    <?php } else { ?>
                                                                        <button class="btn btn-danger btn-sm w-100" id="edit-q1-status" value="1" onclick="toggleStatus('edit', 1);" aria-labelledby="#Q1-header"><i class="fa-solid fa-lock"></i></button>
                                                                    <?php } ?>
                                                                </td>

                                                                <td>
                                                                    <?php if (isset($quarters[2]["status"]) && $quarters[2]["status"] == 0) { ?>
                                                                        <button class="btn btn-success btn-sm w-100" id="edit-q2-status" value="0" onclick="toggleStatus('edit', 2);" aria-labelledby="#Q2-header"><i class="fa-solid fa-lock-open"></i></button>
                                                                    <?php } else { ?>
                                                                        <button class="btn btn-danger btn-sm w-100" id="edit-q2-status" value="1" onclick="toggleStatus('edit', 2);" aria-labelledby="#Q2-header"><i class="fa-solid fa-lock"></i></button>
                                                                    <?php } ?>
                                                                </td>

                                                                <td>
                                                                    <?php if (isset($quarters[3]["status"]) && $quarters[3]["status"] == 0) { ?>
                                                                        <button class="btn btn-success btn-sm w-100" id="edit-q3-status" value="0" onclick="toggleStatus('edit', 3);" aria-labelledby="#Q3-header"><i class="fa-solid fa-lock-open"></i></button>
                                                                    <?php } else { ?>
                                                                        <button class="btn btn-danger btn-sm w-100" id="edit-q3-status" value="1" onclick="toggleStatus('edit', 3);" aria-labelledby="#Q3-header"><i class="fa-solid fa-lock"></i></button>
                                                                    <?php } ?>
                                                                </td>

                                                                <td>
                                                                    <?php if (isset($quarters[4]["status"]) && $quarters[4]["status"] == 0) { ?>
                                                                        <button class="btn btn-success btn-sm w-100" id="edit-q4-status" value="0" onclick="toggleStatus('edit', 4);" aria-labelledby="#Q4-header"><i class="fa-solid fa-lock-open"></i></button>
                                                                    <?php } else { ?>
                                                                        <button class="btn btn-danger btn-sm w-100" id="edit-q4-status" value="1" onclick="toggleStatus('edit', 4);" aria-labelledby="#Q4-header"><i class="fa-solid fa-lock"></i></button>
                                                                    <?php } ?>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </fieldset>
                                            </div>

                                            <!-- Fiscal Cycle -->
                                            <fieldset class="border p-2">
                                                <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h4 class="mb-0">Fiscal Cycle</h4></legend>
                                                <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                                    <!-- Start Date -->
                                                    <div class="form-group col-6 pe-2">
                                                        <label for="edit-start"><span class="required-field">*</span> Start Date:</label>
                                                        <div class="input-group w-100 h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" id="edit-start" name="edit-start" value="<?php echo $display_start; ?>" required>
                                                        </div>
                                                    </div>

                                                    <!-- End Date -->
                                                    <div class="form-group col-6 ps-2">
                                                        <label for="edit-end"><span class="required-field">*</span> End Date:</label>
                                                        <div class="input-group w-100 h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" id="edit-end" name="edit-end" value="<?php echo $display_end; ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </fieldset>

                                            <!-- Caseload Term -->
                                            <fieldset class="border p-2">
                                                <legend class="float-none w-auto px-4 py-0 m-0 text-center"><h4 class="mb-0">Caseload Term</h4></legend>
                                                <div class="form-row d-flex justify-content-center align-items-center mb-3">
                                                    <!-- Start Date -->
                                                    <div class="form-group col-6 pe-2">
                                                        <label for="edit-caseload_term-start"><span class="required-field">*</span> Start Date:</label>
                                                        <div class="input-group w-100 h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" id="edit-caseload_term-start" name="edit-caseload_term-start" value="<?php echo $display_caseload_start; ?>" required>
                                                        </div>
                                                    </div>

                                                    <!-- End Date -->
                                                    <div class="form-group col-6 ps-2">
                                                        <label for="edit-caseload_term-end"><span class="required-field">*</span> End Date:</label>
                                                        <div class="input-group w-100 h-auto">
                                                            <div class="input-group-prepend">
                                                                <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                            </div>
                                                            <input type="text" class="form-control" id="edit-caseload_term-end" name="edit-caseload_term-end" value="<?php echo $display_caseload_end; ?>" required>
                                                        </div>
                                                    </div>
                                                </div>
                                            </fieldset>

                                            <div class="form-group row mb-3">
                                                <div class="form-group col-3 text-center pe-2">
                                                    <label for="edit-status">Status</label>
                                                    <?php if ($active == 1) { ?>
                                                        <button class="btn btn-success w-100" id="edit-status" value=1 onclick="updateStatus('edit-status');">Active</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-danger w-100" id="edit-status" value=0 onclick="updateStatus('edit-status');">Inactive</button>
                                                    <?php } ?>
                                                </div>

                                                <div class="form-group col-3 text-center px-2">
                                                    <label for="edit-comp">Comparison Period</label>
                                                    <?php if ($comparison == 1) { ?>
                                                        <button class="btn btn-success w-100" id="edit-comp" value=1 onclick="updateYesNoToggle('edit-comp');">Yes</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-danger w-100" id="edit-comp" value=0 onclick="updateYesNoToggle('edit-comp');">No</button>
                                                    <?php } ?>
                                                </div>

                                                <div class="form-group col-3 text-center px-2">
                                                    <label for="edit-next">Next Period</label>
                                                    <?php if ($next == 1) { ?>
                                                        <button class="btn btn-success w-100" id="edit-next" value=1 onclick="updateYesNoToggle('edit-next');">Yes</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-danger w-100" id="edit-next" value=0 onclick="updateYesNoToggle('edit-next');">No</button>
                                                    <?php } ?>
                                                </div>

                                                <div class="form-group col-3 text-center ps-2">
                                                    <label for="edit-editable">Editable</label>
                                                    <?php if ($editable == 1) { ?> 
                                                        <button class="btn btn-success w-100" id="edit-editable" value=1 onclick="updateYesNoToggle('edit-editable');">Yes</button>
                                                    <?php } else { ?>
                                                        <button class="btn btn-danger w-100" id="edit-editable" value=0 onclick="updateYesNoToggle('edit-editable');">Yes</button>
                                                    <?php } ?>
                                                </div>
                                            </div>

                                            <!-- Required Field Indicator -->
                                            <div class="row">
                                                <p class="text-center fst-italic m-0"><span class="required-field">*</span> indicates a required field</p>
                                            </div>
                                        </div>

                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-primary" onclick="editPeriod(<?php echo $period_id; ?>);"><i class="fa-solid fa-floppy-disk"></i> Save Period</button>
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
    }
?>