<?php
/**
 * Модуль Яндекс.Метрика
 *
 * Реализует подключение Яндекс.Метрики 
 */
class InaMetrika extends InaModule
{
	/**#@+
	* Параметры опций модуля
	* @const
	*/
	const MENU_SLUG			= 'in-yandex-metrika.php';
	const SECTION			= 'ina_ymetrika';
	const OPTION_ENABLED	= 'ina_ymetrika_enabled';
	const OPTION_ID			= 'ina_ymetrika_id';
	const OPTION_WEBVISOR	= 'ina_ymetrika_webvisor';
	const OPTION_HASH		= 'ina_ymetrika_hash';
	const OPTION_NOINDEX	= 'ina_ymetrika_noindex';

	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Инициализируем параметры
		add_action('admin_init', 'InaMetrika::initSettings');
	}
	
	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		add_submenu_page(
			InaManager::MENU_SLUG,		 						// parent_slug - The slug name for the parent menu
			__('Yandex Metrika Options', 'inanalytics'), 		// page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('Yandex Metrika', 'inanalytics'), 				// menu_title - The text to be used for the menu
			'manage_options', 									// capability - The capability required for this menu to be displayed to the user.
			self::MENU_SLUG, 									// menu_slug - The slug name to refer to this menu by
			'InaMetrika::showOptionPage'						// function - The function to be called to output the content for this page
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
			__('Yandex Metrika Options', 'inanalytics'), 	// title -  Title of the section
			'InaMetrika::showSectionDescription',			// callback - Function that fills the section with the desired content
			self::MENU_SLUG									// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: включение модуля
		register_setting(self::MENU_SLUG, self::OPTION_ENABLED);
		add_settings_field( 
			self::OPTION_ENABLED,								// id - String for use in the 'id' attribute of tags
			__('Yandex Metrika enabled', 'inanalytics' ),		// Title of the field
			'InaMetrika::showModuleEnabled',					// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 									// page - The menu page on which to display this field
			self::SECTION 										// section - The section of the settings page
		);
		// Параметр: ID Метрики
		register_setting(self::MENU_SLUG, self::OPTION_ID);
		add_settings_field( 
			self::OPTION_ID,						// id - String for use in the 'id' attribute of tags
			__('Yandex Metrika ID', 'inanalytics'),	// Title of the field
			'InaMetrika::showMetrikaId',			// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 						// page - The menu page on which to display this field
			self::SECTION 							// section - The section of the settings page
		);
		// Параметр: Вебвизор, карта скроллинга, аналитика форм
		register_setting(self::MENU_SLUG, self::OPTION_WEBVISOR);
		add_settings_field( 
			self::OPTION_WEBVISOR,					// id - String for use in the 'id' attribute of tags
			__('Webvisor Enabled', 'inanalytics'),	// Title of the field
			'InaMetrika::showWebVisorEnabled',		// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 						// page - The menu page on which to display this field
			self::SECTION 							// section - The section of the settings page
		);		
		// Параметр: Отслеживание хеша в адресной строке браузера
		register_setting(self::MENU_SLUG, self::OPTION_HASH);
		add_settings_field( 
			self::OPTION_HASH,							// id - String for use in the 'id' attribute of tags
			__('Hash Tracking Enabled', 'inanalytics'),	// Title of the field
			'InaMetrika::showHashEnabled',				// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 							// page - The menu page on which to display this field
			self::SECTION 								// section - The section of the settings page
		);	
		// Параметр: Запрет отправки на индексацию страниц сайта
		register_setting(self::MENU_SLUG, self::OPTION_NOINDEX);
		add_settings_field( 
			self::OPTION_NOINDEX,							// id - String for use in the 'id' attribute of tags
			__('Don\'t send page stats for Yandex indexing', 'inanalytics'),	// Title of the field
			'InaMetrika::showNoIndex',				// callback - Function that fills the field with the desired inputs
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
		_e('Check this for enabling Yandex Metrika. Read more here', 'inanalytics');
	}	
	/**
	 * Показывает поле Метрика ID
	 */   
	 public static function showMetrikaId()
	{
		$name = self::OPTION_ID;
		$value = get_option($name);
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the Yandex.Metrika ID. Read more here', 'inanalytics');
	}
	/**
	 * Показывает поле Вебвизора
	 */   
	 public static function showWebVisorEnabled()
	{
		$name = self::OPTION_WEBVISOR;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e('Check this for enabling WebVisor, Click Maps and Form Analytics. Read more here', 'inanalytics');
	}	
	/**
	 * Показывает поле отслеживание хеша
	 */   
	 public static function showHashEnabled()
	{
		$name = self::OPTION_HASH;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e('Check this for enabling tracking hash in URL. Read more here', 'inanalytics');
	}	
	/**
	 * Показывает Запрет отправки на индексацию страниц сайта
	 */   
	 public static function showNoIndex()
	{
		$name = self::OPTION_NOINDEX;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e('Disallow send indexing pages. Read more here', 'inanalytics');
	}		

	/**
	 * Показывает поле Bounce Rate Category
	 */   

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
		$js = parent::getJS(INA_FOLDER . 'js/yandex-metrika.js') . PHP_EOL;
		$js = str_replace(
			array(
				'%ID%',
				'%BOUNCERATE%',
				'%WEBVISOR%',
				'%HASH%',
				'%NOINDEX%'
			), 
			array(
				get_option(self::OPTION_ID),
				get_option(InaBounceRate::OPTION_ENABLED)	? ',accurateTrackBounce:true' 	: '',
				get_option(self::OPTION_WEBVISOR)			? ',webvisor:true' 	: '',
				get_option(self::OPTION_HASH)				? ',trackHash:true'	: '',
				get_option(self::OPTION_NOINDEX)			? ',ut:"noindex"' 	: ''
			), 
			$js);
		return $js;		
	}		
	
}