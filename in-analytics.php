<?php
/*
Plugin Name: IN-Analytics
Plugin URI: http://in-analytics.com/
Description: Just another Google Analytics Plugin
Version: 0.4
Author: Ivan Nikitin
Author URI: http://ivannikitin.com
Text Domain: inanalytics
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
define('INA_TEXT_DOMAIN', 	'inanalytics');
define('INA_FOLDER', 		plugin_dir_path(__FILE__));
define('INA_URL', 			plugin_dir_url(__FILE__));

// Классы
require(INA_FOLDER . 'inc/_module.class.php');
require(INA_FOLDER . 'inc/_measurementprotocol.class.php');
require(INA_FOLDER . 'inc/analytics.class.php');
require(INA_FOLDER . 'inc/metrika.class.php');
require(INA_FOLDER . 'inc/bouncerate.class.php');
require(INA_FOLDER . 'inc/userid.class.php');
require(INA_FOLDER . 'inc/openstat.class.php');
require(INA_FOLDER . 'inc/forms.class.php');
require(INA_FOLDER . 'inc/wordpress.class.php');
require(INA_FOLDER . 'inc/pagetracking.class.php');
require(INA_FOLDER . 'inc/readmarkers.class.php');
require(INA_FOLDER . 'inc/downloads.class.php');
require(INA_FOLDER . 'inc/customcode.class.php');
require(INA_FOLDER . 'inc/manager.class.php');


// Меню администратора
add_action('admin_menu', 'InaManager::adminMenu');
