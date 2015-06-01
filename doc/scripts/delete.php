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

$endpoint_id = $_GET['endpoint_id'];

/**
 * Delete the endpoint.
 */
$mysqli->query("DELETE FROM tbl_endpoint WHERE endpoint_id = {$endpoint_id};");
$mysqli->query("DELETE FROM tbl_parameter WHERE parameter_endpoint_id = {$endpoint_id};");

// Close MySQL.
$mysqli->close();

// Re-route home.
header('Location: '.$GLOBALS['config']['url']);
