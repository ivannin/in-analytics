<?php
/**
 * Базовый класс модуля
 */
class ModuleBase
{
    /**
     * @var ModuleManager $manager      Ссылка на менеджера модулей
     */
    protected $manager;
    
    /**
     * Конструктор класса
     * @param ModuleManager $manager    Менеджер модулей
     */
    public function __construct(ModuleManager $manager) 
    {
        // Сохраним ссылку на менеджера модулей
        $this->manager = $manager;
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
    public function setOption( $moduleName, $optionName, $value )
    {
        $this->manager->setOption( get_class( $this ), $optionName, $value );
    }    
    
}
