<?php 
    // begin the session; unless session is already started
    if (session_status() === PHP_SESSION_NONE) { session_start(); } 

    // set the timezone
    date_default_timezone_set("America/Chicago");
    
    // get additional required files
    include("includes/config.php");
    include("includes/functions.php");
    include("includes/version.php");

    // connect to the database
    $conn = mysqli_connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);

    // get maintenance mode setting
    $getInactivityTimeout = mysqli_query($conn, "SELECT inactivity_timeout FROM settings WHERE id=1");
    $inactivityTimeout = mysqli_fetch_array($getInactivityTimeout)["inactivity_timeout"];
    
    // check to see if we should logout the user due to inactivity
    if (isset($_SESSION["last_action"]) && $inactivityTimeout != -1)
    {
        // get the time since last action
        $seconds_inactive = time() - $_SESSION["last_action"];

        // get the time in second to expire session
        $expire_seconds = $inactivityTimeout * 60;

        if ($seconds_inactive >= $expire_seconds)
        {
            header("Location: logout.php?error=6"); // user has been inactive for longer than inactivity limit; logout
        }  
    }
    $_SESSION["last_action"] = time();

    // get maintenance mode setting
    $checkMaintenanceMode = mysqli_query($conn, "SELECT maintenance_mode FROM settings WHERE id=1");
    $checkMaintenanceModeValue = mysqli_fetch_array($checkMaintenanceMode)["maintenance_mode"];

    // re-verify the the user if there is currently a session set
    if ((isset($_SESSION["status"]) && $_SESSION["status"] == 1) && ($_SESSION["email"] <> SUPER_LOGIN_EMAIL))
    {
        if (isset($_SESSION["id"]) && isset($_SESSION["email"]))
        {
            if (verifyUser($conn, $_SESSION["id"]))
            {
                if (!isUserActive($conn, $_SESSION["id"]))
                {
                    header("Location: logout.php?error=4");
                }
            }
            else { header("Location: logout.php?error=3"); }
        }
        else { header("Location: logout.php"); } // session is not set; logout user
    }

    // build default user settings array
    $USER_SETTINGS = [];
    $USER_SETTINGS["dark_mode"] = 0;
    $USER_SETTINGS["page_length"] = 10;

    // get the user's settings and messages if logged in
    $newMessages = 0; // assume the user has no new messages
    if (isset($_SESSION["status"]) && $_SESSION["status"] == 1)
    {
        // get user's settings
        $getUserSettings = mysqli_prepare($conn, "SELECT * FROM user_settings WHERE user_id=?");
        mysqli_stmt_bind_param($getUserSettings, "i", $_SESSION["id"]);
        if (mysqli_stmt_execute($getUserSettings))
        {
            $getUserSettingsResult = mysqli_stmt_get_result($getUserSettings);
            if (mysqli_num_rows($getUserSettingsResult)) // user's settings found
            {
                $USER_SETTINGS = mysqli_fetch_array($getUserSettingsResult);
            }
        }
        
        // check for new messages
        $newMessages = checkNewMessages($conn, $_SESSION["id"]);
    }

    // build the user's permissions based on their role
    $PERMISSIONS = [];
    $getRolePermissions = mysqli_prepare($conn, "SELECT p.name FROM role_permissions rp JOIN permissions p ON rp.permission_id=p.id WHERE rp.role_id=?");
    mysqli_stmt_bind_param($getRolePermissions, "i", $_SESSION["role"]);
    if (mysqli_stmt_execute($getRolePermissions))
    {
        $getRolePermissionsResults = mysqli_stmt_get_result($getRolePermissions);
        if (mysqli_num_rows($getRolePermissionsResults) > 0) // permissions found; continue
        {
            while ($permission = mysqli_fetch_array($getRolePermissionsResults))
            {
                // store permission name locally
                $permission_name = $permission["name"];
                $PERMISSIONS[$permission_name] = 1;
            }
        }
    }

    // get header dropdown state
    if (isset($_COOKIE["BAP_HeaderDropdown"])) { $header_dropdown_state = $_COOKIE["BAP_HeaderDropdown"]; } else { $header_dropdown_state = "false"; }
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
    <?php /* Bootstrap 5.3 prep
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script> */ ?>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.2/font/bootstrap-icons.css">

    <!-- Bootstrap, jQuery, and Popper    -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>

    <!-- Google Client Library -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

    <!-- Google Fonts -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto">

    <!-- Google Charts -->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

    <!-- Data Tables -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.12.1/css/jquery.dataTables.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.dataTables.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/select/1.3.4/css/select.dataTables.min.css"/>
    <link href="https://cdn.datatables.net/fixedheader/3.4.0/css/fixedHeader.dataTables.min.css" rel="stylesheet"/>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.js"></script>
    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.12.1/js/jquery.dataTables.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.colVis.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/plug-ins/1.12.1/api/sum().js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/select/1.4.0/js/dataTables.select.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/plug-ins/1.13.2/pagination/select.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/plug-ins/1.13.2/dataRender/ellipsis.js"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.4.0/js/dataTables.fixedHeader.min.js"></script>

    <!-- jQuery UI -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js" integrity="sha512-57oZ/vW8ANMjR/KQ6Be9v/+/h6bq9/l3f0Oc7vn6qMqyhvPd1cvKBRWWpzu0QoneImqr2SkmO4MSqU+RpHom3Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">

    <!-- Date Range Picker -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css"/>

    <!-- Time Picker -->
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>

    <!-- Mutliselect Plugin -->
    <link rel="stylesheet" href="./node_modules/choices.js/public/assets/styles/choices.min.css"/>
    <script src="./node_modules/choices.js/public/assets/scripts/choices.min.js"></script>

    <!-- Custom styling sheets -->
    <?php if (isset($USER_SETTINGS) && $USER_SETTINGS["dark_mode"] == 1) { ?>
        <link rel="stylesheet" href="styles/dark.css?<?php echo $version; ?>">
    <?php } else { // default stylesheet ?>
        <link rel="stylesheet" href="styles/main.css?<?php echo $version; ?>">
    <?php } ?>

    <!-- Selectize JS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/css/selectize.default.min.css" integrity="sha512-pTaEn+6gF1IeWv3W1+7X7eM60TFu/agjgoHmYhAfLEU8Phuf6JKiiE8YmsNC0aCgQv4192s4Vai8YZ6VNM6vyQ==" crossorigin="anonymous" referrerpolicy="no-referrer"/>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.15.2/js/selectize.min.js" integrity="sha512-IOebNkvA/HZjMM7MxL0NYeLYEalloZ8ckak+NDtOViP7oiYzG5vn6WVXyrJDiJPhl4yRdmNAG49iuLmhkUdVsQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <!-- Toast UI Calender -->
    <!-- <link rel="stylesheet" type="text/css" href="https://uicdn.toast.com/tui-calendar/latest/tui-calendar.css" /> -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@toast-ui/calendar@2.1.3/dist/toastui-calendar.min.css">
    <link rel="stylesheet" type="text/css" href="toastUI.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.4/css/bulma.min.css">
    <link rel="stylesheet" type="text/css" href="https://uicdn.toast.com/tui.date-picker/latest/tui-date-picker.css" />
    <link rel="stylesheet" type="text/css" href="https://uicdn.toast.com/tui.time-picker/latest/tui-time-picker.css" />
    <script src="https://uicdn.toast.com/tui.code-snippet/latest/tui-code-snippet.js"></script>
    <script src="https://uicdn.toast.com/tui.time-picker/latest/tui-time-picker.min.js"></script>
    <script src="https://uicdn.toast.com/tui.date-picker/latest/tui-date-picker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@toast-ui/calendar@2.1.3/dist/toastui-calendar.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.20.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chance/1.0.13/chance.min.js"></script>
    <!-- <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN"
            crossorigin="anonymous"></script> -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/immer/10.0.3/cjs/immer.cjs.production.min.js"></script> -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.6/purify.min.js"></script> -->
    <!-- <script src="https://uicdn.toast.com/tui.dom/v3.0.0/tui-dom.js"></script> -->



    <!-- Tab title and page icon -->
    <link rel="icon" href="img/icon.png">
    <title>
        CESA 5 | Business Admin Panel
    </title>
