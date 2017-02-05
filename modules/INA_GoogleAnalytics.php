<?php
/**
 * Базовый класс модуля
 */
class INA_GoogleAnalytics extends INA_Tracker
{
    /**
     * Конструктор класса
     * @param ModuleManager $manager    Менеджер модулей
     */
    public function __construct(INA_ModuleManager $manager) 
    {
		parent::__construct( $manager );
		
		// Свойства модуля
		$this->title 		= __( 'Google Analytics', INA_TEXT_DOMAIN );
		$this->description	= __( 'Basic features of Google Analytics tracker', INA_TEXT_DOMAIN );
		$this->menuTitle	= __( 'Google Analytics', INA_TEXT_DOMAIN );
		$this->menuOrder 	= 1;
    }
	
	/* -------------------- Параметры модуля -------------------- */
    /**
     * @const          Параметр "Домен для куки"
     */    
    const PARAM_COOKIE_DOMAIN = 'cookie_domain';
	
    /**
     * @const          Параметр "Домены для междоменного отслеживания"
     */    
    const PARAM_CROSS_DOMAINS = 'cross_domains';
	
    /**
     * @const          Параметр "Дедографические отчеты"
     */    
    const PARAM_DEMOGRAPHICS = 'demographics';	
	

	/* -------------------- Основная работа -------------------- */
    /**
     * Устанавдиваем обработчики на head или foot
     */    
    public function handle()
    {
		// Вызываем родителя
		parent::handle();
		
		// Ставим наши обработчики
		add_action( 'ina_ga_before_init', 	array( $this, 'showFunction' ) );
		add_action( 'ina_ga_init', 			array( $this, 'showCreate' ) );
		add_action( 'ina_ga_after_init', 	array( $this, 'showHitOptions' ) );
		add_action( 'ina_ga_tracking', 		array( $this, 'showSendPageView' ) );
    }

    /**
     * Формирует код Google Analytics
     */    
    public function showCode()
    {
		if (WP_DEBUG) echo '<!-- IN-Analytics: ', get_class( $this ), ' -->', PHP_EOL;
		
		parent::showCode();
		
		echo '<script>', PHP_EOL;
		do_action( 'ina_ga_before_init', 		$this );
		do_action( 'ina_ga_init', 				$this );
		do_action( 'ina_ga_after_init', 		$this );
		do_action( 'ina_ga_before_tracking', 	$this );
		do_action( 'ina_ga_tracking', 			$this );
		do_action( 'ina_ga_after_tracking', 	$this );
		echo '</script>', PHP_EOL;
		if (WP_DEBUG) echo '<!--/IN-Analytics: ', get_class( $this ), ' -->', PHP_EOL;
	}
	
	
    /**
     * Формирует код функции Google Analytics
	 * @param INA_GoogleAnalytics	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showFunction( $tracker )
    {
		$code = "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
var gaOpt={};" . PHP_EOL;

		echo apply_filters( 'ina_ga_function', $code );
	}

    /**
     * Формирует код Create Tracker Google Analytics
	 * @param INA_GoogleAnalytics	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showCreate( $tracker )
    {
		// Домен куки
		$cookieDomain = $this->getOption( self::PARAM_COOKIE_DOMAIN );
		if ( empty( $cookieDomain )) $cookieDomain = 'auto';
		
		$code = "gaOpt.cookieDomain='$cookieDomain';"  . PHP_EOL;
		
		$gaId = $this->getId();
		if ( empty ($gaId) ) 
			echo '/* ',  esc_html__( 'ATTENTION! Google Analytics ID is empty!', INA_TEXT_DOMAIN ), ' */', PHP_EOL;
		
		$code .= "ga('create', '$gaId', gaOpt);" . PHP_EOL;
		
		// Кросс-доменное отслеживание
		$domains = explode ( "\n", $this->getOption( self::PARAM_CROSS_DOMAINS ) );
		if ( count( $domains ) > 0 )
		{
			$code .= "ga('require', 'linker');" . PHP_EOL;
			$code .= "ga('linker:autoLink', " . json_encode( $domains ) . " );" . PHP_EOL;
		}
		
		// Демографические отчеты
		
		
		echo apply_filters( 'ina_ga_create', $code );
	}

    /**
     * Формирует код переменной параметра хита
	 * @param INA_GoogleAnalytics	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showHitOptions( $tracker )
    {
		$code = "var gaHitOpt={};" . PHP_EOL;
		
		echo apply_filters( 'ina_ga_hit_options', $code );
	}
	
    /**
     * Формирует код отправки хита pageview
	 * @param INA_GoogleAnalytics	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showSendPageView( $tracker )
    {
		$code = "ga('send', 'pageview', gaHitOpt);" . PHP_EOL;
		
		echo apply_filters( 'ina_ga_pageview', $code );
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
			<label for="cookieDomain"><?php esc_html_e( 'Google Analytics cookie domain', INA_TEXT_DOMAIN)?></label>
			<input id="cookieDomain" name="cookieDomain" type="text" value="<?php echo esc_attr( $this->getOption( self::PARAM_COOKIE_DOMAIN ) )?>" />
			<p>
				<?php esc_html_e( 'Specify the Google Analytics cookie domain or leave blank for auto select.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://developers.google.com/analytics/devguides/collection/analyticsjs/cookies-user-id', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
			</p>
		</div>

		<div class="field-row">
			<label for="crossDomains"><?php esc_html_e( 'Domains list for cross-domain tracking', INA_TEXT_DOMAIN)?></label>
			<textarea id="crossDomains" name="crossDomains"><?php echo esc_textarea( $this->getOption( self::PARAM_CROSS_DOMAINS ) )?></textarea>
			<p>
				<?php esc_html_e( 'Specify all domains for cross-domain tracking or leave blank to disable cross-domain tracking. Specify one domain per line.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://support.google.com/analytics/answer/1034342', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>
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
		
		$cookieDomain = isset( $_POST['cookieDomain'] ) ? sanitize_text_field( $_POST['cookieDomain'] ) : 'auto';
		$this->setOption( self::PARAM_COOKIE_DOMAIN, $cookieDomain );
		
		$crossDomains = implode( "\n", array_map( 'sanitize_text_field', explode( "\n", $_POST['crossDomains'] ) ) );
		$this->setOption( self::PARAM_CROSS_DOMAINS, $crossDomains );
		
		
	}		
	

	
}