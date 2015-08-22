<?php
/**
 * Модуль Яндекс.Метрика
 *
 * Реализует подключение Яндекс.Метрики 
 */
class InaReadMarkers extends InaModule
{
	/**#@+
	* Параметры опций модуля
	* @const
	*/
	const MENU_SLUG			= 'in-read-markers.php';
	const SECTION			= 'ina_readmarkers';
	const OPTION_ENABLED	= 'ina_readmarkers_enabled';
	const OPTION_SELECTOR	= 'ina_readmarkers_selector';
	const OPTION_CATEGORY	= 'ina_readmarkers_category';
	const OPTION_MIDDLE		= 'ina_readmarkers_middle';
	const OPTION_END		= 'ina_readmarkers_end';


	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Инициализируем параметры
		add_action('admin_init', 'InaReadMarkers::initSettings');
	}
	
	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		add_submenu_page(
			InaManager::MENU_SLUG,		 			// parent_slug - The slug name for the parent menu
			__('Reading Tracking', 'inanalytics'), 	// page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('Reading Tracking', 'inanalytics'), 	// menu_title - The text to be used for the menu
			'manage_options', 						// capability - The capability required for this menu to be displayed to the user.
			self::MENU_SLUG, 						// menu_slug - The slug name to refer to this menu by
			'InaReadMarkers::showOptionPage'		// function - The function to be called to output the content for this page
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
			self::SECTION,									// id - String for use in the 'id' attribute of tags
			__('Reading Tracking Options', 'inanalytics'), 	// title -  Title of the section
			'InaReadMarkers::showSectionDescription',		// callback - Function that fills the section with the desired content
			self::MENU_SLUG									// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: включение модуля
		register_setting(self::MENU_SLUG, self::OPTION_ENABLED);
		add_settings_field( 
			self::OPTION_ENABLED,							// id - String for use in the 'id' attribute of tags
			__('Reading Tracking enabled', 'inanalytics'),	// Title of the field
			'InaReadMarkers::showModuleEnabled',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 								// page - The menu page on which to display this field
			self::SECTION 									// section - The section of the settings page
		);
		// Параметр: селектор статьи
		register_setting(self::MENU_SLUG, self::OPTION_SELECTOR);
		add_settings_field( 
			self::OPTION_SELECTOR,								// id - String for use in the 'id' attribute of tags
			__('Article container selector', 'inanalytics'),	// Title of the field
			'InaReadMarkers::showSelector',						// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 									// page - The menu page on which to display this field
			self::SECTION 										// section - The section of the settings page
		);		
		// Параметр: Категория
		register_setting(self::MENU_SLUG, self::OPTION_CATEGORY);
		add_settings_field( 
			self::OPTION_CATEGORY,								// id - String for use in the 'id' attribute of tags
			__('Event category', 'inanalytics'),				// Title of the field
			'InaReadMarkers::showCategory',						// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 									// page - The menu page on which to display this field
			self::SECTION 										// section - The section of the settings page
		);			
		
		// Параметр: Середина
		register_setting(self::MENU_SLUG, self::OPTION_MIDDLE);
		add_settings_field( 
			self::OPTION_MIDDLE,									// id - String for use in the 'id' attribute of tags
			__('The middle of article action mark', 'inanalytics'),	// Title of the field
			'InaReadMarkers::showMiddle',							// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 										// page - The menu page on which to display this field
			self::SECTION 											// section - The section of the settings page
		);
		
		// Параметр: Конец
		register_setting(self::MENU_SLUG, self::OPTION_END);
		add_settings_field( 
			self::OPTION_END,										// id - String for use in the 'id' attribute of tags
			__('The end of article action mark', 'inanalytics'),	// Title of the field
			'InaReadMarkers::showEnd',								// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 										// page - The menu page on which to display this field
			self::SECTION 											// section - The section of the settings page
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
		_e('Check this for enabling Yandex Metrika. Read more here', 'inanalytics');
	}

	/**
	 * Показывает поле Selector
	 */   
	public static function showSelector()
	{
		$name = self::OPTION_SELECTOR;
		$value = get_option($name, '.single article');
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the selector of article container. This uses jQuery syntax. Read more here', 'inanalytics');
	}

	/**
	 * Показывает поле категории
	 */   
	public static function showCategory()
	{
		$name = self::OPTION_CATEGORY;
		$value = get_option($name, __('Reading', 'inanalytics'));
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the category of Google Analytics events and timing.', 'inanalytics');
	}
	
	/**
	 * Показывает поле середины
	 */   
	public static function showMiddle()
	{
		$name = self::OPTION_MIDDLE;
		$value = get_option($name, __('Middle', 'inanalytics'));
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the middle of article tracking mark.  Read more here', 'inanalytics');
	}	

 	/**
	 * Показывает поле конца
	 */   
	public static function showEnd()
	{
		$name = self::OPTION_END;
		$value = get_option($name, __('End', 'inanalytics'));
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the end of article tracking mark.  Read more here', 'inanalytics');
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
	 * Метод возвращает JS код модуля для HEAD
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
		$js = parent::getJS(INA_FOLDER . 'js/reading-markers.js') . PHP_EOL;
		$js = str_replace(
			array(
				'%SELECTOR%',
				'%CATEGORY%',
				'%MIDDLE%',
				'%END%'
			), 
			array(
				get_option(self::OPTION_SELECTOR),
				get_option(self::OPTION_CATEGORY),
				get_option(self::OPTION_MIDDLE),
				get_option(self::OPTION_END)
			), 
			$js);
		return $js;	
	}

	/**
	 * Метод регистрирует скрипты для модуля
	 *
	 */   
	public function registerScripts()
	{
		$this->addJSFile('waypoints', INA_URL.'js/jquery.waypoints.min.js', '3.1.1', array('jquery'), true);
	}	
	
}