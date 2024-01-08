<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) || isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"]))
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // initialize an array to store all periods; then get all periods and store in the array
            $periods = [];
            $getPeriods = mysqli_query($conn, "SELECT id, name, active, start_date, end_date, caseload_term_start, caseload_term_end FROM `periods` ORDER BY active DESC, name ASC");
            if (mysqli_num_rows($getPeriods) > 0) // periods exist
            {
                while ($period = mysqli_fetch_array($getPeriods))
                {
                    // store period's data in array
                    $periods[] = $period;

                    // store the active period's name
                    if ($period["active"] == 1) 
                    { 
                        $active_period_label = $period["name"];
                        $active_start_date = date("m/d/Y", strtotime($period["start_date"]));
                        $active_end_date = date("m/d/Y", strtotime($period["end_date"])); 
                        $active_caseload_term_start_date = date("m/d/Y", strtotime($period["caseload_term_start"]));
                        $active_caseload_term_end_date = date("m/d/Y", strtotime($period["caseload_term_end"]));
                    }
                }
            }

            // get the caseload ID for the caseload we are viewing, and period ID we are viewing the caseload in
            $caseload_id = null;
            if (isset($_POST["caseload_id"]) && trim($_POST["caseload_id"]) <> "") { $caseload_id = trim($_POST["caseload_id"]); } else { $caseload_id = null; }
            if (isset($_POST["period_id"]) && trim($_POST["period_id"]) <> "") { $period_id = trim($_POST["period_id"]); } else { $period_id = null; }

            // regular caseload
            $export_title = ""; // initialize export title
            if ($caseload_id > 0 && verifyCaseload($conn, $caseload_id))
            {
                // get the caseload details
                $caseload_name = getCaseloadDisplayName($conn, $caseload_id);
                $therapist_id = getCaseloadTherapist($conn, $caseload_id);
                $therapist_name = getUserDisplayName($conn, $therapist_id);
                $category_id = getCaseloadCategory($conn, $caseload_id);
                $category_name = getCaseloadCategoryName($conn, $category_id);
                $subcategory_id = getCaseloadSubcategory($conn, $caseload_id);
                $subcategory_name = getCaseloadSubcategoryName($conn, $subcategory_id);

                // build the export title
                $export_title = $caseload_name;
            }
            // demo caseload
            else if ($caseload_id < 0)
            {
                // get the category name
                $category_id = abs($caseload_id);
                $category_name = getCaseloadCategoryName($conn, $category_id);

                // build the caseload name
                $caseload_name = "<i class=\"fa-solid fa-helmet-safety\"></i> ".$category_name." <i>(DEMO)</i>";

                // build the export title
                $export_title = $category_name." (DEMO)";
            }

            // get caseload category settings
            $frequencyEnabled = isCaseloadFrequencyEnabled($conn, $caseload_id);
            $uosEnabled = isCaseloadUOSEnabled($conn, $caseload_id);
            $daysEnabled = isCaseloadDaysEnabled($conn, $caseload_id);
            $isClassroom = isCaseloadClassroom($conn, $caseload_id);
            $medicaid = isCaseloadMedicaid($conn, $caseload_id);
            $allowAssistants = isCaseloadAssistantsEnabled($conn, $caseload_id);

            if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) || isCaseloadAssigned($conn, $_SESSION["id"], $caseload_id) || isCoordinatorAssigned($conn, $_SESSION["id"], $caseload_id))
            {
                ?>
                    <!-- Page Styling Override -->
                    <style>
                        #caseloads tbody td, #caseloads-startReport tbody td, #caseloads-endReport tbody td
                        {
                            font-size: 16px !important;
                        }

                        .selectize-dropdown
                        {
                            z-index: 10000 !important;
                        }

                        .selectize-dropdown .selected
                        {
                            background-color: #f05323 !important;
                        }
                        
                        .selectize-dropdown .option:hover
                        {
                            background-color: #f0532399 !important;
                        }

                        /* date picker hover */
                        .ui-state-hover,
                        .ui-widget-content .ui-state-hover,
                        .ui-widget-header .ui-state-hover,
                        .ui-state-focus,
                        .ui-widget-content .ui-state-focus,
                        .ui-widget-header .ui-state-focus 
                        {
                            background: #f05323CC;
                            color: #ffffff;
                            font-weight: 600;
                        }

                        /* date picker active */
                        .ui-state-active,
                        .ui-widget-content .ui-state-active,
                        .ui-widget-header .ui-state-active {
                            border: 1px solid #fbd850;
                            background: #f05323;
                            font-weight: 600;
                            color: #ffffff;
                        }

                        .dt-buttons
                        {
                            width: 100% !important;
                        }
                    </style>

                    <script>
                        /** function to get the modal to add a student to the caseload */
                        function getAddStudentToCaseloadModal()
                        {
                            // get the fixed period name
                            let period = document.getElementById("fixed-period").value;
                            let caseload_id = document.getElementById("fixed-caseload_id").value;

                            // send the data to create the delete student modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getAddStudentToCaseloadModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the edit caseload modal
                                    document.getElementById("add-caseload-modal-div").innerHTML = this.responseText;     
                                    $("#addCaseModal").modal("show");

                                    // initialize tooltips
                                    $("[data-bs-toggle=\"tooltip\"]").tooltip();

                                    // initialize date pickers
                                    $("#add-date_of_birth").datepicker({
                                        changeMonth: true,
                                        changeYear: true,
                                    }).css("z-index", 9999);
                                    $("#add-start_date").datepicker({
                                        changeMonth: true,
                                        changeYear: true,
                                    }).css("z-index", 9999);
                                    $("#add-end_date").datepicker({
                                        changeMonth: true,
                                        changeYear: true,
                                    }).css("z-index", 9999);
                                    $("#add-eval_date").datepicker({
                                        changeMonth: true,
                                        changeYear: true,
                                    }).css("z-index", 9999);

                                    // initialize select dropdowns
                                    $("#add-student_id").selectize().css("z-index", 10000);
                                    $("#add-assistant_id").selectize().css("z-index", 10000);
                                    $("#add-residency").selectize().css("z-index", 10000);
                                    $("#add-district").selectize().css("z-index", 10000);
                                }
                            };
                            xmlhttp.send("period="+period+"&caseload_id="+caseload_id);
                        }

                        /** function to clear the current student selected */
                        function clearStudentSelected(origin)
                        {
                            $("#"+origin+"-student_id")[0].selectize.clear();
                        }

                        /** function to clear the current therapist selected */
                        function clearAssistantSelected(origin)
                        {
                            $("#"+origin+"-assistant_id")[0].selectize.clear();
                            $("#"+origin+"-assistant_id")[0].val(-1);
                        }

                        /** function to show the add a new student div in the add student to caseload modal */
                        function showAddNewStudent(origin, value)
                        {
                            if (value == 0)
                            {
                                // show the new student container
                                document.getElementById(origin+"-new_student-div").classList.remove("d-none");
                                document.getElementById(origin+"-student_button").innerHTML = "<i class='fa-solid fa-minus'></i>";
                                document.getElementById(origin+"-student_button").value = 1;

                                // clear the student dropdown
                                clearStudentSelected("add");
                            }
                            else
                            {
                                document.getElementById(origin+"-new_student-div").classList.add("d-none");
                                document.getElementById(origin+"-student_button").innerHTML = "<i class='fa-solid fa-plus'></i>";
                                document.getElementById(origin+"-student_button").value = 0;
                            }
                        }

                        /** function to add a new student */
                        function addCase()
                        {
                            // get form elements
                            let form_existing_student = document.getElementById("add-existing_student-form");
                            let form_new_student = document.getElementById("add-new_student-form");
                            let form_case_details = document.getElementById("add-case_details-form")

                            // get student ID
                            let student_id = document.getElementById("add-student_id").value;

                            if (((student_id == "" || student_id == null || student_id == undefined) && !form_new_student.checkValidity()) || !form_case_details.checkValidity())
                            {
                                if ((student_id == "" || student_id == null || student_id == undefined) && !form_new_student.checkValidity())
                                {
                                    document.getElementById("add-student_id-feedback").classList.add("d-inline");
                                }
                                form_existing_student.classList.add('was-validated');
                                form_new_student.classList.add('was-validated');
                                form_case_details.classList.add('was-validated');
                            }
                            else
                            {
                                // get the fixed period name
                                let period = document.getElementById("fixed-period").value;

                                // get student data
                                let student_id = document.getElementById("add-student_id").value;
                                let student_fname = document.getElementById("add-fname").value;
                                let student_lname = document.getElementById("add-lname").value;
                                let student_dob = document.getElementById("add-date_of_birth").value;

                                // get caseload information form fields
                                let caseload_id = document.getElementById("add-caseload_id").value;
                                let start_date = document.getElementById("add-start_date").value;
                                let end_date = document.getElementById("add-end_date").value;
                                let eval_date = document.getElementById("add-eval_date").value;
                                let eval_month = document.getElementById("add-eval_month").value;
                                let medicaid_billing = document.getElementById("add-medicaid_billing").value;
                                let eval_only_reason = document.getElementById("add-eval_only-reason").value;
                                let assistant_id = document.getElementById("add-assistant_id").value;
                                let residency = document.getElementById("add-residency").value;
                                let district = document.getElementById("add-district").value;
                                let school = document.getElementById("add-school").value;
                                let grade_level = document.getElementById("add-grade_level").value;
                                let evaluation_method = document.getElementById("add-evaluation_method").value;
                                let enrollment_type = document.getElementById("add-enrollment_type").value;
                                let educational_plan = document.getElementById("add-educational_plan").value;
                                let frequency = document.getElementById("add-frequency").value;
                                let units = document.getElementById("add-uos").value;
                                let bill_to = document.getElementById("add-bill_to").value;
                                let billing_type = document.getElementById("add-billing_type").value;
                                let billing_notes = document.getElementById("add-billing_notes").value;
                                let extra_ieps = document.getElementById("add-extra_ieps").value;
                                let extra_evals = document.getElementById("add-extra_evals").value;
                                let membership_days = document.getElementById("add-membership_days").value;
                                let status = document.getElementById("add-status").value;
                                let classroom_id = document.getElementById("add-classroom").value;

                                // build the string of data to send
                                let sendString = "period="+period+"&caseload_id="+caseload_id+"&student_id="+student_id+"&student_fname="+student_fname+"&student_lname="+student_lname+"&student_dob="+student_dob+"&start_date="+start_date+"&end_date="+end_date+"&eval_date="+eval_date+"&residency="+residency+"&district="+district+"&school="+school+"&grade_level="+grade_level+"&evaluation_method="+evaluation_method+"&enrollment_type="+enrollment_type+"&educational_plan="+educational_plan+"&SOY-frequency="+frequency+"&SOY-UOS="+units+"&billing-to="+bill_to+"&billing-type="+billing_type+"&billing-notes="+billing_notes+"&extra-ieps="+extra_ieps+"&extra-evals="+extra_evals+"&status="+status+"&membership_days="+membership_days+"&assistant_id="+assistant_id+"&eval_month="+eval_month+"&classroom_id="+classroom_id+"&eval_only_reason="+eval_only_reason+"&medicaid_billing="+medicaid_billing;

                                // send the data to process the add student request
                                var xmlhttp = new XMLHttpRequest();
                                xmlhttp.open("POST", "ajax/caseloads/addCase.php", true);
                                xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                                xmlhttp.onreadystatechange = function() 
                                {
                                    if (this.readyState == 4 && this.status == 200)
                                    {
                                        // create the status modal
                                        let status_title = "Add Student To Caseload Status";
                                        let status_body = this.responseText;
                                        createStatusModal("refresh", status_title, status_body);

                                        // hide the current modal
                                        $("#addCaseModal").modal("hide");
                                    }
                                };
                                xmlhttp.send(sendString);
                            }
                        }

                        /** function to get the modal to edit an existing caseload */
                        function getEditCaseModal(id)
                        {
                            // send the data to create the delete student modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getEditCaseModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the edit caseload modal
                                    document.getElementById("edit-caseload-modal-div").innerHTML = this.responseText;     
                                    $("#editCaseModal").modal("show");

                                    // initialize tooltips
                                    $("[data-bs-toggle=\"tooltip\"]").tooltip();

                                    // initialize date pickers
                                    $("#edit-date_of_birth").datepicker({
                                        changeMonth: true,
                                        changeYear: true,
                                    }).css("z-index", 9999);
                                    $("#edit-start_date").datepicker({
                                        changeMonth: true,
                                        changeYear: true,
                                    }).css("z-index", 9999);
                                    $("#edit-end_date").datepicker({
                                        changeMonth: true,
                                        changeYear: true,
                                    }).css("z-index", 9999);
                                    $("#edit-eval_date").datepicker({
                                        changeMonth: true,
                                        changeYear: true,
                                    }).css("z-index", 9999);

                                    // initialize select dropdowns
                                    $("#edit-assistant_id").selectize();
                                    $("#edit-residency").selectize();
                                    $("#edit-district").selectize();
                                }
                            };
                            xmlhttp.send("case_id="+id);
                        }

                        /** function to edit an existing caseload */
                        function editCase(case_id)
                        {
                            // get the form
                            let form = document.getElementById("edit-case_details-form");
                            if (!form.checkValidity())
                            {
                                form.classList.add('was-validated');
                            }
                            else
                            {
                                // get the fixed period name
                                let period = document.getElementById("fixed-period").value;

                                // get student information from form fields
                                let student_fname = document.getElementById("edit-fname").value;
                                let student_lname = document.getElementById("edit-lname").value;
                                let student_dob = document.getElementById("edit-date_of_birth").value;

                                // get caseload information form fields
                                let start_date = document.getElementById("edit-start_date").value;
                                let end_date = document.getElementById("edit-end_date").value;
                                let eval_date = document.getElementById("edit-eval_date").value;
                                let eval_month = document.getElementById("edit-eval_month").value;
                                let medicaid_billing = document.getElementById("edit-medicaid_billing").value;
                                let eval_only_reason = document.getElementById("edit-eval_only-reason").value;
                                let assistant_id = document.getElementById("edit-assistant_id").value;
                                let residency = document.getElementById("edit-residency").value;
                                let district = document.getElementById("edit-district").value;
                                let school = document.getElementById("edit-school").value;
                                let grade_level = document.getElementById("edit-grade_level").value;
                                let evaluation_method = document.getElementById("edit-evaluation_method").value;
                                let enrollment_type = document.getElementById("edit-enrollment_type").value;
                                let educational_plan = document.getElementById("edit-educational_plan").value;
                                let frequency = document.getElementById("edit-frequency").value;
                                let units = document.getElementById("edit-uos").value;
                                let bill_to = document.getElementById("edit-bill_to").value;
                                let billing_type = document.getElementById("edit-billing_type").value;
                                let billing_notes = document.getElementById("edit-billing_notes").value;
                                let membership_days = document.getElementById("edit-membership_days").value;
                                let status = document.getElementById("edit-status").value;
                                let classroom_id = document.getElementById("edit-classroom").value;

                                // build the string of data to send
                                let sendString = "period="+period+"&case_id="+case_id+"&student_fname="+student_fname+"&student_lname="+student_lname+"&student_dob="+student_dob+"&start_date="+start_date+"&end_date="+end_date+"&eval_date="+eval_date+"&residency="+residency+"&district="+district+"&school="+school+"&grade_level="+grade_level+"&evaluation_method="+evaluation_method+"&enrollment_type="+enrollment_type+"&educational_plan="+educational_plan+"&SOY-frequency="+frequency+"&SOY-UOS="+units+"&billing-to="+bill_to+"&billing-type="+billing_type+"&billing-notes="+billing_notes+"&status="+status+"&assistant_id="+assistant_id+"&eval_month="+eval_month+"&membership_days="+membership_days+"&classroom_id="+classroom_id+"&eval_only_reason="+eval_only_reason+"&medicaid_billing="+medicaid_billing;

                                // send the data to process the add student request
                                var xmlhttp = new XMLHttpRequest();
                                xmlhttp.open("POST", "ajax/caseloads/editCase.php", true);
                                xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                                xmlhttp.onreadystatechange = function() 
                                {
                                    if (this.readyState == 4 && this.status == 200)
                                    {
                                        // create the status modal
                                        let status_title = "Edit Case Status";
                                        let status_body = this.responseText;
                                        createStatusModal("refresh", status_title, status_body);

                                        // hide the current modal
                                        $("#editCaseModal").modal("hide");
                                    }
                                };
                                xmlhttp.send(sendString);
                            }
                        }

                        /** function to get the delete caseload modal */
                        function getDeleteCaseModal(id)
                        {
                            // send the data to create the delete caseload modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getDeleteCaseModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the delete caseload modal
                                    document.getElementById("delete-caseload-modal-div").innerHTML = this.responseText;     
                                    $("#deleteCaseModal").modal("show");
                                }
                            };
                            xmlhttp.send("case_id="+id);
                        }
                        
                        /** function to delete the caseload */
                        function deleteCase(id)
                        {
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/deleteCase.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Remove Student From Caseload Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#deleteCaseModal").modal("hide");
                                }
                            };
                            xmlhttp.send("case_id="+id);
                        }

                        /** function to get the modal to view changes made for an existing caseload */
                        function getViewCaseChangesModal(id)
                        {
                            // send the data to create the view caseload changes modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getViewCaseChangesModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the view caseload changes modal
                                    document.getElementById("view-caseload-modal-div").innerHTML = this.responseText;     
                                    $("#viewCaseloadChangesModal").modal("show");
                                }
                            };
                            xmlhttp.send("case_id="+id);
                        }

                        /** function to get the modal to add a chagne to an existing caseload */
                        function getAddCaseChangeModal(id)
                        {
                            // send the data to create the delete caseload modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getAddCaseChangeModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the add caseload change modal
                                    document.getElementById("add-caseload_change-modal-div").innerHTML = this.responseText; 
                                    $("#addCaseChangeModal").modal("show");

                                    // initialize date fields
                                    $(function() {
                                        $("#add-case_changes-change_date").datepicker();
                                    });

                                    // hide the view caseload modal
                                    $("#viewCaseloadChangesModal").modal("hide");
                                }
                            };
                            xmlhttp.send("case_id="+id);
                        }

                        /** function to add a change to an existing caseload */
                        function addCaseChange(id)
                        {
                            // get the form fields
                            let date = document.getElementById("add-case_changes-change_date").value;
                            let frequency = document.getElementById("add-case_changes-frequency").value;
                            let units = document.getElementById("add-case_changes-uos").value;

                            // get IEP status
                            let iep_status = 0;
                            if ($("#add-additional_iep").is(":checked")) { iep_status = 1; }

                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/addCaseChange.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Add Caseload Change Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#addCaseChangeModal").modal("hide");
                                }
                            };
                            xmlhttp.send("case_id="+id+"&date="+date+"&frequency="+frequency+"&units="+units+"&iep_meeting="+iep_status);
                        }

                        /** function to get the modal to view changes made for an existing caseload */
                        function getEditCaseChangeModal(id)
                        {
                            // send the data to create the view caseload changes modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getEditCaseChangeModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the view caseload changes modal
                                    document.getElementById("edit-caseload_change-modal-div").innerHTML = this.responseText;     
                                    $("#editCaseChangeModal").modal("show");

                                    // initialize date fields
                                    $(function() {
                                        $("#edit-case_changes-change_date").datepicker();
                                    });

                                    // hide the view caseload modal
                                    $("#viewCaseloadChangesModal").modal("hide");
                                }
                            };
                            xmlhttp.send("change_id="+id);
                        }

                        /** function to edit a caseload's change */
                        function editCaseChange(id)
                        {
                            // get the form fields
                            let date = document.getElementById("edit-case_changes-change_date").value;
                            let frequency = document.getElementById("edit-case_changes-frequency").value;
                            let units = document.getElementById("edit-case_changes-uos").value;

                            // get IEP status
                            let iep_status = 0;
                            if ($("#edit-additional_iep").is(":checked")) { iep_status = 1; }

                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/editCaseChange.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Edit Case Change Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the current modal
                                    $("#editCaseChangeModal").modal("hide");
                                }
                            };
                            xmlhttp.send("change_id="+id+"&date="+date+"&frequency="+frequency+"&units="+units+"&iep_meeting="+iep_status);
                        }

                        /** function to remove a caseload's change */
                        function removeCaseChange(id)
                        {
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/removeCaseChange.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Remove Caseload Change Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the view caseload modal
                                    $("#viewCaseloadChangesModal").modal("hide");
                                }
                            };
                            xmlhttp.send("change_id="+id);
                        }

                        /** function to update the status element */
                        function updateStatus(id)
                        {
                            // get current status of the element
                            let element = document.getElementById(id);
                            let status = element.value;

                            if (status == 0) // currently set to inactive
                            {
                                // update status to active
                                element.value = 1;
                                element.innerHTML = "Active";
                                element.classList.remove("btn-danger");
                                element.classList.add("btn-success");
                            }
                            else // currently set to active, or other?
                            {
                                // update status to inactive
                                element.value = 0;
                                element.innerHTML = "Inactive";
                                element.classList.remove("btn-success");
                                element.classList.add("btn-danger");
                            }
                        }

                        /** function to check the evaluation method selected */
                        function checkEvaluationMethod(value, origin)
                        {
                            if (value == 1 || value == 2)
                            {
                                document.getElementById(origin+"-caseload_details").classList.remove("d-none");

                                if (value == 1)
                                {
                                    document.getElementById(origin+"-eval_only_reasoning-div").classList.remove("d-flex");
                                    document.getElementById(origin+"-eval_only_reasoning-div").classList.add("d-none");

                                    document.getElementById(origin+"-caseload_details-regular").classList.remove("d-none");
                                    document.getElementById(origin+"-caseload_details-regular").classList.add("d-flex");

                                    document.getElementById(origin+"-caseload_details-evaluation_only").classList.add("d-none");
                                    document.getElementById(origin+"-caseload_details-evaluation_only").classList.remove("d-flex");

                                    document.getElementById(origin+"-caseload_details-regular-extra").classList.remove("d-none");

                                    // add required field attribute
                                    document.getElementById(origin+"-frequency").required = true;
                                    document.getElementById(origin+"-uos").required = true;
                                }
                                else if (value == 2)
                                {
                                    document.getElementById(origin+"-eval_only_reasoning-div").classList.remove("d-none");
                                    document.getElementById(origin+"-eval_only_reasoning-div").classList.add("d-flex");

                                    document.getElementById(origin+"-caseload_details-evaluation_only").classList.remove("d-none");
                                    document.getElementById(origin+"-caseload_details-evaluation_only").classList.add("d-flex");

                                    document.getElementById(origin+"-caseload_details-regular").classList.add("d-none");
                                    document.getElementById(origin+"-caseload_details-regular").classList.remove("d-flex");

                                    document.getElementById(origin+"-caseload_details-regular-extra").classList.add("d-none");

                                    // remove required field attribute
                                    document.getElementById(origin+"-frequency").required = false;
                                    document.getElementById(origin+"-uos").required = false;
                                }
                            } 
                            /*  
                            else
                            {
                                document.getElementById(origin+"-caseload_details").classList.add("d-none");
                            }
                            */
                        }

                        /** function to check the billing type */
                        function checkBillingType(value, origin)
                        {
                            if (value == 2)
                            {
                                document.getElementById(origin+"-caseload_details-regular-extra").classList.add("d-none");
                                document.getElementById(origin+"-caseload_details-regular-day_use").classList.remove("d-none");
                            }
                            else if (value == 1)
                            {
                                document.getElementById(origin+"-caseload_details-regular-extra").classList.remove("d-none");
                                document.getElementById(origin+"-caseload_details-regular-day_use").classList.add("d-none");
                            }
                            else
                            {
                                document.getElementById(origin+"-caseload_details-regular-extra").classList.remove("d-none");
                                document.getElementById(origin+"-caseload_details-regular-day_use").classList.remove("d-none");
                                document.getElementById(origin+"-caseload_details-regular-extra").classList.add("d-none");
                                document.getElementById(origin+"-caseload_details-regular-day_use").classList.add("d-none");
                            }
                        }

                        /** function to get the age of a student */
                        function getAge(dateOfBirth)
                        {
                            var today = new Date();
                            var birthDate = new Date(dateOfBirth);
                            var age = today.getFullYear() - birthDate.getFullYear();
                            var month = today.getMonth() - birthDate.getMonth();
                            if (month < 0 || (month === 0 && today.getDate() < birthDate.getDate()))
                            {
                                age--;
                            }
                            return age;
                        }

                        /** function to display the student's age */
                        function updateAge(value, origin)
                        {
                            let age = getAge(value);
                            document.getElementById(origin+"-age").value = age;
                        }

                        /** function to create and display the modal to request a caseload transfer */
                        function getRequestCaseloadTransferModal(case_id)
                        {
                            // get the fixed period name
                            let period = document.getElementById("fixed-period").value;

                            // send the data to create the view caseload changes modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getRequestCaseloadTransferModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the view caseload changes modal
                                    document.getElementById("request_caseload_transfer-modal-div").innerHTML = this.responseText;     
                                    $("#requestCaseloadTransferModal").modal("show");

                                    // initialize date fields
                                    $(function() {
                                        $("#transfer_request-transfer_date").datepicker();
                                        $("#transfer_request-new_caseload").selectize();
                                    });
                                }
                            };
                            xmlhttp.send("case_id="+case_id+"&period="+period);
                        }

                        /** function to request to transfer a student from the user's caseload to another caseload */
                        function requestCaseloadTransfer(case_id)
                        {
                            // get form parameters
                            let input_case_id = document.getElementById("transfer_request-case_id").value;
                            let new_caseload = document.getElementById("transfer_request-new_caseload").value;
                            let transfer_date = document.getElementById("transfer_request-transfer_date").value;
                            let comments = document.getElementById("transfer_request-comments").value;

                            // get IEP status
                            let iep_status = 0;
                            if ($("#transfer_request-IEP_status").is(":checked")) { iep_status = 1; }

                            // build the string of data to send
                            let sendString = "case_id="+case_id+"&new_caseload="+new_caseload+"&transfer_date="+transfer_date+"&comments="+encodeURIComponent(comments)+"&IEP_status="+iep_status;
                            
                            if (input_case_id == case_id)
                            {
                                // send the data to create the view caseload changes modal
                                var xmlhttp = new XMLHttpRequest();
                                xmlhttp.open("POST", "ajax/caseloads/requestCaseloadTransfer.php", true);
                                xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                                xmlhttp.onreadystatechange = function() 
                                {
                                    if (this.readyState == 4 && this.status == 200)
                                    {
                                        // create the status modal
                                        let status_title = "Transfer Caseload Request Status";
                                        let status_body = this.responseText;
                                        createStatusModal("refresh", status_title, status_body);

                                        // hide the current modal
                                        $("#requestCaseloadTransferModal").modal("hide");
                                    }
                                };
                                xmlhttp.send(sendString);
                            }
                            else
                            {
                                // create the status modal
                                let status_title = "Transfer Caseload Request Status";
                                let status_body = "Failed to request to transfer student out of your caseload. An unexpected error has occurred! Please try again later.<br>";
                                createStatusModal("refresh", status_title, status_body);

                                // hide the current modal
                                $("#requestCaseloadTransferModal").modal("hide");
                            }
                        }

                        /** function to populate the school dropdown list */
                        function getSchoolsForDistrict(district_id, origin)
                        {
                            let schools = $.ajax({
                                type: "POST",
                                url: "ajax/misc/getSchoolsForDistrictDropdown.php",
                                data: {
                                    district_id: district_id
                                },
                                async: false,
                            }).responseText;

                            // add school dropdown options
                            document.getElementById(origin+"-school").innerHTML = schools;
                        }

                        /** function to get the modal to dismiss a student from a caseload */
                        function getDismissStudentModal(id)
                        {
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getDismissStudentModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the dismiss student modal
                                    document.getElementById("dismiss_student-modal-div").innerHTML = this.responseText;     
                                    $("#dismissStudentModal").modal("show");

                                    // hide the view case changes modal
                                    $("#viewCaseloadChangesModal").modal("hide");

                                    // initialize date picker
                                    $("#dismiss_student-dismissal_date").datepicker();
                                }
                            };
                            xmlhttp.send("case_id="+id);
                        }

                        /** function to dismiss a student from a caseload */
                        function dismissStudent(case_id)
                        {
                            // get form parameters
                            let dismissal_date = document.getElementById("dismiss_student-dismissal_date").value;
                            let reason_id = document.getElementById("dismiss_student-reason").value;
                            let medicaid_billing = document.getElementById("dismiss_student-medicaid_billing").value;

                            // get dismissal IEP status
                            let dismissal_iep = 0;
                            if ($("#dismiss_student-additional_iep").is(":checked")) { dismissal_iep = 1; }

                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/dismissStudent.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Student Dismissal Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the dismiss student modal
                                    $("#dismissStudentModal").modal("hide");
                                }
                            }
                            xmlhttp.send("case_id="+case_id+"&dismissal_date="+dismissal_date+"&dismissal_iep="+dismissal_iep+"&reason_id="+reason_id+"&medicaid_billing="+medicaid_billing);
                        }

                        /** function to get the modal to edit a dismissal date */
                        function getEditDismissalModal(case_id)
                        {
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getEditDismissalModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the dismiss student modal
                                    document.getElementById("edit-dismiss_student-modal-div").innerHTML = this.responseText;     
                                    $("#editDismissStudentModal").modal("show");

                                    // hide the view case changes modal
                                    $("#viewCaseloadChangesModal").modal("hide");
                                }
                            };
                            xmlhttp.send("case_id="+case_id);
                        }

                        /** function to edit a student's dismissal */
                        function editStudentDismissal(case_id)
                        {
                            // get form parameters
                            let dismissal_date = document.getElementById("edit-dismiss_student-dismissal_date").value;
                            let reason_id = document.getElementById("edit-dismiss_student-reason").value;
                            let medicaid_billing_completed = document.getElementById("edit-dismiss_student-medicaid_billing").value;
                            let eval_month = document.getElementById("edit-dismiss_student-eval_month").value;

                            // get dismissal IEP status
                            let dismissal_iep = 0;
                            if ($("#edit-dismiss_student-additional_iep").is(":checked")) { dismissal_iep = 1; }

                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/editStudentDismissal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Edit Student Dismissal Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the edit dismiss student modal
                                    $("#editDismissStudentModal").modal("hide");
                                }
                            }
                            xmlhttp.send("case_id="+case_id+"&dismissal_date="+dismissal_date+"&dismissal_iep="+dismissal_iep+"&reason_id="+reason_id+"&eval_month="+eval_month+"&medicaid_billing_completed="+medicaid_billing_completed);
                        }

                        /** function to update the UOS adjustment for a case */
                        function updateUOSAdjustment(case_id, value)
                        {
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/updateUOSAdjustment.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {

                                }
                            }
                            xmlhttp.send("case_id="+case_id+"&uos="+value);
                        }

                        /** function to update the UOS adjustment for a case */
                        function updateExtraEvals(case_id, value)
                        {
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/updateExtraEvals.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {

                                }
                            }
                            xmlhttp.send("case_id="+case_id+"&extra_evals="+value);
                        }

                        /** function to update the UOS adjustment for a case */
                        function updateExtraIEPs(case_id, value)
                        {
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/updateExtraIEPs.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {

                                }
                            }
                            xmlhttp.send("case_id="+case_id+"&extra_ieps="+value);
                        }

                        /** function to get the modal to view which other caseloads the student is in */
                        function getViewStudentModal(case_id, student_id)
                        {
                            // get the fixed period name
                            let period = document.getElementById("fixed-period").value;

                            // send the data to create and display the modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getViewStudentModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the modal
                                    document.getElementById("view-student-modal-div").innerHTML = this.responseText;     
                                    $("#viewStudentModal").modal("show");
                                }
                            };
                            xmlhttp.send("student_id="+student_id+"&period="+period+"&case_id="+case_id);
                        }

                        /** function to toggle the page view */
                        function toggleView(type)
                        {
                            // hide both page views
                            document.getElementById("view-caseload-div").classList.add("d-none");
                            document.getElementById("view-startEndReport-div").classList.add("d-none");
                            document.getElementById("view-caseload-button").classList.remove("btn-primary");
                            document.getElementById("view-startEndReport-button").classList.remove("btn-primary");
                            document.getElementById("view-caseload-button").classList.add("btn-secondary");
                            document.getElementById("view-startEndReport-button").classList.add("btn-secondary");

                            // only hide medicaid tab if elements exists
                            if (document.getElementById("view-medicaid-div") && document.getElementById("view-medicaid-button")) 
                            {
                                document.getElementById("view-medicaid-div").classList.add("d-none");
                                document.getElementById("view-medicaid-button").classList.remove("btn-primary");
                                document.getElementById("view-medicaid-button").classList.add("btn-secondary");
                            }

                            // display and select the view toggled
                            document.getElementById("view-"+type+"-button").classList.add("btn-primary");
                            document.getElementById("view-"+type+"-div").classList.remove("d-none");

                            // if type is startEndReport, display secondary subpage buttons; otherwise, hide buttons
                            if (type == "startEndReport")
                            {
                                document.getElementById("view-startEndReport-buttons-div").classList.remove("d-none");
                            } else {
                                document.getElementById("view-startEndReport-buttons-div").classList.add("d-none");
                            }
                        }

                        /** function to toggle report view */
                        function toggleStartEndView(type)
                        {
                            // hide both report views
                            document.getElementById("view-startEndReport-startReport-div").classList.add("d-none");
                            document.getElementById("view-startEndReport-endReport-div").classList.add("d-none");
                            document.getElementById("view-startEndReport-startReport-button").classList.remove("btn-primary");
                            document.getElementById("view-startEndReport-endReport-button").classList.remove("btn-primary");
                            document.getElementById("view-startEndReport-startReport-button").classList.add("btn-secondary");
                            document.getElementById("view-startEndReport-endReport-button").classList.add("btn-secondary");

                            // display and select the view toggled
                            document.getElementById("view-startEndReport-"+type+"-button").classList.add("btn-primary");
                            document.getElementById("view-startEndReport-"+type+"-div").classList.remove("d-none");
                        }

                        /** function to get the modal to clear the caseload */
                        function getClearCaseloadModal()
                        {
                            // get the parameters
                            let period = document.getElementById("fixed-period").value;
                            let caseload = document.getElementById("fixed-caseload_id").value;

                            // send the data to create and display the modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getClearCaseloadModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the modal
                                    document.getElementById("clear-caseload-modal-div").innerHTML = this.responseText;     
                                    $("#clearCaseloadModal").modal("show");
                                }
                            };
                            xmlhttp.send("caseload_id="+caseload+"&period="+period);
                        }

                        /** function to clear all students from the caseload */
                        function clearCaseload()
                        {
                            // get the parameters
                            let period = document.getElementById("fixed-period").value;
                            let caseload = document.getElementById("fixed-caseload_id").value;

                            // send the request to clear the caseload
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/clearCaseload.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Clear Caseload Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the clear caseload modal
                                    $("#clearCaseloadModal").modal("hide");
                                }
                            }
                            xmlhttp.send("caseload_id="+caseload+"&period="+period);
                        }

                        /** function to get a student's grade level */
                        function getGradeLevel(student_id)
                        {
                            // get the parameters
                            let period = document.getElementById("fixed-period").value;

                            // send the request to clear the caseload
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getStudentGrade.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // store the grade
                                    let grade = this.responseText;
                                    document.getElementById("add-grade_level").value = grade;
                                }
                            }
                            xmlhttp.send("student_id="+student_id+"&period="+period);
                        }

                        /** function to get a student's locations */
                        function getLocations(student_id)
                        {
                            // get the parameters
                            let period = document.getElementById("fixed-period").value;

                            // send the request to clear the caseload
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getStudentLocations.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // store the grade
                                    let locations = JSON.parse(this.responseText);
                                    $("#add-residency").data("selectize").setValue(locations.residency, false);
                                    $("#add-district").data("selectize").setValue(locations.district_attending, false);
                                    $("#add-school").val(locations.school_attending);
                                }
                            }
                            xmlhttp.send("student_id="+student_id+"&period="+period);
                        }

                        /** function to toggle additional details */
                        function toggleDetails(value)
                        {
                            if (value == 1) // details are currently displayed; hide details
                            {
                                // hide div
                                document.getElementById("showDetails").value = 0;
                                document.getElementById("details-div").classList.add("d-none");
                            }
                            else // details are currently hidden; display details
                            {
                                // display div
                                document.getElementById("showDetails").value = 1;
                                document.getElementById("details-div").classList.remove("d-none");
                            }
                        }

                        /** function to toggle medicaid billing completed */
                        function toggleMedicaidBillingDone(case_id, checked)
                        {
                            // convert checked status to int
                            let done = 0;
                            if (checked === true) { 
                                done = 1;
                            } else {
                                done = 0;
                            }

                            // send the request 
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/updateMedicaidBillingDoneStatus.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {

                                }
                            }
                            xmlhttp.send("case_id="+case_id+"&status="+done);
                        }

                        /** function to toggle medicaid billed  */
                        function toggleMedicaidBilled(case_id, checked)
                        {
                            // convert checked status to int
                            let done = 0;
                            if (checked === true) { 
                                done = 1;
                            } else {
                                done = 0;
                            }

                            // send the request 
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/updatedMedicaidBilledStatus.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {

                                }
                            }
                            xmlhttp.send("case_id="+case_id+"&status="+done);
                        }

                        /** function to get the modal to undo caseload dismissals */
                        function getUndoCaseDismissalModal(case_id)
                        {
                            // send the data to create and display the modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/getUndoDismissalModal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // display the modal
                                    document.getElementById("undo-dismissal-modal-div").innerHTML = this.responseText;     
                                    $("#undoDismissalModal").modal("show");
                                }
                            };
                            xmlhttp.send("case_id="+case_id);
                        }

                        /** function to undo a dismissal */
                        function undoDismissal(case_id)
                        {
                            // send the data to create and display the modal
                            var xmlhttp = new XMLHttpRequest();
                            xmlhttp.open("POST", "ajax/caseloads/undoDismissal.php", true);
                            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xmlhttp.onreadystatechange = function() 
                            {
                                if (this.readyState == 4 && this.status == 200)
                                {
                                    // create the status modal
                                    let status_title = "Undo Dismissal Status";
                                    let status_body = this.responseText;
                                    createStatusModal("refresh", status_title, status_body);

                                    // hide the clear caseload modal
                                    $("#undoDismissalModal").modal("hide");
                                }
                            };
                            xmlhttp.send("case_id="+case_id);
                        }
                    </script>

                    <div class="report">
                        <div class="row report-body m-0">
                            <!-- Page Header -->
                            <div class="table-header sticky-top p-0">
                                <div class="row d-flex justify-content-center align-items-center text-center py-2 px-3">
                                    <!-- Period & Filters-->
                                    <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 p-0">
                                        <div class="row px-3">
                                            <!-- Period Selection -->
                                            <div class="col-9 p-0">
                                                <div class="input-group h-auto">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text h-100" id="nav-search-icon"><i class="fa-solid fa-calendar-days"></i></span>
                                                    </div>
                                                    <input id="fixed-period" type="hidden" value="" aria-hidden="true">
                                                    <select class="form-select" id="search-period" name="search-period" onchange="searchCaseloads();">
                                                        <?php
                                                            for ($p = 0; $p < count($periods); $p++)
                                                            {
                                                                if ($period_id != null && $periods[$p]["id"] == $period_id) { echo "<option value='".$periods[$p]["name"]."' selected>".$periods[$p]["name"]."</option>"; }
                                                                else { echo "<option value='".$periods[$p]["name"]."'>".$periods[$p]["name"]."</option>"; }
                                                            }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>

                                            <!-- Filters -->
                                            <div class="col-3 ps-2 py-0">
                                                <div class="dropdown float-start">
                                                    <button class="btn btn-primary" type="button" id="filtersMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-magnifying-glass"></i>
                                                    </button>
                                                    <div class="dropdown-menu filters-menu px-2" aria-labelledby="filtersMenu" style="width: 320px;">
                                                        <!-- Search Table -->
                                                        <div class="row mx-0 mt-0 mb-2">
                                                            <div class="input-group h-auto p-0">
                                                                <div class="input-group-prepend">
                                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                                        <label for="search-all"><i class="fa-solid fa-magnifying-glass"></i></label>
                                                                    </span>
                                                                </div>
                                                                <input class="form-control" type="text" placeholder="Search table" id="search-all" name="search-all" autocomplete="off">
                                                            </div>
                                                        </div>

                                                        <!-- Filter By Status -->
                                                        <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                            <div class="col-4 ps-0 pe-1">
                                                                <label for="search-status">Status:</label>
                                                            </div>

                                                            <div class="col-8 ps-1 pe-0">
                                                                <select class="form-select w-100" id="search-status" name="search-status">
                                                                    <option value="">Show All</option>
                                                                    <option value="Active" style="background-color: #006900; color: #ffffff;" selected>Active</option>
                                                                    <option value="Inactive" style="background-color: #e40000; color: #ffffff;">Inactive</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <!-- Filter By District Attending -->
                                                        <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                            <div class="col-4 ps-0 pe-1">
                                                                <label for="search-customers">District:</label>
                                                            </div>

                                                            <div class="col-8 ps-1 pe-0">
                                                                <select class="form-select w-100" id="search-customers" name="search-customers">
                                                                    <option></option>
                                                                    <?php
                                                                        $getCustomers = mysqli_query($conn, "SELECT DISTINCT c.id, c.name FROM `customers` c 
                                                                                                            JOIN cases ON (c.id=cases.residency OR c.id=cases.district_attending)
                                                                                                            ORDER BY c.name ASC");
                                                                        if (mysqli_num_rows($getCustomers) > 0) // services exist
                                                                        {
                                                                            while ($customer = mysqli_fetch_array($getCustomers))
                                                                            {
                                                                                echo "<option value='".$customer["id"]."'>".$customer["name"]."</option>";
                                                                            }
                                                                        }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <!-- Filter By Grade Level -->
                                                        <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                            <div class="col-4 ps-0 pe-1">
                                                                <label for="search-grade_level">Grade:</label>
                                                            </div>

                                                            <div class="col-8 ps-1 pe-0">
                                                                <select class="form-select w-100" id="search-grade_level" name="search-grade_level">
                                                                    <option value=""></option>
                                                                    <option value="0">Kindergarten</option>
                                                                    <option value="1">1st Grade</option>
                                                                    <option value="2">2nd Grade</option>
                                                                    <option value="3">3rd Grade</option>
                                                                    <option value="4">4th Grade</option>
                                                                    <option value="5">5th Grade</option>
                                                                    <option value="6">6th Grade</option>
                                                                    <option value="7">7th Grade</option>
                                                                    <option value="8">8th Grade</option>
                                                                    <option value="9">9th Grade</option>
                                                                    <option value="10">10th Grade</option>
                                                                    <option value="11">11th Grade</option>
                                                                    <option value="12">12th Grade</option>
                                                                    <option value="13">Post 12th Grade</option>
                                                                    <option value="-1">Pre-Kindergarten</option>
                                                                    <option value="-2">4-year-old Kindergarten</option>
                                                                    <option value="-3">3-year-old Kindergarten</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <!-- Filter By Evaluation Method -->
                                                        <div class="row d-flex justify-content-between align-items-center mx-0 mt-0 mb-2">
                                                            <div class="col-4 ps-0 pe-1">
                                                                <label for="search-evaluation_method">Evaluation:</label>
                                                            </div>

                                                            <div class="col-8 ps-1 pe-0">
                                                                <select class="form-select" id="search-evaluation_method" name="search-evaluation_method">
                                                                    <option></option>
                                                                    <?php /* <option>Pending Evaluation</option> */ ?>
                                                                    <option>Evaluation Only</option>
                                                                    <option>Regular</option>
                                                                </select>
                                                            </div>
                                                        </div>

                                                        <div class="row m-0">
                                                            <button class="btn btn-secondary w-100" id="clearFilters"><i class="fa-solid fa-xmark"></i> Clear Filters</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <input id="search-caseload_id" type="hidden" aria-hidden="true" value="<?php echo $caseload_id; ?>">
                                        <input id="fixed-caseload_id" type="hidden" aria-hidden="true" value="<?php echo $caseload_id; ?>">
                                    </div>

                                    <!-- Page Header -->
                                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-8 col-xxl-8 p-0">
                                        <h2 class="m-0"><?php echo $caseload_name; ?></h2>
                                    </div>

                                    <!-- Page Management Dropdown -->
                                    <div class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-2 col-xxl-2 d-flex justify-content-end p-0">
                                        <!-- Show Details -->
                                        <button class="btn btn-primary mx-1 dropdown-toggle" id="showDetails" value="0" onclick="toggleDetails(this.value);">
                                            <i class="fa-solid fa-chart-pie"></i>
                                        </button>

                                        <button class="btn btn-primary mx-1 dropdown-toggle" id="exportsMenu" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                            <i class="fa-solid fa-cloud-arrow-down"></i>
                                        </button>
                                        <ul class="quickNav-dropdown dropdown-menu p-0" aria-labelledby="exportsMenu" style="min-width: 32px !important;">
                                            <li id="csv-export-div" style="font-size: 24px; text-align: center !important; width: 100% !important;"></li>
                                            <li id="xlsx-export-div" style="font-size: 24px;"></li>
                                            <li id="pdf-export-div" style="font-size: 24px;"></li>
                                            <li id="print-export-div" style="font-size: 24px;"></li>
                                        </ul>

                                        <?php if ((verifyUserCaseload($conn, $caseload_id) && !verifyCoordinator($conn, $_SESSION["id"])) || (verifyCoordinator($conn, $_SESSION["id"]) && verifyUserCaseload($conn, $caseload_id) && isUserCaseloadManage($conn, $_SESSION["id"], $caseload_id))) { 
                                            if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) || (!isset($PERMISSION["VIEW_CASELOADS_ALL"]) && !isCaseloadLocked($conn, $caseload_id))) { ?>
                                                <div class="dropdown">
                                                    <button class="btn btn-primary dropdown-toggle px-4" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                                                        <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"])) { ?>
                                                            Manage Caseload
                                                        <?php } else { ?>
                                                            Manage My Caseload
                                                        <?php } ?>
                                                    </button>
                                                    <ul class="dropdown-menu p-0" aria-labelledby="dropdownMenuButton1">
                                                        <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getAddStudentToCaseloadModal();">Add Student To Caseload</button></li>
                                                        <?php if ($_SESSION["role"] == 1) { ?>
                                                            <li><button class="dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0" type="button" onclick="getClearCaseloadModal();">Clear Caseload</button></li>
                                                        <?php } ?>
                                                    </ul>
                                                </div>
                                            <?php }
                                        } ?>
                                    </div>
                                </div>

                                <!-- View Subpage Buttons -->
                                <div class="btn-group w-100 m-0 p-0" role="group" aria-label="Button group to select which the page view">
                                    <button class="btn btn-primary btn-subpages-primary w-100 rounded-0" id="view-caseload-button" style="border-top: 2px solid white;" onclick="toggleView('caseload');" value="1">Caseload</button>
                                    <button class="btn btn-secondary btn-subpages-primary w-100 rounded-0" id="view-startEndReport-button" style="border-top: 2px solid white;" onclick="toggleView('startEndReport');" value="0">Start-End Changes</button>
                                    <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) && $medicaid == 1) { ?>
                                        <button class="btn btn-secondary btn-subpages-primary w-100 rounded-0" id="view-medicaid-button" style="border-top: 2px solid white;" onclick="toggleView('medicaid');" value="0">Medicaid</button>
                                    <?php } ?>
                                </div>

                                <!-- View Start-End Report Buttons -->
                                <div class="btn-group w-100 m-0 p-0 d-none" id="view-startEndReport-buttons-div" role="group" aria-label="Button group to select which the page view">
                                    <button class="btn btn-primary w-100 rounded-0" id="view-startEndReport-startReport-button" onclick="toggleStartEndView('startReport');" style="border-top: 1px solid white;">Start Changes (start date after 9/1)</button>
                                    <button class="btn btn-secondary w-100 rounded-0" id="view-startEndReport-endReport-button" onclick="toggleStartEndView('endReport');" style="border-top: 1px solid white;">End Changes (end date before 6/1)</button>
                                </div>
                            </div>

                            <!-- Caseload Container -->
                            <div id="view-caseload-div" class="p-0">
                                <div class="table-header d-none" id="details-div">
                                    <div class="row justify-content-center py-2" style="font-size: 18px;">
                                        <div class="col-12 col-sm-6 col-md-6 col-lg-4 col-xl-3 col-xxl-3 text-center">
                                            <b>Active Students In Caseload: </b> <span id="count-span"><span>
                                        </div>

                                        <div class="col-12 col-sm-6 col-md-6 col-lg-4 col-xl-3 col-xxl-3 text-center">
                                            <b>Projected Annual Units: </b> <span id="units-span"><span>
                                        </div>
                                    </div>
                                </div>

                                <table id="caseloads" class="report_table w-100">
                                    <thead>
                                        <tr>
                                            <th class="text-center py-1 px-2">Student</th>
                                            <th class="text-center py-1 px-2">Location</th>
                                            <th class="text-center py-1 px-2">Term</th>
                                            <th class="text-center py-1 px-2">Assistant</th>
                                            <th class="text-center py-1 px-2">Current Frequency</th>
                                            <th class="text-center py-1 px-2"><span data-bs-toggle="tooltip" data-bs-placement="bottom" title="Additional units of service (UOS) from extra IEP meetings or evaluations.">Add. UOS<span></th>
                                            <th class="text-center py-1 px-2"><span data-bs-toggle="tooltip" data-bs-placement="bottom" title="Units of service (UOS) to be billed.">UOS To Bill</span></th>
                                            <th class="text-center py-1 px-2">Membership Days</th>
                                            <th class="text-center py-1 px-2">Billing Notes</th>
                                            <th class="text-center py-1 px-2">Actions</th>

                                            <!-- Hidden Columns -->
                                            <th class="text-center py-1 px-2"></th>
                                            <th class="text-center py-1 px-2"></th>
                                            <th class="text-center py-1 px-2"></th>
                                            <th class="text-center py-1 px-2"></th>

                                            <!-- Export Columns -->
                                            <th class="text-center py-1 px-2">Name</th>
                                            <th class="text-center py-1 px-2">DOB</th>
                                            <th class="text-center py-1 px-2">Grade</th>
                                            <th class="text-center py-1 px-2">Residency</th>
                                            <th class="text-center py-1 px-2">Attending</th>
                                            <th class="text-center py-1 px-2">School</th>
                                            <th class="text-center py-1 px-2">Classroom</th>
                                            <th class="text-center py-1 px-2">Start</th>
                                            <th class="text-center py-1 px-2">End</th>
                                            <th class="text-center py-1 px-2">Current Frequency</th>
                                            <th class="text-center py-1 px-2">Add. UOS</th>
                                            <th class="text-center py-1 px-2">UOS to Bill</th>
                                        </tr>
                                    </thead>

                                    <tfoot>
                                        <tr>
                                            <?php if ($uosEnabled === true) { ?>
                                                <th colspan="5" class="py-1 px-2" style="text-align: right !important;">TOTALS:</th>
                                                <th class="py-1 px-2" id="sum-ADD-UOS"></th> <!-- Additional UOS sum -->
                                                <th class="py-1 px-2" id="sum-EOY-UOS"></th> <!-- EOY UOS sum -->
                                                <th class="py-1 px-2" colspan="3"><div class="float-end" id="caseload-colVis-button"></div></th>
                                            <?php } else if ($daysEnabled === true) { ?>
                                                <th colspan="7" class="py-1 px-2" style="text-align: right !important;">TOTALS:</th>
                                                <th class="py-1 px-2" id="sum-days"></th> <!-- membership days sum -->
                                                <th class="py-1 px-2" colspan="2"><div class="float-end" id="caseload-colVis-button"></div></th>
                                            <?php } ?>
                                        </tr>
                                    </tfoot>
                                </table>
                                <?php createTableFooterV2("caseloads", "BAP_MyCaseload_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                            </div>

                            <!-- Start-End Changes Report Container -->
                            <div id="view-startEndReport-div" class="d-none p-0">
                                <div id="view-startEndReport-startReport-div" class="p-0">
                                    <table id="caseloads-startReport" class="report_table w-100">
                                        <thead>
                                            <tr>
                                                <th class="text-center py-1 px-2">Student</th>
                                                <th class="text-center py-1 px-2">Location</th>
                                                <th class="text-center py-1 px-2">Start Date</th>
                                                <th class="text-center py-1 px-2">End Date</th>
                                                <th class="text-center py-1 px-2">Evaluation</th>
                                                <th class="text-center py-1 px-2">Assistant</th>
                                            </tr>
                                        </thead>
                                    </table>
                                    <?php createTableFooterV2("caseloads-startReport", "BAP_MyCaseload_StartChanges_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                                </div>

                                <div id="view-startEndReport-endReport-div" class=" d-none p-0">
                                    <table id="caseloads-endReport" class="report_table w-100">
                                        <thead>
                                            <tr>
                                                <th class="text-center py-1 px-2">Student</th>
                                                <th class="text-center py-1 px-2">Location</th>
                                                <th class="text-center py-1 px-2">Start Date</th>
                                                <th class="text-center py-1 px-2">End Date</th>
                                                <th class="text-center py-1 px-2">Month Evaluation Started</th>
                                                <th class="text-center py-1 px-2">Reason For Dismissal</th>
                                                <th class="text-center py-1 px-2">End Notes</th>
                                                <th class="text-center py-1 px-2">Assistant</th>
                                                <th class="text-center py-1 px-2">Actions</th>
                                            </tr>
                                        </thead>
                                    </table>
                                    <?php createTableFooterV2("caseloads-endReport", "BAP_MyCaseload_EndChanges_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                                </div>
                            </div>

                            <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"])) { ?>
                                <!-- Caseload Container -->
                                <div id="view-medicaid-div" class="d-none p-0">
                                    <!-- Key -->
                                    <div class="table-header p-1">
                                        <div class="row justify-content-center">
                                            <div class="col-3 col-lg-2">
                                                <div class="evaluation_method-div d-flex justify-content-center align-items-center text-center p-1 w-100 h-100"><b>Evaluation Method</b></div>
                                            </div>

                                            <div class="col-3 col-lg-2">
                                                <div class="enrollment_type-div d-flex justify-content-center align-items-center text-center p-1 w-100 h-100"><b>Enrollment Type</b></div>
                                            </div>

                                            <div class="col-3 col-lg-2">
                                                <div class="educational_plan-div d-flex justify-content-center align-items-center text-center p-1 w-100 h-100"><b>Educational Plan</b></div>
                                            </div>

                                            <div class="col-3 col-lg-2">
                                                <div class="billing_to-div d-flex justify-content-center align-items-center text-center p-1 w-100 h-100"><b>Billing To</b></div>
                                            </div>
                                        </div>  
                                    </div>

                                    <!-- Report -->
                                    <table id="caseloads-medicaid" class="report_table w-100">
                                        <thead>
                                            <tr>
                                                <th class="text-center py-1 px-2">Student</th>
                                                <th class="text-center py-1 px-2">Bill To</th>
                                                <th class="text-center py-1 px-2">Start Date</th>
                                                <th class="text-center py-1 px-2">End Date</th>
                                                <th class="text-center py-1 px-2">Month</th>
                                                <th class="text-center py-1 px-2">Assistant</th>
                                                <th class="text-center py-1 px-2">Actions</th>
                                            </tr>
                                        </thead>
                                    </table>
                                    <?php createTableFooterV2("caseloads-medicaid", "BAP_MyCaseload_Medicaid_PageLength", $USER_SETTINGS["page_length"], true, true); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <!--
                        ### MODALS ###
                    -->
                    <!-- Add Caseload Modal -->
                    <div id="add-caseload-modal-div"></div>
                    <!-- End Add Caseload Modal -->

                    <!-- Edit Case Modal -->
                    <div id="edit-caseload-modal-div"></div>
                    <!-- End Edit Case Modal -->

                    <!-- View Case Changes Modal -->
                    <div id="view-caseload-modal-div"></div>
                    <!-- End View Case Changes Modal -->

                    <!-- Add Caseload Change Modal -->
                    <div id="add-caseload_change-modal-div"></div>
                    <!-- End Add Caseload Change Modal -->

                    <!-- Edit Case Change Modal -->
                    <div id="edit-caseload_change-modal-div"></div>
                    <!-- End Edit Case Change Modal -->

                    <!-- Transfer Caseload Modal -->
                    <div id="transfer-caseload-modal-div"></div>
                    <!-- End Transfer Caseload Modal -->

                    <!-- Request Caseload Transfer Modal -->
                    <div id="request_caseload_transfer-modal-div"></div>
                    <!-- End Request Caseload Transfer Modal -->

                    <!-- Delete Caseload Modal -->
                    <div id="delete-caseload-modal-div"></div>
                    <!-- End Delete Caseload Modal -->

                    <!-- Dismiss Student Modal -->
                    <div id="dismiss_student-modal-div"></div>
                    <!-- End Dismiss Student Modal -->

                    <!-- Edit Dismiss Student Modal -->
                    <div id="edit-dismiss_student-modal-div"></div>
                    <!-- End Edit Dismiss Student Modal -->

                    <!-- View Student Modal -->
                    <div id="view-student-modal-div"></div>
                    <!-- End View Student Modal -->

                    <!-- Clear Caseload Modal -->
                    <div id="clear-caseload-modal-div"></div>
                    <!-- End Clear Caseload Modal -->

                    <!-- Undo Dismissal Modal -->
                    <div id="undo-dismissal-modal-div"></div>
                    <!-- End Undo Dismissal Modal -->

                    <script>
                        // initialize tooltips
                        $("[data-bs-toggle=\"tooltip\"]").tooltip();

                        // get the current active period
                        let active_period = "<?php echo $active_period_label; ?>"; 

                        <?php if ($period_id != null && verifyPeriod($conn, $period_id)) {
                            /* setting period to previously selected on Manage Projects handled above */
                        } else { ?>
                            // set the search filters to values we have saved in storage
                            if (sessionStorage["BAP_MyCaseload_Search_Period"] != "" && sessionStorage["BAP_MyCaseload_Search_Period"] != null && sessionStorage["BAP_MyCaseload_Search_Period"] != undefined) { $('#search-period').val(sessionStorage["BAP_MyCaseload_Search_Period"]); }
                            else { $('#search-period').val(active_period); } // no period set; default to active period 
                        <?php } ?>

                        // set the search filters to values we have saved in storage
                        $('#search-all').val(sessionStorage["BAP_MyCaseload_Search_All"]);
                        $('#search-status').val(sessionStorage["BAP_MyCaseload_Search_Status"]);
                        $('#search-customers').val(sessionStorage["BAP_MyCaseload_Search_Attending"]);
                        $('#search-grade_level').val(sessionStorage["BAP_MyCaseload_Search_GradeLevel"]);
                        $('#search-evaluation_method').val(sessionStorage["BAP_MyCaseload_Search_EvaluationMethod"]);

                        /** function to search for caseloads */
                        function searchCaseloads()
                        {
                            // get the value of the period we are searching
                            var period = document.getElementById("search-period").value;

                            // get the caseload ID
                            var caseload_id = document.getElementById("search-caseload_id").value;

                            if (period != "" && period != null && period != undefined)
                            {
                                if (caseload_id != "" && caseload_id != null && caseload_id != undefined)
                                {
                                    // set the fixed period and caseload id
                                    document.getElementById("fixed-period").value = period;
                                    document.getElementById("fixed-caseload_id").value = caseload_id;

                                    // update session storage stored search parameter
                                    sessionStorage["BAP_MyCaseload_Search_Period"] = period;

                                    // get caseload stats
                                    let stats = JSON.parse($.ajax({
                                        type: "POST",
                                        url: "ajax/caseloads/getCaseloadStats.php",
                                        async: false,
                                        data : {
                                            period: period,
                                            caseload_id: caseload_id,
                                        }
                                    }).responseText);
                                    let total_students = numberWithCommas(stats["count"]);
                                    let total_units = numberWithCommas(stats["units"]);

                                    // display caseload stats
                                    document.getElementById("count-span").innerHTML = total_students;
                                    document.getElementById("units-span").innerHTML = total_units;

                                    // initialize the caseloads table
                                    var caseloads = $("#caseloads").DataTable({
                                        ajax: {
                                            url: "ajax/caseloads/getCaseload.php",
                                            type: "POST",
                                            data: {
                                                caseload_id: caseload_id,
                                                period: period
                                            }
                                        },
                                        destroy: true,
                                        autoWidth: false,
                                        fixedHeader: true,
                                        async: false,
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        columns: [
                                            // display columns
                                            { data: "student", orderable: true, width: "16.25%" },
                                            { data: "location", orderable: true, width: "15%" },
                                            { data: "daterange", orderable: true, width: "7.5%" }, 

                                            <?php if ($allowAssistants === true) { ?>
                                                { data: "assistant", orderable: true, width: "11.5%", className: "text-center" },
                                            <?php } else { ?>
                                                { data: "assistant", orderable: true, width: "11.5%", className: "text-center" },
                                            <?php } ?>

                                            <?php if ($frequencyEnabled === true) { ?>
                                                { data: "EOY-frequency", orderable: true, width: "11.5%", className: "text-center" },
                                            <?php } else { ?>
                                                { data: "EOY-frequency", orderable: false, visible: false, },
                                            <?php } ?>

                                            <?php if ($uosEnabled === true) { ?>
                                                { data: "additional-UOS", orderable: true, width: "4%", className: "text-center" },
                                                { data: "EOY-UOS", orderable: true, width: "4%", className: "text-center" },
                                            <?php } else { ?>
                                                { data: "additional-UOS", orderable: false, visible: false, },
                                                { data: "EOY-UOS", orderable: false, visible: false, },
                                            <?php } ?>

                                            <?php if ($daysEnabled === true) { ?>
                                                { data: "membership_days", orderable: false, visible: true, width: "10%", className: "text-center" },
                                            <?php } else { ?>
                                                { data: "membership_days", orderable: false, visible: false, },
                                            <?php } ?>

                                            { data: "billing_notes", orderable: true, visible: false },
                                            { data: "actions", orderable: false, width: "5%" }, // 8
                                            { data: "attending_id", orderable: true, visible: false },
                                            { data: "status", orderable: true, visible: false },
                                            { data: "evaluation_method", orderable: false, visible: false },
                                            { data: "grade_id", orderable: false, visible: false },

                                            // export columns
                                            { data: "export-student_name", orderable: false, visible: false }, // 13
                                            { data: "export-student_dob", orderable: false, visible: false },
                                            { data: "export-student_grade", orderable: false, visible: false },
                                            { data: "export-residency", orderable: false, visible: false },
                                            { data: "export-attending", orderable: false, visible: false },
                                            { data: "export-school", orderable: false, visible: false },
                                            { data: "export-classroom", orderable: false, visible: false }, // 19
                                            { data: "export-start_date", orderable: false, visible: false },
                                            { data: "export-end_date", orderable: false, visible: false },
                                            { data: "export-frequency", orderable: false, visible: false },
                                            { data: "export-additional_uos", orderable: false, visible: false },
                                            { data: "export-uos_to_bill", orderable: false, visible: false },
                                            { data: "export-membership_days", orderable: false, visible: false }, 
                                        ],
                                        order: [
                                            [ 10, "asc" ], // attending, ascending
                                            [ 0, "asc" ] // name, ascending
                                        ],
                                        dom: 'rt',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        saveState: false,
                                        drawCallback: function ()
                                        {
                                            <?php if ($uosEnabled === true) { ?>
                                                // get sum of units
                                                let ADD_UOS = this.api().column(5, { search: "applied" }).data().sum();
                                                let EOY_UOS = this.api().column(6, { search: "applied" }).data().sum();

                                                // update the table footer
                                                document.getElementById("sum-ADD-UOS").innerHTML = numberWithCommas(ADD_UOS);
                                                document.getElementById("sum-EOY-UOS").innerHTML = numberWithCommas(EOY_UOS);
                                            <?php } else if ($daysEnabled === true) { ?>
                                                // get sum of days
                                                let membership_days = this.api().column(7, { search: "applied" }).data().sum();

                                                // update the table footer
                                                document.getElementById("sum-days").innerHTML = numberWithCommas(membership_days);
                                            <?php } ?>
                                        },
                                        rowCallback: function (row, data, index)
                                        {
                                            updatePageSelection("caseloads");
                                        },
                                    });

                                    // create the export buttons
                                    new $.fn.dataTable.Buttons(caseloads, {
                                        buttons: [
                                            // CSV BUTTON
                                            {
                                                extend: "csv",
                                                exportOptions: {
                                                    columns: [ 14, 15, 16, 17, 18, 19, <?php if ($isClassroom == 1) { ?>20,<?php } ?> 21, 22 <?php if ($frequencyEnabled == 1) { ?>,23<?php } ?> <?php if ($uosEnabled == 1) { ?>,24, 25<?php } ?> <?php if ($daysEnabled == 1) { ?>,26<?php } ?> ]
                                                },
                                                text: "<i class='fa-solid fa-file-csv'></i>",
                                                className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                                title: "<?php echo $export_title; ?>",
                                                init: function(api, node, config) {
                                                    // remove default button classes
                                                    $(node).removeClass('dt-button');
                                                    $(node).removeClass('buttons-csv');
                                                    $(node).removeClass('buttons-html5');
                                                }
                                            },
                                        ]
                                    });
                                    new $.fn.dataTable.Buttons(caseloads, {
                                        buttons: [
                                            // EXCEL BUTTON
                                            {
                                                extend: "excel",
                                                exportOptions: {
                                                    columns: [ 14, 15, 16, 17, 18, 19, <?php if ($isClassroom == 1) { ?>20,<?php } ?> 21, 22 <?php if ($frequencyEnabled == 1) { ?>,23<?php } ?> <?php if ($uosEnabled == 1) { ?>,24, 25<?php } ?> <?php if ($daysEnabled == 1) { ?>,26<?php } ?> ]
                                                },
                                                text: "<i class='fa-solid fa-file-excel'></i>",
                                                className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                                title: "<?php echo $export_title; ?>",
                                                init: function(api, node, config) {
                                                    // remove default button classes
                                                    $(node).removeClass('dt-button');
                                                    $(node).removeClass('buttons-excel');
                                                    $(node).removeClass('buttons-html5');
                                                }
                                            },
                                        ]
                                    });
                                    new $.fn.dataTable.Buttons(caseloads, {
                                        buttons: [
                                            // PDF BUTTON
                                            {
                                                extend: "pdf",
                                                exportOptions: {
                                                    columns: [ 14, 15, 16, 17, 18, 19, <?php if ($isClassroom == 1) { ?>20,<?php } ?> 21, 22 <?php if ($frequencyEnabled == 1) { ?>,23<?php } ?> <?php if ($uosEnabled == 1) { ?>,24, 25<?php } ?> <?php if ($daysEnabled == 1) { ?>,26<?php } ?> ]
                                                },
                                                orientation: "landscape",
                                                text: "<i class='fa-solid fa-file-pdf'></i>",
                                                className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                                title: "<?php echo $export_title; ?>",
                                                init: function(api, node, config) {
                                                    // remove default button classes
                                                    $(node).removeClass('dt-button');
                                                    $(node).removeClass('buttons-excel');
                                                    $(node).removeClass('buttons-html5');
                                                }
                                            },
                                        ]
                                    });
                                    new $.fn.dataTable.Buttons(caseloads, {
                                        buttons: [
                                            // PRINT BUTTON
                                            {
                                                extend: "print",
                                                exportOptions: {
                                                    columns: [ 14, 15, 16, 17, 18, 19, <?php if ($isClassroom == 1) { ?>20,<?php } ?> 21, 22 <?php if ($frequencyEnabled == 1) { ?>,23<?php } ?> <?php if ($uosEnabled == 1) { ?>,24, 25<?php } ?> <?php if ($daysEnabled == 1) { ?>,26<?php } ?> ]
                                                },
                                                orientation: "landscape",
                                                text: "<i class='fa-solid fa-print'></i>",
                                                className: "dropdown-item quickNav-dropdown-item text-center w-100 px-3 py-2 rounded-0",
                                                title: "<?php echo $export_title; ?>",
                                                init: function(api, node, config) {
                                                    // remove default button classes
                                                    $(node).removeClass('dt-button');
                                                    $(node).removeClass('buttons-excel');
                                                    $(node).removeClass('buttons-html5');
                                                }
                                            },
                                        ]
                                    });
                                    // add buttons to page description area
                                    caseloads.buttons(0, null).container().appendTo("#csv-export-div");
                                    caseloads.buttons(1, null).container().appendTo("#xlsx-export-div");
                                    caseloads.buttons(2, null).container().appendTo("#pdf-export-div");
                                    caseloads.buttons(3, null).container().appendTo("#print-export-div");
                                    
                                    // create the column visibility buttons
                                    new $.fn.dataTable.Buttons(caseloads, {
                                        buttons: [
                                            {
                                                extend:    'colvis',
                                                text:      '<i class="fa-solid fa-eye fa-sm"></i>',
                                                titleAttr: 'Column Visibility',
                                                className: "m-0 px-2 py-0",
                                                columns: [8], // columns to toggle visibility for
                                            }
                                        ],
                                    });
                                    // add buttons to container
                                    caseloads.buttons(4, null).container().appendTo("#caseload-colVis-button");

                                    // initialize the caseloads table
                                    var caseloads_startReport = $("#caseloads-startReport").DataTable({
                                        ajax: {
                                            url: "ajax/caseloads/getCaseloadStartReport.php",
                                            type: "POST",
                                            data: {
                                                caseload_id: caseload_id,
                                                period: period
                                            }
                                        },
                                        destroy: true,
                                        autoWidth: false,
                                        async: false,
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        columns: [
                                            // display columns
                                            { data: "student", orderable: true, width: "20%" },
                                            { data: "location", orderable: true, width: "20%" },
                                            { data: "start", orderable: true, width: "10%", className: "text-center" },
                                            { data: "end", orderable: true, width: "10%", className: "text-center" },
                                            { data: "evaluation", orderable: true, width: "20%", className: "text-center" },
                                            { data: "assistant", orderable: true, width: "20%", className: "text-center" },
                                            { data: "status", orderable: true, visible: false },
                                            { data: "district", orderable: true, visible: false },
                                            { data: "grade", orderable: true, visible: false },
                                            { data: "evaluation", orderable: true, visible: false },
                                        ],
                                        dom: 'rt',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        saveState: false,
                                        rowCallback: function (row, data, index)
                                        {
                                            updatePageSelection("caseloads-startReport");
                                        }
                                    });

                                    // initialize the caseloads table
                                    var caseloads_endReport = $("#caseloads-endReport").DataTable({
                                        ajax: {
                                            url: "ajax/caseloads/getCaseloadEndReport.php",
                                            type: "POST",
                                            data: {
                                                caseload_id: caseload_id,
                                                period: period
                                            }
                                        },
                                        destroy: true,
                                        autoWidth: false,
                                        async: false,
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        columns: [
                                            // display columns
                                            { data: "student", orderable: true, width: "17.5%" },
                                            { data: "location", orderable: true, width: "17.5%" },
                                            { data: "start", orderable: true, width: "7.5%", className: "text-center" },
                                            { data: "end", orderable: true, width: "7.5%", className: "text-center" },
                                            { data: "month", orderable: true, width: "10%", className: "text-center" },
                                            { data: "dismissal_reasoning", orderable: true, width: "15%", className: "text-center" },
                                            { data: "end_notes", orderable: true, visible: false, className: "text-center" },
                                            { data: "assistant", orderable: true, width: "12.5%", className: "text-center" },
                                            { data: "actions", orderable: false, width: "12.5%" },
                                            { data: "status", orderable: true, visible: false },
                                            { data: "district", orderable: true, visible: false },
                                            { data: "grade", orderable: true, visible: false },
                                            { data: "evaluation", orderable: true, visible: false },
                                        ],
                                        dom: 'rt',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        saveState: false,
                                        rowCallback: function (row, data, index)
                                        {
                                            updatePageSelection("caseloads-endReport");
                                        },
                                        initComplete: function() {
                                            // initialize tooltips
                                            $("[data-bs-toggle=\"tooltip\"]").tooltip();
                                        }
                                    });

                                    // initialize the caseloads table
                                    var caseloads_medicaid = $("#caseloads-medicaid").DataTable({
                                        ajax: {
                                            url: "ajax/caseloads/getCaseloadMedicaidReport.php",
                                            type: "POST",
                                            data: {
                                                caseload_id: caseload_id,
                                                period: period
                                            }
                                        },
                                        destroy: true,
                                        autoWidth: false,
                                        async: false,
                                        pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                        lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                        columns: [
                                            // display columns
                                            { data: "student", orderable: true, width: "20%" },
                                            { data: "location", orderable: true, width: "20%" },
                                            { data: "start", orderable: true, width: "7.5%", className: "text-center" },
                                            { data: "end", orderable: true, width: "7.5%", className: "text-center" },
                                            { data: "month", orderable: true, width: "10%", className: "text-center" },
                                            { data: "assistant", orderable: true, width: "17.5%", className: "text-center" },
                                            { data: "actions", orderable: true, width: "17.5%" },
                                            { data: "status", orderable: true, visible: false },
                                            { data: "district", orderable: true, visible: false },
                                            { data: "grade", orderable: true, visible: false },
                                            { data: "evaluation", orderable: true, visible: false },
                                        ],
                                        dom: 'rt',
                                        language: {
                                            search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                            lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                            info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                        },
                                        saveState: false,
                                        rowCallback: function (row, data, index)
                                        {
                                            updatePageSelection("caseloads-medicaid");
                                        },
                                        initComplete: function() {
                                            // initialize tooltips
                                            $("[data-bs-toggle=\"tooltip\"]").tooltip();
                                        }
                                    });

                                    // search table by custom search filter
                                    $('#search-all').keyup(function() {
                                        sessionStorage["BAP_MyCaseload_Search_All"] = $(this).val();
                                        caseloads.search($(this).val()).draw();
                                        caseloads_startReport.search($(this).val()).draw();
                                        caseloads_endReport.search($(this).val()).draw();
                                        caseloads_medicaid.search($(this).val()).draw();
                                    });

                                    // search table by customer
                                    $('#search-customers').change(function() {
                                        sessionStorage["BAP_MyCaseload_Search_Attending"] = $(this).val();
                                        if ($(this).val() != "") 
                                        { 
                                            caseloads.columns(10).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                            caseloads_startReport.columns(7).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                            caseloads_endReport.columns(10).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                            caseloads_medicaid.columns(8).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                        }
                                        else 
                                        { 
                                            caseloads.columns(10).search("").draw(); 
                                            caseloads_startReport.columns(7).search("").draw(); 
                                            caseloads_endReport.columns(10).search("").draw(); 
                                            caseloads_medicaid.columns(8).search("").draw(); 
                                        }
                                    });

                                    // search table by caseload status
                                    $('#search-status').change(function() {
                                        sessionStorage["BAP_MyCaseload_Search_Status"] = $(this).val();
                                        if ($(this).val() != "") 
                                        { 
                                            caseloads.columns(11).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                            caseloads_startReport.columns(6).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                            caseloads_medicaid.columns(7).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                        }
                                        else
                                        { 
                                            caseloads.columns(11).search("").draw(); 
                                            caseloads_startReport.columns(6).search("").draw(); 
                                            caseloads_medicaid.columns(7).search("").draw(); 
                                        }
                                    });

                                    // search table by caseload status
                                    $('#search-evaluation_method').change(function() {
                                        sessionStorage["BAP_MyCaseload_Search_EvaluationMethod"] = $(this).val();
                                        if ($(this).val() != "") 
                                        { 
                                            caseloads.columns(12).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                            caseloads_startReport.columns(9).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                            caseloads_endReport.columns(12).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                            caseloads_medicaid.columns(10).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                        }
                                        else 
                                        { 
                                            caseloads.columns(12).search("").draw(); 
                                            caseloads_startReport.columns(9).search("").draw(); 
                                            caseloads_endReport.columns(12).search("").draw(); 
                                            caseloads_medicaid.columns(10).search("").draw(); 
                                        }
                                    });

                                    // search table by caseload status
                                    $('#search-grade_level').change(function() {
                                        sessionStorage["BAP_MyCaseload_Search_GradeLevel"] = $(this).val();
                                        if ($(this).val() != "") 
                                        { 
                                            caseloads.columns(13).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                            caseloads_startReport.columns(8).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                            caseloads_endReport.columns(11).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                            caseloads_medicaid.columns(9).search("^" + $(this).val() + "$", true, false, true).draw(); 
                                        }
                                        else 
                                        { 
                                            caseloads.columns(13).search("").draw(); 
                                            caseloads_startReport.columns(8).search("").draw(); 
                                            caseloads_endReport.columns(11).search("").draw(); 
                                            caseloads_medicaid.columns(9).search("").draw(); 
                                        }
                                    });

                                    // function to clear search filters
                                    $('#clearFilters').click(function() {
                                        sessionStorage["BAP_MyCaseload_Search_All"] = "";
                                        sessionStorage["BAP_MyCaseload_Search_Attending"] = "";
                                        sessionStorage["BAP_MyCaseload_Search_Status"] = "";
                                        sessionStorage["BAP_MyCaseload_Search_EvaluationMethod"] = "";
                                        sessionStorage["BAP_MyCaseload_Search_GradeLevel"] = "";
                                        $('#search-all').val("");
                                        $('#search-customers').val("");
                                        $('#search-status').val("");
                                        $('#search-evaluation_method').val("");
                                        $('#search-grade_level').val("");
                                        caseloads.search("").columns().search("").draw();
                                        caseloads_startReport.search("").columns().search("").draw();
                                        caseloads_endReport.search("").columns().search("").draw();
                                        caseloads_medicaid.search("").columns().search("").draw();
                                        caseloads_medicaid.search("").columns().search("").draw();
                                    });

                                    // redraw caseload table with current search fields
                                    if ($('#search-all').val() != "") 
                                    { 
                                        caseloads.search($('#search-all').val()).draw(); 
                                        caseloads_startReport.search($('#search-all').val()).draw(); 
                                        caseloads_endReport.search($('#search-all').val()).draw(); 
                                        caseloads_medicaid.search($('#search-all').val()).draw(); 
                                    }
                                    if ($('#search-customers').val() != "") 
                                    { 
                                        caseloads.columns(10).search("^" + $('#search-customers').val() + "$", true, false, true).draw(); 
                                        caseloads_startReport.columns(7).search("^" + $('#search-customers').val() + "$", true, false, true).draw(); 
                                        caseloads_endReport.columns(10).search("^" + $('#search-customers').val() + "$", true, false, true).draw(); 
                                        caseloads_medicaid.columns(8).search("^" + $('#search-customers').val() + "$", true, false, true).draw(); 
                                    }
                                    if ($('#search-status').val() != "") 
                                    { 
                                        caseloads.columns(11).search("^" + $('#search-status').val() + "$", true, false, true).draw(); 
                                        caseloads_startReport.columns(6).search("^" + $('#search-status').val() + "$", true, false, true).draw(); 
                                        caseloads_medicaid.columns(7).search("^" + $('#search-status').val() + "$", true, false, true).draw(); 
                                    }
                                    if ($('#search-evaluation_method').val() != "") 
                                    { 
                                        caseloads.columns(12).search("^" + $('#search-evaluation_method').val() + "$", true, false, true).draw(); 
                                        caseloads_startReport.columns(9).search("^" + $('#search-evaluation_method').val() + "$", true, false, true).draw(); 
                                        caseloads_endReport.columns(12).search("^" + $('#search-evaluation_method').val() + "$", true, false, true).draw(); 
                                        caseloads_medicaid.columns(10).search("^" + $('#search-evaluation_method').val() + "$", true, false, true).draw(); 
                                    }
                                    if ($('#search-grade_level').val() != "") 
                                    { 
                                        caseloads.columns(13).search("^" + $('#search-grade_level').val() + "$", true, false, true).draw(); 
                                        caseloads_startReport.columns(8).search("^" + $('#search-grade_level').val() + "$", true, false, true).draw(); 
                                        caseloads_endReport.columns(11).search("^" + $('#search-grade_level').val() + "$", true, false, true).draw(); 
                                        caseloads_medicaid.columns(9).search("^" + $('#search-grade_level').val() + "$", true, false, true).draw(); 
                                    }
                                }
                                else { createStatusModal("alert", "Loading Caseload Error", "You must select a caseload to display!"); }
                            }
                            else { createStatusModal("alert", "Loading Caseload Error", "Failed to load my caseload. You must select a period to display your caseload for."); }
                        }

                        // search caseloads from the default parameters
                        searchCaseloads();
                    </script>
                <?php 
            }
            else { denyAccess(); }

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>