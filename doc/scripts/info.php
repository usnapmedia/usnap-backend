<?php

// Require config file.
require_once 'config.php';

// Make database connection.
$mysqli = new mysqli($GLOBALS['config']['db_host'], $GLOBALS['config']['db_user'], $GLOBALS['config']['db_pass'], $GLOBALS['config']['db_schema'], $GLOBALS['config']['db_port']);

if($mysqli->connect_errno) 
{
	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	exit;
}

// Get all of the POST variables and escape strings.
$api  = $mysqli->real_escape_string($_POST['api']);
$desc = $mysqli->real_escape_string(nl2br($_POST['description']));
$name = $mysqli->real_escape_string($_POST['company']);

// Update the company information.
$mysqli->query("UPDATE tbl_company 
				   SET company_api_name = '{$api}',
		    		   company_api_description = '{$desc}', 
				       company_name = '{$name}'
				 WHERE company_id = 1");

// Close MySQL.
$mysqli->close();

// Re-route home.
header('Location: '.$GLOBALS['config']['url']);