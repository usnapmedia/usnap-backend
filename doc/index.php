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
 * Query for all of the endpoints that have been
 * added via admin.php.
 */
$query_endpoint = $mysqli->query("SELECT * FROM tbl_endpoint ORDER BY endpoint_type;");

$endpoints  = array();
$parameters = array();

$i = 0;

while($row_endpoint = $query_endpoint->fetch_object())
{ 
	array_push($endpoints, $row_endpoint);
	
	/**
	 * Get the endpoint_id so it can be used when
	 * selecting the parameters from the parameter
	 * table.
	 */
	$endpoint_id = $row_endpoint->endpoint_id;
	
	/**
	 * Query for all of the parameters that have been
	 * added via admin.php
	 */
	$query_parameter = $mysqli->query("SELECT * FROM tbl_parameter WHERE parameter_endpoint_id = {$endpoint_id};");
	
	while($row_parameter = $query_parameter->fetch_object())
		array_push($parameters, $row_parameter);
	
	// Close the query for parameters.
	$query_parameter->close();
	
	$endpoints[$i]->parameters = $parameters;
	
	// Reset parameters array.
	$parameters = array();
	
	++$i;
} 

// Close the query for endpoints.
$query_endpoint->close(); 

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
        <meta name="description" content="uSnap API">
        <meta name="author" content="TANIOS">
    
        <title><?php echo $api_name; ?></title>
        
        <link href='http://fonts.googleapis.com/css?family=Cabin' rel='stylesheet' type='text/css'>
        <link href="css/base.css" rel="stylesheet">        
    </head>
    
    <!-- Body Start -->
    <body>
    	
    	<!-- Content Wrapper Start -->
        <div id="content-wrapper">
        	
        	<div class="center_links">
        		<!-- <a href="<?php// echo $GLOBALS['config']['url']; ?>admin.php">Admin Area</a> -->
        	</div>
        	
        	<!-- Header Wrapper Start -->
            <div id="header-wrapper">
            	<?php 
                if($api_name) 
				{ ?>
                	<div id="title" class="center"><?php echo $api_name; ?></div> 
                <?php
				} ?>
                
                <?php 
                if($api_desc) 
				{ ?>
                	<div id="description"><?php echo $api_desc; ?></div>   
                <?php
				} ?>
            </div>
            <!-- Header Wrapper End -->
            
            <!-- API Wrapper Start -->
            <div id="api-wrapper">
            	<?php
            	if(count($endpoints) > 0)
                {
                	foreach($endpoints as $endpoint) 
    				{ ?>
                        <div class="endpoint-wrapper radius">
                            <div class="method radius center <?php echo $endpoint->endpoint_type; ?>"><?php echo strtoupper($endpoint->endpoint_type); ?></div>
                            <div class="path"><?php echo $endpoint->endpoint_path; ?></div>
                            <div class="arrow"><img src="img/down.png" /></div>
                            <div class="clear"></div>
                    
                            <!-- Details Start -->
                            <div class="details">
                            	<div class="url"><a href="<?php echo $GLOBALS['config']['url']; ?>edit.php?endpoint_id=<?php echo $endpoint->endpoint_id; ?>">Edit</a></div>
                                <div class="title"><?php echo $endpoint->endpoint_title; ?></div>
                                <div class="description"><?php echo $endpoint->endpoint_description; ?></div>
                                <div class="extra-padding"></div>
                                <div class="title">Request URL</div>
                                <div class="code radius"><?php echo $endpoint->endpoint_url; ?></div>
                                <div class="extra-padding"></div>
                        
                            	<?php
                            	if($endpoint->parameters)
        						{ ?>
        	                        <div class="title">Parameters</div>
        	                        <div class="list">
        	                        	<ul>
        	                        	<?php
        		                        foreach($endpoint->parameters as $parameter)
        								{ 
        									if($parameter->parameter_description)
        										$parameter->parameter_description = ' - '.$parameter->parameter_description; ?>
        										
        		                            <li><?php echo $parameter->parameter_title.$parameter->parameter_description; ?></li>
        	                            <?php
        								} ?>
        								</ul>
        	                        </div>
        	                        
        	                        <div class="extra-padding"></div>
        	                    <?php
        						} ?>
        						
                                <div class="title">Response</div>
                                <div class="code radius">
<pre><?php echo $endpoint->endpoint_response; ?></pre>
                            </div>
                        </div>
                        <!-- Details End -->
                    </div>
                    <?php
                    } 
                } 
                else 
                { ?>
                    <div class="endpoint-wrapper radius" style="text-align:center;">No endpoints created. Please click the <a href="<?php echo $GLOBALS['config']['url']; ?>admin.php">Admin Area</a> link to create your first endpoint!</div>
                <?php
                } ?>
            </div>
            <!-- API Wrapper End -->
            
            <!-- Footer Wrapper Start -->
            <div id="footer-wrapper" class="center">
                <div id="copyright">&#169; <?php echo $company_name; ?></div>
            </div>
            <!-- Footer Wrapper End -->
            
        </div>
        <!-- Content Wrapper End -->
        
        <!-- Import JS Last -->
        <script src="js/jquery.min.js"></script>
        <script src="js/base.js"></script>
    </body>
    <!-- Body End -->
</html>