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
     * @var int         Порядок в меню
     */
    public $menuOrder = 10;	
		
    /**
     * @var ModuleManager $manager      Ссылка на менеджера модулей
     */
    protected $manager;
	
    /**
     * @var string      JavaScript в шапке
     */
    protected $jsHead;
	
    /**
     * @var string      Другой код в шапке
     */
    protected $otherHead;

    /**
     * @var string      JavaScript в подвале
     */
    protected $jsFoot;
	
    /**
     * @var string      Другой код в подвале
     */
    protected $otherFoot;
	
    
    /**
     * Конструктор класса
     * @param ModuleManager $manager    Менеджер модулей
     */
    public function __construct(INA_ModuleManager $manager) 
    {
        // Сохраним ссылку на менеджера модулей
        $this->manager = $manager;
		
		// Инициализация кода
		$this->jsHead 		= '';
		$this->otherHead 	= '';
		$this->jsFoot 		= '';
		$this->otherFoot	= '';
    }
    
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
	
	/* ------------ Методы модуля, возвращающие код ------------ */
    /**
     * Возвращает JavaScript шапки
     */    
    public function getJSHead()
    {
		if ( $this->isEnabled() )
			return apply_filters(get_class( $this ) . '_head_js',  $this->jsHead);
    }	
    /**
     * Возвращает другой код шапки
     */    
    public function getOtherHead()
    {
		if ( $this->isEnabled() )
			return apply_filters(get_class( $this ) . '_head_other',  $this->otherHead);
    }	
    /**
     * Возвращает JavaScript подвала
     */    
    public function getJSFoot()
    {
		if ( $this->isEnabled() )
			return apply_filters(get_class( $this ) . '_foot_js',  $this->jsFoot);
    }	
    /**
     * Возвращает другой код подвала
     */    
    public function getOtherFoot()
    {
		if ( $this->isEnabled() )
			return apply_filters(get_class( $this ) . '_foot_other',  $this->otherFoot);
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
    
	
}
