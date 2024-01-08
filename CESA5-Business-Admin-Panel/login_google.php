<?php
    include("includes/google_config.php");

    // store Google vars locally
    $client_id = GOOGLE_CLIENT_ID;
    $client_secret = GOOGLE_CLIENT_SECRET;
    $redirect_uri = GOOGLE_REDIRECT_URI;

    // include the Google API library
    require_once("vendor/autoload.php");

    $client = new Google\Client();
	$client->setClientId($client_id);
	$client->setClientSecret($client_secret);
	$client->setRedirectUri($redirect_uri);
	$client->addScope("email");

    if (isset($_GET["code"]))
    {
        $client->fetchAccessTokenWithAuthCode($_GET["code"]);

        if ($token = $client->getAccessToken())
        {
            if ($client->isAccessTokenExpired()) { header("Location: login.php?error=2"); } // token expired, ask user to attempt to login again 
            else
            {
                $client->setAccessToken($token["access_token"]);                
                
                $google_oauth = new Google\Service\Oauth2($client);
                $google_account_info = $google_oauth->userinfo->get();
                $google_email = $google_account_info->email;

                // process the login attempt
                require_once("processLogin.php");
            }
        }
        else { header("Location: login.php?error=2"); } // invalid, try again
    }
    else { header("Location: " . $client->createAuthUrl()); } // go to Google authentication page
?>