<?php

// Require config file.
require_once 'config.php';

// Setup some variables.
$param_titles = null;
$param_descr  = null;

// Make database connection.
$mysqli = new mysqli($GLOBALS['config']['db_host'], $GLOBALS['config']['db_user'], $GLOBALS['config']['db_pass'], $GLOBALS['config']['db_schema'], $GLOBALS['config']['db_port']);

if($mysqli->connect_errno) 
{
	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	exit;
}

// Get all of the POST variables and escape strings.
$type  = $_POST['type'];
$title = $mysqli->real_escape_string($_POST['title']);
$desc  = $mysqli->real_escape_string(nl2br($_POST['description']));
$path  = $mysqli->real_escape_string($_POST['path']);
$url   = $mysqli->real_escape_string($_POST['url']);
$resp  = $mysqli->real_escape_string($_POST['response']);

if($_POST['parameter_title'])
	$param_titles = array_values($_POST['parameter_title']);

if($_POST['parameter_description'])
	$param_descr  = array_values($_POST['parameter_description']);

/**
 * Insert the new endpoint into the database and get the
 * endpoint ID so then the parameters can link back.
 */
$mysqli->query("INSERT INTO tbl_endpoint (endpoint_creation_datetime, 
							    		  endpoint_type, 
									      endpoint_title,
									      endpoint_description,
									      endpoint_path,
									      endpoint_url,
									      endpoint_response) VALUES (NOW(), '{$type}', '{$title}', '{$desc}', '{$path}', '{$url}', '{$resp}')");
$endpoint_id = $mysqli->insert_id;

/**
 * Loop through all the parameters and insert
 * them into the database for that endpoint.
 */
for($i = 1; $i < count($param_titles); ++$i)
{
	$titles       = null;
	$descriptions = null;
	
	if($param_titles[$i])
		$titles = $mysqli->real_escape_string($param_titles[$i]);
	
	if($param_descr[$i])
		$descriptions = $mysqli->real_escape_string(nl2br($param_descr[$i]));
	
	$mysqli->query("INSERT INTO tbl_parameter (parameter_endpoint_id,
									      	   parameter_title,
									      	   parameter_description) VALUES ({$endpoint_id}, '{$titles}', '{$descriptions}')");
}

// Close MySQL.
$mysqli->close();

// Re-route home.
header('Location: '.$GLOBALS['config']['url']);