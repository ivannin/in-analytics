<?php
/**
 * Модуль Отслеживание User ID
 *
 * Реализует отслеживание User ID
 */
class InaUserID extends InaModule
{
	/**#@+
	* Параметры опций модуля
	* @const
	*/
	const MENU_SLUG					= 'in-analytics-user-id.php';
	const SECTION					= 'ina_user_id';
	const OPTION_ENABLED			= 'ina_uid_enabled';
	const OPTION_DIMENSION_ENABLED	= 'ina_uid_custom_dim_enabled';
	const OPTION_CUSTOM_DIMENSION	= 'ina_uid_custom_dim';

	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Инициализируем параметры
		add_action('admin_init', 'InaUserID::initSettings');
	}
	
	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		add_submenu_page(
			InaManager::MENU_SLUG,		 						// parent_slug - The slug name for the parent menu
			__('User ID Tracking Options', 'inanalytics'), 		// page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('User ID', 'inanalytics'), 						// menu_title - The text to be used for the menu
			'manage_options', 									// capability - The capability required for this menu to be displayed to the user.
			self::MENU_SLUG, 									// menu_slug - The slug name to refer to this menu by
			'InaUserID::showOptionPage'							// function - The function to be called to output the content for this page
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
			__('User ID Tracking Options', 'inanalytics'), 		// title -  Title of the section
			'InaUserID::showSectionDescription',				// callback - Function that fills the section with the desired content
			self::MENU_SLUG										// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: включение модуля
		register_setting(self::MENU_SLUG, self::OPTION_ENABLED);
		add_settings_field( 
			self::OPTION_ENABLED,								// id - String for use in the 'id' attribute of tags
			__('User ID tracking enabled', 'inanalytics' ),		// Title of the field
			'InaUserID::showModuleEnabled',						// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 									// page - The menu page on which to display this field
			self::SECTION 										// section - The section of the settings page
		);
		// Параметр: Отправка User ID в произвольный параметр
		register_setting(self::MENU_SLUG, self::OPTION_DIMENSION_ENABLED);
		add_settings_field( 
			self::OPTION_DIMENSION_ENABLED,					// id - String for use in the 'id' attribute of tags
			__('Also send User ID to custom dimension', 'inanalytics'),		// Title of the field
			'InaUserID::showCustomDimensionEnabled',	// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 							// page - The menu page on which to display this field
			self::SECTION 								// section - The section of the settings page
		);
		// Параметр: Имя произвольного параметра
		register_setting(self::MENU_SLUG, self::OPTION_CUSTOM_DIMENSION);
		add_settings_field( 
			self::OPTION_CUSTOM_DIMENSION,					// id - String for use in the 'id' attribute of tags
			__('Custom Dimension Name', 'inanalytics'),	// Title of the field
			'InaUserID::showCustomDimension',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 							// page - The menu page on which to display this field
			self::SECTION 								// section - The section of the settings page
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
		_e('Check this for enabling User ID tracking. You also must enable this in Google Analytics Administration. Read more here', 'inanalytics');
	}
	/**
	 * Показывает поле вклчюение произвольного параметра
	 */   
	 public static function showCustomDimensionEnabled()
	{
		$name = self::OPTION_DIMENSION_ENABLED;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e('Check this for enabling send User ID tracking. You also must create the custom dimension in Google Analytics Administration. Read more here', 'inanalytics');
	}
	/**
	 * Показывает поле Bounce Rate Timeout
	 */   
	 public static function showCustomDimension()
	{
		$name = self::OPTION_CUSTOM_DIMENSION;
		$value = get_option($name, 'dimension1');
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the custom dimension name. Read more here', 'inanalytics');
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
		// User ID подсталвляется в runtime
		// InaManager::handleUserID
		return '';
	}
}