</head>

<!-- Keep Scroll Position -->
<script>
    document.addEventListener("DOMContentLoaded", function(event) { 
        var scrollpos = localStorage.getItem('scrollpos');
        if (scrollpos) window.scrollTo(0, scrollpos);
    });

    window.onbeforeunload = function(e) {
        localStorage.setItem('scrollpos', window.scrollY);
    };
</script>

<!-- Status Modals -->
<div id="refresh-status-modal-div"></div>
<div id="alert-status-modal-div"></div>
<!-- End Status Modals -->

<script type="text/javascript">
    /** functions to look for closing/hiding of refresh status modals */
    $(document).on("hide.bs.modal", "#refreshStatusModal", function() {
        // delete modal from page
        document.getElementById("refresh-status-modal-div").innerHTML = "";

        // refresh the page
        window.location.reload();
    });

    /** function to store the state of the header dropdown */
    function toggleHeaderDropdown()
    {
        // get current status of dropdown
        let status = document.getElementById("header-dropdown-toggle").getAttribute("aria-expanded");

        if (status == "true") { document.getElementById("header-dropdown-toggle").innerHTML = "<i class='fa-solid fa-angle-up'></i>"; }
        else { document.getElementById("header-dropdown-toggle").innerHTML = "<i class='fa-solid fa-angle-down'></i>"; }

        // store current status in a cookie
        document.cookie = "BAP_HeaderDropdown="+status+"; expires=Tue, 19 Jan 2038 04:14:07 GMT";
    }

    <?php if (isset($_SESSION["masquerade"]) && $_SESSION["masquerade"] == 1) { ?>
        /** function to exit masquerade mode */
        function leaveMasquerade()
        {
            var xmlhttp = new XMLHttpRequest();
            xmlhttp.open("POST", "ajax/users/leaveMasquerade.php", true);
            xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xmlhttp.onreadystatechange = function() 
            {
                if (this.readyState == 4 && this.status == 200)
                {
                    window.location.href = "dashboard.php";
                }
            };
            xmlhttp.send();
        }
    <?php } ?>
