<?php
/**
 * Модуль Код пользователя в Head и Footer
 *
 * Реализует добавление произвольного кода в хедер и футер страниц
 */
class InaCustomCode extends InaModule
{
	/**#@+
	* Параметры опций модуля
	* @const
	*/
	const MENU_SLUG			= 'in-analytics-custom-code.php';
	const SECTION			= 'ina_custom_code';
	const OPTION_ENABLED	= 'ina_cc_enabled';
	const OPTION_HEADER		= 'ina_cc_header';
	const OPTION_FOOTER		= 'ina_cc_footer';

	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Инициализируем параметры
		add_action('admin_init', 'InaCustomCode::initSettings');
	}
	
	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		add_submenu_page(
			InaManager::MENU_SLUG,		 				// parent_slug - The slug name for the parent menu
			__('Custom Code Options', 'inanalytics'), 	// page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('Custom Code', 'inanalytics'), 			// menu_title - The text to be used for the menu
			'manage_options', 							// capability - The capability required for this menu to be displayed to the user.
			self::MENU_SLUG, 							// menu_slug - The slug name to refer to this menu by
			'InaCustomCode::showOptionPage'				// function - The function to be called to output the content for this page
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
			self::SECTION,								// id - String for use in the 'id' attribute of tags
			__('Custom Code Options', 'inanalytics'), 	// title -  Title of the section
			'InaCustomCode::showSectionDescription',	// callback - Function that fills the section with the desired content
			self::MENU_SLUG								// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: включение модуля
		register_setting(self::MENU_SLUG, self::OPTION_ENABLED);
		add_settings_field( 
			self::OPTION_ENABLED,						// id - String for use in the 'id' attribute of tags
			__('Custom Code enabled', 'inanalytics' ),	// Title of the field
			'InaCustomCode::showModuleEnabled',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 							// page - The menu page on which to display this field
			self::SECTION 								// section - The section of the settings page
		);
		// Параметр: код хедера
		register_setting(self::MENU_SLUG, self::OPTION_HEADER);
		add_settings_field( 
			self::OPTION_HEADER,						// id - String for use in the 'id' attribute of tags
			__('Code in page header', 'inanalytics'),	// Title of the field
			'InaCustomCode::showHeaderCode',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 							// page - The menu page on which to display this field
			self::SECTION 								// section - The section of the settings page
		);		
		// Параметр: код футера
		register_setting(self::MENU_SLUG, self::OPTION_FOOTER);
		add_settings_field( 
			self::OPTION_FOOTER,						// id - String for use in the 'id' attribute of tags
			__('Code in page footer', 'inanalytics' ),	// Title of the field
			'InaCustomCode::showFooterCode',			// callback - Function that fills the field with the desired inputs
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
		_e('Check this for enabling the custom HTML/JS code at header and footer of each page. Read more here', 'inanalytics');
	}	
	
	
	/**
	 * Показывает поле header code
	 */   
	public static function showHeaderCode()
	{
		$name = self::OPTION_HEADER;
		$value = get_option($name);
		_e('Specify additional HTML/JS code in header. Read more here', 'inanalytics');	
		echo "<br/><textarea name='{$name}' cols='100' rows='8'>{$value}</textarea>";
	}	
	
	/**
	 * Показывает поле footer code
	 */   
	public static function showFooterCode()
	{
		$name = self::OPTION_FOOTER;
		$value = get_option($name);
		_e('Specify additional HTML/JS code in header. Read more here', 'inanalytics');	
		echo "<br/><textarea name='{$name}' cols='100' rows='8'>{$value}</textarea>";
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
		return '';
	}
	
	/**
	 * Метод возвращает JS код модуля для Footer
	 */   
	public function getFooterJS($templateFile='')
	{
		$js = '';
		return $js;
	}	
	
}