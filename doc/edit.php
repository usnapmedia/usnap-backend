<?php

// Require config file.
require_once 'scripts/config.php';

// Make database connection.
$mysqli = new mysqli($GLOBALS['config']['db_host'], $GLOBALS['config']['db_user'], $GLOBALS['config']['db_pass'], $GLOBALS['config']['db_schema'], $GLOBALS['config']['db_port']);

if($mysqli->connect_errno) 
{
	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	exit;
}

$endpoint_id = $_GET['endpoint_id'];

/**
 * Query for the endpoint information.
 */
$query_endpoint = $mysqli->query("SELECT * FROM tbl_endpoint WHERE endpoint_id = {$endpoint_id};");

$endpoint   = null;
$parameters = array();

while($row_endpoint = $query_endpoint->fetch_object())
	$endpoint = $row_endpoint;

// Close the query for endpoint.
$query_endpoint->close(); 

$query_parameters = $mysqli->query("SELECT * FROM tbl_parameter WHERE parameter_endpoint_id = {$endpoint_id};");

while($row_parameter = $query_parameters->fetch_object())
	array_push($parameters, $row_parameter);

// Close the query for parameter.
$query_parameters->close(); 

// Close MySQL.
$mysqli->close();

?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="Awesome API Template">
        <meta name="author" content="Awesome API Template">
    
        <title>Endpoint Update</title>
        
        <link href='http://fonts.googleapis.com/css?family=Cabin' rel='stylesheet' type='text/css'>
        <link href="css/admin.css" rel="stylesheet">        
    </head>
    
    <!-- Body Start -->
    <body>
    	
    	<!-- Content Wrapper Start -->
        <div id="content-wrapper">
        	
        	<div class="center_links">
        		<a href="<?php echo $GLOBALS['config']['url']; ?>">Go Home</a> |||
        		<a href="<?php echo $GLOBALS['config']['url']; ?>admin.php">Admin Area</a>
        	</div>
        	
        	<!-- Section Start -->
        	<div class="section radius" style="float:none;width:60%;margin:0px auto;">
        		
				<div class="header">
					<h3 style="float:left;">Update An Endpoint</h3>
					<h3 style="float:right;"><a href="<?php echo $GLOBALS['config']['url']; ?>scripts/delete.php?endpoint_id=<?php echo $endpoint_id; ?>">Delete Endpoint</a></h3>
					<div class="clear"></div>
				</div>
				
				<!-- Form Start -->
				<form action="scripts/update.php" method="POST">
					<input type="hidden" name="endpoint_id" value="<?php echo $endpoint_id; ?>" />
					
					<!-- Input Type -->
					<label>Type</label>
					<br />
					<div class="input-wrapper radius">
						<?php
						$get    = null;
						$post   = null;
						$put    = null;
						$delete = null;
						$error  = null;
						
						switch($endpoint->endpoint_type) {
							case 'get':
								$get = 'selected="selected"';
								break;
							case 'post':
								$post = 'selected="selected"';
								break;
							case 'put':
								$put = 'selected="selected"';
								break;
							case 'delete':
								$delete = 'selected="selected"';
								break;
							case 'error':
								$error = 'selected="selected"';
								break;
						} ?>
						
						<select name="type">
							<option value="get" <?php echo $get; ?>>GET</option>
							<option value="post" <?php echo $post; ?>>POST</option>
							<option value="put" <?php echo $put; ?>>PUT</option>
							<option value="delete" <?php echo $delete; ?>>DELETE</option>
							<option value="error" <?php echo $error; ?>>ERROR</option>
						</select>
					</div>
					<!-- Input Type -->
					
					<br />
					
					<!-- Input Title -->
					<label>Title</label>
					<br />
					<div class="input-wrapper radius"><input autocomplete="off" type="text" name="title" value="<?php echo $endpoint->endpoint_title; ?>"/></div>
					<!-- Input Title -->
					
					<br />
					
					<!-- Input Description -->
					<label>Description</label>
					<br />
					<div class="input-wrapper radius"><textarea rows="8" name="description"><?php echo $endpoint->endpoint_description; ?></textarea></div>
					<!-- Input Description -->
					
					<br />
					
					<!-- Input Path -->
					<label>Path</label>
					<br />
					<div class="input-wrapper radius"><input autocomplete="off" type="text" name="path" value="<?php echo $endpoint->endpoint_path; ?>" /></div>
					<!-- Input Path -->
					
					<br />
					
					<!-- Input Request URL -->
					<label>Request URL</label>
					<br />
					<div class="input-wrapper radius"><input autocomplete="off" type="text" name="url" value="<?php echo $endpoint->endpoint_url; ?>" /></div>
					<!-- Input Request URL -->
					
					<br />
					
					<!-- Input Response -->
					<label>Response</label>
					<br />
					<div class="input-wrapper radius"><textarea rows="8" name="response"><?php echo $endpoint->endpoint_response; ?></textarea></div>
					<!-- Input Response -->
					
					<!-- Input Parameters -->
					<div class="header"><h3>Parameters</h3></div>
					<div class="clear"></div>
					<div id="clone_param" style="display:none;">
						<label>Parameter Title</label>
						<br />
						<div class="input-wrapper radius"><input autocomplete="off" type="text" name="parameter_title[]" /></div>
						
						<br />
						
						<label>Parameter Description</label>
						<br />
						<div class="input-wrapper radius"><textarea rows="8" name="parameter_description[]"></textarea></div>
						
						<br />
					</div>
					
					<div id="param_list">
						<a href="javascript:;" id="add_another_parameter">Add New Parameter</a>
						
						<?php
						foreach($parameters as $parameter) 
						{ ?>
							<div id="clone_param">
								<label>Title</label>
								<br />
								<div class="input-wrapper radius"><input autocomplete="off" type="text" name="parameter_title[]" value="<?php echo $parameter->parameter_title; ?>" /></div>
								
								<br />
								
								<label>Parameter Description</label>
								<br />
								<div class="input-wrapper radius"><textarea rows="8" name="parameter_description[]"><?php echo $parameter->parameter_description; ?></textarea></div>
								
								<br />
							</div>
						<?php
						} ?>
					</div>
					<!-- Input Parameters -->
					
					<br />
					
					<div class="submit_form"><input class="radius" type="submit" value="Update" /></div>
				</form>
				<!-- Form End -->
				
			</div>
			<!-- Section End -->
			
			<div class="clear"></div>
		</div>
		<!-- Content Wrapper End -->
		
		<!-- Import JS Last -->
        <script src="js/jquery.min.js"></script>
        <script src="js/base.js"></script>
	</body>
	<!-- Body End -->
	
</html>