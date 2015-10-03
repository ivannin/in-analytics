<?php
// Установка обработчиков
add_action('wp_head', 				'InaManager::wpHead');
add_action('wp_footer', 			'InaManager::wpFooter');
add_action('wp_enqueue_scripts',	'InaManager::enqueueScripts');

/**
 * Менеджер - Класс реализующий основной функционал плагина
 *
 */
class InaManager
{
	/**#@+
	* Константы класса
	* @const
	*/
	const SECTION					= 'in-analytics-options.php';	// ВАЖНО! ЭТО КОСЯК WP! Должно совпадать с menu_slug
	const MENU_SLUG					= 'in-analytics-options.php';
	const OPTION_HEADER_JS			= 'ina_header_js';
	const OPTION_FOOTER_JS			= 'ina_footer_js';
	const OPTION_ENQUEUE_SCRIPTS	= 'ina_enqueue_scripts';
	
	// ------------------------------ Singleton ------------------------------
	/**
	 * Экзмепляр менеджера
	 * @var SingletonTest
	 */
	protected static $_instance;

    /**
     * Создание менеджера
     * @return InaManager
     */
    public static function create() 
	{
        // проверяем актуальность экземпляра
        if (null === self::$_instance) {
            // создаем новый экземпляр
            self::$_instance = new self();
        }
        // возвращаем созданный или существующий экземпляр
        return self::$_instance;
    }

	/**
	 * Массив модулей функционала плагина
	 * @var mixed 
	 */	
	public $modules;
	
	/**
	 * JS Код для шапки
	 * @var string
	 */
	protected $headerJS;
	
	/**
	 * JS Код для подвала
	 * @var string
	 */
	protected $footerJS;
	

	/**
	 * Конструктор класса
	 */
	private function __construct()
	{
		$this->modules = array(
			'ga_basic' 		=> new InaAnalytics(),
			'metrika' 		=> new InaMetrika(),
			'bounce-rate'	=> new InaBounceRate(),
			'ga_user_id'	=> new InaUserID(),
			'ga_openstat'	=> new InaOpenstat(),
			'ga_pageview' 	=> new InaReadMarkers(),			
			'ga_forms'		=> new InaForms(),
			'ga_reading' 	=> new InaPageTracking(),
			'ga_downloads' 	=> new InaDownloads(),
			'custom_code' 	=> new InaCustomCode(),
		);
		$this->headerJS = '';
		$this->footerJS = '';
		
	}	
	
