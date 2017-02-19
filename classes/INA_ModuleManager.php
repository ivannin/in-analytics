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

		// Выполняем модули
		foreach ( $this->modules as $module )
		{
			if ( $module->isEnabled() )
				$module->handle();
		}

		
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
			uasort($this->modules, array( $this, 'moduleCompare' ) );
        }       
    }

    /**
     * Сравнивает два модуля на основе свойства menuOrder
     * @param INA_ModuleBase $objA    Первый модуль
     * @param INA_ModuleBase $objB    Второй модуль
     */    
    protected function moduleCompare($objA, $objB)
    {
		return $objA->menuOrder > $objB->menuOrder;
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
     * @const           Слаг раздела In-Analytics
     */    
    const OPTION_SLUG = 'in-analytics';	
	
	/**
     * Добавляет меню в админку
     */
    public function addAdminMenu()
    {
		// Раздел
		add_menu_page( 
			/* translators: page_title - The text to be displayed in the title tags of the page when the menu is selected and the h2 of settings page */
			__( 'IN-Analytics Settings', INA_TEXT_DOMAIN),
			/* translators: menu_title - The text to be used for the menu */
			__( 'IN-Analytics', INA_TEXT_DOMAIN),
			'manage_options', 										// The capability required for this menu to be displayed to the user
			self::OPTION_SLUG,										// The slug name to refer to this menu by (should be unique for this menu)
			array( $this, 'showOptionPage'),						// The function to be called to output the content for this page
			$this->baseURL . 'img/in-analytics-grayscale-20x20.png',// Logo
			80);													// Position under Settings)
		
		// Страницы модулей
		foreach ($this->modules as $moduleId => $module)
		{
			if ( $module->isEnabled() )
			{
				// Меню модуля
				add_submenu_page(
					self::OPTION_SLUG, 						// parent	The filename of the core WordPress admin file that supplies the top-level menu in which you want to insert your submenu
					$module->title,							// page_title
					$module->menuTitle,						// menu_title, 
					'manage_options',						// access_level/capability, 
					$moduleId,								// The slug name to refer to this menu
					array( $module, 'showOptionPage' ) ); 	// Render function
			}
		}		
			
	}
	
    /**
     * @const           Поле nonce для доступа в форме
     */    
    const NONCE = 'ina_nonce';	
		
	/**
     * Вывод страницы настроек
     */ 
	public function showOptionPage() 
	{ 
		// Сохранение настроек
		if ( $_SERVER['REQUEST_METHOD'] == 'POST' )
		{
			// Проверка nonce
			$nonce = isset( $_POST[self::NONCE] ) ? $_POST[self::NONCE] : '';
			if ( ! empty( $nonce ) && wp_verify_nonce( $nonce, get_class( $this ) ) )
			{
				// Читаем включение модулей
				foreach ($this->modules as $moduleId => $module)
				{
					$moduleEnabled = (bool) isset( $_POST[$moduleId] ) ?  $_POST[$moduleId] : false;
					$module->setEnabled( $moduleEnabled );
				}
				// Сохраняем параметры
				$this->saveOptions();
			}
		}
	?>
	<a  class="ina-logo" href="<?php esc_html_e( 'http://in-analytics.com', INA_TEXT_DOMAIN)?>" title="<?php esc_html_e( 'Visit the official IN-Analytics site', INA_TEXT_DOMAIN)?>">
		<img src="<?php echo $this->baseURL ?>img/in-analytics-transparent-100x83.png" />
	</a>
	<form action="<?php echo $_SERVER['REQUEST_URI']?>" method="post" class="ina-settings">
			<?php wp_nonce_field( get_class( $this ), self::NONCE ) ?>
			<h2><?php esc_html_e( 'IN-Analytics Settings', INA_TEXT_DOMAIN)?></h2>
			<p><?php esc_html_e( 'This is the list of available modules. Check the required functions for activation plugin modules. Then you will see the settings pages for each activated module.', INA_TEXT_DOMAIN)?></p>
				
			<fieldset>
				<legend><?php esc_html_e( 'Modules', INA_TEXT_DOMAIN)?></legend>
				<?php foreach ($this->modules as $moduleId => $module): ?>
					<div class="checkbox-field">
						<input type="checkbox" id="<?php echo $moduleId ?>" name="<?php echo $moduleId ?>" <?php echo ( $module->isEnabled() ) ? 'checked' : '' ?> value="1" />
						<label for="<?php echo $moduleId ?>"><?php echo $module->title ?></label>
						<p><?php echo $module->description ?></p>
					</div>
				<?php endforeach ?>
			</fieldset>
			<?php submit_button() ?>
		</form>
	<?php		
	}
}
