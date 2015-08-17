<?php
// Установка обработчиков
add_action('wp_head', 'InaManager::wpHead');
add_action('wp_footer', 'InaManager::wpFooter');

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
	protected static $headerJS;
	
	/**
	 * JS Код для подвала
	 * @var string
	 */
	protected static $footerJS;		
	

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
			'ga_forms'		=> new InaForms(),
			'ga_pageview' 	=> new InaPageTracking(),
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
			InaManager::MENU_SLUG,						// menu_slug - The slug name to refer to this menu by (should be unique for this menu).
			'InaManager::adminMenuOptionPage',			// function - The function that displays the page content for the menu page. if the function is a member of a class within the plugin it should be referenced as array( $this, 'function_name' )
			'dashicons-chart-line',						// icon
			85											// position - The position in the menu order this menu should appear. 
		);
		// Формируем подстраницы
		foreach($manager->modules as $module)
			$module->adminMenu();
		
		// Формируем опции для runtime режима
		update_option(self::OPTION_HEADER_JS, $manager->getHeaderJS());
		update_option(self::OPTION_FOOTER_JS, $manager->getFooterJS());
	}	
	
	/**
	 * Формирует страницу в меню администратора
	 */   
	 public static function adminMenuOptionPage()
	{
		echo '<h2>', __('IN Analytics Options', 'inanalytics'), '</h2>' . PHP_EOL;
		
		if (WP_DEBUG)
		{
			echo '<h2>', __('Debug Info', 'inanalytics'), '</h2>' . PHP_EOL;
			echo '<h3>', __('Header JavaScript', 'inanalytics'), '</h3>' . PHP_EOL;
			echo '<pre>', htmlspecialchars(get_option(self::OPTION_HEADER_JS)), '</pre>' . PHP_EOL;
			echo '<h3>', __('Footer JavaScript', 'inanalytics'), '</h3>' . PHP_EOL;
			echo '<pre>', htmlspecialchars(get_option(self::OPTION_FOOTER_JS)), '</pre>' . PHP_EOL;
		
		}
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
	 * Метод собирает код для шапки и подвала
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
		$js = get_option(self::OPTION_HEADER_JS);
		$js = InaUserID::handleUserID($js);
		$js = apply_filters(self::HOOK_FILTER_HEADER_JS, $js);
		if (!empty($js))
			$js = '<script id="in-analytics-head">' . PHP_EOL . $js . PHP_EOL . '</script>' . PHP_EOL;
		// Вывод 
		do_action(self::HOOK_ACTION_HEADER_BEFORE);
		echo $js;
		do_action(self::HOOK_ACTION_HEADER_AFTER);
		if (WP_DEBUG) echo '<!--/IN-Analytics -->', PHP_EOL;
	}
	
	/**
	 * Формирует вывод в FOOTER
	 */   
	public static function wpFooter()
	{
		if (WP_DEBUG) echo '<!-- IN-Analytics -->', PHP_EOL;
		$js = get_option(self::OPTION_FOOTER_JS);
		$js = apply_filters(self::HOOK_FILTER_FOOTER_JS, $js);
		if (!empty($js))
			$js = '<script id="in-analytics-footer">' . PHP_EOL . $js . PHP_EOL . '</script>' . PHP_EOL;
		// Вывод 
		do_action(self::HOOK_ACTION_FOOTER_BEFORE);
		echo $js;
		do_action(self::HOOK_ACTION_FOOTER_AFTER);		
		if (WP_DEBUG) echo '<!--/IN-Analytics -->', PHP_EOL;		
	}

}