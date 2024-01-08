<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        // get additional required files
        include("../../includes/config.php");
        include("../../includes/functions.php");

        // connect to the database
        $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

        if (checkUserPermission($conn, "VIEW_SALARY_COMPARISON_STATE"))
        {
            // get the required POST parameters 
            if (isset($_POST["position"]) && $_POST["position"] <> "") { $position_code = trim($_POST["position"]); } else { $position_code = null; }
            if (isset($_POST["area"]) && $_POST["area"] <> "") { $area_code = trim($_POST["area"]); } else { $area_code = null; }
            if (isset($_POST["work_type"]) && $_POST["work_type"] <> "") { $work_type = trim($_POST["work_type"]); } else { $work_type = null; }
            if (isset($_POST["work_county"]) && $_POST["work_county"] <> "") { $work_county = trim($_POST["work_county"]); } else { $work_county = null; }
            if (isset($_POST["work_level"]) && $_POST["work_level"] <> "") { $work_level = trim($_POST["work_level"]); } else { $work_level = null; }

            // create the DPI position string (position code with position name)
            $position = getPositionString($conn, $position_code);

            // create the DPI area string (area code with area name)
            $area = getAreaString($conn, $area_code);

            // create SQL LIKE filters (non-required)
            $position_filter = "%".$position."%";
            $area_filter = "%".$area."%";
            $work_type_filter = "%".$work_type."%";
            $work_county_filter = "%".$work_county."%";
            $work_level_filter = "%".$work_level."%";

            // get the total amount of employees who match this 
            $total_count = 0; // initialize total employee count to 0
            $getTotalCount = mysqli_prepare($conn, "SELECT COUNT(id) AS total_count FROM dpi_employees WHERE assignment_position LIKE ? AND assignment_area LIKE ? AND work_type LIKE ? AND work_county LIKE ? AND work_level LIKE ? AND total_salary>0");
            mysqli_stmt_bind_param($getTotalCount, "sssss", $position_filter, $area_filter, $work_type_filter, $work_county_filter, $work_level_filter);
            if (mysqli_stmt_execute($getTotalCount))
            {
                $getTotalCountResult = mysqli_stmt_get_result($getTotalCount);
                if (mysqli_num_rows($getTotalCountResult) > 0)
                {
                    $total_count = mysqli_fetch_array($getTotalCountResult)["total_count"];
                }
            }

            // get the average salary of all within the count; regardless of demographics and experience
            $avg_salary = 0;
            $getAvgSalary = mysqli_prepare($conn, "SELECT AVG(total_salary) AS avg_salary FROM dpi_employees WHERE assignment_position LIKE ? AND assignment_area LIKE ? AND work_type LIKE ? AND work_county LIKE ? AND work_level LIKE ? AND total_salary>0");
            mysqli_stmt_bind_param($getAvgSalary, "sssss", $position_filter, $area_filter, $work_type_filter, $work_county_filter, $work_level_filter);
            if (mysqli_stmt_execute($getAvgSalary))
            {
                $getAvgSalaryResult = mysqli_stmt_get_result($getAvgSalary);
                if (mysqli_num_rows($getAvgSalaryResult) > 0)
                {
                    $avg_salary = mysqli_fetch_array($getAvgSalaryResult)["avg_salary"];
                }
            }

            // get the average years of experience of all within the count; regardless of demographics
            $avg_experience = 0;
            $getAvgExperience = mysqli_prepare($conn, "SELECT AVG(total_experience) AS avg_experience FROM dpi_employees WHERE assignment_position LIKE ? AND assignment_area LIKE ? AND work_type LIKE ? AND work_county LIKE ? AND work_level LIKE ? AND total_salary>0");
            mysqli_stmt_bind_param($getAvgExperience, "sssss", $position_filter, $area_filter, $work_type_filter, $work_county_filter, $work_level_filter);
            if (mysqli_stmt_execute($getAvgExperience))
            {
                $getAvgExperienceResult = mysqli_stmt_get_result($getAvgExperience);
                if (mysqli_num_rows($getAvgExperienceResult) > 0)
                {
                    $avg_experience = mysqli_fetch_array($getAvgExperienceResult)["avg_experience"];
                }
            }

            // get the number of male employees
            $male_employees_count = 0; // initialize male employee count to 0
            $getMaleEmployeesCount = mysqli_prepare($conn, "SELECT COUNT(id) AS male_count FROM dpi_employees WHERE assignment_position LIKE ? AND assignment_area LIKE ? AND gender=1 AND work_type LIKE ? AND work_county LIKE ? AND work_level LIKE ? AND total_salary>0");
            mysqli_stmt_bind_param($getMaleEmployeesCount, "sssss", $position_filter, $area_filter, $work_type_filter, $work_county_filter, $work_level_filter);
            if (mysqli_stmt_execute($getMaleEmployeesCount))
            {
                $getMaleEmployeesCountResult = mysqli_stmt_get_result($getMaleEmployeesCount);
                if (mysqli_num_rows($getMaleEmployeesCountResult) > 0)
                {
                    $male_employees_count = mysqli_fetch_array($getMaleEmployeesCountResult)["male_count"];
                }
            }

            // get the number of female employees
            $female_employees_count = 0; // initialize female employee count to 0
            $getFemaleEmployeesCount = mysqli_prepare($conn, "SELECT COUNT(id) AS female_count FROM dpi_employees WHERE assignment_position LIKE ? AND assignment_area LIKE ? AND gender=2 AND work_type LIKE ? AND work_county LIKE ? AND work_level LIKE ? AND total_salary>0");
            mysqli_stmt_bind_param($getFemaleEmployeesCount, "sssss", $position_filter, $area_filter, $work_type_filter, $work_county_filter, $work_level_filter);
            if (mysqli_stmt_execute($getFemaleEmployeesCount))
            {
                $getFemaleEmployeesCountResult = mysqli_stmt_get_result($getFemaleEmployeesCount);
                if (mysqli_num_rows($getFemaleEmployeesCountResult) > 0)
                {
                    $female_employees_count = mysqli_fetch_array($getFemaleEmployeesCountResult)["female_count"];
                }
            }

            ?>
                <!-- CESA Salary Breakdown Table -->
                <table class="report_table w-100" id="salaries-breakdown">
                    <thead>
                        <tr>
                            <th class="text-center p-1" colspan="5">Overall Breakdown</th>
                        </tr>

                        <tr>
                            <th class="text-center p-1">Total Employees</th>
                            <th class="text-center p-1">Male Employees</th>
                            <th class="text-center p-1">Female Employees</th>
                            <th class="text-center p-1">Average Salary</th>
                            <th class="text-center p-1">Average Years Of Experience</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <td class="text-center"><?php if (isset($total_count)) { echo number_format($total_count); } else { echo "0"; } ?></td>
                            <td class="text-center"><?php if (isset($male_employees_count)) { echo number_format($male_employees_count); } else { echo "0"; }?></td>
                            <td class="text-center"><?php if (isset($female_employees_count)) { echo number_format($female_employees_count); } else { echo "0"; } ?></td>
                            <td class="text-center"><?php if (isset($avg_salary)) { echo printDollar($avg_salary); } else { echo "$0.00"; } ?></td>
                            <td class="text-center"><?php if (isset($avg_experience)) { echo number_format($avg_experience); } else { echo "0"; } ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>