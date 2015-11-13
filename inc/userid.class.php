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
	const MENU_SLUG						= 'in-analytics-user-id.php';
	const SECTION						= 'ina_user_id';
	const OPTION_ENABLED				= 'ina_uid_enabled';
	const OPTION_DIMENSION_ENABLED		= 'ina_uid_custom_dim_enabled';
	const OPTION_CUSTOM_DIMENSION		= 'ina_uid_custom_dim';
	const OPTION_ROLE_ENABLED			= 'ina_uid_custom_dim_role_enabled';	
	const OPTION_ROLE_DIMENSION			= 'ina_uid_custom_dim_role';

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
		// Параметр: Отправка User Role в произвольный параметр
		register_setting(self::MENU_SLUG, self::OPTION_ROLE_ENABLED);
		add_settings_field( 
			self::OPTION_ROLE_ENABLED,					// id - String for use in the 'id' attribute of tags
			__('Also send User Role to custom dimension', 'inanalytics'),		// Title of the field
			'InaUserID::showRoleDimensionEnabled',	// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 							// page - The menu page on which to display this field
			self::SECTION 								// section - The section of the settings page
		);
		// Параметр: Имя произвольного параметра для роли
		register_setting(self::MENU_SLUG, self::OPTION_ROLE_DIMENSION);
		add_settings_field( 
			self::OPTION_ROLE_DIMENSION,				// id - String for use in the 'id' attribute of tags
			__('Custom Dimension Name', 'inanalytics'),	// Title of the field
			'InaUserID::showRoleDimension',				// callback - Function that fills the field with the desired inputs
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
	 * Показывает поле параметра User ID
	 */   
	 public static function showCustomDimension()
	{
		$name = self::OPTION_CUSTOM_DIMENSION;
		$value = get_option($name, 'dimension1');
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the custom dimension name for User ID. Read more here', 'inanalytics');
	}
	/**
	 * Показывает поле вклчюение произвольного параметра роли
	 */   
	public static function showRoleDimensionEnabled()
	{
		$name = self::OPTION_ROLE_ENABLED;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e('Check this for enabling send User Role tracking. You also must create the custom dimension in Google Analytics Administration. Read more here', 'inanalytics');
	}
	/**
	 * Показывает поле параметра роли
	 */   
	public static function showRoleDimension()
	{
		$name = self::OPTION_ROLE_DIMENSION;
		$value = get_option($name, 'dimension2');
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the custom dimension name for User Role. Read more here', 'inanalytics');
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
	
	// ------------------------------ RUNTIME ------------------------------
	
	/**
	 * Подстановка UserID в runtime
	 */   
	public static function handleUserID($js)
	{
		// Информация о пользователе и его ролях
		global $current_user;
		$user_ID = $current_user->ID;
		$user_login = $current_user->user_login;
		$user_roles = $current_user->roles;
		$user_role_id = array_shift($user_roles);
		
		// Получаем название роли пользователя 
		global $wp_roles;
		$user_role = (!empty($user_ID)) ? translate_user_role($wp_roles->roles[$user_role_id]['name']) : '';
			
		
		/* DEBUG
		echo '<pre>', PHP_EOL,PHP_EOL, 'ROLE: ', $user_role, PHP_EOL, 
			'ID: ', $user_ID, PHP_EOL,
			'current_user: ', var_dump($current_user), '</pre>';
		*/
	
		// Работаем только если включе GA, режим UserID или пользователь зашел не анонимно
		if (get_option(InaAnalytics::OPTION_ENABLED) && get_option(InaUserID::OPTION_ENABLED) && $user_ID)
		{
			// Формируем заново строку параметров в ga('create')
			$createParams = str_replace("%DOMAIN%", get_option(InaAnalytics::OPTION_COOKIE), "{'cookieDomain':'%DOMAIN%'}");
			// Расширяем параметры
			$createParamsWithUserID = str_replace("'}", "','userId':'{$user_ID}'}", $createParams);
			// Заменяем строку
			$js = str_replace($createParams, $createParamsWithUserID, $js);
			
			// Строка pageview
			$pageview = "ga('send', 'pageview', gaOpt);";			
			
			// Если включена передача произвольного параметра USER ID, добавляем данные перед pageview
			if (get_option(InaUserID::OPTION_DIMENSION_ENABLED) && get_option(InaUserID::OPTION_CUSTOM_DIMENSION))
			{
				
					
				// Строка установки dimension
				$dimensionString = str_replace(
					array(
						'%DIMESION%',
						'%USER_ID%'
					),
					array(
						get_option(InaUserID::OPTION_CUSTOM_DIMENSION),
						$user_login
					),
					"ga('set', '%DIMESION%', '%USER_ID%');");
				// Подставляем строку
				$js = str_replace($pageview, $dimensionString . PHP_EOL . $pageview, $js);
			}				
			
			// Если включена передача произвольного параметра РОЛИ, добавляем данные перед pageview
			if (get_option(InaUserID::OPTION_ROLE_ENABLED) && get_option(InaUserID::OPTION_ROLE_DIMENSION))
			{
				
				// Строка установки dimension
				$dimensionString = str_replace(
					array(
						'%DIMESION%',
						'%USER_ROLE%'
					),
					array(
						get_option(InaUserID::OPTION_ROLE_DIMENSION),
						$user_role
					),
				"ga('set', '%DIMESION%', '%USER_ROLE%');");
				// Подставляем строку
				$js = str_replace($pageview, $dimensionString . PHP_EOL . $pageview, $js);
			}			
			
		}
		return $js;
	}		
}