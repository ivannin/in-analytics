<?php
/**
 * Загрузчик модулей и настроек
 */
class ModuleManager
{
    /**
     * @const SETTINGS          Массив параметров модулей
     */    
    const SETTINGS = 'ina_settings';
    
    /**
     * @var mixed $settings     Массив параметров модулей
     */
    protected $settings;
	
    /**
     * @var mixed $settings     Папка плагина
     */
    public $baseDir;	
    
    /**
     * @var mixed $modules      Массив загруженных модулей модулей
     */
    protected $modules;     
    
    /**
     * Конструктор класса
     * @param string $baseDir   Папка плагина
     */    
    function __construct($baseDir='') 
    {
		// Папка плагина
		$this->baseDir = $baseDir;
		
        // Загружаем настройки
        $this->settings = get_option( self::SETTINGS, array() );
        
        // Загружаем модули
        $this->loadModiles();
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
    public function saveOption()
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
    public function loadModiles()
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
                        error_log( 'ModuleManager::loadModiles: ' . $e->getMessage() );
                    }
                }
            }
            closedir($handle);
        }       
    }    
}
