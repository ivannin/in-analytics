<?php
/**
 * Модуль отслеживания загрузок и внешних ссылок
 *
 * Реализует отслеживание загрузок файлов и перехода по внешним ссылкам 
 */
class InaDownloads extends InaModule
{
	/**#@+
	* Параметры опций модуля
	* @const
	*/
	const MENU_SLUG							= 'in-downloads.php';
	const SECTION							= 'ina_downloads';
	const OPTION_DOWNLOADS_ENABLED			= 'ina_downloads_enabled';
	const OPTION_DOWNLOADS_EXTENSIONS		= 'ina_downloads_ext';
	const OPTION_OUTBOUND_LINKS_ENABLED		= 'ina_outbound_links_enabled';
	const OPTION_OUTBOUND_LINKS_CATEGORY 	= 'ina_outbound_links_category';
	const OPTION_OUTBOUND_LINKS_ACTION 		= 'ina_outbound_links_action';

	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Инициализируем параметры
		add_action('admin_init', 'InaDownloads::initSettings');
	}
	
	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		add_submenu_page(
			InaManager::MENU_SLUG,		 				// parent_slug - The slug name for the parent menu
			__('Downloads and Outbound Links Tracking Options', 'inanalytics'), 	// page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('Downloads and Outbound Links', 'inanalytics'),	// menu_title - The text to be used for the menu
			'manage_options', 							// capability - The capability required for this menu to be displayed to the user.
			self::MENU_SLUG, 							// menu_slug - The slug name to refer to this menu by
			'InaDownloads::showOptionPage'				// function - The function to be called to output the content for this page
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
			__('Downloads and Outbound Links Tracking Options', 'inanalytics'), 	// title -  Title of the section
			'InaDownloads::showSectionDescription',	// callback - Function that fills the section with the desired content
			self::MENU_SLUG							// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: включение отслеживания скачиваний
		register_setting(self::MENU_SLUG, self::OPTION_DOWNLOADS_ENABLED);
		add_settings_field( 
			self::OPTION_DOWNLOADS_ENABLED,			// id - String for use in the 'id' attribute of tags
			__('Downloads Tracking enabled', 'inanalytics'),// Title of the field
			'InaDownloads::showDownloadsEnabled',	// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 						// page - The menu page on which to display this field
			self::SECTION 							// section - The section of the settings page
		);
		// Параметр: включение отслеживания скачиваний
		register_setting(self::MENU_SLUG, self::OPTION_DOWNLOADS_EXTENSIONS);
		add_settings_field( 
			self::OPTION_DOWNLOADS_EXTENSIONS,		// id - String for use in the 'id' attribute of tags
			__('File extensions for tracking', 'inanalytics'),// Title of the field
			'InaDownloads::showDownloadsExt',		// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 						// page - The menu page on which to display this field
			self::SECTION 							// section - The section of the settings page
		);		
		
		
		// Параметр: включение отслеживания исходящих ссылок
		register_setting(self::MENU_SLUG, self::OPTION_OUTBOUND_LINKS_ENABLED);
		add_settings_field( 
			self::OPTION_OUTBOUND_LINKS_ENABLED,	// id - String for use in the 'id' attribute of tags
			__('Outbound Links Tracking enabled', 'inanalytics'),	// Title of the field
			'InaDownloads::showLinksEnabled',		// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 						// page - The menu page on which to display this field
			self::SECTION 							// section - The section of the settings page
		);
		
		// Параметр: Категория событий исходящих ссылок
		register_setting(self::MENU_SLUG, self::OPTION_OUTBOUND_LINKS_CATEGORY);
		add_settings_field( 
			self::OPTION_OUTBOUND_LINKS_CATEGORY,		// id - String for use in the 'id' attribute of tags
			__('Outbound Link Event Category', 'inanalytics'),	// Title of the field
			'InaDownloads::showOutboundLinksCategory',	// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 							// page - The menu page on which to display this field
			self::SECTION 								// section - The section of the settings page
		);		
		
		// Параметр: Действие события исходящих ссылок
		register_setting(self::MENU_SLUG, self::OPTION_OUTBOUND_LINKS_ACTION);
		add_settings_field( 
			self::OPTION_OUTBOUND_LINKS_ACTION,			// id - String for use in the 'id' attribute of tags
			__('Outbound Link Event Action', 'inanalytics'),	// Title of the field
			'InaDownloads::showOutboundLinksAction',	// callback - Function that fills the field with the desired inputs
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
	 * Показывает поле доступности скачиваний
	 */   
	 public static function showDownloadsEnabled()
	{
		$name = self::OPTION_DOWNLOADS_ENABLED;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e('Check this for enabling files downloads tracking. Read more here', 'inanalytics');
	}
	
	/**
	 * Показывает поле расширений файлов
	 */   
	 public static function showDownloadsExt()
	{
		$name = self::OPTION_DOWNLOADS_EXTENSIONS;
		$value = get_option($name, "pdf\ndoc\ndocx\nxsl\nxslx\nzip\nrar\nexe");
		_e('Specify file extensions for tracking downloading, one value per line. Read more here', 'inanalytics');	
		echo "<br/><textarea name='{$name}' cols='30' rows='10'>{$value}</textarea>";
	}	
	
	/**
	 * Показывает поле включения отслеживания внешних ссылок
	 */   
	public static function showLinksEnabled()
	{
		$name = self::OPTION_OUTBOUND_LINKS_ENABLED;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e('Check this for enabling outbound links tracking. Read more here', 'inanalytics');
	}	

	/**
	 * Показывает поле категории события
	 */   
	public static function showOutboundLinksCategory()
	{
		$name = self::OPTION_OUTBOUND_LINKS_CATEGORY;
		$value = get_option($name, __('Outbound links', 'inanalytics'));
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the category for event occurs when outbound link is clicking. Read more here', 'inanalytics');
	}
	
	/**
	 * Показывает поле действия события
	 */   
	public static function showOutboundLinksAction()
	{
		$name = self::OPTION_OUTBOUND_LINKS_ACTION;
		$value = get_option($name, __('Click', 'inanalytics'));
		echo "<input type='text' name='{$name}' value='{$value}'>&nbsp;&nbsp;";
		_e('Specify the action for event occurs when outbound link is clicking. Read more here', 'inanalytics');
	}	

	/**
	 * Метод возвращает доступность модуля
     * @return bool	 
	 */   
	public function isEnabled()
	{
		return (bool) 
			get_option(self::OPTION_DOWNLOADS_ENABLED) || 
			get_option(self::OPTION_OUTBOUND_LINKS_ENABLED);
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
		$js = parent::getJS(INA_FOLDER . 'js/links.js') . PHP_EOL;
		$extList = explode("\n", get_option(self::OPTION_DOWNLOADS_EXTENSIONS));
		for ($i=0; $i < count($extList); $i++)
			$extList[$i] = trim($extList[$i]);
		$extStr = implode('|', $extList);
		$js = str_replace(
			array(
				'%DOWNLOAD_ENABLED%',
				'%OUTBOUND_LINKS_ENABLED%',
				'%EXTENSIONS%',
				'%CATEGORY%',
				'%ACTION%'
			), 
			array(
				get_option(self::OPTION_DOWNLOADS_ENABLED)		? '1' : '0',
				get_option(self::OPTION_OUTBOUND_LINKS_ENABLED)	? '1' : '0',
				$extStr,
				get_option(self::OPTION_OUTBOUND_LINKS_CATEGORY),
				get_option(self::OPTION_OUTBOUND_LINKS_ACTION)
			), 
			$js);
		return $js;	
	}
}