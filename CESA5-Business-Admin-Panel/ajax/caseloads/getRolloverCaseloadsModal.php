<?php
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get additional required files
        include("../../includes/functions.php");
        include("../../includes/config.php");

        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // get period name from POST
            if (isset($_POST["period"]) && $_POST["period"] <> "") { $period = $_POST["period"]; } else { $period = null; }

            if ($period != null && $period_id = getPeriodID($conn, $period))
            {
                // initialize an array to store all periods; then get all periods and store in the array
                $periods = [];
                $getPeriods = mysqli_query($conn, "SELECT id, name, active, start_date, end_date, caseload_term_start, caseload_term_end FROM `periods` ORDER BY active DESC, name ASC");
                if (mysqli_num_rows($getPeriods) > 0) // periods exist
                {
                    while ($periodDetails = mysqli_fetch_array($getPeriods))
                    {
                        // store period's data in array
                        $periods[] = $periodDetails;

                        // store the acitve period's name
                        if ($periodDetails["active"] == 1) 
                        { 
                            $active_period_label = $periodDetails["name"];
                            $active_start_date = date("m/d/Y", strtotime($periodDetails["start_date"]));
                            $active_end_date = date("m/d/Y", strtotime($periodDetails["end_date"])); 
                            $active_caseload_term_start_date = date("m/d/Y", strtotime($periodDetails["caseload_term_start"]));
                            $active_caseload_term_end_date = date("m/d/Y", strtotime($periodDetails["caseload_term_end"]));
                        }
                    }
                }

                ?>
                    <!-- Rollover Caseloads Modal -->
                    <div class="modal fade" tabindex="-1" role="dialog" id="rolloverCaseloadsModal" data-bs-backdrop="static" aria-labelledby="rolloverCaseloadsModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg" role="document">
                            <div class="modal-content">
                                <div class="modal-header primary-modal-header">
                                    <h5 class="modal-title primary-modal-title" id="rolloverCaseloadsModalLabel">Rollover Caseloads</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="form-row d-flex justify-content-center align-items-center mb-3 px-3">
                                        <p class="m-0">
                                            When rolling over caseloads from one period to another, we will assume all students in the following year will be enrolled from the term start date to term end date.
                                            We will also assume that the students will be beginning with their frequency and units of service from the end of their prior year. All additional IEP meetings and 
                                            evaluations will be removed. We will also not rollover any changes to the students within the caseload.
                                        </p>
                                    </div>

                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Period From -->
                                        <div class="form-group col-5">
                                            <label for="rollover-period_from"><span class="required-field">*</span> Period From:</label>
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                </div>
                                                <select class="form-select" id="rollover-period_from" name="rollover-period_from" required>
                                                    <option></option>
                                                    <?php 
                                                        for ($p = 0; $p < count($periods); $p++)
                                                        {
                                                            if ($periods[$p]["id"] == $period_id)
                                                            {
                                                                echo "<option value='".$periods[$p]["id"]."' selected>".$periods[$p]["name"]."</option>";
                                                            }
                                                            else
                                                            {
                                                                echo "<option value='".$periods[$p]["id"]."'>".$periods[$p]["name"]."</option>";
                                                            }
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Spacer -->
                                        <div class="form-group col-1"></div>

                                        <!-- Period To -->
                                        <div class="form-group col-5">
                                            <label for="rollover-period_to"><span class="required-field">*</span> Period To:</label>
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                </div>
                                                <select class="form-select" id="rollover-period_to" name="rollover-period_to" required>
                                                    <option></option>
                                                    <?php 
                                                        for ($p = 0; $p < count($periods); $p++)
                                                        {
                                                            echo "<option value='".$periods[$p]["id"]."'>".$periods[$p]["name"]."</option>";
                                                        }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-row d-flex justify-content-center align-items-center my-3">
                                        <!-- Start Date -->
                                        <div class="form-group col-5">
                                            <label for="rollover-start_date"><span class="required-field">*</span> Start Date:</label>
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-day"></i></span>
                                                </div>
                                                <input type="text" class="form-control" style="z-index: 9999;" id="rollover-start_date" name="rollover-start_date" autocomplete="off" required>
                                            </div>
                                        </div>

                                        <!-- Spacer -->
                                        <div class="form-group col-1"></div>

                                        <!-- End Date -->
                                        <div class="form-group col-5">
                                            <label for="rollover-end_date"><span class="required-field">*</span> End Date:</label>
                                            <div class="input-group w-100 h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-day"></i></span>
                                                </div>
                                                <input type="text" class="form-control" style="z-index: 9999;" id="rollover-end_date" name="rollover-end_date" autocomplete="off" required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="">
                                        <table id="rollover-caseloads" class="report_table">
                                            <thead>
                                                <tr>
                                                    <th></th>
                                                    <th></th>
                                                    <th style="font-size: 14px !important;">Caseload</th>
                                                    <th style="font-size: 14px !important;"># of <?php echo $period; ?> Students</th>
                                                </tr>
                                            </thead>
                                        </table>
                                        <div class="row table-footer d-flex align-items-center justify-content-end m-0 p-2">
                                            <button class="btn btn-secondary w-auto mx-1" type="button" onclick="selectAll('rollover-caseloads');">
                                                Select All
                                            </button>

                                            <button class="btn btn-danger w-auto mx-1" type="button" onclick="deselectAll('rollover-caseloads');">
                                                Deselect All
                                            </button>
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
                                    <button type="button" class="btn btn-primary" onclick="rolloverCaseloads();"><i class="fa-solid fa-right-left"></i> Rollover Caseloads</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="fa-solid fa-xmark"></i> Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Rollover Caseloads Modal -->
                <?php
            }
            
            // disconnect from the database
            mysqli_close($conn);
        }
    }
?>