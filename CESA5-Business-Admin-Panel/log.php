<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            ?> 
                <div class="report">
                    <div class="table-header">
                        <div class="row d-flex justify-content-center align-items-center text-center p-2">
                            <div class="col-12">
                                <h1 class="report-title m-0">Log</h1>
                            </div>
                        </div>

                        <!-- Body -->
                        <div class="row d-flex justify-content-center align-items-around m-0 pb-2">
                            <!-- Filters (Labels) -->
                            <div class="row d-flex justify-content-center align-items-around m-0">
                                <!-- User -->
                                <div class="col-2 text-center"><label for="user_id">User</label></div>

                                <!-- Start Date -->
                                <div class="col-2 text-center"><label for="date-start">Start Date</label></div>

                                <!-- End Date -->
                                <div class="col-2 text-center"><label for="date-end">End Date</label></div>

                                <!-- # Of Records -->
                                <div class="col-2 text-center"><label for="date-end"># Of Entries</label></div>
                            </div>

                            <!-- Filters (Input) -->
                            <div class="row d-flex justify-content-center align-items-around m-0">
                                <!-- User -->
                                <div class="col-2">
                                    <div class="input-group w-100">
                                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                                        <select class="form-select" id="user_id" name="user_id" onchange="searchLog();">
                                            <option></option>
                                            <option value="0"><?php echo SUPER_LOGIN_EMAIL; ?></option>
                                            <option value="-2">AUTOMATION</option>
                                            <?php
                                                // create an option for all users in the log
                                                $logUsers = mysqli_query($conn, "SELECT DISTINCT l.user_id, u.email FROM log l JOIN users u ON l.user_id=u.id");
                                                if (mysqli_num_rows($logUsers) > 0)
                                                {
                                                    while ($user = mysqli_fetch_array($logUsers))
                                                    {
                                                        echo "<option value='".$user["user_id"]."'>".$user["email"]."</option>";
                                                    }
                                                }                                    
                                            ?>
                                        </select>
                                    </div>
                                </div>

                                <!-- Start Date -->
                                <div class="col-2">
                                    <div class="input-group w-100">
                                        <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
                                        <input type="text" class="form-control" id="date-start" name="date-start" onchange="searchLog();">
                                    </div>
                                </div>

                                <!-- End Date -->
                                <div class="col-2">
                                    <div class="input-group w-100">
                                        <span class="input-group-text"><i class="fa-solid fa-calendar-days"></i></span>
                                        <input type="text" class="form-control" id="date-end" name="date-end" onchange="searchLog();">
                                    </div>
                                </div>

                                <!-- End Date -->
                                <div class="col-2">
                                    <div class="input-group w-100">
                                        <span class="input-group-text"><i class="fa-solid fa-list-ol"></i></span>
                                        <select class="form-select" id="records" name="records" onchange="searchLog();">
                                            <option value="100" selected>Show last 100 records</option>
                                            <option value="250">Show last 250 records</option>
                                            <option value="500">Show last 500 records</option>
                                            <option value="1000">Show last 1000 records</option>
                                            <option value="">Show All</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row d-flex justify-content-center align-items-center text-center pt-3 pb-2">
                                <!-- Page Length -->
                                <div class="col-12 col-sm-12 col-md-12 col-lg-3 col-xl-3 col-xxl-3">
                                    <?php createPageLengthContainer("log", "BAP_Log_PageLength", $USER_SETTINGS["page_length"]); ?>
                                </div>

                                <!-- Department Filters -->
                                <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6">
                                    <div class="row justify-content-center">
                                        <!-- Search All -->
                                        <div class="col-12 col-sm-12 col-md-6 col-lg-6 col-xl-6 col-xxl-6 h-100 px-2">
                                            <div class="input-group h-auto">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text h-100" id="nav-search-icon">
                                                        <label for="search-all"><i class="fa-solid fa-magnifying-glass"></i></label>
                                                    </span>
                                                </div>
                                                <input class="form-control" type="text" placeholder="Search table..." id="search-all" name="search-all" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Spacer -->
                                <div class="col-12 col-sm-12 col-md-12 col-lg-3 col-xl-3 col-xxl-3"></div>
                            </div>
                        </div>
                    </div> 

                    <!-- Log -->
                    <div class="row d-flex justify-content-center align-items-around m-0 p-0" id="log-div"></div>
                </div>

                <script>
                    // function to search the logs based on the given search filters
                    function searchLog()
                    {
                        // get search filters
                        var user_id = document.getElementById("user_id").value;
                        var start = document.getElementById("date-start").value;
                        var end = document.getElementById("date-end").value;
                        var records = document.getElementById("records").value;

                        // send the data to search the log
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/misc/searchLog.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                document.getElementById("log-div").innerHTML = this.responseText;
                                var log = $("#log").DataTable({
                                    autoWidth: false,
                                    pageLength: <?php if (isset($USER_SETTINGS["page_length"])) { echo $USER_SETTINGS["page_length"]; } else { echo 10; } ?>,
                                    lengthMenu: [ [10, 25, 50, 100, 250, -1], [10, 25, 50, 100, 250, "All"] ],
                                    dom: 'rt',
                                    columns: [
                                        { visible: false },
                                        { width: "12.5%" }, // time
                                        { width: "7.5%" }, // user ID
                                        { width: "15%" }, // user name
                                        { width: "10%" }, // user email
                                        { width: "10%" }, // user role
                                        { width: "45%" }, // log message
                                    ],
                                    order: [ // order by time descending (most recent log entries first based on record)
                                        [ 0, "desc" ],
                                        [ 1, "desc" ]
                                    ],
                                    language: {
                                        search: '<div class="input-group mb-1"><div class="input-group-prepend"><span class="d-flex align-items-center h-100 mx-2" id="nav-search-icon"><i class="fa fa-search" aria-hidden="true"></i></span></div>_INPUT_</div>',
                                        lengthMenu: '<div class="mx-1 mb-1">Show _MENU_ entries</div>',
                                        info: '<div class="mx-1">Showing _START_ to _END_ of _TOTAL_ entries</div>'
                                    },
                                    paging: true,
                                    rowCallback: function (row, data, index)
                                    {
                                        updatePageSelection("log");
                                    },
                                });

                                // search table by custom search filter
                                $('#search-all').keyup(function() {
                                    log.search($(this).val()).draw();
                                });
                            }
                        };
                        xmlhttp.send("user_id="+user_id+"&start="+start+"&end="+end+"&records="+records);
                    }

                    // initialize the date range pickers
                    $(function() {
                        $("#date-start").daterangepicker({
                            autoUpdateInput: false,
                            singleDatePicker: true,
                            showDropdowns: true,
                            minYear: 2022,
                            maxYear: <?php echo date("Y"); ?>,
                            locale: {
                                cancelLabel: "Clear"
                            }
                        });

                        $("#date-end").daterangepicker({
                            autoUpdateInput: false,
                            singleDatePicker: true,
                            showDropdowns: true,
                            minYear: 2022,
                            maxYear: <?php echo date("Y"); ?>,
                            locale: {
                                cancelLabel: "Clear"
                            }
                        });

                        // look for clearing of daterangepickers
                        $("#date-start").on("cancel.daterangepicker", function(ev, picker) {
                            $("#date-start").val("");
                        });
                        $("#date-end").on("cancel.daterangepicker", function(ev, picker) {
                            $("#date-end").val("");
                        });
                        $('#date-start').on('apply.daterangepicker', function (ev, picker) {
                            $("#date-start").val(picker.startDate.format('L'));
                        });
                        $("#date-end").on('apply.daterangepicker', function (ev, picker) {
                            $("#date-end").val(picker.startDate.format('L'));
                        });
                    });

                    // search the log on load with default parameters
                    searchLog();
                </script>
            <?php
            
            // disconnect from the database
            mysqli_close($conn);
        }
        else { denyAccess(); }
    }
    else { goToLogin(); }

    include_once("footer.php"); 
?>