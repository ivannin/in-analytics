<?php
/**
 * Модуль основного кода Google Analytics
 *
 * Реализует формирование оснвовного кода
 */
class InaAnalytics extends InaModule
{
	/**#@+
	* Параметры опций модуля
	* @const
	*/
	const MENU_SLUG							= 'in-analytics-ga.php';
	const SECTION							= 'ina_google_analytics';
	const OPTION_ENABLED					= 'ina_ga_enabled';
	const OPTION_ID							= 'ina_ga_id';
	const OPTION_COOKIE						= 'ina_ga_cookie';
	const OPTION_DISPLAYFEATURES			= 'ina_ga_displayfeatures';
	const OPTION_LINKATTRIBUTION			= 'ina_ga_linkattribution';

	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Инициализируем параметры
		add_action('admin_init', 'InaAnalytics::initSettings');
	}
	
	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		add_submenu_page(
			InaManager::MENU_SLUG,		 					// parent_slug - The slug name for the parent menu
			__('Google Analytics Options', 'inanalytics'), 	// page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('Google Analytics', 'inanalytics'), 			// menu_title - The text to be used for the menu
			'manage_options', 								// capability - The capability required for this menu to be displayed to the user.
			self::MENU_SLUG, 								// menu_slug - The slug name to refer to this menu by
			'InaAnalytics::showOptionPage'					// function - The function to be called to output the content for this page
		);
	}
	
	/**
	 * Формирует страницу в меню администратора
	 */   
	 public static function showOptionPage($page='', $option_group='', $title='')
	{
		// Create the option page
		parent::showOptionPage(
			self::MENU_SLUG,	// The slug name of the page for settings sections
			self::MENU_SLUG		// The settings group name! ВАЖНО! ЭТО КОСЯК, ПО ДРУГОМУ НЕ РАБОТАЕТ
		);
	}	
	
	/**
	 * Инициализирует параметры модуля
	 */   
	 public static function initSettings()
	{
		// Создает секцию параметров
		add_settings_section(
			self::SECTION,											// id - String for use in the 'id' attribute of tags
			__( 'Google Analytics Basic Options', 'inanalytics'), 	// title -  Title of the section
			'InaAnalytics::showSectionDescription',					// callback - Function that fills the section with the desired content
			self::MENU_SLUG											// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: включение модуля
		register_setting(self::MENU_SLUG, self::OPTION_ENABLED);
		add_settings_field( 
			self::OPTION_ENABLED,							// id - String for use in the 'id' attribute of tags
			__( 'Google Analytics enabled', 'inanalytics' ),// Title of the field
			'InaAnalytics::showModuleEnabled',				// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);
		// Параметр: Google Analytics ID
		register_setting(self::MENU_SLUG, self::OPTION_ID);
		add_settings_field( 
			self::OPTION_ID,								// id - String for use in the 'id' attribute of tags
			__( 'Google Analytics ID', 'inanalytics'),		// Title of the field
			'InaAnalytics::showGoogleAnalyticsID',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);
		// Параметр: Google Analytics Cookie Domain
		register_setting(self::MENU_SLUG, self::OPTION_COOKIE);
		add_settings_field( 
			self::OPTION_COOKIE,								// id - String for use in the 'id' attribute of tags
			__( 'Google Analytics cookie domain', 'inanalytics'),		// Title of the field
			'InaAnalytics::showCookieDomain',				// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);		
		// Параметр: Display Features
		register_setting(self::MENU_SLUG, self::OPTION_DISPLAYFEATURES);
		add_settings_field( 
			self::OPTION_DISPLAYFEATURES,					// id - String for use in the 'id' attribute of tags
			__( 'Enable demographic and interest reports', 'inanalytics'),		// Title of the field
			'InaAnalytics::showDisplayFeatures',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);		
		// Параметр: enhanced link attribution
		register_setting(self::MENU_SLUG, self::OPTION_LINKATTRIBUTION);
		add_settings_field( 
			self::OPTION_LINKATTRIBUTION,					// id - String for use in the 'id' attribute of tags
			__( 'Enable enhanced link attribution', 'inanalytics' ),		// Title of the field
			'InaAnalytics::showLinkAttribution',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);	
	}

	/**
	 * Формирует страницу в меню администратора
	 */   
	 public static function showSectionDescription()
	{
		echo 'showSectionDescription';
	}	
	
	/**
	 * Показывает поле доступности модуля
	 */   
	 public static function showModuleEnabled()
	{
		$name = self::OPTION_ENABLED;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e( 'Check this for enabling Google Analytics tracking', 'inanalytics');
	}	
	/**
	 * Показывает поле Google Analytics ID
	 */   
	 public static function showGoogleAnalyticsID()
	{
		$name = self::OPTION_ID;
		$value = get_option($name);
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e( 'Specify the Google Analytics ID. See this value in the settings of Google Analytics. Read more here', 'inanalytics');
	}
	/**
	 * Показывает поле Google Analytics Cookie Domain
	 */   
	 public static function showCookieDomain()
	{
		$name = self::OPTION_COOKIE;
		$value = get_option($name, 'auto');
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e( 'Specify the Google Analytics cookie domain. Read more here', 'inanalytics');
	}	
	
	/**
	 * Показывает поле Google Analytics Display Features
	 */   
	 public static function showDisplayFeatures()
	{
		$name = self::OPTION_DISPLAYFEATURES;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e( 'Check this for enabling demographic and interest data in your Analytics reports. You also must enable this in Google Analytics Administration. Read more here', 'inanalytics');	
	}
	/**
	 * Показывает поле enhanced link attribution
	 */   
	 public static function showLinkAttribution()
	{
		$name = self::OPTION_LINKATTRIBUTION;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e( 'Check this if you want to see separate information for multiple links on a page that all have the same destination. You also must enable this in Google Analytics Administration. Read more here', 'inanalytics');	
	}
	
	/**
	 * Метод возвращает доступность модуля
     * @return bool	 
	 */   
	public function isEnabled()
	{
		return (bool) get_option(self::OPTION_ENABLED);
	}
	
	/**
	 * Метод возвращает JS код модуля
	 */   
	public function getHeaderJS($templateFile='')
	{
		$js = '';
		if (empty(get_option(self::OPTION_ID)))
			$js .= '/*! GOOGLE ANALYTICS ID NOT SPECIFIED !*/';
		$js .= parent::getHeaderJS(INA_FOLDER . 'js/google-analytics.js') . PHP_EOL;
		$js = str_replace(
			array(
				'%ANALYTICS_ID%',
				'%DOMAIN%'
			), 
			array(
				get_option(self::OPTION_ID),
				get_option(self::OPTION_COOKIE)
			), 
			$js);
		if (get_option(self::OPTION_DISPLAYFEATURES))
				$js .= "ga('require', 'displayfeatures');" . PHP_EOL;		
		if (get_option(self::OPTION_LINKATTRIBUTION))
				$js .= "ga('require', 'linkid', 'linkid.js');" . PHP_EOL;
		return $js;
	}
}