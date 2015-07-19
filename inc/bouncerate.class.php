<?php
/**
 * Модуль Точный показатель отказов
 *
 * Реализует учет точного показателя отказов
 */
class InaBounceRate extends InaModule
{
	/**#@+
	* Параметры опций модуля
	* @const
	*/
	const MENU_SLUG			= 'in-analytics-bounce-rate.php';
	const SECTION			= 'ina_bounce_rate';
	const OPTION_ENABLED	= 'ina_br_enabled';
	const OPTION_TIMEOUT	= 'ina_br_timeout';
	const OPTION_CATEGORY	= 'ina_br_category';
	const OPTION_ACTION		= 'ina_br_action';


	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Инициализируем параметры
		add_action('admin_init', 'InaBounceRate::initSettings');
	}
	
	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		add_submenu_page(
			InaManager::MENU_SLUG,		 						// parent_slug - The slug name for the parent menu
			__('Accurate Bounce Rate Options', 'inanalytics'), 	// page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('Accurate Bounce Rate', 'inanalytics'), 			// menu_title - The text to be used for the menu
			'manage_options', 									// capability - The capability required for this menu to be displayed to the user.
			self::MENU_SLUG, 									// menu_slug - The slug name to refer to this menu by
			'InaBounceRate::showOptionPage'						// function - The function to be called to output the content for this page
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
			self::SECTION,										// id - String for use in the 'id' attribute of tags
			__('Accurate Bounce Rate Options', 'inanalytics'), 	// title -  Title of the section
			'InaBounceRate::showSectionDescription',			// callback - Function that fills the section with the desired content
			self::MENU_SLUG										// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: включение модуля
		register_setting(self::MENU_SLUG, self::OPTION_ENABLED);
		add_settings_field( 
			self::OPTION_ENABLED,								// id - String for use in the 'id' attribute of tags
			__('Accurate Bounce Rate enabled', 'inanalytics' ),	// Title of the field
			'InaBounceRate::showModuleEnabled',					// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 									// page - The menu page on which to display this field
			self::SECTION 										// section - The section of the settings page
		);
		// Параметр: Bounce Rate Timer
		register_setting(self::MENU_SLUG, self::OPTION_TIMEOUT, 'intval');
		add_settings_field( 
			self::OPTION_TIMEOUT,							// id - String for use in the 'id' attribute of tags
			__('Page Bounce Timeout', 'inanalytics'),		// Title of the field
			'InaBounceRate::showBounceRateTimer',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);
		// Параметр: Bounce Rate Category
		register_setting(self::MENU_SLUG, self::OPTION_CATEGORY);
		add_settings_field( 
			self::OPTION_CATEGORY,							// id - String for use in the 'id' attribute of tags
			__('Non-Bounce Event Category', 'inanalytics'),	// Title of the field
			'InaBounceRate::showBounceRateCategory',		// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);		
		// Параметр: Bounce Rate Action
		register_setting(self::MENU_SLUG, self::OPTION_ACTION);
		add_settings_field( 
			self::OPTION_ACTION,							// id - String for use in the 'id' attribute of tags
			__('Non-Bounce Event Action', 'inanalytics'),	// Title of the field
			'InaBounceRate::showBounceRateAction',			// callback - Function that fills the field with the desired inputs
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
		_e('Check this for enabling accurate bounce rate in your reports. Read more here', 'inanalytics');
	}	
	/**
	 * Показывает поле Bounce Rate Timeout
	 */   
	 public static function showBounceRateTimer()
	{
		$name = self::OPTION_TIMEOUT;
		$value = get_option($name, 15000);
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the page bounce timer (milliseconds). Read more here', 'inanalytics');
	}
	/**
	 * Показывает поле Bounce Rate Category
	 */   
	 public static function showBounceRateCategory()
	{
		$name = self::OPTION_CATEGORY;
		$value = get_option($name, __('Visits', 'inanalytics'));
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the event category. This event occurs when detecting a visit without bounce', 'inanalytics');
	}	
	/**
	 * Показывает поле Bounce Rate Action
	 */   
	 public static function showBounceRateAction()
	{
		$name = self::OPTION_ACTION;
		$value = get_option($name, __('Visit without bounce', 'inanalytics'));
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the event action. This event occurs when detecting a visit without bounce', 'inanalytics');
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
		if (get_option(InaAnalytics::OPTION_ENABLED))
		{
			$gaJS .= parent::getHeaderJS(INA_FOLDER . 'js/accurate-bounce-rate.js') . PHP_EOL;
			$gaJS = str_replace(
				array(
					'%CATEGORY%',
					'%ACTION%',
					'%TIMEOUT%'
				), 
				array(
					get_option(self::OPTION_CATEGORY),
					get_option(self::OPTION_ACTION),
					get_option(self::OPTION_TIMEOUT)
				), 
				$gaJS);
			$js .= $gaJS;
		}
		return $js;
	}
}