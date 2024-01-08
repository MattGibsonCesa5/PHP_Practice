<?php
	if (isset($_GET["error"])) { $error = $_GET["error"]; }

	// Initialize the session
	session_start();
 
	// Unset all of the session variables
	$_SESSION = array();
 
	// Destroy the session.
	session_destroy();
 
	// Redirect to login page
	if (isset($error) && is_numeric($error)) { header("Location: login.php?error=".$error); }
	else { header("location: login.php"); }

	exit;
?>