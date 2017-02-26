<?php
/**
 * Модуль Яндекс.Метрика
 */
class INA_YandexMetrika extends INA_Tracker
{
    /**
     * Конструктор класса
     * @param ModuleManager $manager    Менеджер модулей
     */
    public function __construct(INA_ModuleManager $manager) 
    {
		parent::__construct( $manager );
		
		// Свойства модуля
		$this->title 		= __( 'Yandex.Metrika', INA_TEXT_DOMAIN );
		$this->description	= __( 'Basic features of Yandex.Metrika tracker', INA_TEXT_DOMAIN );
		$this->menuTitle	= __( 'Yandex.Metrika', INA_TEXT_DOMAIN );
		$this->menuOrder 	= 2;
    }
	/* -------------------- Параметры модуля -------------------- */
    /**
     * @const          Параметр "Вебвизор, карта скроллинга, аналитика форм"
     */    
    const PARAM_WEBVISOR = 'webvisor';
	
    /**
     * @const          Параметр "Запрет отправки на индексацию страниц сайта"
     */    
    const PARAM_NOINDEX = 'noindex';	

	
	/* -------------------- Основная работа -------------------- */
    /**
     * Устанавдиваем обработчики на head или foot
     */    
    public function handle()
    {
		// Вызываем родителя
		parent::handle();
		
		// Ставим наши обработчики
		add_action( 'ina_metrika_load', 		array( $this, 'showLoadScript' ) );		
		add_action( 'ina_metrika_before_init', 	array( $this, 'showMetrikaOptions' ) );		
		add_action( 'ina_metrika_init', 		array( $this, 'showMetrikaInit' ) );		
    }

    /**
     * Формирует код Google Analytics
     */    
    public function showCode()
    {
		if (WP_DEBUG) echo '<!-- IN-Analytics: ', get_class( $this ), ' -->', PHP_EOL;
		
		parent::showCode();
		
		do_action( 'ina_metrika_load', 				$this );
		echo '<script>', PHP_EOL;
		do_action( 'ina_metrika_before_init', 		$this );
		do_action( 'ina_metrika_init', 				$this );
		do_action( 'ina_metrika_after_init', 		$this );
		echo '</script>', PHP_EOL;
		if (WP_DEBUG) echo '<!--/IN-Analytics: ', get_class( $this ), ' -->', PHP_EOL;
	}
	
    /**
     * Формирует код загрузки метрики
	 * https://yandex.ru/support/metrika/code/counter-initialize.xml
	 * @param INA_YandexMetrika	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showLoadScript( $tracker )
    {
		$code = '<script src="//mc.yandex.ru/metrika/watch.js" type="text/javascript"></script>' . PHP_EOL;
		echo apply_filters( 'ina_metrika_load_script', $code );
	}
	
    /**
     * Возвращает имя переменной трекера Метрики
     */    
    public function getMetrikaVar()
    {
		$var = 'yaCounter' . $this->getId();
		return apply_filters( 'ina_metrika_counter_variable', $var );
	}	
	

    /**
     * Формирует код пред инициализации метрики
	 * https://yandex.ru/support/metrika/code/counter-initialize.xml
	 * @param INA_YandexMetrika	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showMetrikaOptions( $tracker )
    {
		$id = $this->getId();
		$webvisor = $this->getOption( self::PARAM_WEBVISOR ) ? ',webvisor:true' : '';
		$code = "var yaCounterOptions={id:{$id},clickmap:true,trackLinks:true,accurateTrackBounce:true,trackHash:true{$webvisor}};" . PHP_EOL;
		echo apply_filters( 'ina_metrika_options_code', $code );
	}	
	
    /**
     * Формирует код инициализации метрики
	 * https://yandex.ru/support/metrika/code/counter-initialize.xml
	 * @param INA_YandexMetrika	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showMetrikaInit( $tracker )
    {
		$counter = $this->getMetrikaVar();
		$code = "var {$counter}=new Ya.Metrika(yaCounterOptions);" . PHP_EOL;
		echo apply_filters( 'ina_metrika_init_code', $code );
	}	

	/* ------------ Параметры модуля ------------ */
    /**
     * Формирует содержимое формы настроек модуля
     */    
    public function showOptionForm() 
	{ 
		parent::showOptionForm();
	?>
		<div class="field-row">
			<label for="webvisor"><?php esc_html_e( 'Webvisor', INA_TEXT_DOMAIN)?></label>
			<input id="webvisor" name="webvisor" type="checkbox" value="1" <?php checked( $this->getOption( self::PARAM_WEBVISOR ), 1 ); ?> />
				<?php esc_html_e( 'Enable WebVisor.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://yandex.ru/support/metrika/general/counter-webvisor.xml', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
		</div>		
	
	<?php 
	}
	
    /**
     * Читает содержимое формы настроек модуля
     */    
    public function readOptionForm()
    {
		parent::readOptionForm();

		$webvisor = isset( $_POST['webvisor'] ) ? (bool) sanitize_text_field( $_POST['webvisor'] ) : false;
		$this->setOption( self::PARAM_WEBVISOR, $webvisor );		
		
	}	
}