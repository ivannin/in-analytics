<?php
/**
 * Модуль интеграции со службами Яндекс
 *
 * Реализует обработку метки _openstat
 */
class InaOpenstat extends InaModule
{
	/**#@+
	* Параметры опций модуля
	* @const
	*/
	const MENU_SLUG					= 'in-analytics-openstat.php';
	const SECTION					= 'ina_openstat';
	const OPTION_ENABLED			= 'ina_openstat_enabled';

	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Инициализируем параметры
		add_action('admin_init', 'InaOpenstat::initSettings');
	}
	
	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		add_submenu_page(
			InaManager::MENU_SLUG,		 						// parent_slug - The slug name for the parent menu
			__('Openstat Tag Tracking', 'inanalytics'), 		// page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('Openstat Tag', 'inanalytics'), 						// menu_title - The text to be used for the menu
			'manage_options', 									// capability - The capability required for this menu to be displayed to the user.
			self::MENU_SLUG, 									// menu_slug - The slug name to refer to this menu by
			'InaOpenstat::showOptionPage'							// function - The function to be called to output the content for this page
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
			__('Openstat Tracking Options', 'inanalytics'), 		// title -  Title of the section
			'InaOpenstat::showSectionDescription',				// callback - Function that fills the section with the desired content
			self::MENU_SLUG										// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: включение модуля
		register_setting(self::MENU_SLUG, self::OPTION_ENABLED);
		add_settings_field( 
			self::OPTION_ENABLED,								// id - String for use in the 'id' attribute of tags
			__('Openstat tracking enabled', 'inanalytics' ),		// Title of the field
			'InaOpenstat::showModuleEnabled',						// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 									// page - The menu page on which to display this field
			self::SECTION 										// section - The section of the settings page
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
		_e('Check this for enabling Openstat tag tracking. This allows you tracking correctly clicks from Yandex.Direct, Yandex.Market and other Russian Ad Services. Read more here', 'inanalytics');
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
		return parent::getHeaderJS(INA_FOLDER . 'js/openstat.js') . PHP_EOL;
	}
}