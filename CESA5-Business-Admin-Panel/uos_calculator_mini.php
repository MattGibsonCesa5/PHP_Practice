<?php 
    session_start();

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]))
        {
            // get the BAP version
            include("includes/version.php");

            ?>
                <!DOCTYPE html>

                <html lang="en">

                <head>
                    <!-- Required meta tags -->
                    <meta charset="utf-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

                    <!-- Site Icon -->
                    <link rel="icon" href="img/icon.png">

                    <!-- JavaScript Functions -->
                    <script type="text/javascript" src="js/functions.js?<?php echo $version; ?>"></script>

                    <!-- Bootstrap Stylesheet -->
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
                    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
                    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">

                    <!-- Bootstrap, jQuery, and Popper Import -->
                    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
                    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" crossorigin="anonymous"></script>
                    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
                    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

                    <!-- Font Awesome -->
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g==" crossorigin="anonymous" referrerpolicy="no-referrer"/>

                    <!-- Custom styling sheets -->
                    <?php if (isset($USER_SETTINGS) && $USER_SETTINGS["dark_mode"] == 1) { ?>
                        <link rel="stylesheet" href="styles/dark.css?<?php echo $version; ?>">
                    <?php } else { // default stylesheet ?>
                        <link rel="stylesheet" href="styles/main.css?<?php echo $version; ?>">
                    <?php } ?>

                    <!-- Tab title and page icon -->
                    <link rel="icon" href="img/icon.png">
                    <title>
                        CESA 5 | Business Admin Panel
                    </title>
                </head>

                <script>
                    /** function to calculate the annaul units of service for a frequency */
                    function calculateUOS(value, type)
                    {
                        let annual_uos = 0;

                        let parsed_value = parseInt(value);

                        let type_multiply = 0;
                        if (type == "week") { type_multiply = 36; }
                        else if (type == "month") { type_multiply = 9; }
                        else if (type == "quarter") { type_multiply = 4; }
                        else if (type == "trimester") { type_multiply = 3; }
                        else if (type == "semester") { type_multiply = 2; }
                        else if (type == "year") { type_multiply = 1; }

                        if (parsed_value > 0) { annual_uos = (((((parsed_value * type_multiply) * 0.3) + (parsed_value * type_multiply)) / 15) + 12); }

                        document.getElementById("total-"+type).value = annual_uos.toFixed(2);

                        calculateTotalUOS();
                    }

                    /** function to calculate the combined annual UOS total */
                    function calculateTotalUOS()
                    {
                        // initialize the total (assume 0)
                        let total = 0;

                        // get the current element values - parse to an integer
                        let weekly = parseFloat(document.getElementById("total-week").value);
                        let monthly = parseFloat(document.getElementById("total-month").value);
                        let quarterly = parseFloat(document.getElementById("total-quarter").value);
                        let trimesterly = parseFloat(document.getElementById("total-trimester").value);
                        let semesterly = parseFloat(document.getElementById("total-semester").value);
                        let yearly = parseFloat(document.getElementById("total-year").value);

                        if (isNaN(weekly)) { weekly = 0; }
                        if (isNaN(monthly)) { monthly = 0; }
                        if (isNaN(quarterly)) { quarterly = 0; }
                        if (isNaN(trimesterly)) { trimesterly = 0; }
                        if (isNaN(semesterly)) { semesterly = 0; }
                        if (isNaN(yearly)) { yearly = 0; }

                        total = weekly + monthly + quarterly + trimesterly + semesterly + yearly;

                        document.getElementById("total-uos").value = Math.ceil(total);
                    }

                    /** function to clear the UOS calculator */
                    function clearCalculator()
                    {
                        // set all form elements to blank
                        document.getElementById("input-week").value = "";
                        document.getElementById("input-month").value = "";
                        document.getElementById("input-quarter").value = "";
                        document.getElementById("input-trimester").value = "";
                        document.getElementById("input-semester").value = "";
                        document.getElementById("input-year").value = "";
                        document.getElementById("total-week").value = "";
                        document.getElementById("total-month").value = "";
                        document.getElementById("total-quarter").value = "";
                        document.getElementById("total-trimester").value = "";
                        document.getElementById("total-semester").value = "";
                        document.getElementById("total-year").value = "";
                        document.getElementById("total-uos").value = "";
                    }
                </script>

                <div class="report">
                    <div class="row justify-content-center report-header mb-3 mx-0"> 
                        <div class="col-12 col-sm-12 col-md-12 col-lg-10 col-xl-10 col-xxl-10 p-0">
                            <h1 class="report-title m-0">UOS Calculator</h1>
                            <p class="report-description m-0">Enter in the fields and get the units of service based on the frequency.</p>
                        </div>
                    </div>

                    <div class="row report-body justify-content-center m-0">
                        <div class="col-12 col-sm-12 col-md-12 col-lg-10 col-xl-10 col-xxl-10">
                            <div class="form-row d-flex justify-content-center align-items-center">
                                <!-- Enrollment Type -->
                                <div class="form-group col-3">
                                    <h2><b>Frequency</b></h2>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <!-- Enrollment Type -->
                                <div class="form-group col-3">
                                    
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <!-- Enrollment Type -->
                                <div class="form-group col-3">
                                    <h2><b>Annual UOS</b></h2>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center mt-2 mb-4">
                                <div class="form-group col-3">
                                    <label for="total-week"><h3>Minutes/Week</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-week" name="input-week" onkeyup="calculateUOS(this.value, 'week');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-week" name="total-week" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <label for="total-month"><h3>Minutes/Month</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-month" name="input-month" onkeyup="calculateUOS(this.value, 'month');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-month" name="total-month" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <label for="total-quarter"><h3>Minutes/Quarter</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-quarter" name="input-quarter" onkeyup="calculateUOS(this.value, 'quarter');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-quarter" name="total-quarter" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <label for="total-trimester"><h3>Minutes/Trimester</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-trimester" name="input-trimester" onkeyup="calculateUOS(this.value, 'trimester');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-trimester" name="total-trimester" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <label for="total-semester"><h3>Minutes/Semester</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-semester" name="input-semester" onkeyup="calculateUOS(this.value, 'semester');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-semester" name="total-semester" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <label for="total-year"><h3>Minutes/Year</h3></label>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>
                                
                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="input-year" name="input-year" onkeyup="calculateUOS(this.value, 'year');">
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-1"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-year" name="total-year" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-3">
                                    <h3><label for="total-uos"><b>TOTAL</b> <i>(rounded)</i></label></h3>
                                </div>

                                <!-- Divider -->
                                <div class="form-group col-5"></div>

                                <div class="form-group col-3">
                                    <input class="form-control" type="number" id="total-uos" readonly disabled>
                                </div>
                            </div>

                            <div class="form-row d-flex justify-content-center align-items-center my-4">
                                <div class="form-group col-6 col-sm-6 col-md-4 col-lg-4 col-xl-3 col-xxl-3">
                                    <button class="btn btn-secondary w-100" onclick="clearCalculator();">
                                        <div class="row">
                                            <div class="col-3"><i class="fa-solid fa-delete-left"></i></div>
                                            <div class="col-6">Clear</div>
                                            <div class="col-3"></div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
        }
    }
?>