	// --------------------------- ADMIN OPTIONS ---------------------------
	/**
	 * Формирует раздел в меню администратора
	 */   
	 public static function adminMenu()
	{
		// Получаем экземпляр менеджера
		$manager = self::create();
		
		// Формируем раздел
		add_menu_page( 
			__('IN Analytics Options', 'inanalytics'),	// page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('Analytics', 'inanalytics'), 			// menu_title - The on-screen name text for the menu
			'manage_options',							// capability - The capability required for this menu to be displayed to the user. User levels are deprecated and should not be used here!
			self::MENU_SLUG,							// menu_slug - The slug name to refer to this menu by (should be unique for this menu).
			'InaManager::adminMenuOptionPage',			// function - The function that displays the page content for the menu page. if the function is a member of a class within the plugin it should be referenced as array( $this, 'function_name' )
			'dashicons-chart-line',						// icon
			85											// position - The position in the menu order this menu should appear. 
		);
		
		// Создает секцию параметров
		add_settings_section(
			self::SECTION,								// id - String for use in the 'id' attribute of tags
			__('IN Analytics Options', 'inanalytics'), 	// title -  Title of the section
			'InaManager::showSectionDescription',		// callback - Function that fills the section with the desired content
			self::MENU_SLUG								// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: Google Analytics
		register_setting(self::MENU_SLUG, InaAnalytics::OPTION_ENABLED);
		add_settings_field( 
			InaAnalytics::OPTION_ENABLED,					// id - String for use in the 'id' attribute of tags
			__('Google Analytics enabled', 'inanalytics' ),	// Title of the field
			'InaAnalytics::showModuleEnabled',				// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);
		// Параметр: Яндекс.Метрика
		register_setting(self::MENU_SLUG, InaMetrika::OPTION_ENABLED);
		add_settings_field( 
			InaMetrika::OPTION_ENABLED,						// id - String for use in the 'id' attribute of tags
			__('Yandex Metrika enabled', 'inanalytics' ),	// Title of the field
			'InaMetrika::showModuleEnabled',				// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);
		// Параметр: Точный показатель отказов
		register_setting(self::MENU_SLUG, InaBounceRate::OPTION_ENABLED);
		add_settings_field( 
			InaBounceRate::OPTION_ENABLED,						// id - String for use in the 'id' attribute of tags
			__('Accurate Bounce Rate enabled', 'inanalytics' ),	// Title of the field
			'InaBounceRate::showModuleEnabled',					// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 									// page - The menu page on which to display this field
			self::SECTION 										// section - The section of the settings page
		);	
		// Параметр: Отслеживание User ID
		register_setting(self::MENU_SLUG, InaUserID::OPTION_ENABLED);
		add_settings_field( 
			InaUserID::OPTION_ENABLED,							// id - String for use in the 'id' attribute of tags
			__('User ID tracking enabled', 'inanalytics' ),		// Title of the field
			'InaUserID::showModuleEnabled',						// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 									// page - The menu page on which to display this field
			self::SECTION 										// section - The section of the settings page
		);
		// Параметр: Парсер Openstat
		register_setting(self::MENU_SLUG, InaOpenstat::OPTION_ENABLED);
		add_settings_field( 
			InaOpenstat::OPTION_ENABLED,						// id - String for use in the 'id' attribute of tags
			__('Openstat tag tracking enabled', 'inanalytics' ),// Title of the field
			'InaOpenstat::showModuleEnabled',					// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 									// page - The menu page on which to display this field
			self::SECTION 										// section - The section of the settings page
		);
		// Параметр: Маркеры чтения
		register_setting(self::MENU_SLUG, InaReadMarkers::OPTION_ENABLED);
		add_settings_field( 
			InaReadMarkers::OPTION_ENABLED,					// id - String for use in the 'id' attribute of tags
			__('Reading Tracking enabled', 'inanalytics'),	// Title of the field
			'InaReadMarkers::showModuleEnabled',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);		
		// Параметр: Интеграция с CF7
		register_setting(self::MENU_SLUG, InaForms::OPTION_CF7_ENABLED);
		add_settings_field( 
			InaForms::OPTION_CF7_ENABLED,		// id - String for use in the 'id' attribute of tags
			__('CF7 Plugin Intergation enabled', 'inanalytics'),	// Title of the field
			'InaForms::showCF7Enabled',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);
		// Параметр: Интеграция с GravityForms
		register_setting(self::MENU_SLUG, InaForms::OPTION_CRAVITY_ENABLED);
		add_settings_field( 
			InaForms::OPTION_CRAVITY_ENABLED,	// id - String for use in the 'id' attribute of tags
			__('Gravity Forms Plugin Intergation enabled', 'inanalytics'),	// Title of the field
			'InaForms::showGravityEnabled',		// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 					// page - The menu page on which to display this field
			self::SECTION 						// section - The section of the settings page
		);		
		// Параметр: включение отслеживания скачиваний
		register_setting(self::MENU_SLUG, InaDownloads::OPTION_DOWNLOADS_ENABLED);
		add_settings_field( 
			InaDownloads::OPTION_DOWNLOADS_ENABLED,			// id - String for use in the 'id' attribute of tags
			__('Downloads Tracking enabled', 'inanalytics'),// Title of the field
			'InaDownloads::showDownloadsEnabled',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);
		
		// Параметр: включение отслеживания исходящих ссылок
		register_setting(self::MENU_SLUG, InaDownloads::OPTION_OUTBOUND_LINKS_ENABLED);
		add_settings_field( 
			InaDownloads::OPTION_OUTBOUND_LINKS_ENABLED,	// id - String for use in the 'id' attribute of tags
			__('Outbound Links Tracking enabled', 'inanalytics'),	// Title of the field
			'InaDownloads::showLinksEnabled',				// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);	

		// Параметр: произвольный код
		register_setting(self::MENU_SLUG, InaCustomCode::OPTION_ENABLED);
		add_settings_field( 
			InaCustomCode::OPTION_ENABLED,				// id - String for use in the 'id' attribute of tags
			__('Custom Code enabled', 'inanalytics'),	// Title of the field
			'InaCustomCode::showModuleEnabled',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 							// page - The menu page on which to display this field
			self::SECTION 								// section - The section of the settings page
		);	
		

		
		// Формируем подстраницы
		foreach($manager->modules as $module)
			if ($module->isEnabled())
				$module->adminMenu();
		
		// Формируем опции для runtime режима
		update_option(self::OPTION_HEADER_JS, 		$manager->getHeaderJS());
		update_option(self::OPTION_FOOTER_JS, 		$manager->getFooterJS());
		update_option(self::OPTION_ENQUEUE_SCRIPTS, InaModule::$jsScripts);
	}	
	
	/**
	 * Формирует страницу в меню администратора
	 */   
	 public static function adminMenuOptionPage()
	{
		echo '<form action="options.php" method="post">';
		settings_fields(self::SECTION);
		do_settings_sections(self::MENU_SLUG);
		submit_button();
		echo '</form>';
		
		if (WP_DEBUG)
		{
			echo '<h2>', __('Debug Info', 'inanalytics'), '</h2>' . PHP_EOL;
			echo '<p>', __('To hide this section open wp-config.php, find string "define(\'WP_DEBUG\', true);" and change "true" to "false"', 'inanalytics'), '</p>' . PHP_EOL;
			echo '<h3>', __('Header JavaScript', 'inanalytics'), '</h3>' . PHP_EOL;
			echo '<pre>', htmlspecialchars(get_option(self::OPTION_HEADER_JS)), '</pre>' . PHP_EOL;
			echo '<h3>', __('Footer JavaScript', 'inanalytics'), '</h3>' . PHP_EOL;
			echo '<pre>', htmlspecialchars(get_option(self::OPTION_FOOTER_JS)), '</pre>' . PHP_EOL;
			echo '<h3>', __('Enqueue JavaScript', 'inanalytics'), '</h3>' . PHP_EOL;
			echo '<pre>', htmlspecialchars(var_export(get_option(self::OPTION_ENQUEUE_SCRIPTS)), true), '</pre>' . PHP_EOL;
		}
	}
	
	/**
	 * Формирует описание в меню администратора
	 */   
	public static function showSectionDescription()
	{
		echo 'Select features which you need, check it, press Save button and specify settings for each feature on pages below.';
	}	
	
	
	
	/**
	 * Возвращает код шапки
	 */   
	public function getHeaderJS()
	{
		if (empty($this->headerJS) && empty($this->footerJS))
			$this->readModules();
		return $this->headerJS;
	}	
	/**
	 * Возвращает код подвала
	 */   
	public function getFooterJS()
	{
		if (empty($this->headerJS) && empty($this->footerJS))
			$this->readModules();
		return $this->footerJS;
	}
	
	/**
	 * Метод собирает код для шапки и подвала, а также внешние скрипты
	 */   
	protected function readModules()
	{
		$this->headerJS = '';
		$this->footerJS = '';		
		foreach($this->modules as $module)
		{
			if ($module->isEnabled())
			{
				$this->headerJS .= $module->getHeaderJS();
				$this->footerJS .= $module->getFooterJS();
				$module->registerScripts();
			}
		}
	}
	
	
	// ------------------------------ RUNTIME ------------------------------
	
	/**#@+
	* Названия фильтров и действий
	* @const
	*/
	const HOOK_FILTER_HEADER_JS		= 'ina_header_js';
	const HOOK_FILTER_FOOTER_JS		= 'ina_footer_js';
	const HOOK_ACTION_HEADER_BEFORE	= 'ina_header_before';
	const HOOK_ACTION_HEADER_AFTER	= 'ina_header_after';
	const HOOK_ACTION_FOOTER_BEFORE	= 'ina_footer_before';
	const HOOK_ACTION_FOOTER_AFTER	= 'ina_footer_after';
	
	/**
	 * Формирует вывод в HEAD
	 */   
	public static function wpHead()
	{
		if (WP_DEBUG) echo '<!-- IN-Analytics -->', PHP_EOL;
		$headerCode = get_option(self::OPTION_HEADER_JS);
		$headerCode = InaUserID::handleUserID($headerCode);
		$headerCode = apply_filters(self::HOOK_FILTER_HEADER_JS, $headerCode);
		// Сформированный JS
		if (!empty($headerCode))
		{
			$headerCode = '<script id="in-analytics-head">' . PHP_EOL . $headerCode . PHP_EOL . '</script>' . PHP_EOL;
		}
		// Дополнительный код
		if (get_option(InaCustomCode::OPTION_ENABLED))
		{
			$headerCode .= get_option(InaCustomCode::OPTION_HEADER) . PHP_EOL;				
		}
		// Вывод 
		do_action(self::HOOK_ACTION_HEADER_BEFORE);
		echo $headerCode;
		do_action(self::HOOK_ACTION_HEADER_AFTER);
		if (WP_DEBUG) echo '<!--/IN-Analytics -->', PHP_EOL;
	}
	
	/**
	 * Формирует вывод в FOOTER
	 */   
	public static function wpFooter()
	{
		if (WP_DEBUG) echo '<!-- IN-Analytics -->', PHP_EOL;
		$footerCode = get_option(self::OPTION_FOOTER_JS);
		$footerCode = apply_filters(self::HOOK_FILTER_FOOTER_JS, $footerCode);
		// Сформированный JS
		if (!empty($footerCode))
		{
			$footerCode = '<script id="in-analytics-footer">' . PHP_EOL . $footerCode . PHP_EOL . '</script>' . PHP_EOL;
		}
		// Дополнительный код
		if (get_option(InaCustomCode::OPTION_ENABLED))
		{
			$footerCode .= get_option(InaCustomCode::OPTION_FOOTER) . PHP_EOL;				
		}		
		// Вывод 
		do_action(self::HOOK_ACTION_FOOTER_BEFORE);
		echo $footerCode;
		do_action(self::HOOK_ACTION_FOOTER_AFTER);		
		if (WP_DEBUG) echo '<!--/IN-Analytics -->', PHP_EOL;		
	}

	
	/**
	 * Регистрируем скрипты
	 */   
	public static function enqueueScripts()
	{
		$jsScripts = get_option(self::OPTION_ENQUEUE_SCRIPTS);
		InaModule::enqueueScripts($jsScripts);
	}	
	
	
}