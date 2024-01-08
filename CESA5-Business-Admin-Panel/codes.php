<?php 
    include_once("header.php");

    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) 
    { 
        if (isset($_SESSION["role"]) && $_SESSION["role"] == 1)
        {
            // get the settings file
            include("getSettings.php");

            // connect to the database
            $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

            // pre-initialize all codes
            $health = [];
            $health["Single"] = "";
            $health["Family"] = "";
            $health["None"] = "";

            $dental = [];
            $dental["Single"] = "";
            $dental["Family"] = "";
            $dental["None"] = "";

            $WRS = [];
            $WRS["Yes"] = "";
            $WRS["No"] = "";

            $MaritalStatus = [];
            $MaritalStatus["Single"] = "";
            $MaritalStatus["Family"] = "";

            $AddressTypes = [];
            $AddressTypes["Street"] = "";
            $AddressTypes["PO"] = "";

            // get current codes from database
            $getHealthCodes = mysqli_query($conn, "SELECT code, plan FROM codes WHERE indicator='Health'");
            while ($code = mysqli_fetch_array($getHealthCodes))
            {
                if ($code["plan"] == "None") { $health["None"] = $code["code"]; }
                else if ($code["plan"] == "Single") { $health["Single"] = $code["code"]; }
                else if ($code["plan"] == "Family") { $health["Family"] = $code["code"]; }
            }

            $getDentalCodes = mysqli_query($conn, "SELECT code, plan FROM codes WHERE indicator='Dental'");
            while ($code = mysqli_fetch_array($getDentalCodes))
            {
                if ($code["plan"] == "None") { $dental["None"] = $code["code"]; }
                else if ($code["plan"] == "Single") { $dental["Single"] = $code["code"]; }
                else if ($code["plan"] == "Family") { $dental["Family"] = $code["code"]; }
            }

            $getWRSCodes = mysqli_query($conn, "SELECT code, plan FROM codes WHERE indicator='WRS'");
            while ($code = mysqli_fetch_array($getWRSCodes))
            {
                if ($code["plan"] == "Yes") { $WRS["Yes"] = $code["code"]; }
                else if ($code["plan"] == "No") { $WRS["No"] = $code["code"]; }
            }

            $getGenderCodes = mysqli_query($conn, "SELECT code, plan FROM codes WHERE indicator='Gender'");
            while ($code = mysqli_fetch_array($getGenderCodes))
            {
                if ($code["plan"] == "Male") { $Gender["Male"] = $code["code"]; }
                else if ($code["plan"] == "Female") { $Gender["Female"] = $code["code"]; }
            }

            $getMaritalStatusCodes = mysqli_query($conn, "SELECT code, plan FROM codes WHERE indicator='Marital Status'");
            while ($code = mysqli_fetch_array($getMaritalStatusCodes))
            {
                if ($code["plan"] == "Single") { $MaritalStatus["Single"] = $code["code"]; }
                else if ($code["plan"] == "Married") { $MaritalStatus["Married"] = $code["code"]; }
            }

            $getAddressCodes = mysqli_query($conn, "SELECT code, plan FROM codes WHERE indicator='Address Type'");
            while ($code = mysqli_fetch_array($getAddressCodes))
            {
                if ($code["plan"] == "Street") { $AddressTypes["Street"] = $code["code"]; }
                else if ($code["plan"] == "PO") { $AddressTypes["PO"] = $code["code"]; }
            }

            ?>
                <script>
                    /** function to process updates when we modify an expense */
                    function modifiedCode(row)
                    {
                        // enable the button as we made a change
                        document.getElementById("edit-"+row).removeAttribute("disabled");
                    }

                    /** function to save an expense */
                    function saveCodes(row)
                    {
                        // insurance row has been edited
                        if (row == "health" || row == "dental")
                        {
                            // get the new values
                            var single = document.getElementById(row + "-single").value;
                            var family = document.getElementById(row + "-family").value;
                            var none = document.getElementById(row + "-none").value;

                            // create the string of data to send
                            var sendString = "type="+row+"&single="+single+"&family="+family+"&none="+none;
                        }
                        // WRS row has been edited
                        else if (row == "wrs")
                        {
                            // get the new values
                            var yes = document.getElementById(row + "-yes").value;
                            var no = document.getElementById(row + "-no").value;

                            // create the string of data to send
                            var sendString = "type="+row+"&yes="+yes+"&no="+no;
                        }
                        // Gender row has been edited
                        else if (row == "gender")
                        {
                            // get the new values
                            var male = document.getElementById(row + "-male").value;
                            var female = document.getElementById(row + "-female").value;

                            // create the string of data to send
                            var sendString = "type="+row+"&male="+male+"&female="+female;
                        }
                        // Marital Status row has been edited
                        else if (row == "marital_status")
                        {
                            // get the new values
                            var single = document.getElementById(row + "-single").value;
                            var married = document.getElementById(row + "-married").value;

                            // create the string of data to send
                            var sendString = "type="+row+"&single="+single+"&married="+married;
                        }
                        // Address Types row has been edited
                        else if (row == "address_type")
                        {
                            // get the new values
                            var street = document.getElementById(row + "-street").value;
                            var po = document.getElementById(row + "-po").value;

                            // create the string of data to send
                            var sendString = "type="+row+"&street="+street+"&po="+po;
                        }

                        // send the data to update the expense
                        var xmlhttp = new XMLHttpRequest();
                        xmlhttp.open("POST", "ajax/misc/updateCodes.php", true);
                        xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xmlhttp.onreadystatechange = function() 
                        {
                            if (this.readyState == 4 && this.status == 200)
                            {
                                if (this.responseText == 1)
                                {
                                    // set the button to disabled as we saved the expense
                                    document.getElementById("edit-"+row).setAttribute("disabled", true);
                                }
                            }
                        };
                        xmlhttp.send(sendString);
                    }
                </script>

                <div class="report">
                    <div class="row report-header mb-3 mx-0"> 
                        <div class="col-3 p-0"></div>
                        <div class="col-6 p-0">
                            <fieldset class="border p-2">
                                <legend class="float-none w-auto px-4 py-0 m-0"><h1 class="report-title m-0">Codes</h1></legend>
                                <div class="report-description">
                                    <p>Manage the budgeting codes for employee benefits. These codes will be used to check uploads.</p>
                                    <p>In our system, we have fixed statuses for insurance, retirement plans, etc; however, our codes may not match the codes from outside sources. When you upload employees, please ensure the data in the upload matches the codes you have set here!</p>
                                </div>
                            </fieldset>
                        </div>
                        <div class="col-3 p-0"></div>
                    </div>

                    <div class="row report-body m-0">
                        <!-- Insurances Table -->
                        <table id="insurances" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th colspan="6" class="text-center">Insurance Codes</th>
                                </tr>
                            
                                <tr>
                                    <th rowspan="2" class="text-center">Insurance</th>
                                    <th rowspan="2" class="text-center">Description</th>
                                    <th colspan="3" class="text-center">Codes</th>
                                    <th rowspan="2" class="text-center">Actions</th>
                                </tr>

                                <tr>
                                    <th class="text-center">Single</th>
                                    <th class="text-center">Family</th>
                                    <th class="text-center">None</th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- Health Insurance Codes -->
                                <tr>
                                    <td><label id="health-label">Health</label></td>
                                    <td>Edit the health insurance codes.</td>
                                    <td><input class="form-control" type="text" id="health-single" name="health-single" value="<?php echo $health["Single"]; ?>" onchange="modifiedCode('health');" aria-labelledby="health-label"></td>
                                    <td><input class="form-control" type="text" id="health-family" name="health-family" value="<?php echo $health["Family"]; ?>" onchange="modifiedCode('health');" aria-labelledby="health-label"></td>
                                    <td><input class="form-control" type="text" id="health-none" name="health-none" value="<?php echo $health["None"]; ?>" onchange="modifiedCode('health');" aria-labelledby="health-label"></td>
                                    <td><button class="btn btn-secondary w-100" id="edit-health" aria-label="Save data in row." onclick="saveCodes('health');" disabled><i class="fa-solid fa-floppy-disk"></i></button></td>
                                </tr>

                                <!-- Dental Insurance Codes -->
                                <tr>
                                    <td><label id="dental-label">Dental</label></td>
                                    <td>Edit the dental insurance codes.</td>
                                    <td><input class="form-control" type="text" id="dental-single" name="dental-single" value="<?php echo $dental["Single"]; ?>" onchange="modifiedCode('dental');" aria-labelledby="dental-label"></td>
                                    <td><input class="form-control" type="text" id="dental-family" name="dental-family" value="<?php echo $dental["Family"]; ?>" onchange="modifiedCode('dental');" aria-labelledby="dental-label"></td>
                                    <td><input class="form-control" type="text" id="dental-none" name="dental-none" value="<?php echo $dental["None"]; ?>" onchange="modifiedCode('dental');" aria-labelledby="dental-label"></td>
                                    <td><button class="btn btn-secondary w-100" id="edit-dental" aria-label="Save data in row." onclick="saveCodes('dental');" disabled><i class="fa-solid fa-floppy-disk"></i></button></td>
                                </tr>
                            </tbody>
                        </table>

                        <hr class="projects-hr my-3">

                        <!-- Retirement Table -->
                        <table id="retirement" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th colspan="5" class="text-center">Wisconsin Retirement System Codes</th>
                                </tr>

                                <tr>
                                    <th rowspan="2" class="text-center">Name</th>
                                    <th rowspan="2" class="text-center">Description</th>
                                    <th colspan="2" class="text-center">Codes</th>
                                    <th rowspan="2" class="text-center">Actions</th>
                                </tr>

                                <tr>
                                    <th class="text-center">Eligible</th>
                                    <th class="text-center">Not Eligible</th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- Health Insurance Codes -->
                                <tr>
                                    <td><label id="wrs-label">WRS Eligibility</label></td>
                                    <td>Edit the WRS eligibility codes.</td>
                                    <td><input class="form-control" type="text" id="wrs-yes" name="wrs-yes" value="<?php echo $WRS["Yes"]; ?>" onchange="modifiedCode('wrs');" aria-labelledby="wrs-label"></td>
                                    <td><input class="form-control" type="text" id="wrs-no" name="wrs-no" value="<?php echo $WRS["No"]; ?>" onchange="modifiedCode('wrs');" aria-labelledby="wrs-label"></td>
                                    <td><button class="btn btn-secondary w-100" id="edit-wrs" aria-label="Save data in row." onclick="saveCodes('wrs');" disabled><i class="fa-solid fa-floppy-disk"></i></button></td>
                                </tr>
                            </tbody>
                        </table>

                        <hr class="projects-hr my-3">

                        <!-- Gender Table -->
                        <table id="gender" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th colspan="5" class="text-center">Gender Codes</th>
                                </tr>

                                <tr>
                                    <th rowspan="2" class="text-center">Name</th>
                                    <th rowspan="2" class="text-center">Description</th>
                                    <th colspan="2" class="text-center">Codes</th>
                                    <th rowspan="2" class="text-center">Actions</th>
                                </tr>

                                <tr>
                                    <th class="text-center">Male</th>
                                    <th class="text-center">Female</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr>
                                    <td><label id="gender-label">Gender</label></td>
                                    <td>Edit the gender codes.</td>
                                    <td><input class="form-control" type="text" id="gender-male" name="gender-male" value="<?php echo $Gender["Male"]; ?>" onchange="modifiedCode('gender');" aria-labelledby="gender-label"></td>
                                    <td><input class="form-control" type="text" id="gender-female" name="gender-female" value="<?php echo $Gender["Female"]; ?>" onchange="modifiedCode('gender');" aria-labelledby="gender-label"></td>
                                    <td><button class="btn btn-secondary w-100" id="edit-gender" aria-label="Save data in row." onclick="saveCodes('gender');" disabled><i class="fa-solid fa-floppy-disk"></i></button></td>
                                </tr>
                            </tbody>
                        </table>

                        <hr class="projects-hr my-3">

                        <!-- Marital Status Table -->
                        <table id="marital_status" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th colspan="5" class="text-center">Marital Status Codes</th>
                                </tr>

                                <tr>
                                    <th rowspan="2" class="text-center">Name</th>
                                    <th rowspan="2" class="text-center">Description</th>
                                    <th colspan="2" class="text-center">Codes</th>
                                    <th rowspan="2" class="text-center">Actions</th>
                                </tr>

                                <tr>
                                    <th class="text-center">Single</th>
                                    <th class="text-center">Married</th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- Health Insurance Codes -->
                                <tr>
                                    <td><label id="marital_status-label">Marital Status</label></td>
                                    <td>Edit the marital status codes.</td>
                                    <td><input class="form-control" type="text" id="marital_status-single" name="marital_status-single" value="<?php echo $MaritalStatus["Single"]; ?>" onchange="modifiedCode('marital_status');" aria-labelledby="marital_status-label"></td>
                                    <td><input class="form-control" type="text" id="marital_status-married" name="marital_status-married" value="<?php echo $MaritalStatus["Married"]; ?>" onchange="modifiedCode('marital_status');" aria-labelledby="marital_status-label"></td>
                                    <td><button class="btn btn-secondary w-100" id="edit-marital_status" aria-label="Save data in row." onclick="saveCodes('marital_status');" disabled><i class="fa-solid fa-floppy-disk"></i></button></td>
                                </tr>
                            </tbody>
                        </table>

                        <hr class="projects-hr my-3">

                        <!-- Address Types Table -->
                        <table id="address_types" class="report_table w-100">
                            <thead>
                                <tr>
                                    <th colspan="5" class="text-center">Address Type Codes</th>
                                </tr>

                                <tr>
                                    <th rowspan="2" class="text-center">Name</th>
                                    <th rowspan="2" class="text-center">Description</th>
                                    <th colspan="2" class="text-center">Codes</th>
                                    <th rowspan="2" class="text-center">Actions</th>
                                </tr>

                                <tr>
                                    <th class="text-center">Street</th>
                                    <th class="text-center">PO Box</th>
                                </tr>
                            </thead>

                            <tbody>
                                <!-- Health Insurance Codes -->
                                <tr>
                                    <td><label id="address_type-label">Address Type</label></td>
                                    <td>Edit the address type codes.</td>
                                    <td><input class="form-control" type="text" id="address_type-street" name="address_type-street" value="<?php echo $AddressTypes["Street"]; ?>" onchange="modifiedCode('address_type');" aria-labelledby="address_type-label"></td>
                                    <td><input class="form-control" type="text" id="address_type-po" name="address_type-po" value="<?php echo $AddressTypes["PO"]; ?>" onchange="modifiedCode('address_type');" aria-labelledby="address_type-label"></td>
                                    <td><button class="btn btn-secondary w-100" id="edit-address_type" aria-label="Save data in row." onclick="saveCodes('address_type');" disabled><i class="fa-solid fa-floppy-disk"></i></button></td>
                                </tr>
                            </tbody>
                        </table>

                        <hr class="projects-hr my-3">
                    </div>
                </div>

                <script>
                    var insurances = $("#insurances").DataTable({
                        autoWidth: false,
                        paging: false,
                        info: false,
                        filter: false,
                        columns: [
                            { orderable: true, width: "15%" },
                            { orderable: false, width: "30%" },
                            { orderable: false, width: "15%" },
                            { orderable: false, width: "15%" },
                            { orderable: false, width: "15%" },
                            { orderable: false, width: "10%" }
                        ],
                        paging: false,
                    });

                    var retirement = $("#retirement").DataTable({
                        autoWidth: false,
                        paging: false,
                        info: false,
                        filter: false,
                        columns: [
                            { orderable: true, width: "15%" },
                            { orderable: false, width: "30%" },
                            { orderable: false, width: "22.5%" },
                            { orderable: false, width: "22.5%" },
                            { orderable: false, width: "10%" }
                        ],
                        paging: false,
                    });

                    var marital_status = $("#gender").DataTable({
                        autoWidth: false,
                        paging: false,
                        info: false,
                        filter: false,
                        columns: [
                            { orderable: true, width: "15%" },
                            { orderable: false, width: "30%" },
                            { orderable: false, width: "22.5%" },
                            { orderable: false, width: "22.5%" },
                            { orderable: false, width: "10%" }
                        ],
                        paging: false,
                    });

                    var marital_status = $("#marital_status").DataTable({
                        autoWidth: false,
                        paging: false,
                        info: false,
                        filter: false,
                        columns: [
                            { orderable: true, width: "15%" },
                            { orderable: false, width: "30%" },
                            { orderable: false, width: "22.5%" },
                            { orderable: false, width: "22.5%" },
                            { orderable: false, width: "10%" }
                        ],
                        paging: false,
                    });

                    var address_types = $("#address_types").DataTable({
                        autoWidth: false,
                        paging: false,
                        info: false,
                        filter: false,
                        columns: [
                            { orderable: true, width: "15%" },
                            { orderable: false, width: "30%" },
                            { orderable: false, width: "22.5%" },
                            { orderable: false, width: "22.5%" },
                            { orderable: false, width: "10%" }
                        ],
                        paging: false,
                    });
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