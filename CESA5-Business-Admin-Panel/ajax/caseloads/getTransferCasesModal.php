<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_CASELOADS_ALL") && checkUserPermission($conn, "VIEW_THERAPISTS"))
        {
            // get the parameters from POST
            if (isset($_POST["caseload_id"]) && $_POST["caseload_id"] <> "") { $caseload_id = $_POST["caseload_id"]; } else { $caseload_id = null; }
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period)) // verify the period exists; if it exists, store the period ID
            {
                // get the caseload term for the period
                $term_start = $term_end = "";
                $getTerm = mysqli_prepare($conn, "SELECT caseload_term_start, caseload_term_end FROM periods WHERE id=?");
                mysqli_stmt_bind_param($getTerm, "i", $period_id);
                if (mysqli_stmt_execute($getTerm))
                {
                    $getTermResult = mysqli_stmt_get_result($getTerm);
                    if (mysqli_num_rows($getTermResult) > 0)
                    {
                        $term = mysqli_fetch_array($getTermResult);
                        $term_start = $term["caseload_term_start"];
                        $term_end = $term["caseload_term_end"];
                    }
                }

                // convert dates to readable format
                if (isset($term_start) && $term_start <> "") { $term_start = date("m/d/Y", strtotime($term_start)); }
                if (isset($term_end) && $term_end <> "") { $term_end = date("m/d/Y", strtotime($term_end)); }

                if (verifyCaseload($conn, $caseload_id))
                {
                    // get the caseload name
                    $caseload_name = getCaseloadDisplayName($conn, $caseload_id);

                    ?>
                        <!-- Transfer Cases Modal -->
                        <div class="modal fade" tabindex="-1" role="dialog" id="transferCasesModal" data-bs-backdrop="static" aria-labelledby="transferCasesModalLabel" aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header primary-modal-header">
                                        <h5 class="modal-title primary-modal-title" id="transferCasesModalLabel">Transfer Caseload</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>

                                    <div class="modal-body">
                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Current Caseload -->
                                            <div class="form-group col-11">
                                                <label for="transfer-previous_therapist">Current Caseload:</label>
                                                <input type="text" class="form-control" id="transfer-previous_therapist" name="transfer-previous_therapist" value="<?php echo $caseload_name; ?>" readonly disabled>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Students -->
                                            <div class="form-group col-11">
                                                <label for="transfer-district_id">Transfer Students From (District):</label>
                                                <select class="form-select" id="transfer-district_id" name="transfer-district_id" required>
                                                    <option value="-1">All Students</option>
                                                    <?php
                                                        // get a list of district that the current caseload has students in
                                                        $getCaseloadDistricts = mysqli_prepare($conn, "SELECT DISTINCT d.id AS district_id, d.name FROM customers d
                                                                                                        JOIN cases c ON d.id=c.district_attending
                                                                                                        WHERE c.caseload_id=?
                                                                                                        ORDER BY d.name ASC");
                                                        mysqli_stmt_bind_param($getCaseloadDistricts, "i", $caseload_id);
                                                        if (mysqli_stmt_execute($getCaseloadDistricts))
                                                        {
                                                            $getCaseloadDistrictsResults = mysqli_stmt_get_result($getCaseloadDistricts);
                                                            if (mysqli_num_rows($getCaseloadDistrictsResults) > 0)
                                                            {
                                                                while ($district = mysqli_fetch_array($getCaseloadDistrictsResults))
                                                                {
                                                                    // store district details locally
                                                                    $district_id = $district["district_id"];
                                                                    $district_name = $district["name"];

                                                                    // build the dropdown option
                                                                    echo "<option value='".$district_id."'>".$district_name."</option>";
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- New Caseload -->
                                            <div class="form-group col-11">
                                                <label for="transfer-new_caseload"><span class="required-field">*</span> Transfer To:</label>
                                                <select class="w-100" id="transfer-new_caseload" name="transfer-new_caseload">
                                                    <option></option>
                                                    <?php
                                                            // get a list of all caseload categories
                                                            $getCategories = mysqli_query($conn, "SELECT * FROM caseload_categories ORDER BY name ASC");
                                                            if (mysqli_num_rows($getCategories) > 0)
                                                            {
                                                                // for each category, attempt to bill districts
                                                                while ($category = mysqli_fetch_array($getCategories))
                                                                {
                                                                    // store category details locally
                                                                    $category_id = $category["id"];
                                                                    $category_name = $category["name"];

                                                                    // create the option group
                                                                    echo "<optgroup label='".$category_name."'>";

                                                                    // get all caseloads for the category
                                                                    $getCaseloads = mysqli_prepare($conn, "SELECT c.id AS caseload_id FROM caseloads c 
                                                                                                        JOIN users u ON u.id=c.employee_id
                                                                                                        LEFT JOIN caseloads_status cs ON c.id=cs.caseload_id
                                                                                                        WHERE cs.status=1 AND cs.period_id=? AND c.category_id=?
                                                                                                        ORDER BY u.lname ASC, u.fname ASC");
                                                                    mysqli_stmt_bind_param($getCaseloads, "ii", $period_id, $category_id);
                                                                    if (mysqli_stmt_execute($getCaseloads))
                                                                    {
                                                                        $getCaseloadsResults = mysqli_stmt_get_result($getCaseloads);
                                                                        if (mysqli_num_rows($getCaseloadsResults) > 0) // caseloads found
                                                                        {
                                                                            while ($caseloads = mysqli_fetch_array($getCaseloadsResults))
                                                                            {
                                                                                // store caseload details locally
                                                                                $new_caseload_id = $caseloads["caseload_id"];

                                                                                // get caseload display name
                                                                                $caseload_name = getCaseloadDisplayName($conn, $new_caseload_id);

                                                                                // create the option
                                                                                echo "<option value='".$new_caseload_id."'>".$caseload_name."</option>";
                                                                            }
                                                                        }
                                                                    }

                                                                    // close the option group
                                                                    echo "</optgroup>";
                                                                }
                                                            }
                                                        ?>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center mt-3">
                                            <!-- Start Date -->
                                            <div class="form-group col-5">
                                                <label for="transfer-transfer_date"><span class="required-field">*</span> Transfer Date:</label>
                                                <input type="text" class="form-control w-100" id="transfer-transfer_date" name="transfer-transfer_date" value="<?php echo $term_start; ?>" autocomplete="off" aria-describedby="dateHelpBlock" required>
                                            </div>

                                            <!-- Divider -->
                                            <div class="form-group col-1 p-0"></div>
                                            
                                            <!-- End Date -->
                                            <div class="form-group col-5">
                                                <label for="transfer-end_date"><span class="required-field">*</span> End Date:</label>
                                                <input type="text" class="form-control w-100" id="transfer-end_date" name="transfer-end_date" value="<?php echo $term_end; ?>" autocomplete="off" required>
                                            </div>
                                        </div>
                                        <div class="form-row d-flex justify-content-center align-items-center mb-3 px-3">
                                            <div id="dateHelpBlock" class="form-text">
                                                The "transfer date" will become the end date from the caseload we are transferring from, 
                                                and become the start date for the caseload we are transferring into. The transfer date
                                                must be a date on or after the term start date of <?php echo $term_start; ?>.
                                            </div>
                                        </div>

                                        <div class="form-row d-flex justify-content-center align-items-center my-3">
                                            <!-- Remove IEP meeting -->
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="transfer-remove_iep" name="transfer-remove_iep">
                                                <label class="form-check-label" for="transfer-remove_iep">Remove IEP meeting units (-12)?</label>
                                            </div>
                                        </div>

                                        <!-- Required Field Indicator -->
                                        <div class="row justify-content-center">
                                            <div class="col-11 text-center fst-italic">
                                                <span class="required-field">*</span> indicates a required field
                                            </div>
                                        </div>
                                    </div>

                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-primary" onclick="transferCases(<?php echo $caseload_id; ?>);"><i class="fa-solid fa-right-left"></i> Transfer Caseload</button>
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- End Transfer Cases Modal -->
                    <?php
                }
            }
        }

        // disconnect from the database
        mysqli_close($conn);
    }
?>