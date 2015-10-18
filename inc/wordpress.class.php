<?php
/* Hooks */
if (get_option(InaWordpress::OPTION_WP_ENABLED))
{
	//add_action('wpcf7_posted_data', 'InaWordpress::sendCF7Post');	
		
		
}
	


/**
 * Модуль Интеграция с WordPress
 *
 * Реализует отслеживание передачи контактных форм
 */
class InaWordpress extends InaMeasurementProtocol
{
	/**#@+
	* Параметры опций модуля
	* @const
	*/
	const MENU_SLUG								= 'in-analytics-wordpress.php';
	const SECTION								= 'ina_wordpress';
	const OPTION_WP_ENABLED						= 'ina_wp_event_enabled';
	const OPTION_CATEGORY						= 'ina_wp_event_category';
	const OPTION_CATEGORY_DEFAULT				= 'WordPress';
	const OPTION_EVENT_USER_REGISTER			= 'ina_wp_event_user_register';
	const OPTION_EVENT_USER_REGISTER_DEFAULT	= 'User register';
	const OPTION_EVENT_USER_LOGIN				= 'ina_wp_event_user_login';
	const OPTION_EVENT_USER_LOGIN_DEFAULT		= 'User login';
	const OPTION_EVENT_USER_PASSRESET			= 'ina_wp_event_user_pass_reset';
	const OPTION_EVENT_USER_PASSRESET_DEFAULT	= 'Reset password';	
	const OPTION_EVENT_COMMENT					= 'ina_wp_event_comment';
	const OPTION_EVENT_COMMENT_DEFAULT			= 'Comment';	

	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Инициализируем параметры
		add_action('admin_init', 'InaWordpress::initSettings');
	}
	
	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		add_submenu_page(
			InaManager::MENU_SLUG,		 				// parent_slug - The slug name for the parent menu
			__('Wordpress Events Options', 'inanalytics'), // page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('WordPress Events', 'inanalytics'), 		// menu_title - The text to be used for the menu
			'manage_options', 							// capability - The capability required for this menu to be displayed to the user.
			self::MENU_SLUG, 							// menu_slug - The slug name to refer to this menu by
			'InaWordpress::showOptionPage'				// function - The function to be called to output the content for this page
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
			self::SECTION,							// id - String for use in the 'id' attribute of tags
			__('Wordpress Events Options', 'inanalytics'), // title -  Title of the section
			'InaWordpress::showSectionDescription',	// callback - Function that fills the section with the desired content
			self::MENU_SLUG							// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: Интеграция с WordPress
		register_setting(self::MENU_SLUG, self::OPTION_WP_ENABLED);
		add_settings_field( 
			self::OPTION_WP_ENABLED,			// id - String for use in the 'id' attribute of tags
			__('WordPress Events Tracking enabled', 'inanalytics' ),	// Title of the field
			'InaWordpress::showEnabled',		// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);
		// Параметр: Категория событий
		register_setting(self::MENU_SLUG, self::OPTION_CATEGORY);
		add_settings_field( 
			self::OPTION_CATEGORY,				// id - String for use in the 'id' attribute of tags
			__('WordPress Event Category', 'inanalytics'),	// Title of the field
			'InaWordpress::showCategory',		// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);		
		// Параметр: Событие Регистрация пользователя
		register_setting(self::MENU_SLUG, self::OPTION_EVENT_USER_REGISTER);
		add_settings_field( 
			self::OPTION_EVENT_USER_REGISTER,	// id - String for use in the 'id' attribute of tags
			__('Event User Register', 'inanalytics'),	// Title of the field
			'InaWordpress::showUserRegister',	// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);
		// Параметр: Событие Авторизация пользователя
		register_setting(self::MENU_SLUG, self::OPTION_EVENT_USER_LOGIN);
		add_settings_field( 
			self::OPTION_EVENT_USER_LOGIN,		// id - String for use in the 'id' attribute of tags
			__('Event User Login', 'inanalytics'),	// Title of the field
			'InaWordpress::showUserLogin',	// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);
		// Параметр: Событие Восстановление пароля
		register_setting(self::MENU_SLUG, self::OPTION_EVENT_USER_PASSRESET);
		add_settings_field( 
			self::OPTION_EVENT_USER_PASSRESET,	// id - String for use in the 'id' attribute of tags
			__('Event Reset Password', 'inanalytics'),	// Title of the field
			'InaWordpress::showResetPassword',	// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);
		// Параметр: Событие Комментарий
		register_setting(self::MENU_SLUG, self::OPTION_EVENT_COMMENT);
		add_settings_field( 
			self::OPTION_EVENT_COMMENT,			// id - String for use in the 'id' attribute of tags
			__('Event Comment', 'inanalytics'),	// Title of the field
			'InaWordpress::showComment',	// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);
		
	}

	/**
	 * Формирует страницу в меню администратора
	 */   
	 public static function showSectionDescription()
	{
		_e('This module implements the tracking of certain Wordpress events as Google Analytics events. You can use these events to track goals, for example, comments or user registration.', 'inanalytics');
	}	
	
	/**
	 * Показывает поле Интеграция с WordPress
	 */   
	 public static function showEnabled()
	{
		$name = self::OPTION_WP_ENABLED;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e('Check this for enabling tracking WordPress Events. Read more here', 'inanalytics');
	}
	
	/**
	 * Показывает поле Event Category
	 */   
	 public static function showCategory()
	{
		$name = self::OPTION_CATEGORY;
		$value = get_option($name, __('Events Category', 'inanalytics'), self::OPTION_CATEGORY_DEFAULT);
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the event category.', 'inanalytics');
	}	
	/**
	 * Показывает поле Событие регистрация пользователя
	 */   
	 public static function showUserRegister()
	{
		$name = self::OPTION_EVENT_USER_REGISTER;
		$value = get_option($name, __('User Register', 'inanalytics'), self::OPTION_EVENT_USER_REGISTER_DEFAULT);
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the User Register event action. This event occurs when new user registers.', 'inanalytics');
	}

	/**
	 * Показывает поле Событие регистрация пользователя
	 */   
	 public static function showUserLogin()
	{
		$name = self::OPTION_EVENT_USER_LOGIN;
		$value = get_option($name, __('User Register', 'inanalytics'), self::OPTION_EVENT_USER_LOGIN_DEFAULT);
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the User Login event action. This event occurs when user login', 'inanalytics');
	}

	/**
	 * Показывает поле Событие регистрация пользователя
	 */   
	 public static function showResetPassword()
	{
		$name = self::OPTION_EVENT_USER_PASSRESET;
		$value = get_option($name, __('Reset Password', 'inanalytics'), self::OPTION_EVENT_USER_PASSRESET_DEFAULT);
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the User resets password event action. This event occurs when the user has requested an email message to retrieve their password', 'inanalytics');
	}

	/**
	 * Показывает поле Событие Комментарий
	 */   
	 public static function showComment()
	{
		$name = self::OPTION_EVENT_COMMENT;
		$value = get_option($name, __('Reset Password', 'inanalytics'), self::OPTION_EVENT_COMMENT_DEFAULT);
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the User Login event action. This event occurs just after a comment is saved in the database.', 'inanalytics');
	}

	
	/**
	 * Метод возвращает доступность модуля
     * @return bool	 
	 */   
	public function isEnabled()
	{
		return (bool) get_option(self::OPTION_WP_ENABLED));
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
		/* DEBUG 
		file_put_contents(INA_FOLDER.'/cf7Object.txt', var_export($cf7Object, true));*/
		
		$label = (is_object($cf7Object)) ? $cf7Object->_wpcf7 : '';
		
		// Передача на Google Analytics через Measurement Protocol
		if (get_option(InaAnalytics::OPTION_ENABLED))
		{
			InaMeasurementProtocol::sendHit(InaMeasurementProtocol::HIT_EVENT, array(
				'category'	=> get_option(self::OPTION_CATEGORY, self::OPTION_CATEGORY_DEFAULT),
				'action'	=> get_option(self::OPTION_EVENT_USER_REGISTER, self::OPTION_EVENT_USER_REGISTER_DEFAULT),
				'label'		=> $label,
			));
		}
		
		return $cf7Object;
	}


}