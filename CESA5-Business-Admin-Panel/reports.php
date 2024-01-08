<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"]) || 
            isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED"]) || 
            isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"]) || 
            isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"]) || 
            isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"]))
        {
            ?> 
                <!-- Header -->
                <div class="row m-0 p-0">
                    <h1 class="col-12 col-sm-8 col-md-6 col-lg-4 col-xl-4 col-xxl-4 page-header my-3 py-3 ps-3 pe-5">
                        <a class="back-button" href="dashboard.php" title="Return to the dashboard."><i class="fa-solid fa-angles-left"></i></a>
                        <div class="d-inline float-end">Reports</div>
                    </h1>
                </div>

                <!-- Body -->
                <div class="row d-flex justify-content-center align-items-around m-0">
                    <?php if (isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"])) { ?>
                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="days_budgeted.php">Budgeted Employees</a>
                    </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"])) { ?>
                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="days_misbudgeted.php">Misbudgeted Employees</a>
                    </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED"])) { ?>
                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="report_inactive.php">Budgeted Inactive Employees</a>
                    </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"])) { ?>
                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="report_testEmployees.php">Test Employees</a>
                    </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"])) { ?>
                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="salary_projection.php">Salary Projection</a>
                    </div>
                    <?php } ?>

                    <?php if (isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"])) { ?>
                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="employee_changes.php">Employee Changes</a>
                    </div>
                    <?php } ?>

                    <?php if ($_SESSION["role"] == 1) { // ADMIN ONLY ?>
                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="report-sped-billing.php">SPED Billing Verification</a>
                    </div>

                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="report-payroll.php">Payroll Report</a>
                    </div>

                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="report-consecutive_yoe.php">Consecutive Y.O.E Report</a>
                    </div>

                    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-4 col-xxl-4 p-3">
                        <a class="btn-dash d-flex w-100 h-100 justify-content-center align-items-center p-5" href="cash_tracker.php">Cash Flow Tracker</a>
                    </div>
                    <?php } ?>
                </div>
            <?php
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }
    
    include_once("footer.php"); 
?>