</script>

<!-- Header -->
<header class="row header align-middle align-items-center m-0 py-2">
    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6">
        <a href="dashboard.php" class="header-brand">
            <img src="img/cesa5_logo_noTagline.png" style="height: 36px;" alt="CESA 5 logo">
            <span class="align-middle mb-1">Business Admin Panel</span>
        </a>
        <?php
            // include additional dropdown navigation only for users who have access to those pages
            if ((isset($PERMISSIONS) && is_array($PERMISSIONS) && count($PERMISSIONS) > 0) || (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1))
            {
                ?>
                    <button id="header-dropdown-toggle" class="nav-dropdown-btn" type="button" data-bs-toggle="collapse" data-bs-target="#header-dropdown-content" aria-controls="header-dropdown-content" aria-expanded="<?php echo $header_dropdown_state; ?>" aria-label="Toggle header dropdown quick navigation bar." onclick="toggleHeaderDropdown();">
                        <?php if ($header_dropdown_state == "true") { // header quick nav is open ?>
                            <i class="fa-solid fa-angle-up"></i>
                        <?php } else { // header quick nav is closed ?>
                            <i class="fa-solid fa-angle-down"></i>
                        <?php } ?>
                    </button>
                <?php
            }
        ?>
    </div>
    <div class="col-12 col-sm-12 col-md-12 col-lg-6 col-xl-6 col-xxl-6">
        <div class="header-logout d-flex align-items-center">
            <?php if (isset($_SESSION["status"]) && $_SESSION["status"] == 1) { ?>
                <?php if (isset($_SESSION["masquerade"]) && $_SESSION["masquerade"] == 1) { ?>
                    <div class="alert alert-dark my-0 mx-2 py-2" role="alert" style="font-size: 14px;">
                        <i class="fa-solid fa-user-secret"></i> Masquerading as another user.
                    </div>
                <?php } ?>

                <?php if ($_SESSION["role"] == 1 && $_SESSION["id"] == 0 && $_SESSION["email"] == SUPER_LOGIN_EMAIL) { ?>
                    <!-- phpMyAdmin -->
                    <a class="notification nav-dropdown nav-dropdown-btn mx-2 float-end" href="<?php echo $_SERVER["REQUEST_SCHEME"]."://".$_SERVER["SERVER_NAME"]; ?>/phpmyadmin" target="_blank">
                        <i class="fa-solid fa-database"></i>
                    </a>
                <?php } ?>

                <?php if (($checkMaintenanceModeValue == 1) && (isset($_SESSION["role"]) && ($_SESSION["role"] == 1 || $_SESSION["role"] == 4))) { ?>
                    <div class="alert alert-danger alert-dismissible fade show my-0 mx-2" role="alert" style="font-size: 14px;">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?php if ($_SESSION["role"] == 1) { ?><a class="mm-redirect" href="admin.php"><?php } ?>Maintenance mode<?php if ($_SESSION["role"] == 1) { ?></a><?php } ?> is enabled.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php } ?>

                <?php /*
                <!-- Help -->
                <a class="notification nav-dropdown nav-dropdown-btn mx-2 float-end" href="guide.php">
                    <i class="fa-solid fa-circle-question"></i>
                </a>
                */ ?>

                <?php if (!isset($_SESSION["masquerade"])) { // only display messages tab if user is not in masquerade mode ?>
                    <!-- Notifications -->
                    <a class="notification nav-dropdown nav-dropdown-btn mx-2 float-end" href="messages.php">
                        <i class="fa-solid fa-envelope"></i>
                        <?php if ($newMessages > 0) { ?>
                            <span class="badge">!</span>
                        <?php } ?>
                    </a>
                <?php } ?>

                <!-- Profile -->
                <a class="nav-dropdown nav-dropdown-btn ms-2 float-end" id="accountMenuButton" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><i class="fa-solid fa-user"></i></a>
                <ul class="dropdown-menu p-0" aria-labelledby="accountMenuButton">
                    <li><hr class="dropdown-divider"></li>
                    <li class="nav-profile-name text-center mx-2">
                        <?php 
                            if (isset($_SESSION["masquerade"]) && $_SESSION["masquerade"] == 1)
                            {
                                if (isset($_SESSION["email"]) && $_SESSION["email"] <> "") 
                                { 
                                    echo $_SESSION["email"] . " <button class='btn-leaveMasquerade' onclick='leaveMasquerade();' title='Logout as user.'><i class='fa-solid fa-right-from-bracket'></i></button>"; 
                                } 
                            }
                            else
                            {
                                if (isset($_SESSION["email"]) && $_SESSION["email"] <> "") { echo $_SESSION["email"]; } 
                            }
                        ?>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="px-3 py-2 rounded-0 dropdown-item" href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a></li>
                    <li><a class="px-3 py-2 rounded-0 dropdown-item" href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
                    <li><hr class="dropdown-divider"></li>
                </ul>
            <?php } ?>
        </div>
    </div>
