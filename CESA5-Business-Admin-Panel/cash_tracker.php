<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?>
                <div class="row w-100">
                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-6 col-xxl-6">
                        <div id="revenues-chart_div"></div>
                    </div>

                    <div class="col-12 col-sm-12 col-md-12 col-lg-12 col-xl-6 col-xxl-6">
                        <div id="expenses-chart_div"></div>
                    </div>

                    <div class="col-12">
                        <div id="chart_div"></div>
                    </div>
                </div>

                <script>
                    // get net income tracker data
                    var income_chart_data = JSON.parse($.ajax({
                        url: "ajax/tracker/getIncomeTracker.php",
                        async: false
                    }).responseText);

                    // get revenues tracker data
                    var revenues_chart_data = JSON.parse($.ajax({
                        url: "ajax/tracker/getRevenuesTracker.php",
                        async: false
                    }).responseText);
                    revenues_chart_data.unshift(['Revenue Method', 'Services', 'Other Services', 'Other Revenues', { role: 'annotation' } ]);

                    // get expenses tracker data
                    var expenses_chart_data = JSON.parse($.ajax({
                        url: "ajax/tracker/getExpensesTracker.php",
                        async: false
                    }).responseText);
                    expenses_chart_data.unshift(['Expense Method', 'Project Expenses', 'Salaries', 'Health Insurance', 'Dental Insurance', 'Wisconsin Retirement System', 'FICA', 'Long-term Disability', 'Life Insurance', { role: 'annotation' } ]);

                    // load the Visualization API and the piechart package
                    google.charts.load('current', {'packages':['corechart']});
                    
                    // set a callback to run when the Google Visualization API is loaded
                    google.charts.setOnLoadCallback(drawRevenuesChart);
                    google.charts.setOnLoadCallback(drawExpensesChart);
                    google.charts.setOnLoadCallback(drawIncomeChart);

                    // redraw graph when window resize is completed  
                    function resize() 
                    {
                        drawChart();
                        drawRevenuesChart();
                        drawExpensesChart();
                    }
                        
                    // draw the income tracker chart
                    function drawIncomeChart() 
                    {
                        // Create our data table out of JSON data loaded from server.
                        var data = new google.visualization.arrayToDataTable(income_chart_data);

                        var options = {
                            width: "100%",
                            height: 400,
                            title: 'Company Performance',
                            curveType: 'function',
                            legend: { position: 'bottom' }
                        };
                
                        // Instantiate and draw our chart, passing in some options.
                        var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
                        chart.draw(data, options);
                    }

                    // draw the revenues tracker chart
                    function drawRevenuesChart() 
                    {
                        // Create our data table out of JSON data loaded from server.
                        var data = new google.visualization.arrayToDataTable(revenues_chart_data);

                        var options = {
                            width: "100%",
                            height: 400,
                            legend: { position: 'top', maxLines: 3 },
                            bar: { groupWidth: '75%' },
                            isStacked: true
                        };

                        // Instantiate and draw our chart, passing in some options.
                        var chart = new google.visualization.ColumnChart(document.getElementById('revenues-chart_div'));
                        chart.draw(data, options);
                    }

                    // draw the expneses tracker chart
                    function drawExpensesChart()
                    {
                        // Create our data table out of JSON data loaded from server.
                        var data = new google.visualization.arrayToDataTable(expenses_chart_data);

                        var options = {
                            width: "100%",
                            height: 400,
                            legend: { position: 'top', maxLines: 3 },
                            bar: { groupWidth: '75%' },
                            isStacked: true
                        };

                        // Instantiate and draw our chart, passing in some options.
                        var chart = new google.visualization.ColumnChart(document.getElementById('expenses-chart_div'));
                        chart.draw(data, options);
                    }

                    // on page load and page resize, resize/redraw the charts
                    window.onload = resize;
                    window.onresize = resize;
                </script>
            <?php

            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include("footer.php"); 
?>