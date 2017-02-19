<?php
/**
 * Базовый класс модуля
 */
class INA_ModuleBase
{
    /**
     * @var string      Название модуля
     */
    public $title;

    /**
     * @var string      Краткое описание модуля модуля
     */
    public $description;

    /**
     * @var string      Название модуля для меню
     */
    public $menuTitle;	
	
    /**
     * @var int         Порядок в меню
     */
    public $menuOrder = 10;	
		
    /**
     * @var ModuleManager $manager      Ссылка на менеджера модулей
     */
    protected $manager;
    
    /**
     * Конструктор класса
     * @param ModuleManager $manager    Менеджер модулей
     */
    public function __construct(INA_ModuleManager $manager) 
    {
        // Сохраним ссылку на менеджера модулей
        $this->manager = $manager;

    }
	
	/* -------------------- Доступность модуля -------------------- */
	
    /**
     * @const ENABLED          Параметр "Модуль доступен"
     */    
    const ENABLED = 'ina_settings';	
	
    /**
     * Возвращает доступность модуля
     * @return bool     
     */    
    public function isEnabled()
    {
        return $this->getOption( self::ENABLED );
    }

    /**
     * Устанавливает доступность модуля
     * @param bool  $value         Значение параметра
     */    
    public function setEnabled( $value )
    {
        $this->setOption( self::ENABLED, $value );
    }	
	
	/* -------------------- Основная работа -------------------- */
    /**
     * Выполняет необходимые действия модуля после инициализации системы   
     */    
    public function handle()
    {
		// По умлочанию ничего не делаем
    }

	
	/* -------------------- Сервисные функции -------------------- */
    /**
     * Возвращает параметр настройки
     * @param string $optionName    Имя параметра
     * @return mixed     
     */    
    protected function getOption( $optionName )
    {
        return $this->manager->getOption( get_class( $this ), $optionName );
    }
    
    /**
     * Устанавливает параметр настройки
     * @param string $optionName    Имя параметра
     * @param mixed  $value         Значение параметра
     */    
    public function setOption( $optionName, $value )
    {
        $this->manager->setOption( get_class( $this ), $optionName, $value );
    }
	
    /**
     * Сохраняет параметры настройки
     */    
    public function saveOptions()
    {
        $this->manager->saveOptions();
    }	
	
    /**
     * Загружает js файл из папки js
     * @param string $fileName		Имя файда
     * @return string				Возвращает содержимое файла
     */    
    protected function loadJS( $fileName )
    {
		// Полный путь к файлу
		$fileName = $this->manager->baseDir . 'js/' . $fileName;
		
		// Проверяем наличие минимизированной версии, если это не отладочный режим
		if ( ! WP_DEBUG )
		{
			$fileNameMinimized = str_replace('.js', '.min.js', $fileName);
			if ( file_exists( $fileNameMinimized ) )
				$fileName = $fileNameMinimized;
		}

		// Если имя файла пустое или нет файла, возвращает пусто или отладочную надпись
		if ( ! file_exists( $fileName ) )
			return ( WP_DEBUG ) ? '/*! ' . get_class($this) . ' : ' . $fileName . '- JS file not found !*/' : '';		
		
		// Возвращаем файл
		return file_get_contents( $fileName );
    }
	
    
	/* ------------ Страница настроек модуля ------------ */
	
    /**
     * @const           Поле nonce для доступа в форме
     */    
    const NONCE = 'ina_nonce';
	
    /**
     * Формирует страницу настроек модуля
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
				
				// Читаем параметры формы
				$this->readOptionForm();
				
				// Сохраняем параметры
				$this->saveOptions();
			}
		}
	?>
	<a  class="ina-logo" href="<?php esc_html_e( 'http://in-analytics.com', INA_TEXT_DOMAIN)?>" title="<?php esc_html_e( 'Visit the official IN-Analytics site', INA_TEXT_DOMAIN)?>">
		<img src="<?php echo $this->manager->baseURL ?>img/in-analytics-transparent-100x83.png" />
	</a>
	<form action="<?php echo $_SERVER['REQUEST_URI']?>" method="post" class="ina-settings">
		<?php wp_nonce_field( get_class( $this ), self::NONCE ) ?>
		<?php $this->showOptionForm() ?>
		<?php submit_button() ?>
	</form>
	<?php			
	}

    /**
     * Читает содержимое формы настроек модуля
     */    
    public function readOptionForm()
    {
		// Nothing
	}		
	
    /**
     * Формирует содержимое формы настроек модуля
     */    
    public function showOptionForm() 
	{ ?>
		<h2><?php echo $this->title?></h2>
		<p><?php echo $this->description?></p>		
	<?php 
	}	
	
}
