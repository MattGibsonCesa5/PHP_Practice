<?php
    session_start();

    if ((isset($_SESSION["status"]) && $_SESSION["status"] == 1) && isset($_SESSION["role"])) { header("Location: dashboard.php"); } // user is already logged in; redirect to dashboard
    else // user is not logged in; display login page
    { 
        include_once("header.php");
        include_once("includes/google_config.php");

        ?>
            <script>
                // clear both session and local storage for a clean slate post-login
                sessionStorage.clear();
                localStorage.clear();
            </script>

            <style>
                <?php if (date("m") == 12 || date("m") == 1 || date("m") == 2) { ?>
                    .login-screen
                    {
                        /* BACKGROUND IMAGE */
                        background-image: url("img/CESA5-snow.jpg");
                        background-size: cover;
                        background-position: center;
                        background-repeat: no-repeat;
                        height: calc(100vh - 92px);
                        overflow-x: hidden;
                        box-shadow: inset 0 0 0 2000px rgba(255, 255, 255, 0.66);
                    }
                <?php } else { ?>
                    .login-screen
                    {
                        /* BACKGROUND IMAGE */
                        background-image: url("img/CESA 5 Building.jpg");
                        background-size: 100%;
                        background-position: center;
                        background-repeat: no-repeat;
                        height: calc(100vh - 92px);
                        overflow-x: hidden;
                        box-shadow: inset 0 0 0 2000px rgba(255, 255, 255, 0.66);
                    }
                <?php } ?>
            </style>

            <div class="login-screen p-5">
                <div class="row login-container">
                    <div class="col-7 login-body">
                        <h1 class="text-start login-label m-0">LOGIN TO</h5>
                        <h1 class="text-start login-header">CESA 5 Business Admin Panel</h1>

                        <form method="POST" action="processLogin.php">
                            <?php
                                // get error code if one was found; then display error message
                                if (isset($_GET["error"]) && $_GET["error"] <> "") 
                                { 
                                    $error = clean_data($_GET["error"]); 

                                    if ($error == 1)
                                    {
                                        ?>
                                            <div class="alert alert-danger" role="alert">
                                                <i class="fa-solid fa-triangle-exclamation"></i> LOGIN ERROR! Your username and/or password was incorrect. Please try again.
                                            </div>
                                        <?php
                                    }
                                    else if ($error == 2)
                                    {
                                        ?>
                                            <div class="alert alert-danger" role="alert">
                                                <i class="fa-solid fa-triangle-exclamation"></i> LOGIN ERROR! An unexpected error has occurred. Please try logging in again!
                                            </div>
                                        <?php
                                    }
                                    else if ($error == 3)
                                    {
                                        ?>
                                            <div class="alert alert-danger" role="alert">
                                                <i class="fa-solid fa-triangle-exclamation"></i> LOGIN ERROR! You are not a registered account. Please contact your business services administrator if you believe this error is incorrect.
                                            </div>
                                        <?php
                                    }
                                    else if ($error == 4)
                                    {
                                        ?>
                                            <div class="alert alert-danger" role="alert">
                                                <i class="fa-solid fa-triangle-exclamation"></i> LOGIN ERROR! You do not have access to the CESA 5 Business Admin Panel. If you believe this is incorrect, please contact your business services administrator.
                                            </div>
                                        <?php
                                    }
                                    else if ($error == 5)
                                    {
                                        ?>
                                            <div class="alert alert-danger" role="alert">
                                                <i class="fa-solid fa-triangle-exclamation"></i> LOGIN ERROR! The CESA 5 Business Admin Panel is currently in maintenance mode. If you believe this is incorrect, please contact your business services adminstrator.
                                            </div>
                                        <?php
                                    }
                                    else if ($error == 6)
                                    {
                                        ?>
                                            <div class="alert alert-danger" role="alert">
                                                <i class="fa-solid fa-triangle-exclamation"></i> You have been logged out due to inactivity. Please login to continue.
                                            </div>
                                        <?php
                                    }
                                }
                            ?>

                            <?php if (in_array($_SERVER["REMOTE_ADDR"], VALID_LOGIN_IPADDRESSES)) { // only show username/password login if a valid IP address ?>
                                <div class="mb-3">
                                    <label class="login-label m-0" for="email">EMAIL</label>
                                    <input class="form-control" type="text" placeholder="Email" name="email" id="email" required>
                                </div>

                                <div class="mb-3">
                                    <label class="login-label m-0" for="email">PASSWORD</label>
                                    <input class="form-control" type="password" placeholder="Password" name="password" id="password" required>
                                </div>
                            <?php } ?>

                            <div class="text-center">
                                <?php if (in_array($_SERVER["REMOTE_ADDR"], VALID_LOGIN_IPADDRESSES)) { // only show username/password login if a valid IP address ?>
                                    <div class="mb-2">
                                        <button type="submit" class="btn btn-primary login-button m-0 w-100">
                                            Sign in
                                        </button>
                                    </div>
                                <?php } ?>
                            </div>
                        </form>
                        
                        <form method="POST" action="login_google.php">
                            <!-- Sign in with Google -->
                            <div id="g_id_onload" 
                                data-client_id="<?php echo GOOGLE_CLIENT_ID; ?>" 
                                data-login_uri="<?php echo GOOGLE_REDIRECT_URI; ?>"
                                data-context="signin"
                                data-close_on_tap_outside="false"
                                data-itp_support="true">
                            </div>

                            <?php /* Google sign in button
                            <div class="g_id_signin" 
                                data-type="standard" 
                                data-shape="pill" 
                                data-theme="filled_blue" 
                                data-text="signin_with" 
                                data-size="large"
                                data-logo_alignment="left" 
                                data-width="340">
                            </div>
                            */ ?>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary login-button w-100 h-100 p-1">
                                    <img src="img/g-logo-extra_space.png" style="border-radius: 100%; height: 28px; width: 28px;" class="float-start">
                                    <span style="vertical-align: middle !important;">Sign in with Google</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="col-5 login-branding d-flex align-items-center py-5 px-3">
                        <a href="https://www.cesa5.org" target="_blank"><img class="w-100" src="img/cesa5_logo.png"></a>
                    </div>
                </div>
            </div>
        <?php 

        include_once("footer_login.php");
    } 
?>

<script> document.title = "Login | CESA 5 | Business Admin Panel"; </script>