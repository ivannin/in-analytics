<?php
/**
 * Загрузчик модулей и настроек
 */
class INA_ModuleManager
{
    /**
     * @const           Массив параметров модулей
     */    
    const SETTINGS = 'ina_settings';
    
    /**
     * @var mixed		Массив параметров модулей
     */
    protected $settings;
	
    /**
     * @var string 		Папка плагина
     */
    public $baseDir;	
	
    /**
     * @var string		URL плагина     
     */
    public $baseURL;	
    
    /**
     * @var mixed $modules      Массив загруженных модулей модулей
     */
    protected $modules;     
    
    /**
     * Конструктор класса
     * @param string $baseDir   Папка плагина
     */    
    function __construct($baseDir='', $baseURL='') 
    {
		// Папка и URL плагина
		$this->baseDir = $baseDir;
		$this->baseURL = $baseURL;
		
        // Загружаем настройки
        $this->settings = get_option( self::SETTINGS, array() );
        
        // Загружаем модули
        $this->loadModiles();
		
		// Ставим обработчик на инициализацию для runtime
		add_action( 'wp_head', array( $this, 'writeHead' ) );
		add_action( 'wp_footer', array( $this, 'writeFoot' ) );
		
		// Формируем админ-страницы
		if ( is_admin() )
		{
			add_action( 'admin_enqueue_scripts', array( $this, 'adminScriptsAndCSS' ) );
			add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );
			add_action( 'admin_init', array( $this, 'settingsInit' ) );
		}
    }
        
    /* ---------------------- Параметры и настройки ----------------------
    /**
     * Возвращает параметр настройки
     * @param string $moduleName    Имя модуля
     * @param string $optionName    Имя параметра
     * @return mixed
     */    
    public function getOption($moduleName, $optionName)
    {
        // Проверяем раздел указанного модуля
        if ( ! array_key_exists( $moduleName, $this->settings ) )
            return false;
        
        // Проверяем наличие указанной настройки
        if ( ! array_key_exists( $optionName, $this->settings[$moduleName] ) )
            return false;
        
        // Возвращаем настройку
        return $this->settings[$moduleName][$optionName];
    }
    
    /**
     * Устанавливает параметр настройки
     * @param string $moduleName    Имя модуля
     * @param string $optionName    Имя параметра
     * @param mixed  $value         Значение параметра
     */    
    public function setOption($moduleName, $optionName, $value)
    {
        $this->settings[$moduleName][$optionName] = $value;
    }    
    
    /**
     * Сохраняет все настройки
     * при сохранении настроект autoload ставится в false, чтобы при выключенном плагине не грузить память
     */    
    public function saveOptions()
    {
        // Если настройки уже есть...
        if ( get_option( self::SETTINGS ) ) 
        {
            // просто их апдейтим
            update_option( self::SETTINGS, $this->settings);
        } 
        else 
        {
            // Настроект нет, создаем без автолоада
            add_option( self::SETTINGS, $this->settings, ' ', 'no' );
        }        
    }    
    
    /* ---------------------- Загрузка модулей ----------------------
    /**
     * Загружает файлы модулей и создает экземпляры классов
     */    
    protected function loadModiles()
    {
        // Массив модулей
        $this->modules = array();
        
        // Читаем все файлы в папке модулей
        if ( $handle = opendir( $this->baseDir . 'modules' ) ) 
        {
            while ( false !== ($entry = readdir( $handle ) ) ) 
            {
                // Если это не ссылка на папки и имя не начинается на "-"
                if ($entry != "." && $entry != ".." && $entry[0] != '-')
                {
                    $file = $this->baseDir . 'modules' . '/' . $entry;
                    $class = str_replace( '.php', '', $entry );
                    
                    // Пытаемся загрузить файл и инициализировать экземпляр класса
                    try
                    {
                        include( $file );
                        $this->modules[$class] = new $class( $this );
                        
                    }
                    catch (Exception $e)
                    {
                        // Ошибку пишем в лог
                        error_log( 'INA_ModuleManager::loadModiles LOAD: ' . $e->getMessage() );
                    }
                }
            }
            closedir($handle);
			
			// Сортируем модули в порядке их свойства menuOrder
			try
			{
				usort($this->modules, array( $this, 'moduleCompare' ) );
			}
			catch (Exception $e)
			{
				// Ошибку пишем в лог
				error_log( 'INA_ModuleManager::loadModiles REORDER: ' . $e->getMessage() );
			}			
        }       
    }

    /**
     * Сравнивает два модуля на основе свойства menuOrder
     * @param INA_ModuleBase $objA    Первый модуль
     * @param INA_ModuleBase $objB    Второй модуль
     */    
    protected function moduleCompare($objA, $objB)
    {
		return $objA->menuOrder < $objB->menuOrder;
    }
	
    /* ---------------------- Обработчики событий ----------------------
	/**
     * Код IN-Analytics в шапку
     */    
    public function writeHead()
    {
		$output = ( WP_DEBUG ) ? '<!-- IN-Analytics -->' : '';
		// Собираем код
		$js = '';
		$other = '';
		foreach ($this->modules as $module)
		{
			$js 	.= $module->getJSHead();
			$other 	.= $module->getOtherHead();
		}
		if ( ! empty( $js ) )
			$js = PHP_EOL . '<script>' . PHP_EOL . $js . '</script>' . PHP_EOL;
		$js = apply_filters( 'ina_head_js', $js );
		
		if ( ! empty( $other ) )
			$other = PHP_EOL . $other . PHP_EOL;
		$other = apply_filters( 'ina_head_other', $other );
	
		$output .= $js . $other;
		$output .= ( WP_DEBUG ) ? '<!--/IN-Analytics -->' : '';
		echo $output;
	}
	
	/**
     * Код IN-Analytics в подвал
     */    
    public function writeFoot()
    {
		$output = ( WP_DEBUG ) ? '<!-- IN-Analytics -->'  . PHP_EOL : '';
		// Собираем код
		$js = '';
		$other = '';
		foreach ($this->modules as $module)
		{
			$js 	.= $module->getJSHead();
			$other 	.= $module->getOtherHead();
		}
		if ( ! empty( $js ) )
			$js = PHP_EOL . '<script>' . PHP_EOL . $js . '</script>' . PHP_EOL;
		$js = apply_filters( 'ina_foot_js', $js );
		
		if ( ! empty( $other ) )
			$other = PHP_EOL . $other . PHP_EOL;
		$other = apply_filters( 'ina_foot_other', $other );
		
		$output .= $js . $other;
		$output .= ( WP_DEBUG ) ? '<!--/IN-Analytics -->' . PHP_EOL : '';
		echo $output;	
	}	

    /* ---------------------- Админ страницы ---------------------- */
	/**
     * Загрузка скриптов и CSS в админ страницы
     */    
    public function adminScriptsAndCSS()
    {
        wp_register_style( 'ina_styles', $this->baseURL . 'css/admin-page.css', false, '1.0.0' );
        wp_enqueue_style( 'ina_styles' );	
	}
	
	
	/**
     * Добавляет меню в админку
     */    
    public function addAdminMenu()
    {
		add_menu_page( 
			/* translators: page_title - The text to be displayed in the title tags of the page when the menu is selected and the h2 of settings page */
			__( 'IN-Analytics Settings', INA_TEXT_DOMAIN),
			/* translators: menu_title - The text to be used for the menu */
			__( 'IN-Analytics', INA_TEXT_DOMAIN),
			'manage_options', 							// The capability required for this menu to be displayed to the user
			'in-analytics',								// The slug name to refer to this menu by (should be unique for this menu)
			array( $this, 'showOptionPage'),			// The function to be called to output the content for this page
			'data:image/svg+xml;base64,' . self::SVG, 	// SVG logo
			80);										// Position under Settings);
	}

		
	/**
     * Вывод страницы настроек
     */ 
	public function showOptionPage() { ?>
	<form action="<?php echo $_SERVER['REQUEST_URI']?>" method="post" class="ina-settings">
			<h2><?php esc_html_e( 'IN-Analytics Settings', INA_TEXT_DOMAIN)?></h2>
			<p><?php esc_html_e( 'This is the list of available modules. Check the required functions for activation plugin modules. Then you will see the settings pages for each activated module.', INA_TEXT_DOMAIN)?></p>
			<fieldset>
				<legend><?php esc_html_e( 'Modules', INA_TEXT_DOMAIN)?></legend>
				<?php foreach ($this->modules as $moduleId => $module): ?>
					<div class="checkbox-field">
						<input type="checkbox" id="<?php echo $moduleId ?>" name="<?php echo $moduleId ?>" <?php echo ( $module->isEnabled() ) ? 'checked' : '' ?> />
						<label for="<?php echo $moduleId ?>"><?php echo $module->title ?></label>
						<p><?php echo $module->description ?></p>
					</div>
				<?php endforeach ?>
			</fieldset>
			<?php submit_button() ?>
		</form>
	<?php		
	}
	
	/**
	 * @const OPTION_GROUP 		A settings group name. Must exist prior to the register_setting call. This must match the group name in settings_fields()
	 */
	const OPTION_GROUP = 'ina_option_group';
	
	/**
     * Формирует админ-страницы для всего плагина и для выбранных модулей
     */    
    public function settingsInit()
    {
		// См. http://wp-kama.ru/function/register_setting почему параметры должны быть равны
		register_setting( 
			self::OPTION_GROUP, 						// Название группы, к которой будет принадлежать опция. Это название должно совпадать с названием группы в функции settings_fields()
			self::OPTION_GROUP, 						// Название опции, которая будет сохраняться в БД.
			array( $this, 'sanitizeOptionsCallback') );	// Название функции обратного вызова, которая будет обрабатывать значение опции перед сохранением.
		
		add_settings_section(
			'ina_pluginPage_section',
			/* translators: menu_title - The text to be used for the menu */
			__( 'Your section description', 'in-analytics' ), 
			'ina_settings_section_callback', 
			self::OPTION_GROUP
		);
		
		add_settings_field( 
			'ina_checkbox_field_0', 
			__( 'Settings field description', 'in-analytics' ), 
			'ina_checkbox_field_0_render', 
			self::OPTION_GROUP, 
			'ina_pluginPage_section' 
		);	
	}
	
	/**
     * Callback функция вызывается при сохранении опций
	 * Важно! Функция получит один параметр - значение опции. 
	 * Значение которое указанная функция вернет, будет записано в опцию.
     */    
    public function sanitizeOptionsCallback( $options )
    {
		
	}
	
	
	
	
	
	
    /**
     * @const           Base64 кодировка SVG логотипа
     */    
    const SVG = 'PD94bWwgdmVyc2lvbj0nMS4wJyBlbmNvZGluZz0nVVRGLTgnPz4NCjwhRE9DVFlQRSBzdmcgUFVCTElDICItLy9XM0MvL0RURCBTVkcgMS4xLy9FTiIgImh0dHA6Ly93d3cudzMub3JnL0dyYXBoaWNzL1NWRy8xLjEvRFREL3N2ZzExLmR0ZCI+DQo8c3ZnIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyIgeG1sbnM6eGxpbms9Imh0dHA6Ly93d3cudzMub3JnLzE5OTkveGxpbmsiIGlkPSJMb2dvdHlwZSIgd2lkdGg9IjQwMDBweCIgeT0iMHB4IiB2ZXJzaW9uPSIxLjEiIHg9IjBweCIgdmlld0JveD0iMCAwIDQwMDAgMzMyNiIgaGVpZ2h0PSIzMzI2cHgiPjwhLS1DcmVhdG9yOiBMb2dhc3Rlci0tPjxkZWZzPjxmb250IGlkPSJKb2tlIiBob3Jpei1hZHYteD0iNjI4Ij48Zm9udC1mYWNlIGZvbnQtZmFtaWx5PSJKb2tlIiB1bml0cy1wZXItZW09IjEwMDAiIHBhbm9zZS0xPSI0IDAgMCAwIDAgMCAwIDAgMCAwIiBhc2NlbnQ9IjcwMCIgZGVzY2VudD0iMCIgYWxwaGFiZXRpYz0iMCIvPg0KPG1pc3NpbmctZ2x5cGggaG9yaXotYWR2LXg9IjY2NiIgZD0iTTQwIDBWNzc0SDYyNlYwSDQwWk04MCA0MEg1ODZWNzM0SDgwVjQwWiIvPg0KPGdseXBoIHVuaWNvZGU9IiAiIGdseXBoLW5hbWU9InNwYWNlIiBob3Jpei1hZHYteD0iMjc4Ii8+DQo8aGtlcm4gZzE9InF1b3RlZGJsIiBnMj0iQSIgaz0iOTIiLz4NCjxoa2VybiBnMT0icXVvdGVkYmwiIGcyPSJhIiBrPSI5MiIvPg0KPGhrZXJuIGcxPSJxdW90ZWRibCIgZzI9ImMiIGs9Ijc5Ii8+DQo8aGtlcm4gZzE9InF1b3RlZGJsIiBnMj0iZCIgaz0iNzgiLz4NCjxoa2VybiBnMT0icXVvdGVkYmwiIGcyPSJvIiBrPSI4MiIvPg0KPGhrZXJuIGcxPSJxdW90ZWRibCIgZzI9InEiIGs9IjgyIi8+DQo8aGtlcm4gZzE9InF1b3RlZGJsIiBnMj0icyIgaz0iNzAiLz4NCjxoa2VybiBnMT0icXVvdGVzaW5nbGUiIGcyPSJBIiBrPSI5MiIvPg0KPGhrZXJuIGcxPSJxdW90ZXNpbmdsZSIgZzI9ImEiIGs9IjkyIi8+DQo8aGtlcm4gZzE9InF1b3Rlc2luZ2xlIiBnMj0iYyIgaz0iNzkiLz4NCjxoa2VybiBnMT0icXVvdGVzaW5nbGUiIGcyPSJkIiBrPSI3OCIvPg0KPGhrZXJuIGcxPSJxdW90ZXNpbmdsZSIgZzI9ImUiIGs9Ijc4Ii8+DQo8aGtlcm4gZzE9InF1b3Rlc2luZ2xlIiBnMj0ibyIgaz0iODIiLz4NCjxoa2VybiBnMT0icXVvdGVzaW5nbGUiIGcyPSJxIiBrPSI4MiIvPg0KPGhrZXJuIGcxPSJxdW90ZXNpbmdsZSIgZzI9InMiIGs9IjcwIi8+DQo8aGtlcm4gZzE9InBhcmVubGVmdCIgZzI9IkMiIGs9Ijg2Ii8+DQo8aGtlcm4gZzE9InBhcmVubGVmdCIgZzI9Ik8iIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJwYXJlbmxlZnQiIGcyPSJRIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0icGFyZW5sZWZ0IiBnMj0iUyIgaz0iODciLz4NCjxoa2VybiBnMT0icGFyZW5sZWZ0IiBnMj0iYSIgaz0iNzkiLz4NCjxoa2VybiBnMT0icGFyZW5sZWZ0IiBnMj0iYyIgaz0iODYiLz4NCjxoa2VybiBnMT0icGFyZW5sZWZ0IiBnMj0iZSIgaz0iNzkiLz4NCjxoa2VybiBnMT0icGFyZW5sZWZ0IiBnMj0ibyIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9InBhcmVubGVmdCIgZzI9InEiIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJwYXJlbnJpZ2h0IiBnMj0icGFyZW5sZWZ0IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwbHVzIiBnMj0iQyIgaz0iMzAiLz4NCjxoa2VybiBnMT0icGx1cyIgZzI9IkciIGs9IjMwIi8+DQo8aGtlcm4gZzE9ImNvbW1hIiBnMj0iQyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iY29tbWEiIGcyPSJGIiBrPSI0OCIvPg0KPGhrZXJuIGcxPSJjb21tYSIgZzI9IkoiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImNvbW1hIiBnMj0iTyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iY29tbWEiIGcyPSJQIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJjb21tYSIgZzI9IlIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImNvbW1hIiBnMj0iVCIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9ImNvbW1hIiBnMj0iViIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9ImNvbW1hIiBnMj0iZiIgaz0iNDgiLz4NCjxoa2VybiBnMT0iY29tbWEiIGcyPSJnIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJjb21tYSIgZzI9ImsiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImNvbW1hIiBnMj0icSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iY29tbWEiIGcyPSJzIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJjb21tYSIgZzI9InQiIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJoeXBoZW4iIGcyPSJGIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJoeXBoZW4iIGcyPSJMIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJoeXBoZW4iIGcyPSJNIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJoeXBoZW4iIGcyPSJQIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJoeXBoZW4iIGcyPSJWIiBrPSI4MCIvPg0KPGhrZXJuIGcxPSJoeXBoZW4iIGcyPSJXIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJoeXBoZW4iIGcyPSJZIiBrPSI4NyIvPg0KPGhrZXJuIGcxPSJwZXJpb2QiIGcyPSJDIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwZXJpb2QiIGcyPSJEIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwZXJpb2QiIGcyPSJGIiBrPSI0OCIvPg0KPGhrZXJuIGcxPSJwZXJpb2QiIGcyPSJKIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwZXJpb2QiIGcyPSJPIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwZXJpb2QiIGcyPSJRIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwZXJpb2QiIGcyPSJTIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwZXJpb2QiIGcyPSJUIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0icGVyaW9kIiBnMj0iVSIgaz0iMjkiLz4NCjxoa2VybiBnMT0icGVyaW9kIiBnMj0iVyIgaz0iMjkiLz4NCjxoa2VybiBnMT0icGVyaW9kIiBnMj0iWSIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9InBlcmlvZCIgZzI9ImIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InBlcmlvZCIgZzI9ImQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InBlcmlvZCIgZzI9ImUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InBlcmlvZCIgZzI9ImciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InBlcmlvZCIgZzI9ImoiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InBlcmlvZCIgZzI9Im8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InBlcmlvZCIgZzI9InAiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InBlcmlvZCIgZzI9InIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InBlcmlvZCIgZzI9InYiIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJwZXJpb2QiIGcyPSJ3IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwZXJpb2QiIGcyPSJ5IiBrPSIxMDAiLz4NCjxoa2VybiBnMT0ic2xhc2giIGcyPSJEIiBrPSI4NyIvPg0KPGhrZXJuIGcxPSJjb2xvbiIgZzI9IkYiIGs9IjQ4Ii8+DQo8aGtlcm4gZzE9ImNvbG9uIiBnMj0iUCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iY29sb24iIGcyPSJUIiBrPSI0MSIvPg0KPGhrZXJuIGcxPSJjb2xvbiIgZzI9IlYiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InNlbWljb2xvbiIgZzI9IlQiIGs9IjQxIi8+DQo8aGtlcm4gZzE9InNlbWljb2xvbiIgZzI9IlciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InNlbWljb2xvbiIgZzI9IlkiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkEiIGcyPSJxdW90ZXNpbmdsZSIgaz0iNzIiLz4NCjxoa2VybiBnMT0iQSIgZzI9IkEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkEiIGcyPSJCIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJBIiBnMj0iQyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQSIgZzI9IkQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkEiIGcyPSJGIiBrPSIzMyIvPg0KPGhrZXJuIGcxPSJBIiBnMj0iRyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQSIgZzI9IkoiIGs9IjQzIi8+DQo8aGtlcm4gZzE9IkEiIGcyPSJPIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJBIiBnMj0iUCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQSIgZzI9IlEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkEiIGcyPSJUIiBrPSI3MiIvPg0KPGhrZXJuIGcxPSJBIiBnMj0iVSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQSIgZzI9IlYiIGs9Ijc3Ii8+DQo8aGtlcm4gZzE9IkEiIGcyPSJXIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJBIiBnMj0iWSIgaz0iODQiLz4NCjxoa2VybiBnMT0iQSIgZzI9ImMiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkEiIGcyPSJkIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJBIiBnMj0iZSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQSIgZzI9ImciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkEiIGcyPSJvIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJBIiBnMj0icSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQSIgZzI9InQiIGs9IjcyIi8+DQo8aGtlcm4gZzE9IkEiIGcyPSJ1IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJBIiBnMj0idiIgaz0iNzciLz4NCjxoa2VybiBnMT0iQSIgZzI9InciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkEiIGcyPSJ5IiBrPSI4NCIvPg0KPGhrZXJuIGcxPSJCIiBnMj0iY29tbWEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkIiIGcyPSJwZXJpb2QiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkIiIGcyPSJBIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJCIiBnMj0iRCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQiIgZzI9IkUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkIiIGcyPSJJIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJCIiBnMj0iTCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQiIgZzI9Ik8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkIiIGcyPSJQIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJCIiBnMj0iUiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQiIgZzI9IlUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkIiIGcyPSJWIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0iQiIgZzI9IlciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkIiIGcyPSJZIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0iQiIgZzI9ImIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkIiIGcyPSJoIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJCIiBnMj0iaSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQiIgZzI9ImoiIGs9IjUxIi8+DQo8aGtlcm4gZzE9IkIiIGcyPSJrIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJCIiBnMj0ibCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQiIgZzI9InUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkIiIGcyPSJ5IiBrPSIxMDAiLz4NCjxoa2VybiBnMT0iQyIgZzI9InBhcmVucmlnaHQiIGs9Ijc4Ii8+DQo8aGtlcm4gZzE9IkMiIGcyPSJwbHVzIiBrPSI0NiIvPg0KPGhrZXJuIGcxPSJDIiBnMj0iY29tbWEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkMiIGcyPSJoeXBoZW4iIGs9IjQ2Ii8+DQo8aGtlcm4gZzE9IkMiIGcyPSJwZXJpb2QiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkMiIGcyPSJBIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJDIiBnMj0iRSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQyIgZzI9IkgiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkMiIGcyPSJJIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJDIiBnMj0iSyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQyIgZzI9IkwiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkMiIGcyPSJOIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJDIiBnMj0iTyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQyIgZzI9IlIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkMiIGcyPSJVIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJDIiBnMj0iViIgaz0iNjgiLz4NCjxoa2VybiBnMT0iQyIgZzI9IlciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkMiIGcyPSJZIiBrPSI3MiIvPg0KPGhrZXJuIGcxPSJDIiBnMj0iYSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iQyIgZzI9InIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkMiIGcyPSJ2IiBrPSI2OCIvPg0KPGhrZXJuIGcxPSJDIiBnMj0ieCIgaz0iNDgiLz4NCjxoa2VybiBnMT0iRCIgZzI9InBhcmVucmlnaHQiIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJEIiBnMj0iY29tbWEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkQiIGcyPSJwZXJpb2QiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkQiIGcyPSJzbGFzaCIgaz0iNjQiLz4NCjxoa2VybiBnMT0iRCIgZzI9IkEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkQiIGcyPSJCIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJEIiBnMj0iRCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRCIgZzI9IkUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkQiIGcyPSJJIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJEIiBnMj0iTCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRCIgZzI9Ik0iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkQiIGcyPSJOIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJEIiBnMj0iTyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRCIgZzI9IlAiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkQiIGcyPSJSIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJEIiBnMj0iVSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRCIgZzI9IlYiIGs9IjY5Ii8+DQo8aGtlcm4gZzE9IkQiIGcyPSJXIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJEIiBnMj0iWCIgaz0iNjciLz4NCjxoa2VybiBnMT0iRCIgZzI9IlkiIGs9IjY3Ii8+DQo8aGtlcm4gZzE9IkQiIGcyPSJhIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJFIiBnMj0iaHlwaGVuIiBrPSI0NiIvPg0KPGhrZXJuIGcxPSJFIiBnMj0iQyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRSIgZzI9IkQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkUiIGcyPSJHIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJFIiBnMj0iTyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRSIgZzI9IlEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkUiIGcyPSJTIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJFIiBnMj0iYSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRSIgZzI9ImMiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkUiIGcyPSJvIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJFIiBnMj0icSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRSIgZzI9InUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkUiIGcyPSJ2IiBrPSI3MSIvPg0KPGhrZXJuIGcxPSJFIiBnMj0idyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRSIgZzI9InkiIGs9Ijc3Ii8+DQo8aGtlcm4gZzE9IkYiIGcyPSJwbHVzIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0iRiIgZzI9ImNvbW1hIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0iRiIgZzI9Imh5cGhlbiIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9IkYiIGcyPSJwZXJpb2QiIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJGIiBnMj0iY29sb24iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkYiIGcyPSJzZW1pY29sb24iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkYiIGcyPSJBIiBrPSI2NyIvPg0KPGhrZXJuIGcxPSJGIiBnMj0iQyIgaz0iNTciLz4NCjxoa2VybiBnMT0iRiIgZzI9IkciIGs9IjU3Ii8+DQo8aGtlcm4gZzE9IkYiIGcyPSJKIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0iRiIgZzI9Ik8iIGs9IjU1Ii8+DQo8aGtlcm4gZzE9IkYiIGcyPSJRIiBrPSI1NSIvPg0KPGhrZXJuIGcxPSJGIiBnMj0iYSIgaz0iNjciLz4NCjxoa2VybiBnMT0iRiIgZzI9ImQiIGs9IjU3Ii8+DQo8aGtlcm4gZzE9IkYiIGcyPSJlIiBrPSI1NyIvPg0KPGhrZXJuIGcxPSJGIiBnMj0iZyIgaz0iNTciLz4NCjxoa2VybiBnMT0iRiIgZzI9ImkiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkYiIGcyPSJqIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0iRiIgZzI9Im8iIGs9IjU1Ii8+DQo8aGtlcm4gZzE9IkYiIGcyPSJxIiBrPSI1NSIvPg0KPGhrZXJuIGcxPSJGIiBnMj0iciIgaz0iNTgiLz4NCjxoa2VybiBnMT0iRiIgZzI9InQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkYiIGcyPSJ1IiBrPSI2MyIvPg0KPGhrZXJuIGcxPSJGIiBnMj0idiIgaz0iMzQiLz4NCjxoa2VybiBnMT0iRiIgZzI9InciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkYiIGcyPSJ5IiBrPSI0NCIvPg0KPGhrZXJuIGcxPSJHIiBnMj0iQSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRyIgZzI9IkMiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkciIGcyPSJFIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJHIiBnMj0iRiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRyIgZzI9IkciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkciIGcyPSJJIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJHIiBnMj0iTSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRyIgZzI9Ik8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkciIGcyPSJSIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJHIiBnMj0iUyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRyIgZzI9IlUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkciIGcyPSJWIiBrPSI3MiIvPg0KPGhrZXJuIGcxPSJHIiBnMj0iVyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iRyIgZzI9IlkiIGs9Ijc5Ii8+DQo8aGtlcm4gZzE9IkciIGcyPSJ1IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJHIiBnMj0idiIgaz0iNzIiLz4NCjxoa2VybiBnMT0iRyIgZzI9InciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkgiIGcyPSJDIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJIIiBnMj0iRyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iSCIgZzI9Ik8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkgiIGcyPSJhIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJIIiBnMj0iZCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iSCIgZzI9ImUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkgiIGcyPSJvIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJIIiBnMj0icSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iSCIgZzI9InUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkgiIGcyPSJ2IiBrPSI3NSIvPg0KPGhrZXJuIGcxPSJIIiBnMj0idyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iSCIgZzI9InkiIGs9Ijc5Ii8+DQo8aGtlcm4gZzE9IkkiIGcyPSJCIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJJIiBnMj0iQyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iSSIgZzI9IkciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkkiIGcyPSJPIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJJIiBnMj0iUyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iSSIgZzI9ImEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkkiIGcyPSJjIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJJIiBnMj0iZCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iSSIgZzI9Im8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IkkiIGcyPSJxIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJJIiBnMj0idCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iSiIgZzI9ImNvbW1hIiBrPSI3OCIvPg0KPGhrZXJuIGcxPSJKIiBnMj0icGVyaW9kIiBrPSI3OCIvPg0KPGhrZXJuIGcxPSJKIiBnMj0iQSIgaz0iNDEiLz4NCjxoa2VybiBnMT0iSiIgZzI9IkMiIGs9IjM4Ii8+DQo8aGtlcm4gZzE9IkoiIGcyPSJGIiBrPSI0MCIvPg0KPGhrZXJuIGcxPSJKIiBnMj0iTyIgaz0iMzciLz4NCjxoa2VybiBnMT0iSiIgZzI9ImEiIGs9IjQxIi8+DQo8aGtlcm4gZzE9IkoiIGcyPSJlIiBrPSIzNyIvPg0KPGhrZXJuIGcxPSJKIiBnMj0ibyIgaz0iMzciLz4NCjxoa2VybiBnMT0iSiIgZzI9InUiIGs9IjM3Ii8+DQo8aGtlcm4gZzE9IksiIGcyPSJoeXBoZW4iIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJLIiBnMj0iQyIgaz0iNTIiLz4NCjxoa2VybiBnMT0iSyIgZzI9Ik8iIGs9IjY2Ii8+DQo8aGtlcm4gZzE9IksiIGcyPSJRIiBrPSI2NiIvPg0KPGhrZXJuIGcxPSJLIiBnMj0iUyIgaz0iNTMiLz4NCjxoa2VybiBnMT0iSyIgZzI9ImEiIGs9IjQzIi8+DQo8aGtlcm4gZzE9IksiIGcyPSJlIiBrPSI0MyIvPg0KPGhrZXJuIGcxPSJLIiBnMj0ibyIgaz0iNjYiLz4NCjxoa2VybiBnMT0iSyIgZzI9InUiIGs9IjgzIi8+DQo8aGtlcm4gZzE9IkwiIGcyPSJxdW90ZXNpbmdsZSIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9IkwiIGcyPSJoeXBoZW4iIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJMIiBnMj0iQiIgaz0iNDMiLz4NCjxoa2VybiBnMT0iTCIgZzI9IkMiIGs9IjUwIi8+DQo8aGtlcm4gZzE9IkwiIGcyPSJEIiBrPSI0MiIvPg0KPGhrZXJuIGcxPSJMIiBnMj0iTyIgaz0iNjQiLz4NCjxoa2VybiBnMT0iTCIgZzI9IlAiIGs9IjQzIi8+DQo8aGtlcm4gZzE9IkwiIGcyPSJRIiBrPSI2NCIvPg0KPGhrZXJuIGcxPSJMIiBnMj0iVCIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9IkwiIGcyPSJVIiBrPSI4MSIvPg0KPGhrZXJuIGcxPSJMIiBnMj0iViIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9IkwiIGcyPSJXIiBrPSI0OCIvPg0KPGhrZXJuIGcxPSJMIiBnMj0iWSIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9IkwiIGcyPSJ1IiBrPSI4MSIvPg0KPGhrZXJuIGcxPSJMIiBnMj0idiIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9IkwiIGcyPSJ3IiBrPSI0OCIvPg0KPGhrZXJuIGcxPSJNIiBnMj0iQyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTSIgZzI9IkQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik0iIGcyPSJHIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJNIiBnMj0iTyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTSIgZzI9IlEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik0iIGcyPSJhIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJNIiBnMj0iYyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTSIgZzI9ImQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik0iIGcyPSJlIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJNIiBnMj0ibyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTSIgZzI9InEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik0iIGcyPSJ1IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJOIiBnMj0iQyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTiIgZzI9IkciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik4iIGcyPSJPIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJOIiBnMj0iUSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTiIgZzI9ImEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik4iIGcyPSJkIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJOIiBnMj0iZSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTiIgZzI9ImkiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik4iIGcyPSJvIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJOIiBnMj0idSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTiIgZzI9InYiIGs9IjQxIi8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJwYXJlbnJpZ2h0IiBrPSI5NyIvPg0KPGhrZXJuIGcxPSJPIiBnMj0iY29tbWEiIGs9IjMzIi8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJwZXJpb2QiIGs9IjMzIi8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJBIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJPIiBnMj0iQiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTyIgZzI9IkMiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJEIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJPIiBnMj0iRSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTyIgZzI9IkYiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJHIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJPIiBnMj0iSCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTyIgZzI9IkkiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJKIiBrPSI2OCIvPg0KPGhrZXJuIGcxPSJPIiBnMj0iSyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTyIgZzI9IkwiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJNIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJPIiBnMj0iTiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTyIgZzI9IlAiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJSIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJPIiBnMj0iVCIgaz0iNjAiLz4NCjxoa2VybiBnMT0iTyIgZzI9IlUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJWIiBrPSI2NSIvPg0KPGhrZXJuIGcxPSJPIiBnMj0iVyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTyIgZzI9IlgiIGs9IjY2Ii8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJZIiBrPSI2NiIvPg0KPGhrZXJuIGcxPSJPIiBnMj0iWiIgaz0iNTEiLz4NCjxoa2VybiBnMT0iTyIgZzI9ImEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJiIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJPIiBnMj0iaCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iTyIgZzI9ImsiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Ik8iIGcyPSJsIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJQIiBnMj0iY29tbWEiIGs9IjM3Ii8+DQo8aGtlcm4gZzE9IlAiIGcyPSJoeXBoZW4iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlAiIGcyPSJjb2xvbiIgaz0iMzciLz4NCjxoa2VybiBnMT0iUCIgZzI9InNlbWljb2xvbiIgaz0iMzciLz4NCjxoa2VybiBnMT0iUCIgZzI9IkEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlAiIGcyPSJCIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJQIiBnMj0iRCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUCIgZzI9IkUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlAiIGcyPSJMIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJQIiBnMj0iTyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUCIgZzI9IlAiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlAiIGcyPSJRIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJQIiBnMj0iVSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUCIgZzI9IlYiIGs9IjY0Ii8+DQo8aGtlcm4gZzE9IlAiIGcyPSJXIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJQIiBnMj0iWSIgaz0iNzEiLz4NCjxoa2VybiBnMT0iUCIgZzI9ImEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlAiIGcyPSJkIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJQIiBnMj0iZSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUCIgZzI9Im8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlEiIGcyPSJwYXJlbnJpZ2h0IiBrPSI3MCIvPg0KPGhrZXJuIGcxPSJRIiBnMj0iY29tbWEiIGs9IjMzIi8+DQo8aGtlcm4gZzE9IlEiIGcyPSJwZXJpb2QiIGs9IjMzIi8+DQo8aGtlcm4gZzE9IlEiIGcyPSJBIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJRIiBnMj0iRSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUSIgZzI9IkYiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlEiIGcyPSJLIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJRIiBnMj0iTCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUSIgZzI9Ik4iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlEiIGcyPSJQIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJRIiBnMj0iVCIgaz0iNjAiLz4NCjxoa2VybiBnMT0iUSIgZzI9IlUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlEiIGcyPSJWIiBrPSI2NSIvPg0KPGhrZXJuIGcxPSJRIiBnMj0iVyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUSIgZzI9IlgiIGs9IjYzIi8+DQo8aGtlcm4gZzE9IlEiIGcyPSJZIiBrPSI2NiIvPg0KPGhrZXJuIGcxPSJSIiBnMj0iQyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUiIgZzI9IkciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlIiIGcyPSJPIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJSIiBnMj0iVCIgaz0iNTkiLz4NCjxoa2VybiBnMT0iUiIgZzI9IlUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlIiIGcyPSJWIiBrPSI2NCIvPg0KPGhrZXJuIGcxPSJSIiBnMj0iVyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUiIgZzI9IlkiIGs9IjcxIi8+DQo8aGtlcm4gZzE9IlIiIGcyPSJhIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJSIiBnMj0iZCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUiIgZzI9ImUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlIiIGcyPSJvIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJSIiBnMj0idCIgaz0iNTkiLz4NCjxoa2VybiBnMT0iUiIgZzI9InUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlIiIGcyPSJ2IiBrPSI2NCIvPg0KPGhrZXJuIGcxPSJSIiBnMj0idyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUyIgZzI9InBhcmVucmlnaHQiIGs9Ijg0Ii8+DQo8aGtlcm4gZzE9IlMiIGcyPSJjb21tYSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUyIgZzI9InBlcmlvZCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUyIgZzI9IkUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlMiIGcyPSJHIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJTIiBnMj0iSCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUyIgZzI9IkkiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlMiIGcyPSJLIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJTIiBnMj0iTSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUyIgZzI9IlQiIGs9IjcwIi8+DQo8aGtlcm4gZzE9IlMiIGcyPSJVIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJTIiBnMj0iViIgaz0iNzIiLz4NCjxoa2VybiBnMT0iUyIgZzI9IlciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlMiIGcyPSJZIiBrPSI3OCIvPg0KPGhrZXJuIGcxPSJTIiBnMj0iaSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iUyIgZzI9InAiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlMiIGcyPSJ1IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJUIiBnMj0iY29tbWEiIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJUIiBnMj0iaHlwaGVuIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0iVCIgZzI9InBlcmlvZCIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9IlQiIGcyPSJjb2xvbiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVCIgZzI9InNlbWljb2xvbiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVCIgZzI9IkEiIGs9IjY3Ii8+DQo8aGtlcm4gZzE9IlQiIGcyPSJDIiBrPSI1NyIvPg0KPGhrZXJuIGcxPSJUIiBnMj0iTCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVCIgZzI9Ik8iIGs9IjU1Ii8+DQo8aGtlcm4gZzE9IlQiIGcyPSJRIiBrPSI1NSIvPg0KPGhrZXJuIGcxPSJUIiBnMj0iUyIgaz0iNjgiLz4NCjxoa2VybiBnMT0iVCIgZzI9ImEiIGs9IjY3Ii8+DQo8aGtlcm4gZzE9IlQiIGcyPSJjIiBrPSI1NyIvPg0KPGhrZXJuIGcxPSJUIiBnMj0iZCIgaz0iNTciLz4NCjxoa2VybiBnMT0iVCIgZzI9ImUiIGs9IjU3Ii8+DQo8aGtlcm4gZzE9IlQiIGcyPSJnIiBrPSI1NyIvPg0KPGhrZXJuIGcxPSJUIiBnMj0iaSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVCIgZzI9Im8iIGs9IjU1Ii8+DQo8aGtlcm4gZzE9IlQiIGcyPSJyIiBrPSI1OCIvPg0KPGhrZXJuIGcxPSJUIiBnMj0icyIgaz0iNjgiLz4NCjxoa2VybiBnMT0iVCIgZzI9InUiIGs9IjYzIi8+DQo8aGtlcm4gZzE9IlQiIGcyPSJ2IiBrPSIzNCIvPg0KPGhrZXJuIGcxPSJUIiBnMj0idyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVCIgZzI9IngiIGs9IjUxIi8+DQo8aGtlcm4gZzE9IlQiIGcyPSJ5IiBrPSI0NCIvPg0KPGhrZXJuIGcxPSJVIiBnMj0iY29tbWEiIGs9IjM2Ii8+DQo8aGtlcm4gZzE9IlUiIGcyPSJwZXJpb2QiIGs9IjM2Ii8+DQo8aGtlcm4gZzE9IlUiIGcyPSJBIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJVIiBnMj0iQiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVSIgZzI9IkMiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlUiIGcyPSJEIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJVIiBnMj0iRyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVSIgZzI9IkwiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlUiIGcyPSJPIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJVIiBnMj0iUCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVSIgZzI9IlEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlUiIGcyPSJSIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJVIiBnMj0iUyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVSIgZzI9ImEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlUiIGcyPSJkIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJVIiBnMj0iZSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVSIgZzI9ImciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlUiIGcyPSJtIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJVIiBnMj0ibiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVSIgZzI9Im8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlUiIGcyPSJwIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJVIiBnMj0icyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iViIgZzI9ImNvbW1hIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0iViIgZzI9Imh5cGhlbiIgaz0iODYiLz4NCjxoa2VybiBnMT0iViIgZzI9InBlcmlvZCIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9IlYiIGcyPSJjb2xvbiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iViIgZzI9InNlbWljb2xvbiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iViIgZzI9IkEiIGs9IjcyIi8+DQo8aGtlcm4gZzE9IlYiIGcyPSJDIiBrPSI2MiIvPg0KPGhrZXJuIGcxPSJWIiBnMj0iRCIgaz0iNjEiLz4NCjxoa2VybiBnMT0iViIgZzI9IkciIGs9IjYyIi8+DQo8aGtlcm4gZzE9IlYiIGcyPSJMIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJWIiBnMj0iTyIgaz0iNjAiLz4NCjxoa2VybiBnMT0iViIgZzI9IlAiIGs9IjYyIi8+DQo8aGtlcm4gZzE9IlYiIGcyPSJRIiBrPSI2MCIvPg0KPGhrZXJuIGcxPSJWIiBnMj0iYSIgaz0iNzIiLz4NCjxoa2VybiBnMT0iViIgZzI9ImUiIGs9IjYxIi8+DQo8aGtlcm4gZzE9IlYiIGcyPSJpIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJWIiBnMj0iaiIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9IlYiIGcyPSJvIiBrPSI2MCIvPg0KPGhrZXJuIGcxPSJWIiBnMj0icSIgaz0iNjAiLz4NCjxoa2VybiBnMT0iViIgZzI9InIiIGs9IjYyIi8+DQo8aGtlcm4gZzE9IlYiIGcyPSJ1IiBrPSI2MyIvPg0KPGhrZXJuIGcxPSJXIiBnMj0iY29tbWEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlciIGcyPSJoeXBoZW4iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlciIGcyPSJwZXJpb2QiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlciIGcyPSJjb2xvbiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVyIgZzI9InNlbWljb2xvbiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVyIgZzI9IkEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlciIGcyPSJCIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJXIiBnMj0iQyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVyIgZzI9IkciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlciIGcyPSJPIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJXIiBnMj0iUSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVyIgZzI9IlIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlciIGcyPSJTIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJXIiBnMj0iWSIgaz0iMzQiLz4NCjxoa2VybiBnMT0iVyIgZzI9ImEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlciIGcyPSJkIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJXIiBnMj0iZSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVyIgZzI9ImciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlciIGcyPSJpIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJXIiBnMj0ibSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVyIgZzI9Im4iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlciIGcyPSJyIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJXIiBnMj0idCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iVyIgZzI9InYiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IlciIGcyPSJ5IiBrPSIzNCIvPg0KPGhrZXJuIGcxPSJYIiBnMj0iRCIgaz0iNDIiLz4NCjxoa2VybiBnMT0iWCIgZzI9IlEiIGs9IjYyIi8+DQo8aGtlcm4gZzE9IlkiIGcyPSJjb21tYSIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9IlkiIGcyPSJoeXBoZW4iIGs9Ijg2Ii8+DQo8aGtlcm4gZzE9IlkiIGcyPSJwZXJpb2QiIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJZIiBnMj0iY29sb24iIGs9IjMxIi8+DQo8aGtlcm4gZzE9IlkiIGcyPSJzZW1pY29sb24iIGs9IjMxIi8+DQo8aGtlcm4gZzE9IlkiIGcyPSJBIiBrPSI4MSIvPg0KPGhrZXJuIGcxPSJZIiBnMj0iQiIgaz0iNTkiLz4NCjxoa2VybiBnMT0iWSIgZzI9IkMiIGs9IjcwIi8+DQo8aGtlcm4gZzE9IlkiIGcyPSJEIiBrPSI2OSIvPg0KPGhrZXJuIGcxPSJZIiBnMj0iRyIgaz0iNzAiLz4NCjxoa2VybiBnMT0iWSIgZzI9IkwiIGs9IjM0Ii8+DQo8aGtlcm4gZzE9IlkiIGcyPSJPIiBrPSI2OCIvPg0KPGhrZXJuIGcxPSJZIiBnMj0iUCIgaz0iNzAiLz4NCjxoa2VybiBnMT0iWSIgZzI9IlEiIGs9IjY4Ii8+DQo8aGtlcm4gZzE9IlkiIGcyPSJSIiBrPSI3MCIvPg0KPGhrZXJuIGcxPSJZIiBnMj0iUyIgaz0iNzYiLz4NCjxoa2VybiBnMT0iWSIgZzI9ImEiIGs9IjgxIi8+DQo8aGtlcm4gZzE9IlkiIGcyPSJkIiBrPSI2OSIvPg0KPGhrZXJuIGcxPSJZIiBnMj0iZSIgaz0iNjkiLz4NCjxoa2VybiBnMT0iWSIgZzI9Im8iIGs9IjY4Ii8+DQo8aGtlcm4gZzE9IlkiIGcyPSJxIiBrPSI2OCIvPg0KPGhrZXJuIGcxPSJZIiBnMj0idSIgaz0iNzEiLz4NCjxoa2VybiBnMT0iWSIgZzI9InYiIGs9IjM1Ii8+DQo8aGtlcm4gZzE9IloiIGcyPSJPIiBrPSIzMyIvPg0KPGhrZXJuIGcxPSJaIiBnMj0iYSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iWiIgZzI9Im8iIGs9IjMzIi8+DQo8aGtlcm4gZzE9ImEiIGcyPSJxdW90ZWRibCIgaz0iNzIiLz4NCjxoa2VybiBnMT0iYSIgZzI9InF1b3Rlc2luZ2xlIiBrPSI3MiIvPg0KPGhrZXJuIGcxPSJhIiBnMj0icGFyZW5yaWdodCIgaz0iNTgiLz4NCjxoa2VybiBnMT0iYSIgZzI9IkMiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImEiIGcyPSJFIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJhIiBnMj0iRiIgaz0iMzMiLz4NCjxoa2VybiBnMT0iYSIgZzI9IkgiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImEiIGcyPSJJIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJhIiBnMj0iTSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYSIgZzI9IlIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImEiIGcyPSJUIiBrPSI3MiIvPg0KPGhrZXJuIGcxPSJhIiBnMj0iVSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYSIgZzI9IlYiIGs9Ijc3Ii8+DQo8aGtlcm4gZzE9ImEiIGcyPSJXIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJhIiBnMj0iYyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYSIgZzI9ImQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImEiIGcyPSJlIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJhIiBnMj0iZiIgaz0iMzMiLz4NCjxoa2VybiBnMT0iYSIgZzI9ImciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImEiIGcyPSJpIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJhIiBnMj0ibSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYSIgZzI9InAiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImEiIGcyPSJyIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJhIiBnMj0idCIgaz0iNzIiLz4NCjxoa2VybiBnMT0iYSIgZzI9InUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImEiIGcyPSJ2IiBrPSI3NyIvPg0KPGhrZXJuIGcxPSJhIiBnMj0idyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYSIgZzI9InkiIGs9Ijg0Ii8+DQo8aGtlcm4gZzE9ImIiIGcyPSJjb21tYSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYiIgZzI9InBlcmlvZCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYiIgZzI9Ik8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImIiIGcyPSJoIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJiIiBnMj0iaiIgaz0iNTEiLz4NCjxoa2VybiBnMT0iYiIgZzI9ImsiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImIiIGcyPSJsIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJiIiBnMj0ibyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYiIgZzI9InIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImIiIGcyPSJ1IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJiIiBnMj0ieSIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9ImMiIGcyPSJxdW90ZWRibCIgaz0iNjMiLz4NCjxoa2VybiBnMT0iYyIgZzI9InBhcmVucmlnaHQiIGs9Ijc4Ii8+DQo8aGtlcm4gZzE9ImMiIGcyPSJBIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJjIiBnMj0iSSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYyIgZzI9Ik0iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImMiIGcyPSJUIiBrPSI2MyIvPg0KPGhrZXJuIGcxPSJjIiBnMj0iYSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYyIgZzI9ImQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImMiIGcyPSJoIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJjIiBnMj0iaSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYyIgZzI9ImsiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImMiIGcyPSJsIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJjIiBnMj0ibSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYyIgZzI9InAiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImMiIGcyPSJ2IiBrPSI2OCIvPg0KPGhrZXJuIGcxPSJjIiBnMj0idyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iYyIgZzI9InkiIGs9IjcyIi8+DQo8aGtlcm4gZzE9ImQiIGcyPSJxdW90ZXNpbmdsZSIgaz0iNjQiLz4NCjxoa2VybiBnMT0iZCIgZzI9ImNvbW1hIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJkIiBnMj0icGVyaW9kIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJkIiBnMj0iQSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZCIgZzI9IkgiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImQiIGcyPSJOIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJkIiBnMj0iUCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZCIgZzI9IlQiIGs9IjY0Ii8+DQo8aGtlcm4gZzE9ImQiIGcyPSJVIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJkIiBnMj0iYSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZCIgZzI9ImMiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImQiIGcyPSJlIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJkIiBnMj0iZiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZCIgZzI9ImciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImQiIGcyPSJoIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJkIiBnMj0iaSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZCIgZzI9ImsiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImQiIGcyPSJsIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJkIiBnMj0ibiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZCIgZzI9Im8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImQiIGcyPSJxIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJkIiBnMj0icyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZCIgZzI9InQiIGs9IjY0Ii8+DQo8aGtlcm4gZzE9ImQiIGcyPSJ1IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJkIiBnMj0idiIgaz0iNjkiLz4NCjxoa2VybiBnMT0iZCIgZzI9InciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImQiIGcyPSJ5IiBrPSI2NyIvPg0KPGhrZXJuIGcxPSJlIiBnMj0icXVvdGVzaW5nbGUiIGs9IjY5Ii8+DQo8aGtlcm4gZzE9ImUiIGcyPSJwYXJlbnJpZ2h0IiBrPSIxMDAiLz4NCjxoa2VybiBnMT0iZSIgZzI9ImNvbW1hIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJlIiBnMj0icGVyaW9kIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJlIiBnMj0iRiIgaz0iNDgiLz4NCjxoa2VybiBnMT0iZSIgZzI9IkoiIGs9IjczIi8+DQo8aGtlcm4gZzE9ImUiIGcyPSJLIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJlIiBnMj0iTSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZSIgZzI9Ik4iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImUiIGcyPSJQIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJlIiBnMj0iUiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZSIgZzI9IlYiIGs9IjcxIi8+DQo8aGtlcm4gZzE9ImUiIGcyPSJXIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJlIiBnMj0iWSIgaz0iNzciLz4NCjxoa2VybiBnMT0iZSIgZzI9ImEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImUiIGcyPSJmIiBrPSI0OCIvPg0KPGhrZXJuIGcxPSJlIiBnMj0iaSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZSIgZzI9ImoiIGs9IjczIi8+DQo8aGtlcm4gZzE9ImUiIGcyPSJsIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJlIiBnMj0ibSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZSIgZzI9Im4iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImUiIGcyPSJwIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJlIiBnMj0iciIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZSIgZzI9InQiIGs9IjY5Ii8+DQo8aGtlcm4gZzE9ImUiIGcyPSJ1IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJlIiBnMj0idiIgaz0iNzEiLz4NCjxoa2VybiBnMT0iZSIgZzI9InciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImUiIGcyPSJ5IiBrPSI3NyIvPg0KPGhrZXJuIGcxPSJmIiBnMj0iY29tbWEiIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJmIiBnMj0icGVyaW9kIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0iZiIgZzI9ImEiIGs9IjY3Ii8+DQo8aGtlcm4gZzE9ImYiIGcyPSJkIiBrPSI1NyIvPg0KPGhrZXJuIGcxPSJmIiBnMj0iZSIgaz0iNTciLz4NCjxoa2VybiBnMT0iZiIgZzI9ImYiIGs9Ijk4Ii8+DQo8aGtlcm4gZzE9ImYiIGcyPSJpIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJmIiBnMj0ibCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZiIgZzI9Im8iIGs9IjU1Ii8+DQo8aGtlcm4gZzE9ImYiIGcyPSJ0IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJnIiBnMj0iY29tbWEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImciIGcyPSJwZXJpb2QiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImciIGcyPSJBIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJnIiBnMj0iRiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZyIgZzI9IlQiIGs9IjY5Ii8+DQo8aGtlcm4gZzE9ImciIGcyPSJVIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJnIiBnMj0iVyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZyIgZzI9ImEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImciIGcyPSJkIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJnIiBnMj0iZSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZyIgZzI9ImciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImciIGcyPSJoIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJnIiBnMj0iayIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZyIgZzI9ImwiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImciIGcyPSJtIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJnIiBnMj0ibyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iZyIgZzI9InEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImciIGcyPSJyIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJnIiBnMj0idiIgaz0iNzIiLz4NCjxoa2VybiBnMT0iZyIgZzI9InciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImgiIGcyPSJCIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJoIiBnMj0iTyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iaCIgZzI9ImIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImgiIGcyPSJjIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJoIiBnMj0iZCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iaCIgZzI9ImUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImgiIGcyPSJnIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJoIiBnMj0ibyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iaCIgZzI9InAiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImgiIGcyPSJ0IiBrPSI3MCIvPg0KPGhrZXJuIGcxPSJoIiBnMj0idSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iaCIgZzI9InYiIGs9Ijc1Ii8+DQo8aGtlcm4gZzE9ImgiIGcyPSJ3IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJoIiBnMj0ieSIgaz0iNzkiLz4NCjxoa2VybiBnMT0iaSIgZzI9IkIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImkiIGcyPSJTIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJpIiBnMj0iViIgaz0iMjkiLz4NCjxoa2VybiBnMT0iaSIgZzI9ImEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImkiIGcyPSJjIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJpIiBnMj0iZCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iaSIgZzI9ImUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImkiIGcyPSJmIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJpIiBnMj0iZyIgaz0iMjkiLz4NCjxoa2VybiBnMT0iaSIgZzI9Im4iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImkiIGcyPSJvIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJpIiBnMj0icCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iaSIgZzI9InQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9ImkiIGcyPSJ1IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJpIiBnMj0idiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iaiIgZzI9ImNvbW1hIiBrPSI3OCIvPg0KPGhrZXJuIGcxPSJqIiBnMj0icGVyaW9kIiBrPSI3OCIvPg0KPGhrZXJuIGcxPSJqIiBnMj0iRiIgaz0iNDAiLz4NCjxoa2VybiBnMT0iaiIgZzI9ImEiIGs9IjQxIi8+DQo8aGtlcm4gZzE9ImoiIGcyPSJiIiBrPSIzMyIvPg0KPGhrZXJuIGcxPSJqIiBnMj0iZSIgaz0iMzciLz4NCjxoa2VybiBnMT0iaiIgZzI9Im8iIGs9IjM3Ii8+DQo8aGtlcm4gZzE9ImoiIGcyPSJ1IiBrPSIzNyIvPg0KPGhrZXJuIGcxPSJrIiBnMj0iQiIgaz0iNDMiLz4NCjxoa2VybiBnMT0iayIgZzI9ImEiIGs9IjQzIi8+DQo8aGtlcm4gZzE9ImsiIGcyPSJjIiBrPSI1MiIvPg0KPGhrZXJuIGcxPSJrIiBnMj0iZCIgaz0iNDIiLz4NCjxoa2VybiBnMT0iayIgZzI9ImUiIGs9IjQzIi8+DQo8aGtlcm4gZzE9ImsiIGcyPSJnIiBrPSI1MiIvPg0KPGhrZXJuIGcxPSJrIiBnMj0ibyIgaz0iNjYiLz4NCjxoa2VybiBnMT0ibCIgZzI9IkIiIGs9IjQzIi8+DQo8aGtlcm4gZzE9ImwiIGcyPSJQIiBrPSI0MyIvPg0KPGhrZXJuIGcxPSJsIiBnMj0iYSIgaz0iNDMiLz4NCjxoa2VybiBnMT0ibCIgZzI9ImIiIGs9IjQzIi8+DQo8aGtlcm4gZzE9ImwiIGcyPSJjIiBrPSI1MCIvPg0KPGhrZXJuIGcxPSJsIiBnMj0iZCIgaz0iNDIiLz4NCjxoa2VybiBnMT0ibCIgZzI9ImUiIGs9IjQzIi8+DQo8aGtlcm4gZzE9ImwiIGcyPSJmIiBrPSI4OCIvPg0KPGhrZXJuIGcxPSJsIiBnMj0iZyIgaz0iNTAiLz4NCjxoa2VybiBnMT0ibCIgZzI9Im8iIGs9IjY0Ii8+DQo8aGtlcm4gZzE9ImwiIGcyPSJwIiBrPSI0MyIvPg0KPGhrZXJuIGcxPSJsIiBnMj0icSIgaz0iNjQiLz4NCjxoa2VybiBnMT0ibCIgZzI9InIiIGs9IjQzIi8+DQo8aGtlcm4gZzE9ImwiIGcyPSJ1IiBrPSI4MSIvPg0KPGhrZXJuIGcxPSJsIiBnMj0idiIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9ImwiIGcyPSJ3IiBrPSI0OCIvPg0KPGhrZXJuIGcxPSJsIiBnMj0ieSIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9Im0iIGcyPSJVIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJtIiBnMj0iVyIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibSIgZzI9ImEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im0iIGcyPSJjIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJtIiBnMj0iZCIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibSIgZzI9ImUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im0iIGcyPSJnIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJtIiBnMj0ibyIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibSIgZzI9InAiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im0iIGcyPSJyIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJtIiBnMj0idCIgaz0iNDAiLz4NCjxoa2VybiBnMT0ibSIgZzI9InUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im0iIGcyPSJ2IiBrPSI0NSIvPg0KPGhrZXJuIGcxPSJtIiBnMj0ieSIgaz0iNTMiLz4NCjxoa2VybiBnMT0ibiIgZzI9IlUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im4iIGcyPSJXIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJuIiBnMj0iYyIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibiIgZzI9ImQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im4iIGcyPSJlIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJuIiBnMj0iZiIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibiIgZzI9ImciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im4iIGcyPSJpIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJuIiBnMj0ibyIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibiIgZzI9InAiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im4iIGcyPSJxIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJuIiBnMj0icyIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibiIgZzI9InQiIGs9IjM2Ii8+DQo8aGtlcm4gZzE9Im4iIGcyPSJ1IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJuIiBnMj0idiIgaz0iNDEiLz4NCjxoa2VybiBnMT0ibiIgZzI9InciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im4iIGcyPSJ5IiBrPSI1MSIvPg0KPGhrZXJuIGcxPSJvIiBnMj0icXVvdGVkYmwiIGs9IjYwIi8+DQo8aGtlcm4gZzE9Im8iIGcyPSJxdW90ZXNpbmdsZSIgaz0iNjAiLz4NCjxoa2VybiBnMT0ibyIgZzI9InBhcmVucmlnaHQiIGs9Ijk3Ii8+DQo8aGtlcm4gZzE9Im8iIGcyPSJjb21tYSIgaz0iMzMiLz4NCjxoa2VybiBnMT0ibyIgZzI9InBlcmlvZCIgaz0iMzMiLz4NCjxoa2VybiBnMT0ibyIgZzI9IkEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im8iIGcyPSJFIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJvIiBnMj0iRiIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibyIgZzI9IkgiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im8iIGcyPSJJIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJvIiBnMj0iSiIgaz0iNjgiLz4NCjxoa2VybiBnMT0ibyIgZzI9IksiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im8iIGcyPSJNIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJvIiBnMj0iTiIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibyIgZzI9IlIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im8iIGcyPSJUIiBrPSI2MCIvPg0KPGhrZXJuIGcxPSJvIiBnMj0iViIgaz0iNjUiLz4NCjxoa2VybiBnMT0ibyIgZzI9IlkiIGs9IjY2Ii8+DQo8aGtlcm4gZzE9Im8iIGcyPSJaIiBrPSI1MSIvPg0KPGhrZXJuIGcxPSJvIiBnMj0iYiIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibyIgZzI9ImQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im8iIGcyPSJmIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJvIiBnMj0iZyIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibyIgZzI9ImgiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im8iIGcyPSJpIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJvIiBnMj0iaiIgaz0iNjgiLz4NCjxoa2VybiBnMT0ibyIgZzI9ImsiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im8iIGcyPSJsIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJvIiBnMj0ibSIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibyIgZzI9Im4iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im8iIGcyPSJwIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJvIiBnMj0iciIgaz0iMjkiLz4NCjxoa2VybiBnMT0ibyIgZzI9InQiIGs9IjYwIi8+DQo8aGtlcm4gZzE9Im8iIGcyPSJ1IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJvIiBnMj0idiIgaz0iNjUiLz4NCjxoa2VybiBnMT0ibyIgZzI9InciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9Im8iIGcyPSJ4IiBrPSI2NiIvPg0KPGhrZXJuIGcxPSJvIiBnMj0ieSIgaz0iNjYiLz4NCjxoa2VybiBnMT0icCIgZzI9InBhcmVucmlnaHQiIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJwIiBnMj0iY29tbWEiIGs9IjM3Ii8+DQo8aGtlcm4gZzE9InAiIGcyPSJwZXJpb2QiIGs9IjM3Ii8+DQo8aGtlcm4gZzE9InAiIGcyPSJTIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwIiBnMj0iYSIgaz0iMjkiLz4NCjxoa2VybiBnMT0icCIgZzI9ImgiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InAiIGcyPSJpIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwIiBnMj0iaiIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9InAiIGcyPSJsIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwIiBnMj0ibSIgaz0iMjkiLz4NCjxoa2VybiBnMT0icCIgZzI9Im4iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InAiIGcyPSJvIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJwIiBnMj0icCIgaz0iMjkiLz4NCjxoa2VybiBnMT0icCIgZzI9InUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InEiIGcyPSJxdW90ZWRibCIgaz0iNjAiLz4NCjxoa2VybiBnMT0icSIgZzI9InBlcmlvZCIgaz0iMzMiLz4NCjxoa2VybiBnMT0icSIgZzI9IkUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InEiIGcyPSJIIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJxIiBnMj0iSSIgaz0iMjkiLz4NCjxoa2VybiBnMT0icSIgZzI9IlYiIGs9IjY1Ii8+DQo8aGtlcm4gZzE9InEiIGcyPSJZIiBrPSI2NiIvPg0KPGhrZXJuIGcxPSJxIiBnMj0ibCIgaz0iMjkiLz4NCjxoa2VybiBnMT0icSIgZzI9Im4iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InEiIGcyPSJwIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJxIiBnMj0iciIgaz0iMjkiLz4NCjxoa2VybiBnMT0icSIgZzI9InUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InIiIGcyPSJjb21tYSIgaz0iMjkiLz4NCjxoa2VybiBnMT0iciIgZzI9InBlcmlvZCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iciIgZzI9IkMiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InIiIGcyPSJGIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJyIiBnMj0iVCIgaz0iNTkiLz4NCjxoa2VybiBnMT0iciIgZzI9ImEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InIiIGcyPSJiIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJyIiBnMj0iZCIgaz0iMjkiLz4NCjxoa2VybiBnMT0iciIgZzI9ImUiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InIiIGcyPSJnIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJyIiBnMj0iayIgaz0iMjkiLz4NCjxoa2VybiBnMT0iciIgZzI9ImwiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InIiIGcyPSJtIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJyIiBnMj0ibiIgaz0iMjkiLz4NCjxoa2VybiBnMT0iciIgZzI9Im8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InIiIGcyPSJxIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJyIiBnMj0iciIgaz0iMjkiLz4NCjxoa2VybiBnMT0iciIgZzI9InQiIGs9IjU5Ii8+DQo8aGtlcm4gZzE9InIiIGcyPSJ2IiBrPSI2NCIvPg0KPGhrZXJuIGcxPSJyIiBnMj0ieSIgaz0iNzEiLz4NCjxoa2VybiBnMT0icyIgZzI9InF1b3RlZGJsIiBrPSI3MCIvPg0KPGhrZXJuIGcxPSJzIiBnMj0iY29tbWEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InMiIGcyPSJwZXJpb2QiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InMiIGcyPSJUIiBrPSI3MCIvPg0KPGhrZXJuIGcxPSJzIiBnMj0iVSIgaz0iMjkiLz4NCjxoa2VybiBnMT0icyIgZzI9ImQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InMiIGcyPSJoIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJzIiBnMj0ibiIgaz0iMjkiLz4NCjxoa2VybiBnMT0icyIgZzI9InQiIGs9IjcwIi8+DQo8aGtlcm4gZzE9InMiIGcyPSJ1IiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJ0IiBnMj0iY29tbWEiIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJ0IiBnMj0icGVyaW9kIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0idCIgZzI9IkEiIGs9IjY3Ii8+DQo8aGtlcm4gZzE9InQiIGcyPSJGIiBrPSI5OCIvPg0KPGhrZXJuIGcxPSJ0IiBnMj0iVyIgaz0iMjkiLz4NCjxoa2VybiBnMT0idCIgZzI9ImEiIGs9IjY3Ii8+DQo8aGtlcm4gZzE9InQiIGcyPSJkIiBrPSI1NyIvPg0KPGhrZXJuIGcxPSJ0IiBnMj0iZSIgaz0iNTciLz4NCjxoa2VybiBnMT0idCIgZzI9ImYiIGs9Ijk4Ii8+DQo8aGtlcm4gZzE9InQiIGcyPSJoIiBrPSI2NyIvPg0KPGhrZXJuIGcxPSJ0IiBnMj0iaiIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9InQiIGcyPSJtIiBrPSIzNyIvPg0KPGhrZXJuIGcxPSJ0IiBnMj0ibyIgaz0iNTUiLz4NCjxoa2VybiBnMT0idCIgZzI9InIiIGs9IjU4Ii8+DQo8aGtlcm4gZzE9InQiIGcyPSJzIiBrPSI2OCIvPg0KPGhrZXJuIGcxPSJ0IiBnMj0idSIgaz0iNjMiLz4NCjxoa2VybiBnMT0idSIgZzI9IkEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InUiIGcyPSJCIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJ1IiBnMj0iRSIgaz0iMjkiLz4NCjxoa2VybiBnMT0idSIgZzI9IkciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InUiIGcyPSJMIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJ1IiBnMj0iTiIgaz0iMjkiLz4NCjxoa2VybiBnMT0idSIgZzI9IlIiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InUiIGcyPSJUIiBrPSI2MCIvPg0KPGhrZXJuIGcxPSJ1IiBnMj0iViIgaz0iNjUiLz4NCjxoa2VybiBnMT0idSIgZzI9IlkiIGs9IjY5Ii8+DQo8aGtlcm4gZzE9InUiIGcyPSJhIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJ1IiBnMj0iYyIgaz0iMjkiLz4NCjxoa2VybiBnMT0idSIgZzI9ImQiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InUiIGcyPSJlIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJ1IiBnMj0iZyIgaz0iMjkiLz4NCjxoa2VybiBnMT0idSIgZzI9ImgiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InUiIGcyPSJpIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJ1IiBnMj0ibiIgaz0iMjkiLz4NCjxoa2VybiBnMT0idSIgZzI9Im8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InUiIGcyPSJwIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJ1IiBnMj0icSIgaz0iMjkiLz4NCjxoa2VybiBnMT0idSIgZzI9InMiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InUiIGcyPSJ0IiBrPSI2MCIvPg0KPGhrZXJuIGcxPSJ1IiBnMj0idiIgaz0iNjUiLz4NCjxoa2VybiBnMT0idSIgZzI9InciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InUiIGcyPSJ5IiBrPSI2OSIvPg0KPGhrZXJuIGcxPSJ2IiBnMj0iY29tbWEiIGs9IjEwMCIvPg0KPGhrZXJuIGcxPSJ2IiBnMj0icGVyaW9kIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0idiIgZzI9IkEiIGs9IjcyIi8+DQo8aGtlcm4gZzE9InYiIGcyPSJGIiBrPSI5MSIvPg0KPGhrZXJuIGcxPSJ2IiBnMj0iRyIgaz0iNjIiLz4NCjxoa2VybiBnMT0idiIgZzI9IkgiIGs9IjcwIi8+DQo8aGtlcm4gZzE9InYiIGcyPSJSIiBrPSI2MiIvPg0KPGhrZXJuIGcxPSJ2IiBnMj0iYSIgaz0iNzIiLz4NCjxoa2VybiBnMT0idiIgZzI9ImIiIGs9IjUxIi8+DQo8aGtlcm4gZzE9InYiIGcyPSJjIiBrPSI2MiIvPg0KPGhrZXJuIGcxPSJ2IiBnMj0iZCIgaz0iNjEiLz4NCjxoa2VybiBnMT0idiIgZzI9ImUiIGs9IjYxIi8+DQo8aGtlcm4gZzE9InYiIGcyPSJnIiBrPSI2MiIvPg0KPGhrZXJuIGcxPSJ2IiBnMj0iaSIgaz0iMjkiLz4NCjxoa2VybiBnMT0idiIgZzI9ImwiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InYiIGcyPSJtIiBrPSI0MiIvPg0KPGhrZXJuIGcxPSJ2IiBnMj0ibiIgaz0iNDIiLz4NCjxoa2VybiBnMT0idiIgZzI9Im8iIGs9IjYwIi8+DQo8aGtlcm4gZzE9InYiIGcyPSJyIiBrPSI2MiIvPg0KPGhrZXJuIGcxPSJ2IiBnMj0idSIgaz0iNjMiLz4NCjxoa2VybiBnMT0idiIgZzI9InYiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InYiIGcyPSJ5IiBrPSIzNCIvPg0KPGhrZXJuIGcxPSJ3IiBnMj0iY29tbWEiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InciIGcyPSJwZXJpb2QiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InciIGcyPSJFIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJ3IiBnMj0iRiIgaz0iMjkiLz4NCjxoa2VybiBnMT0idyIgZzI9IkwiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InciIGcyPSJUIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJ3IiBnMj0iYSIgaz0iMjkiLz4NCjxoa2VybiBnMT0idyIgZzI9ImMiIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InciIGcyPSJkIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJ3IiBnMj0iZSIgaz0iMjkiLz4NCjxoa2VybiBnMT0idyIgZzI9ImciIGs9IjI5Ii8+DQo8aGtlcm4gZzE9InciIGcyPSJoIiBrPSIyOSIvPg0KPGhrZXJuIGcxPSJ3IiBnMj0ibCIgaz0iMjkiLz4NCjxoa2VybiBnMT0idyIgZzI9Im8iIGs9IjI5Ii8+DQo8aGtlcm4gZzE9IngiIGcyPSJUIiBrPSI0OSIvPg0KPGhrZXJuIGcxPSJ4IiBnMj0iZSIgaz0iNDMiLz4NCjxoa2VybiBnMT0ieSIgZzI9ImNvbW1hIiBrPSIxMDAiLz4NCjxoa2VybiBnMT0ieSIgZzI9InBlcmlvZCIgaz0iMTAwIi8+DQo8aGtlcm4gZzE9InkiIGcyPSJBIiBrPSI4MSIvPg0KPGhrZXJuIGcxPSJ5IiBnMj0iQiIgaz0iNTkiLz4NCjxoa2VybiBnMT0ieSIgZzI9IkgiIGs9Ijc4Ii8+DQo8aGtlcm4gZzE9InkiIGcyPSJXIiBrPSIzNSIvPg0KPGhrZXJuIGcxPSJ5IiBnMj0iYSIgaz0iODEiLz4NCjxoa2VybiBnMT0ieSIgZzI9ImIiIGs9IjU5Ii8+DQo8aGtlcm4gZzE9InkiIGcyPSJjIiBrPSI3MCIvPg0KPGhrZXJuIGcxPSJ5IiBnMj0iZCIgaz0iNjkiLz4NCjxoa2VybiBnMT0ieSIgZzI9ImUiIGs9IjY5Ii8+DQo8aGtlcm4gZzE9InkiIGcyPSJpIiBrPSIzMSIvPg0KPGhrZXJuIGcxPSJ5IiBnMj0ibiIgaz0iMjkiLz4NCjxoa2VybiBnMT0ieSIgZzI9Im8iIGs9IjY4Ii8+DQo8aGtlcm4gZzE9InkiIGcyPSJ2IiBrPSIzNSIvPg0KPC9mb250Pg0KPC9kZWZzPjxnIHRyYW5zZm9ybT0ibWF0cml4KDIzLjI1NTgxMzk1MzQ4ODM3LCAwLCAwLCAyMy4yNTU4MTM5NTM0ODgzNywgMCwgMCkiPjxnIHRyYW5zZm9ybT0ibWF0cml4KDEuODUyMzMxNTg1NzY2NDc0NCwgMCwgMCwgMS44NTIzMzE1ODU3NjY0NzQ0LCAtNzAuMTE0NTA4Mzc0Njg1ODQsIC00Mi4zOTk4NzA5MDYwMjA4KSI+PGcgdHJhbnNmb3JtPSJtYXRyaXgoMC4yMjY1Nzk1MjQwNzM0NjU3LCAwLCAwLCAwLjIyNjU3OTUyNDA3MzQ2NTcsIDUyLjEyMjcwMjMwMTc0MzI0LCAyOS4zNzgwMTc4NjY5NjA0NzMpIj48Zz4NCgk8cGF0aCBpZD0ia2NzIiBmaWxsPSIjMjY1NDJBIiBkPSJNMTIyLjc0Miw0OS43NmM1LjczLDUuNzMzLDUuNzMsMTUuMDMzLDAsMjAuNzYxYy01LjczNiw1LjcyNS0xNS4wMyw1LjcyNS0yMC43NjEsMEw4Mi43NzUsNTEuMzEyICAgYy00LjM1My00LjM0Mi05LjkyLTYuNDQ2LTE1LjYyNS02LjQ2N2MtNS42OTksMC4wMjEtMTEuMjY5LDIuMTI1LTE1LjYxNiw2LjQ2N2MtNC4zNDgsNC4zNjEtNi40NTksOS45Mi02LjQ3MiwxNS42MjkgICBjMC4wMTMsNS42OTcsMi4xMjUsMTEuMjY0LDYuNDcyLDE1LjYxMWwxOS4yMDQsMTkuMjEyYzUuNzMsNS43Myw1LjczLDE1LjAyLDAsMjAuNzY2Yy01LjczLDUuNzI1LTE1LjAyMiw1LjcyNS0yMC43NTMsMCAgIGwtMTkuMjEyLTE5LjIxN2MtMjAuMDg4LTIwLjEwMi0yMC4wODgtNTIuNjU4LDAtNzIuNzQ5YzIwLjEwNC0yMC4wOTQsNTIuNjU1LTIwLjA5NCw3Mi43NTctMC4wMTNMMTIyLjc0Miw0OS43NnogTTIzMy44OTYsMTYwLjkzICAgYy01LjczMy01LjczMi0xNS4wMjUtNS43MzItMjAuNzU5LDBjLTUuNzMyLDUuNzI2LTUuNzMyLDE1LjAyNywwLDIwLjc1OGwxOS4yMDcsMTkuMjA0YzQuMzM3LDQuMzU1LDYuNDU0LDkuOTIzLDYuNDY1LDE1LjYyNCAgIGMtMC4wMTEsNS42OTctMi4xMjgsMTEuMjc0LTYuNDY1LDE1LjYyN2MtNC4zNTMsNC4zMzUtOS45Miw2LjQ0NC0xNS42MjcsNi40NjVjLTUuNjk2LTAuMDIxLTExLjI2OS0yLjEzLTE1LjYxNi02LjQ2NSAgIGwtMTkuMjA3LTE5LjIxN2MtNS43MzItNS43MjgtMTUuMDItNS43MjgtMjAuNzU4LDAuMDA4Yy01LjczMiw1LjczLTUuNzMyLDE1LjAyNywwLDIwLjc1OGwxOS4yMDcsMTkuMjA0ICAgYzIwLjA5OSwyMC4wODEsNTIuNjU0LDIwLjA4MSw3Mi43NTksMGMyMC4wODktMjAuMTA5LDIwLjA4OS01Mi42NTUsMC03Mi43NTdMMjMzLjg5NiwxNjAuOTN6IE0xODEuODk0LDcwLjUyMWwxOS4yMDctMTkuMjA5ICAgYzQuMzQ4LTQuMzQyLDkuOTItNi40NDYsMTUuNjE2LTYuNDY3YzUuNzA3LDAuMDIxLDExLjI3NCwyLjEyNSwxNS42MjcsNi40NjdjNC4zMzcsNC4zNjEsNi40NTQsOS45Miw2LjQ2NSwxNS42MjkgICBjLTAuMDExLDUuNjk3LTIuMTI4LDExLjI2NC02LjQ2NSwxNS42MTFsLTE5LjIwNywxOS4yMTJjLTUuNzMyLDUuNzMtNS43MzIsMTUuMDIsMCwyMC43NjZjNS43MjksNS43MjUsMTUuMDI1LDUuNzI1LDIwLjc1OSwwICAgbDE5LjIwNi0xOS4yMTdjMjAuMDg5LTIwLjEwMiwyMC4wODktNTIuNjU4LDAtNzIuNzQ5Yy0yMC4xMDQtMjAuMDk0LTUyLjY2LTIwLjA5NC03Mi43NTktMC4wMTNMMTYxLjEzNiw0OS43NiAgIGMtNS43MzIsNS43MzMtNS43MzIsMTUuMDMzLDAsMjAuNzYxQzE2Ni44NzQsNzYuMjQ2LDE3Ni4xNjEsNzYuMjQ2LDE4MS44OTQsNzAuNTIxeiBNMTAxLjk4MiwyMTIuOTM0bC0xOS4yMDcsMTkuMjA5ICAgYy00LjM1Myw0LjMzNS05LjkyLDYuNDQ0LTE1LjYyNSw2LjQ2NWMtNS42OTktMC4wMjEtMTEuMjY5LTIuMTMtMTUuNjE2LTYuNDY1Yy00LjM0OC00LjM1My02LjQ1OS05LjkzLTYuNDcyLTE1LjYyNyAgIGMwLjAxMy01LjcwMSwyLjEyNS0xMS4yNjksNi40NzItMTUuNjI0bDE5LjIwNC0xOS4yMDRjNS43My01LjczLDUuNzMtMTUuMDMyLDAtMjAuNzU4Yy01LjczLTUuNzMyLTE1LjAyOC01LjczMi0yMC43NTgsMCAgIGwtMTkuMjA3LDE5LjIwOWMtMjAuMDg4LDIwLjEwMi0yMC4wODgsNTIuNjQ3LDAsNzIuNzU3YzIwLjEwNCwyMC4wODEsNTIuNjU1LDIwLjA4MSw3Mi43NTcsMGwxOS4yMTItMTkuMjA0ICAgYzUuNzMtNS43Myw1LjczLTE1LjAyNywwLTIwLjc1OEMxMTcuMDA3LDIwNy4xOTksMTA3LjcxMiwyMDcuMTk5LDEwMS45ODIsMjEyLjkzNHoiLz4NCgk8cGF0aCBpZD0iZGNzMSIgZmlsbD0iIzU5QTE2MCIgZD0iTTE5Mi4wNTMsMTE5LjA3N2wtMjcuNDcyLTI3LjQ2NmMtMTIuNTA0LTEyLjUwNi0zMi43ODMtMTIuNTA2LTQ1LjI5MiwwbC0yNy40NjYsMjcuNDY2ICAgYy0xMi41MDYsMTIuNTA5LTEyLjUwNiwzMi43OSwwLDQ1LjI5OGwyNy40NjYsMjcuNDU4YzEyLjUwOSwxMi41MDksMzIuNzg4LDEyLjUwOSw0NS4yOTIsMGwyNy40NzItMjcuNDU4ICAgQzIwNC41NjIsMTUxLjg2NywyMDQuNTYyLDEzMS41ODYsMTkyLjA1MywxMTkuMDc3eiBNMTcxLjI5NCwxNDMuNjEybC0yNy40NzEsMjcuNDY5Yy0xLjA0MywxLjA0My0yLjczMywxLjA0My0zLjc3MSwwICAgbC0yNy40NzEtMjcuNDY5Yy0xLjAzOC0xLjA0My0xLjAzOC0yLjczNiwwLTMuNzc3bDI3LjQ3MS0yNy40NjNjMS4wMzgtMS4wNDMsMi43MjgtMS4wNDMsMy43NzEsMGwyNy40NzEsMjcuNDYzICAgQzE3Mi4zMzcsMTQwLjg3NSwxNzIuMzM3LDE0Mi41NjksMTcxLjI5NCwxNDMuNjEyeiIvPg0KPC9nPjwvZz48L2c+PC9nPjwvc3ZnPg==';	
}
