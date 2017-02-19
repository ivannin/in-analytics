<<<<<<< HEAD
<?php
/**
 * Plugin Name: IN-Analytics
 * Plugin URI: http://in-analytics.com/
 * Description: Just another Google Analytics Plugin for WooCommerce and other sites
 * Version: 1.0.1
 * Author: Ivan Nikitin and partners
 * Author URI: http://ivannikitin.com
 * Text Domain: in-analytics
 * 
 * Copyright 2016 Ivan Nikitin  (email: info@ivannikitin.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/* ------------------------ Определения плагина ------------------------ */
define('INA_FOLDER', 		plugin_dir_path(__FILE__));
define('INA_URL', 			plugin_dir_url(__FILE__));
define('INA_TEXT_DOMAIN', 	'in-analytics');
/* ------------------------- Загрузка классов -------------------------- */
require( INA_FOLDER . 'classes/INA_ModuleManager.php');
require( INA_FOLDER . 'classes/INA_ModuleBase.php');
require( INA_FOLDER . 'classes/INA_Tracker.php');

/* ---------------- Локализация и инициализация плагина ---------------- */
add_action( 'init', 'ina_load_textdomain' );
function ina_load_textdomain() 
{
	// Загрузка переводов
	load_plugin_textdomain( INA_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

	// Инициализация плагина
	new INA_ModuleManager( INA_FOLDER, INA_URL );
=======
<?php
/**
 * Plugin Name: IN-Analytics
 * Plugin URI: http://in-analytics.com/
 * Description: Just another Google Analytics Plugin for WooCommerce and other sites
 * Version: 1.0.1
 * Author: Ivan Nikitin and partners
 * Author URI: http://ivannikitin.com
 * Text Domain: in-analytics
 * 
 * Copyright 2016 Ivan Nikitin  (email: info@ivannikitin.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/* ------------------------ Определения плагина ------------------------ */
define('INA_FOLDER', 		plugin_dir_path(__FILE__));
define('INA_URL', 			plugin_dir_url(__FILE__));
define('INA_TEXT_DOMAIN', 	'in-analytics');
/* ------------------------- Загрузка классов -------------------------- */
require( INA_FOLDER . 'classes/INA_ModuleManager.php');
require( INA_FOLDER . 'classes/INA_ModuleBase.php');

/* ---------------- Локализация и инициализация плагина ---------------- */
add_action( 'init', 'ina_load_textdomain' );
function ina_load_textdomain() 
{
	// Загрузка переводов
	load_plugin_textdomain( INA_TEXT_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

	// Инициализация плагина
	new INA_ModuleManager( INA_FOLDER, INA_URL );
>>>>>>> master
}