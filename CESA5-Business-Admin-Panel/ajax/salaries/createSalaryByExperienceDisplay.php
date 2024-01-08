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

            ?>
                <!-- Experience Salary Breakdown Table -->
                <table class="report_table w-100" id="salaries-by_experience">
                    <thead>
                        <tr>
                            <th class="text-center p-1" colspan="17">Years Of Total Experience</th>
                        </tr>

                        <tr>
                            <th class="text-center p-1">0</th>
                            <th class="text-center p-1">1</th>
                            <th class="text-center p-1">2</th>
                            <th class="text-center p-1">3</th>
                            <th class="text-center p-1">4</th>
                            <th class="text-center p-1">5</th>
                            <th class="text-center p-1">6</th>
                            <th class="text-center p-1">7</th>
                            <th class="text-center p-1">8</th>
                            <th class="text-center p-1">9</th>
                            <th class="text-center p-1">10</th>
                            <th class="text-center p-1">11</th>
                            <th class="text-center p-1">12</th>
                            <th class="text-center p-1">13</th>
                            <th class="text-center p-1">14</th>
                            <th class="text-center p-1">15</th>
                            <th class="text-center p-1">16+</th>
                        </tr>
                    </thead>

                    <tbody>
                        <tr>
                            <?php 
                                // get the average salary by years of experience
                                for ($x = 0; $x <= 16; $x++)
                                {
                                    if ($position <> "" && $area <> "")
                                    {
                                        $avg_salary = null;
                                        if ($x != 16)
                                        {
                                            $getAvgSalary = mysqli_prepare($conn, "SELECT AVG(total_salary) AS avg_salary FROM dpi_employees WHERE assignment_position=? AND assignment_area=? AND total_experience=? AND (work_type LIKE ? AND work_county LIKE ? AND work_level LIKE ?) AND total_salary>0");
                                            mysqli_stmt_bind_param($getAvgSalary, "ssisss", $position, $area, $x, $work_type_filter, $work_county_filter, $work_level_filter);
                                            if (mysqli_stmt_execute($getAvgSalary))
                                            {
                                                $getAvgSalaryResult = mysqli_stmt_get_result($getAvgSalary);
                                                if (mysqli_num_rows($getAvgSalaryResult) > 0)
                                                {
                                                    $avg_salary = mysqli_fetch_array($getAvgSalaryResult)["avg_salary"];
                                                }
                                            }
                                        }
                                        else if ($x == 16)
                                        {
                                            $getAvgSalary = mysqli_prepare($conn, "SELECT AVG(total_salary) AS avg_salary FROM dpi_employees WHERE assignment_position=? AND assignment_area=? AND total_experience>=? AND (work_type LIKE ? AND work_county LIKE ? AND work_level LIKE ?) AND total_salary>0");
                                            mysqli_stmt_bind_param($getAvgSalary, "ssisss", $position, $area, $x, $work_type_filter, $work_county_filter, $work_level_filter);
                                            if (mysqli_stmt_execute($getAvgSalary))
                                            {
                                                $getAvgSalaryResult = mysqli_stmt_get_result($getAvgSalary);
                                                if (mysqli_num_rows($getAvgSalaryResult) > 0)
                                                {
                                                    $avg_salary = mysqli_fetch_array($getAvgSalaryResult)["avg_salary"];
                                                }
                                            }
                                        }
                                        if (isset($avg_salary)) { echo "<td class='text-end'>".printDollar($avg_salary)."</td>"; } else { echo "<td class='text-end'>-</td>"; }
                                    }
                                    else
                                    {
                                        $avg_salary = null;
                                        if ($x != 16)
                                        {
                                            $getAvgSalary = mysqli_prepare($conn, "SELECT AVG(total_salary) AS avg_salary FROM dpi_employees WHERE assignment_position LIKE ? AND assignment_area LIKE ? AND total_experience=? AND work_type LIKE ? AND work_county LIKE ? AND work_level LIKE ? AND total_salary>0");
                                            mysqli_stmt_bind_param($getAvgSalary, "ssisss", $position_filter, $area_filter, $x, $work_type_filter, $work_county_filter, $work_level_filter);
                                            if (mysqli_stmt_execute($getAvgSalary))
                                            {
                                                $getAvgSalaryResult = mysqli_stmt_get_result($getAvgSalary);
                                                if (mysqli_num_rows($getAvgSalaryResult) > 0)
                                                {
                                                    $avg_salary = mysqli_fetch_array($getAvgSalaryResult)["avg_salary"];
                                                }
                                            }
                                        }
                                        else if ($x == 16)
                                        {
                                            $getAvgSalary = mysqli_prepare($conn, "SELECT AVG(total_salary) AS avg_salary FROM dpi_employees WHERE assignment_position LIKE ? AND assignment_area LIKE ? AND total_experience>=? AND work_type LIKE ? AND work_county LIKE ? AND work_level LIKE ? AND total_salary>0");
                                            mysqli_stmt_bind_param($getAvgSalary, "ssisss", $position_filter, $area_filter, $x, $work_type_filter, $work_county_filter, $work_level_filter);
                                            if (mysqli_stmt_execute($getAvgSalary))
                                            {
                                                $getAvgSalaryResult = mysqli_stmt_get_result($getAvgSalary);
                                                if (mysqli_num_rows($getAvgSalaryResult) > 0)
                                                {
                                                    $avg_salary = mysqli_fetch_array($getAvgSalaryResult)["avg_salary"];
                                                }
                                            }
                                        }
                                        if (isset($avg_salary)) { echo "<td class='text-end'>".printDollar($avg_salary)."</td>"; } else { echo "<td class='text-end'>-</td>"; }
                                    }
                                }
                            ?>
                        </tr>
                    </tbody>
                </table>
            <?php
        }
        
        // disconnect from the database
        mysqli_close($conn);
    }
?>