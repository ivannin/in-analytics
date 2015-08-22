<?php
add_action('wpcf7_posted_data', 'InaForms::sendCF7Post');

/**
 * Модуль Интеграция с формами
 *
 * Реализует отслеживание передачи контактных форм
 */
class InaForms extends InaMeasurementProtocol
{
	/**#@+
	* Параметры опций модуля
	* @const
	*/
	const MENU_SLUG					= 'in-analytics-forms.php';
	const SECTION					= 'ina_forms';
	const OPTION_CF7_ENABLED		= 'ina_forms_cf7_enabled';
	const OPTION_CRAVITY_ENABLED	= 'ina_forms_gravity_enabled';
	const OPTION_CATEGORY			= 'ina_forms_category';
	const OPTION_ACTION				= 'ina_forms_action';


	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Инициализируем параметры
		add_action('admin_init', 'InaForms::initSettings');
	}
	
	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		add_submenu_page(
			InaManager::MENU_SLUG,		 				// parent_slug - The slug name for the parent menu
			__('Contact Forms Options', 'inanalytics'), // page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('Contact Forms', 'inanalytics'), 		// menu_title - The text to be used for the menu
			'manage_options', 							// capability - The capability required for this menu to be displayed to the user.
			self::MENU_SLUG, 							// menu_slug - The slug name to refer to this menu by
			'InaForms::showOptionPage'					// function - The function to be called to output the content for this page
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
			__('Contact Forms Options', 'inanalytics'), // title -  Title of the section
			'InaForms::showSectionDescription',			// callback - Function that fills the section with the desired content
			self::MENU_SLUG								// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: Интеграция с CF7
		register_setting(self::MENU_SLUG, self::OPTION_CF7_ENABLED);
		add_settings_field( 
			self::OPTION_CF7_ENABLED,			// id - String for use in the 'id' attribute of tags
			__('CF7 Plugin Intergation enabled', 'inanalytics' ),	// Title of the field
			'InaForms::showCF7Enabled',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);
		// Параметр: Интеграция с GravityForms
		register_setting(self::MENU_SLUG, self::OPTION_CRAVITY_ENABLED);
		add_settings_field( 
			self::OPTION_CRAVITY_ENABLED,		// id - String for use in the 'id' attribute of tags
			__('Gravity Forms Plugin Intergation enabled', 'inanalytics' ),	// Title of the field
			'InaForms::showGravityEnabled',		// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);		
		// Параметр: Bounce Rate Category
		register_setting(self::MENU_SLUG, self::OPTION_CATEGORY);
		add_settings_field( 
			self::OPTION_CATEGORY,				// id - String for use in the 'id' attribute of tags
			__('Forms Event Category', 'inanalytics'),	// Title of the field
			'InaForms::showCategory',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);		
		// Параметр: Bounce Rate Action
		register_setting(self::MENU_SLUG, self::OPTION_ACTION);
		add_settings_field( 
			self::OPTION_ACTION,				// id - String for use in the 'id' attribute of tags
			__('Forms Event Action', 'inanalytics'),	// Title of the field
			'InaForms::showAction',				// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);		
	}

	/**
	 * Формирует страницу в меню администратора
	 */   
	 public static function showSectionDescription()
	{
		/* DEBUG: Отправка тестового события 
		static::sendHit(InaMeasurementProtocol::HIT_EVENT, array(
			'category'	=> 'Тест',
			'action'	=> 'Тестовое действие',
			'label'		=> 'Тестовое событие',
		)); */
		echo 'showSectionDescription';
	}	
	
	/**
	 * Показывает поле интеграции с CF7
	 */   
	 public static function showCF7Enabled()
	{
		$name = self::OPTION_CF7_ENABLED;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e('Check this for enabling integration with CF7 Plugin. Read more here', 'inanalytics');
	}
	/**
	 * Показывает поле интеграции с CF7
	 */   
	 public static function showGravityEnabled()
	{
		$name = self::OPTION_CRAVITY_ENABLED;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e('Check this for enabling integration with Gravity Forms Plugin. Read more here', 'inanalytics');
	}
	
	/**
	 * Показывает поле Event Category
	 */   
	 public static function showCategory()
	{
		$name = self::OPTION_CATEGORY;
		$value = get_option($name, __('Forms', 'inanalytics'));
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the event category. This event occurs when any form posts', 'inanalytics');
	}	
	/**
	 * Показывает поле Event Action
	 */   
	 public static function showAction()
	{
		$name = self::OPTION_ACTION;
		$value = get_option($name, __('Send', 'inanalytics'));
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the event action. This event occurs when any form posts', 'inanalytics');
	}	
	/**
	 * Метод возвращает доступность модуля
     * @return bool	 
	 */   
	public function isEnabled()
	{
		return (bool) 
			get_option(self::OPTION_CF7_ENABLED) ||
			get_option(self::OPTION_CRAVITY_ENABLED);
	}
	
	/**
	 * Метод возвращает JS код модуля
	 */   
	public function getHeaderJS($templateFile='')
	{
		return '';
	}
	
	// ------------------------------ RUNTIME ------------------------------
	
	/**
	 * Отправка события формы CF7
	 */   
	public static function sendCF7Post($cf7Object)
	{
		if (!get_option(self::OPTION_CF7_ENABLED)) return;
		
		/* DEBUG 
		file_put_contents(INA_FOLDER.'/cf7Object.txt', var_export($cf7Object, true));*/
		
		$label = $cf7Object->_wpcf7;
		
		static::sendHit(InaMeasurementProtocol::HIT_EVENT, array(
			'category'	=> get_option(self::OPTION_CATEGORY),
			'action'	=> get_option(self::OPTION_ACTION),
			'label'		=> $label,
		));
	}

	/**
	 * Отправка события формы Gravity Forms
	 */   
	public static function sendGravityPost($entry, $form)
	{
		if (!get_option(self::OPTION_CRAVITY_ENABLED)) return;

		$label = $form->title;

		static::sendHit(InaMeasurementProtocol::HIT_EVENT, array(
			'category'	=> get_option(self::OPTION_CATEGORY),
			'action'	=> get_option(self::OPTION_ACTION),
			'label'		=> $label,
		));
	}

	
}