</header>

<?php
    // include additional dropdown navigation only for users who have access to those pages
    if ((isset($PERMISSIONS) && is_array($PERMISSIONS) && count($PERMISSIONS) > 0) || (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1))
    {
        ?>
            <!-- Header Dropdown Quick Navigation -->
            <div class="collapse header-dropdown-navbar row mw-100 m-0 <?php if ($header_dropdown_state == "true") { echo "show"; } ?>" id="header-dropdown-content">
                <!-- Dashboard -->
                <a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" href="dashboard.php">Dashboard</a>

                <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_EMPLOYEES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_DEPARTMENTS_ALL"]) || isset($PERMISSIONS["VIEW_DEPARTMENTS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_STATE"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ALL"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"]) || isset($PERMISSIONS["VIEW_RAISE_PROJECTION"])) { ?>
                    <!-- Employees -->
                    <a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" id="quickNav-employees" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Employees<i class="fa-solid fa-angle-down fa-xs ps-2"></i></a>
                    <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-employees">
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="employees.php">Employees</a></li>
                        <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_EMPLOYEES_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="employees_list.php">Employees List</a></li><?php } ?>
                        <?php if (isset($_SESSION["role"]) && $_SESSION["role"] == 1) { 
                            $getPendingSyncApprovals = mysqli_query($conn, "SELECT q.id AS syncQueueCount FROM sync_queue_employee_compensation q 
                                                                            JOIN employees e ON q.employee_id=e.id
                                                                            WHERE q.status=0 AND e.queued=0");
                            $pendingSyncApprovals = mysqli_num_rows($getPendingSyncApprovals);

                            $getNewSyncApprovals = mysqli_query($conn, "SELECT e.id AS syncQueueCount FROM employees e WHERE e.queued=1");
                            $newSyncApprovals = mysqli_num_rows($getNewSyncApprovals);
                            ?>
                            <li>
                                <a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="employees-sync_queue.php">
                                    Employees Sync
                                    <?php if (($pendingSyncApprovals + $newSyncApprovals) > 0) { ?>
                                        <span class="badge rounded-pill bg-danger"><?php echo ($pendingSyncApprovals + $newSyncApprovals); ?></span>
                                    <?php } ?>
                                </a>
                            </li>
                        <?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_DEPARTMENTS_ALL"]) || isset($PERMISSIONS["VIEW_DEPARTMENTS_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="departments.php">Departments</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) && isset($PERMISSIONS["EDIT_EMPLOYEES"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="directors.php">Directors & Supervisors</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) && isset($PERMISSIONS["EDIT_EMPLOYEES"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="therapists.php">Therapists</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) && isset($PERMISSIONS["ADD_EMPLOYEES"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="employees_titles.php">Position Titles</a></li><?php } ?>
                        <?php if ((isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) && isset($PERMISSIONS["EDIT_EMPLOYEES"])) || isset($PERMISSIONS["VIEW_EMPLOYEES_ASSIGNED"])) { 
                            // get the number of pending change requests
                            $changeRequestsCount = 0; // initialize count
                            if (isset($PERMISSIONS["VIEW_EMPLOYEES_ALL"]) && isset($PERMISSIONS["EDIT_EMPLOYEES"]))
                            {
                                $getPendingChangeRequests = mysqli_query($conn, "SELECT id AS changeRequestsCount FROM employee_compensation_change_requests WHERE status=0");
                                $changeRequestsCount = mysqli_num_rows($getPendingChangeRequests);
                            }
                            else if (isset($PERMISSIONS["VIEW_EMPLOYEES_ASSIGNED"]))
                            {
                                $getPendingChangeRequests = mysqli_prepare($conn, "SELECT id AS changeRequestsCount FROM employee_compensation_change_requests WHERE status=0 AND requested_by=?");
                                mysqli_stmt_bind_param($getPendingChangeRequests, "i", $_SESSION["id"]);
                                if (mysqli_stmt_execute($getPendingChangeRequests))
                                {
                                    $getPendingChangeRequestsResults = mysqli_stmt_get_result($getPendingChangeRequests);
                                    $changeRequestsCount = mysqli_num_rows($getPendingChangeRequestsResults);
                                }
                            }
                            ?>
                                <li>
                                    <a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="employees_change_requests.php">
                                        Change Requests
                                        <?php if ($changeRequestsCount > 0) { ?>
                                            <span class="badge rounded-pill bg-danger"><?php echo $changeRequestsCount; ?></span>
                                        <?php } ?>
                                    </a>
                                </li>
                            <?php 
                        } ?>
                        <?php if (isset($PERMISSIONS["VIEW_SALARY_COMPARISON_STATE"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ALL"]) || isset($PERMISSIONS["VIEW_SALARY_COMPARISON_INTERNAL_ASSIGNED"]) || isset($PERMISSIONS["VIEW_RAISE_PROJECTION"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="salary_comparison.php">Salary Comparison</a></li><?php } ?>
                    </ul>
                <?php } ?>
                
                <?php if (isset($PERMISSIONS["VIEW_PROJECT_EXPENSES"]) || isset($PERMISSIONS["VIEW_EMPLOYEE_EXPENSES"])) { ?>
                    <!-- Expenses -->
                    <a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" id="quickNav-expenses" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Expenses<i class="fa-solid fa-angle-down fa-xs ps-2"></i></a>
                    <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-expenses">
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="expenses.php">Expenses</a></li>
                        <?php if (isset($PERMISSIONS["VIEW_PROJECT_EXPENSES"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="expenses_manage.php">Project Expenses</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_EMPLOYEE_EXPENSES"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="expenses_global.php">Employee Expenses</a></li><?php } ?>
                    </ul>
                <?php } ?>

                <?php if (isset($PERMISSIONS["VIEW_SERVICES_ALL"]) || isset($PERMISSIONS["VIEW_SERVICES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_INVOICES_ALL"]) || isset($PERMISSIONS["VIEW_INVOICES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_REVENUES_ALL"]) || isset($PERMISSIONS["VIEW_REVENUES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_OTHER_SERVICES"])) { ?>
                    <!-- Services -->
                    <a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" id="quickNav-services" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Services<i class="fa-solid fa-angle-down fa-xs ps-2"></i></a>
                    <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-services">
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="services.php">Services</a></li>
                        <?php if (isset($PERMISSIONS["VIEW_SERVICES_ALL"]) || isset($PERMISSIONS["VIEW_SERVICES_ASSIGNED"]) || isset($PERMISSIONS["VIEW_OTHER_SERVICES"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="services_manage.php">Manage Services</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_INVOICES_ALL"]) || isset($PERMISSIONS["VIEW_INVOICES_ASSIGNED"])) { ?>
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="services_billed.php">Services Billed</a></li>
                        <?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_INVOICES_ALL"]) || isset($PERMISSIONS["VIEW_INVOICES_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="invoice_comparison.php">Invoice Comparison</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_REVENUES_ALL"]) || isset($PERMISSIONS["VIEW_REVENUES_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="revenues.php">Other Revenues</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["CREATE_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["BUILD_SERVICE_CONTRACTS"]) || 
                                    isset($PERMISSIONS["VIEW_QUARTERLY_INVOICES"]) || isset($PERMISSIONS["CREATE_QUARTERLY_INVOICES"]) || isset($PERMISSIONS["BUILD_QUARTERLY_INVOICES"]) || 
                                    isset($PERMISSIONS["EXPORT_INVOICES"])) { ?> 
                        <li class="btn-group dropend w-100">
                            <!-- Contracts Sub-dropdown -->
                            <a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="contracts.php">Contracts</a>
                            <a class="quickNav-dropdown-item dropdown-item text-center w-25 px-2 py-2 float-end rounded-0" id="quickNav-contracts" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><i class="fa-solid fa-angle-right fa-xs"></i></a>
                            <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-contracts">
                                <?php if (isset($PERMISSIONS["VIEW_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["VIEW_QUARTERLY_INVOICES"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="customer_files.php">View Contracts</a></li><?php } ?>
                                <?php if (isset($PERMISSIONS["CREATE_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["CREATE_QUARTERLY_INVOICES"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="contracts_create.php">Create Contracts</a></li><?php } ?>
                                <?php if (isset($PERMISSIONS["BUILD_SERVICE_CONTRACTS"]) || isset($PERMISSIONS["BUILD_QUARTERLY_INVOICES"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="contracts_builder.php">Build Contracts</a></li><?php } ?>
                                <?php if (isset($PERMISSIONS["EXPORT_INVOICES"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="invoices_export.php">Export Invoices</a></li><?php } ?>
                            </ul>
                        </li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <?php if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1) { ?>
                    <!-- District Files -->
                    <a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" href="customer_files.php">District Files</a>
                <?php } ?>

                <?php if (isset($PERMISSIONS["VIEW_PROJECTS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECTS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"])) { ?>
                    <!-- Projects -->
                    <a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" id="quickNav-projects" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Projects<i class="fa-solid fa-angle-down fa-xs ps-2"></i></a>
                    <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-projects">
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="projects.php">Projects</a></li>
                        <?php if (isset($PERMISSIONS["VIEW_PROJECTS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECTS_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="projects_manage.php">Manage Projects</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="projects_budget.php">Budget Projects</a></li><?php } ?>
                        <?php if ($_SESSION["role"] == 1) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="wufar_codes.php">Codes</a></li><?php } ?>
                    </ul>
                <?php } ?>

                <?php if (isset($PERMISSIONS["VIEW_CUSTOMERS"]) || isset($PERMISSIONS["VIEW_CUSTOMER_GROUPS"])) { ?>
                    <!-- Customers -->
                    <a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" id="quickNav-customers" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Customers<i class="fa-solid fa-angle-down fa-xs ps-2"></i></a>
                    <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-customers">
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="customers.php">Customers</a></li>
                        <?php if (isset($PERMISSIONS["VIEW_CUSTOMERS"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="customers_manage.php">Manage Customers</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_CUSTOMER_GROUPS"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="customers_groups.php">Customer Groups</a></li><?php } ?>
                    </ul>
                <?php } ?>

                <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ALL"]) || isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_STUDENTS_ALL"]) || isset($PERMISSIONS["VIEW_STUDENTS_ASSIGNED"]) || isset($PERMISSIONS["VIEW_THERAPISTS"])) { ?>
                    <!-- Caseloads -->
                    <a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" id="quickNav-caseloads" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Caseloads<i class="fa-solid fa-angle-down fa-xs ps-2"></i></a>
                    <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-caseloads">
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads.php">Caseloads Home</a></li>
                        <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="my-caseloads.php">My Caseloads</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_THERAPISTS"]) && isset($PERMISSIONS["VIEW_CASELOADS_ALL"])) { ?>
                            <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_manage.php">Caseloads</a></li>                    
                            <li class="btn-group dropend w-100">
                                <!-- Caseload Management Sub-dropdown -->
                                <a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads.php">Caseload Management</a>
                                <a class="quickNav-dropdown-item dropdown-item text-center w-25 px-2 py-2 float-end rounded-0" id="quickNav-caseloadManagement" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><i class="fa-solid fa-angle-right fa-xs"></i></a>
                                <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-caseloadManagement">
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_assistants.php">Assistants</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_categories.php">Categories</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_classrooms.php">Classrooms</a></li> 
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_coordinators.php">Coordinators</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="schools.php">Schools</a></li>
                                    <?php
                                        // get the number of pending transfer requests
                                        $getPendingTransferRequests = mysqli_query($conn, "SELECT ct.id AS transferRequestsCount FROM caseload_transfers ct 
                                                                                            JOIN cases c ON ct.case_id=c.id
                                                                                            JOIN periods p ON c.period_id=p.id
                                                                                            WHERE p.active=1 AND ct.transfer_status=0");
                                        $transferRequestsCount = mysqli_num_rows($getPendingTransferRequests);
                                        ?>
                                            <li>
                                                <a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_transfers.php">
                                                    Transfer Requests
                                                    <?php if ($transferRequestsCount > 0) { ?>
                                                        <span class="badge rounded-pill bg-danger"><?php echo $transferRequestsCount; ?></span>
                                                    <?php } ?>
                                                </a>
                                            </li>
                                        <?php 
                                    ?>
                                </ul>
                            </li>
                        <?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_STUDENTS_ALL"]) || isset($PERMISSIONS["VIEW_STUDENTS_ASSIGNED"])) { ?>
                            <li>
                                <a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_students.php">
                                    <?php 
                                        if (isset($PERMISSIONS["VIEW_STUDENTS_ALL"])) { echo "Students"; }
                                        else { echo "My Students"; }
                                    ?>
                                </a>
                            </li>
                        <?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"]) && verifyCoordinator($conn, $_SESSION["id"])) { ?>
                            <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_billing.php">Billing Summary</a></li>
                        <?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_THERAPISTS"]) && isset($PERMISSIONS["VIEW_CASELOADS_ALL"])) { ?>
                            <li class="btn-group dropend w-100">
                                <!-- Caseload Reports Sub-dropdown -->
                                <a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_reports.php">Reports</a>
                                <a class="quickNav-dropdown-item dropdown-item text-center w-25 px-2 py-2 float-end rounded-0" id="quickNav-caseloadReports" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><i class="fa-solid fa-angle-right fa-xs"></i></a>
                                <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-caseloadReports">
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_billing.php">Billing Summary</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_billing_quarterly.php">Quarterly Billing</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_medicaid_billing.php">Medicaid Billing</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_start_end_changes.php">Master Start-End Changes</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_warnings.php">Unit Warnings</a></li>
                                </ul>
                            </li>
                        <?php } ?>
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="uos_calculator.php">UOS Calculator</a></li>
                        <?php if (isset($PERMISSIONS["VIEW_THERAPISTS"]) && isset($PERMISSIONS["VIEW_CASELOADS_ALL"])) { ?>
                            <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="uos_quotes.php">UOS Quotes</a></li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <?php if (isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"]) ||
                            isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"]) || 
                            isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED"]) || 
                            isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"]) || 
                            isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"]) || 
                            isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"])) 
                { ?>





                                                                                <!-- task list -->

<a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" id="quickNav-caseloads" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Task List<i class="fa-solid fa-angle-down fa-xs ps-2"></i></a>
                    <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-caseloads">
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="taskDashboard.php">Tasks Home</a></li>
                        <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="my-caseloads.php">My Tasks</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_THERAPISTS"]) && isset($PERMISSIONS["VIEW_CASELOADS_ALL"])) { ?>
                            <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_manage.php">Tasks</a></li>                    
                            <li class="btn-group dropend w-100">
                                <!-- Caseload Management Sub-dropdown -->
                                <a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="toastUICalender.php">toast ui</a>
                                <a class="quickNav-dropdown-item dropdown-item text-center w-25 px-2 py-2 float-end rounded-0" id="quickNav-caseloadManagement" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><i class="fa-solid fa-angle-right fa-xs"></i></a>
                                <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-caseloadManagement">
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_assistants.php">Add Task</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_categories.php">Catego</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_classrooms.php">Classrooms</a></li> 
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_coordinators.php">Coordinators</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="schools.php">Schools</a></li>
                                    <?php
                                        // get the number of pending transfer requests
                                        $getPendingTransferRequests = mysqli_query($conn, "SELECT ct.id AS transferRequestsCount FROM caseload_transfers ct 
                                                                                            JOIN cases c ON ct.case_id=c.id
                                                                                            JOIN periods p ON c.period_id=p.id
                                                                                            WHERE p.active=1 AND ct.transfer_status=0");
                                        $transferRequestsCount = mysqli_num_rows($getPendingTransferRequests);
                                        ?>
                                            <li>
                                                <a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_transfers.php">
                                                    Transfer Requests
                                                    <?php if ($transferRequestsCount > 0) { ?>
                                                        <span class="badge rounded-pill bg-danger"><?php echo $transferRequestsCount; ?></span>
                                                    <?php } ?>
                                                </a>
                                            </li>
                                        <?php 
                                    ?>
                                </ul>
                            </li>
                        <?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_STUDENTS_ALL"]) || isset($PERMISSIONS["VIEW_STUDENTS_ASSIGNED"])) { ?>
                            <li>
                                <a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_students.php">
                                    <?php 
                                        if (isset($PERMISSIONS["VIEW_STUDENTS_ALL"])) { echo "Students"; }
                                        else { echo "My Students"; }
                                    ?>
                                </a>
                            </li>
                        <?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_CASELOADS_ASSIGNED"]) && verifyCoordinator($conn, $_SESSION["id"])) { ?>
                            <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_billing.php">Billing Summary</a></li>
                        <?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_THERAPISTS"]) && isset($PERMISSIONS["VIEW_CASELOADS_ALL"])) { ?>
                            <li class="btn-group dropend w-100">
                                <!-- Caseload Reports Sub-dropdown -->
                                <a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_reports.php">Reports</a>
                                <a class="quickNav-dropdown-item dropdown-item text-center w-25 px-2 py-2 float-end rounded-0" id="quickNav-caseloadReports" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"><i class="fa-solid fa-angle-right fa-xs"></i></a>
                                <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-caseloadReports">
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_billing.php">Billing Summary</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_billing_quarterly.php">Quarterly Billing</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_medicaid_billing.php">Medicaid Billing</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_start_end_changes.php">Master Start-End Changes</a></li>
                                    <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="caseloads_warnings.php">Unit Warnings</a></li>
                                </ul>
                            </li>
                        <?php } ?>
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="uos_calculator.php">UOS Calculator</a></li>
                        <?php if (isset($PERMISSIONS["VIEW_THERAPISTS"]) && isset($PERMISSIONS["VIEW_CASELOADS_ALL"])) { ?>
                            <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="uos_quotes.php">UOS Quotes</a></li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                <?php if (isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"]) ||
                            isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"]) || 
                            isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED"]) || 
                            isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"]) || 
                            isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"]) || 
                            isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"])) 
                { ?>




                                                                                    <!--task list end -->


                    <!-- Reports -->
                    <a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" id="quickNav-reports" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Reports<i class="fa-solid fa-angle-down fa-xs ps-2"></i></a>
                    <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-reports">
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="reports.php">Reports</a></li>
                        <?php if (isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ALL"]) || isset($PERMISSIONS["VIEW_PROJECT_BUDGETS_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="days_budgeted.php">Budgeted Employees</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_MISBUDGETED_EMPLOYEES_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="days_misbudgeted.php">Misbudgeted Employees</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_BUDGETED_INACTIVE_EMPLOYEES_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="report_inactive.php">Budgeted Inactive Employees</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_TEST_EMPLOYEES_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="report_testEmployees.php">Test Employees</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_SALARY_PROJECTION_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="salary_projection.php">Salary Projection</a></li><?php } ?>
                        <?php if (isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ALL"]) || isset($PERMISSIONS["VIEW_REPORT_EMPLOYEE_CHANGES_ASSIGNED"])) { ?><li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="employee_changes.php">Employee Changes</a></li><?php } ?>
                        <?php if ($_SESSION["role"] == 1) { ?>
                            <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="report-customer_billing.php">Customer Billing Report</a></li>
                            <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="report-sped-billing.php">SPED Billing Verification</a></li>
                            <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="report-payroll.php">Payroll Report</a></li>
                            <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="report-indirect_expenditures.php">Indirect Expenditures Report</a></li>
                            <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="report-consecutive_yoe.php">Consecutive Y.O.E Report</a></li>
                        <?php } ?>
                    </ul>
                <?php } ?>

                

                <?php if ($_SESSION["role"] == 1) { ?>
                    <!-- Manage -->
                    <a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" id="quickNav-manage" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Manage<i class="fa-solid fa-angle-down fa-xs ps-2"></i></a>
                    <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-manage">
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="manage.php">Manage</a></li>
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="users.php">Users</a></li>
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="admin.php">Admin</a></li>
                        <li class="btn-group dropend w-100">
                            <a class="quickNav-dropdown-item dropdown-item d-flex justify-content-between align-items-center px-3 py-2 rounded-0" id="quickNav-automation" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Automation <i class="fa-solid fa-angle-right fa-xs"></i></a>
                            <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-automation">
                                <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="automation.php">Manage Automation</a></li>
                                <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="automation_log.php">Automation Log</a></li>
                            </ul>
                        </li>
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="periods.php">Periods</a></li>
                        <li class="btn-group dropend w-100">
                            <a class="quickNav-dropdown-item dropdown-item d-flex justify-content-between align-items-center px-3 py-2 rounded-0" id="quickNav-notifications" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">Notifications <i class="fa-solid fa-angle-right fa-xs"></i></a>
                            <ul class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto quickNav-dropdown dropdown-menu p-0" aria-labelledby="quickNav-notifications">
                                <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="notifications.php">Manage Notifications</a></li>
                                <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="notifications_log.php">Notifications Log</a></li>
                            </ul>
                        </li>
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="codes.php">Codes</a></li>
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="clear.php">Clear</a></li>
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="roles.php">Roles & Permissions</a></li>
                        <li><a class="quickNav-dropdown-item dropdown-item w-100 ps-3 pe-5 py-2 rounded-0" href="log.php">Log</a></li>
                    </ul>
                <?php } ?>

                <?php if (isset($_SESSION["district"]) && $_SESSION["district"]["status"] == 1 && $_SESSION["district"]["role"] == "Admin") { ?>
                    <!-- Dashboard -->
                    <a class="col-6 col-sm-4 col-md-4 col-lg-3 col-xl-3 col-xxl-auto header-dropdown-navbar-link px-5 py-2" href="users.php">Users</a>
                <?php } ?>
            </div>
        <?php 
    }

    // disconnect from the database
    mysqli_close($conn);
?>

<body class="d-flex flex-column h-100">