<?php
/*
Plugin Name: IN-Analytics
Plugin URI: http://in-analytics.com/
Description: Just another Google Analytics Plugin
Version: 0.1
Author: Ivan Nikitin
Author URI: http://ivannikitin.com
Text Domain: inanalytics
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
define('INA_TEXT_DOMAIN', 	'inanalytics');
define('INA_FOLDER', 		plugin_dir_path(__FILE__));

// Классы
require(INA_FOLDER . 'inc/module.class.php');
require(INA_FOLDER . 'inc/analytics.class.php');
require(INA_FOLDER . 'inc/metrika.class.php');
require(INA_FOLDER . 'inc/bouncerate.class.php');
require(INA_FOLDER . 'inc/userid.class.php');
require(INA_FOLDER . 'inc/pagetracking.class.php');
require(INA_FOLDER . 'inc/manager.class.php');


// Меню администратора
add_action('admin_menu', 'InaManager::adminMenu');
