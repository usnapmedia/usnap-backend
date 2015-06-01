SET NAMES utf8;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
--  Table structure for `tbl_company`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_company`;
CREATE TABLE `tbl_company` (
  `company_id` int(11) NOT NULL AUTO_INCREMENT,
  `company_name` text,
  `company_api_description` text,
  `company_api_name` text,
  PRIMARY KEY (`company_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

INSERT INTO tbl_company (company_id, company_name, company_api_description, company_api_name) VALUES(1, "Company Name Example", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Etiam purus nunc, tempor sed sagittis et, venenatis sed purus. Vestibulum quis lacus placerat, tincidunt justo quis, sollicitudin magna. Aliquam erat volutpat.", "API Name");

-- ----------------------------
--  Table structure for `tbl_endpoint`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_endpoint`;
CREATE TABLE `tbl_endpoint` (
  `endpoint_id` int(11) NOT NULL AUTO_INCREMENT,
  `endpoint_creation_datetime` datetime NOT NULL,
  `endpoint_last_update_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `endpoint_type` text NOT NULL,
  `endpoint_response` text NOT NULL,
  `endpoint_title` text,
  `endpoint_description` text,
  `endpoint_url` text,
  `endpoint_path` text NOT NULL,
  PRIMARY KEY (`endpoint_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- ----------------------------
--  Table structure for `tbl_parameter`
-- ----------------------------
DROP TABLE IF EXISTS `tbl_parameter`;
CREATE TABLE `tbl_parameter` (
  `parameter_id` int(11) NOT NULL AUTO_INCREMENT,
  `parameter_endpoint_id` int(11) NOT NULL,
  `parameter_title` text NOT NULL,
  `parameter_description` text NOT NULL,
  PRIMARY KEY (`parameter_id`),
  KEY `parameter_endpoint_id` (`parameter_endpoint_id`),
  CONSTRAINT `tbl_parameter_ibfk_1` FOREIGN KEY (`parameter_endpoint_id`) REFERENCES `tbl_endpoint` (`endpoint_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

SET FOREIGN_KEY_CHECKS = 1;
