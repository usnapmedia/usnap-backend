<?php

// Require config file.
require_once 'scripts/config.php';

// Setup some variables.
$api_name	  = null;
$api_desc	  = null;
$company_name = null;

// Make database connection.
$mysqli = new mysqli($GLOBALS['config']['db_host'], $GLOBALS['config']['db_user'], $GLOBALS['config']['db_pass'], $GLOBALS['config']['db_schema'], $GLOBALS['config']['db_port']);

if($mysqli->connect_errno) 
{
	echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
	exit;
}

/**
 * Query for the company information that is 
 * updated via admin.php.
 */
$query_info = $mysqli->query("SELECT * FROM tbl_company WHERE company_id = 1;");

while($row_info = $query_info->fetch_object())
{
	$api_name	  = $row_info->company_api_name;
	$api_desc	  = $row_info->company_api_description;
	$company_name = $row_info->company_name;
} 

// Close the query for info.
$query_info->close(); 

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
    
        <title><?php echo $api_name; ?> - Admin</title>
        
        <link href='http://fonts.googleapis.com/css?family=Cabin' rel='stylesheet' type='text/css'>
        <link href="css/admin.css" rel="stylesheet">        
    </head>
    
    <!-- Body Start -->
    <body>
    	
    	<!-- Content Wrapper Start -->
        <div id="content-wrapper">
        	
        	<div class="center_links">
        		<a href="<?php echo $GLOBALS['config']['url']; ?>">Go Home</a>
        	</div>
        	
        	<!-- Section Start -->
        	<div class="section radius first">
        		
				<div class="header"><h3>Add A New Endpoint</h3></div>
				
				<!-- Form Start -->
				<form action="scripts/insert.php" method="POST">
					
					<!-- Input Type -->
					<label>Type</label>
					<br />
					<div class="input-wrapper radius">
						<select name="type">
							<option value="get">GET</option>
							<option value="post">POST</option>
							<option value="put">PUT</option>
							<option value="delete">DELETE</option>
							<option value="error">ERROR</option>
						</select>
					</div>
					<!-- Input Type -->
					
					<br />
					
					<!-- Input Title -->
					<label>Title</label>
					<br />
					<div class="input-wrapper radius"><input autocomplete="off" type="text" name="title" /></div>
					<!-- Input Title -->
					
					<br />
					
					<!-- Input Description -->
					<label>Description</label>
					<br />
					<div class="input-wrapper radius"><textarea rows="8" name="description"></textarea></div>
					<!-- Input Description -->
					
					<br />
					
					<!-- Input Path -->
					<label>Path</label>
					<br />
					<div class="input-wrapper radius"><input autocomplete="off" type="text" name="path" /></div>
					<!-- Input Path -->
					
					<br />
					
					<!-- Input Request URL -->
					<label>Request URL</label>
					<br />
					<div class="input-wrapper radius"><input autocomplete="off" type="text" name="url" /></div>
					<!-- Input Request URL -->
					
					<br />
					
					<!-- Input Response -->
					<label>Response</label>
					<br />
					<div class="input-wrapper radius"><textarea rows="8" name="response"></textarea></div>
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
					</div>
					<!-- Input Parameters -->
					
					<br />
					
					<div class="submit_form"><input class="radius" type="submit" value="Create" /></div>
				</form>
				<!-- Form End -->
				
			</div>
			<!-- Section End -->
			
			<!-- Section Start -->
        	<div class="section radius">
        		
				<div class="header"><h3>Company Info</h3></div>
				
				<!-- Form Start -->
				<form action="scripts/info.php" method="POST">
					<!-- Input API Name -->
					<label>API Name</label>
					<br />
					<div class="input-wrapper radius"><input autocomplete="off" type="text" name="api" value="<?php echo $api_name; ?>"/></div>
					<!-- Input API Name -->
					
					<br />
					
					<!-- Input API Description -->
					<label>API Description</label>
					<br />
					<div class="input-wrapper radius"><textarea rows="8" name="description"><?php echo $api_desc; ?></textarea></div>
					<!-- Input API Description -->
					
					<br />
					
					<!-- Input Company Name -->
					<label>Company Name</label>
					<br />
					<div class="input-wrapper radius"><input autocomplete="off" type="text" name="company" value="<?php echo $company_name; ?>" /></div>
					<!-- Input Company Name -->
					
					<br />
					
					<div class="submit_form"><input class="radius" type="submit" value="Update" /></div>
				</form>
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