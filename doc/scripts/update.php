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
$endpoint_id = $_POST['endpoint_id'];
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
 * Update the endpoint.
 */
$mysqli->query("UPDATE tbl_endpoint SET endpoint_type = '{$type}', 
									    endpoint_title = '{$title}',
									    endpoint_description = '{$desc}',
									    endpoint_path = '{$path}',
									    endpoint_url = '{$url}',
								        endpoint_response = '{$resp}' WHERE endpoint_id = {$endpoint_id};");

$mysqli->query("DELETE FROM tbl_parameter WHERE parameter_endpoint_id = {$endpoint_id};");

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
