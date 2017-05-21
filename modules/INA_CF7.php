<?php
/**
 * Модуль отслеживания Форм Contact Form 7
 */
class INA_CF7 extends INA_ModuleBase
{
	/* -------------------- Параметры модуля -------------------- */
    /**
     * @const          Параметр "Категория событий Google Analytics"
     */    
    const PARAM_GA_EVENT_CATEGORY 				= 'ga_category';
	
    /**
     * @const          Параметр "Категория событий Google Analytics"
     */    
    const PARAM_GA_EVENT_ACTION_SEND 			= 'ga_action_send';


    /**
     * @var string      Категория событий GA
     */
    public $gaEventCategory;

    /**
     * @var string      Действие события отправка GA 
     */
    public $gaEventActionSend;
	
	
    /**
     * @var INA_MeasurementProtocol      Объект протолока Measurement Protocol 
     */
    public $measurementProtocol;	
	
	
    /**
     * Конструктор класса
     * @param ModuleManager $manager    Менеджер модулей
     */
    public function __construct(INA_ModuleManager $manager) 
    {
		parent::__construct( $manager );
		
		// Свойства модуля
		$this->title 		= __( 'Concact Form 7', INA_TEXT_DOMAIN );
		$this->description	= __( 'Concact Form 7 integration', INA_TEXT_DOMAIN );
		$this->menuTitle	= __( 'Concact Form 7 Integration', INA_TEXT_DOMAIN );
		$this->menuOrder 	= 20;
		
		// Google Analytics
		$this->gaEventCategory 		= ( ! empty( $this->getOption( self::PARAM_GA_EVENT_CATEGORY ) ) ) ? $this->getOption( self::PARAM_GA_EVENT_CATEGORY ) : __( 'Forms', INA_TEXT_DOMAIN );
		$this->gaEventActionSend 	= ( ! empty( $this->getOption( self::PARAM_GA_EVENT_ACTION_SEND ) ) ) ? $this->getOption( self::PARAM_GA_EVENT_ACTION_SEND ) : __( 'Send', INA_TEXT_DOMAIN );

    }
	
    /**
     * Устанавливаем обработчики
     */    
    public function handle()
    {
		// Вызываем родителя
		parent::handle();

		// Если включен GA, нициализируем Measurement Protocol
		if ( $this->manager->modules['INA_GoogleAnalytics'] )
		{
			$ga = $this->manager->modules['INA_GoogleAnalytics'];
			$gaId = $ga->getId();
			$uid = get_current_user_id();
			
			if ( ! empty( $gaId ) )
				$this->measurementProtocol = new INA_MeasurementProtocol( $gaId, $uid );
		}
	
	
		// Ставим обработчик
		add_action( 'wpcf7_before_send_mail', 	array( $this, 'formDataSend' ) );				// Отравка формы

    }
	
    /**
     * Успешная отправка формы
	 * @param mixed	$cf7	Объект формы	 
     */    
    public function formDataSend( $cf7 )
	{
		// ОБЯЗАТЕЛЬНО В БЛОКЕ TRY-CATCH. Если будут ошибки, ломаются все формы на сайте!
		try
		{
			// Передача формы в GA
			if ( $this->measurementProtocol  )
			{
				$this->measurementProtocol->sendEvent( 
					$this->gaEventCategory, 
					$this->gaEventActionSend, 
					$cf7->title() 
				);
			}
			
		}
		catch ( Exception $e )
		{
			// Была ошибка!
			WP_DEBUG && file_put_contents( $this->manager->baseDir . strtolower( get_class( $this ) ) . '.error.log', $e->getMessage() . PHP_EOL, FILE_APPEND );
		}
		return true;
	}	
	
	/* ------------ Параметры модуля ------------ */
    /**
     * Формирует содержимое формы настроек модуля
     */    
    public function showOptionForm() 
	{ 
		parent::showOptionForm();
	?>
		<h4><?php esc_html_e( 'Google Analytics', INA_TEXT_DOMAIN)?></h4>
		
		<div class="field-row">
			<label for="ga_category"><?php esc_html_e( 'Event Category', INA_TEXT_DOMAIN) ?></label>
			<input id="ga_category" name="ga_category" type="text" value="<?php echo esc_attr( $this->gaEventCategory ) ?>" />
			<p>
				<?php esc_html_e( 'Specify the event category.', INA_TEXT_DOMAIN)?>				
			</p>
		</div>		

		<div class="field-row">
			<label for="ga_action_send"><?php esc_html_e( 'Event Category', INA_TEXT_DOMAIN) ?></label>
			<input id="ga_action_send" name="ga_action_send" type="text" value="<?php echo esc_attr( $this->gaEventActionSend ) ?>" />
			<p>
				<?php esc_html_e( 'Specify the event category.', INA_TEXT_DOMAIN)?>				
			</p>
		</div>	
	<?php 
	}
	
    /**
     * Читает содержимое формы настроек модуля
     */    
    public function readOptionForm()
    {
		parent::readOptionForm();

		if ( isset( $_POST['ga_category'] ) )
		{
			$this->gaEventCategory = sanitize_text_field( $_POST['ga_category'] );
			$this->setOption( self::PARAM_GA_EVENT_CATEGORY, $this->gaEventCategory );
		}
	
		if ( isset( $_POST['ga_action_send'] ) )
		{
			$this->gaEventActionSend = sanitize_text_field( $_POST['ga_action_send'] );
			$this->setOption( self::PARAM_GA_EVENT_ACTION_SEND, $this->gaEventActionSend );
		}		
	}
}