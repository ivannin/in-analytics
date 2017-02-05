<?php
/**
 * Модуль отслеживания User ID
 */
class INA_User_ID extends INA_ModuleBase
{
    /**
     * @var string      User ID
     */
    public $userId;	
	
	
    /**
     * Конструктор класса
     * @param ModuleManager $manager    Менеджер модулей
     */
    public function __construct(INA_ModuleManager $manager) 
    {
		parent::__construct( $manager );
		
		// Свойства модуля
		$this->title 		= __( 'User ID', INA_TEXT_DOMAIN );
		$this->description	= __( 'User ID cross-device tracking', INA_TEXT_DOMAIN );
		$this->menuTitle	= __( 'User ID', INA_TEXT_DOMAIN );
		$this->menuOrder 	= 10;
		
		// User ID 
		$this->userId = get_current_user_id();
    }
	
    /**
     * Устанавдивает обработчики
     */    
    public function handle()
    {
		// Вызываем родителя
		parent::handle();
		
		// Ставим наши обработчики
		add_action( 'ina_ga_before_init', 		array( $this, 'showGAUserIdCode' ) );	
		add_action( 'ina_metrika_after_init', 	array( $this, 'showMetrikaUserIdCode' ) );	
    }

    /**
     * Формирует код User ID для Google Analytics
	 * @param INA_GoogleAnalytics	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showGAUserIdCode( $tracker )
	{
		$code = ( ! empty( $this->userId ) ) ? "gaOpt.userId='{$this->userId}';" . PHP_EOL : '';
		echo apply_filters( 'ina_ga_user_id', $code );		
	}

    /**
     * Формирует код User ID для Метрики
	 * https://yandex.ru/support/metrika/objects/set-user-id.xml
	 * @param INA_YandexMetrika	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showMetrikaUserIdCode( $tracker )
	{
		$metrika = $tracker->getMetrikaVar();
		$code = ( ! empty( $this->userId ) ) ? "$metrika.setUserID('{$this->userId}');" . PHP_EOL : '';
		echo apply_filters( 'ina_metrika_user_id', $code );		
	}	

	
}