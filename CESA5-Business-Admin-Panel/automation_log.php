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
                        // send the data to search the log
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "automation/getAutomationLog.php", true);
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
                                        { width: "15%" }, // time
                                        { width: "85%" }, // log message
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
                        xmlhttp.send();
                    }

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