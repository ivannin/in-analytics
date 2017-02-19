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
     * @const          Параметр "Демографические отчеты"
     */    
    const PARAM_DEMOGRAPHICS = 'demographics';
	
    /**
     * @const          Параметр "Улучшенная атрибуция ссылок"
     */    
    const PARAM_ENHANCED_LINK_ATTRIBUTION = 'enhanced-link-attribution';
	
    /**
     * @const          Параметр "Точный показатель отказов"
     */    
    const PARAM_ACCURATE_BOUNCE_RATE = 'accurate_bounce_rate';
	
    /**
     * @const          Параметр "Категория событий компенсации показателя отказов"
     */    
    const PARAM_ACCURATE_BOUNCE_RATE_EVENT_CATEGORY = 'accurate_bounce_rate_event_category';	
    const PARAM_ACCURATE_BOUNCE_RATE_EVENT_CATEGORY_DEFAULT = 'Sessions';	
	
    /**
     * @const          Параметр "Действие событий компенсации показателя отказов"
     */    
    const PARAM_ACCURATE_BOUNCE_RATE_EVENT_ACTION = 'accurate_bounce_rate_event_action';
    const PARAM_ACCURATE_BOUNCE_RATE_EVENT_ACTION_DEFAULT = 'Session without bounce';

    /**
     * @const          Параметр "Таймер компенсации показателя отказов"
     */    
    const PARAM_ACCURATE_BOUNCE_RATE_TIMER = 'accurate_bounce_rate_timer';
    const PARAM_ACCURATE_BOUNCE_RATE_TIMER_DEFAULT = '15000';
	
    /**
     * @const          Параметр "Парсер Openstat"
     */    
    const PARAM_OPENSTAT = 'openstat';	
	

	/* -------------------- Основная работа -------------------- */
    /**
     * Устанавдиваем обработчики на head или foot
     */    
    public function handle()
    {
		// Вызываем родителя
		parent::handle();
		
		// Ставим наши обработчики
		add_action( 'ina_ga_before_init', 		array( $this, 'showFunction' ) );
		add_action( 'ina_ga_init', 				array( $this, 'showCreate' ) );
		add_action( 'ina_ga_after_init', 		array( $this, 'showHitOptions' ) );
		add_action( 'ina_ga_before_tracking', 	array( $this, 'showOpenstatParser' ) );
		add_action( 'ina_ga_before_tracking', 	array( $this, 'showURLHashRegister' ) );
		add_action( 'ina_ga_tracking', 			array( $this, 'showSendPageView' ) );
		add_action( 'ina_ga_after_tracking',	array( $this, 'showAccurateBounceRate' ) );
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
		if ( $this->getOption( self::PARAM_DEMOGRAPHICS ) )
			$code .= "ga('require', 'displayfeatures');" . PHP_EOL;
		
		// Улучшенная атрибуция ссылок
		if ( $this->getOption( self::PARAM_ENHANCED_LINK_ATTRIBUTION ) )
			$code .= "ga('require', 'linkid');" . PHP_EOL;
		
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
     * Формирует код парсера Openstat
	 * @param INA_GoogleAnalytics	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showOpenstatParser( $tracker )
    {
		if ( ! $this->getOption( self::PARAM_OPENSTAT ) )
			return;		
		
		$code = "var openstat={_params:{},_parsed:!1,_tag:'_openstat',_decode64:function(a){if('function'==typeof window.atob)return atob(a);var c,d,e,f,g,h,i,j,b='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=',k=0,l=0,m='',n=[];if(!a)return a;a+='';do f=b.indexOf(a.charAt(k++)),g=b.indexOf(a.charAt(k++)),h=b.indexOf(a.charAt(k++)),i=b.indexOf(a.charAt(k++)),j=f<<18|g<<12|h<<6|i,c=j>>16&255,d=j>>8&255,e=255&j,64==h?n[l++]=String.fromCharCode(c):64==i?n[l++]=String.fromCharCode(c,d):n[l++]=String.fromCharCode(c,d,e);while(k<a.length);return m=n.join('')},_parse:function(){var a=window.location.search.substr(1),b=a.split('&');this._params={};for(var c=0;c<b.length;c++){var d=b[c].split('=');this._params[d[0]]=d[1]}this._parsed=!0},enabled:function(){return!(window.location.search.indexOf('utm_')>0)&&(this._parsed||this._parse(),'undefined'!=typeof this._params[this._tag])},pushParams:function(a){if(!this.enabled())return a;var b=this._decode64(this._params[this._tag]),c=b.split(';');return a.campaignName=c[1],a.campaignSource=c[0],a.campaignMedium='cpc',a.campaignContent=c[2]+' ('+c[3]+')',a}};openstat.pushParams(gaHitOpt);" . PHP_EOL;
	
		echo apply_filters( 'ina_ga_openstat', $code );
	}	
	
    /**
     * Формирует код регистрации URL хешей в GA
	 * @param INA_GoogleAnalytics	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showURLHashRegister( $tracker )
    {
		
		$code = "gaHitOpt.page=location.pathname+location.search+location.hash;" . PHP_EOL;
	
		echo apply_filters( 'ina_ga_url_hash', $code );
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

    /**
     * Формирование кода точного показателя отказов
	 * @param INA_GoogleAnalytics	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showAccurateBounceRate( $tracker )
    {
		if ( ! $this->getOption( self::PARAM_ACCURATE_BOUNCE_RATE ) )
			return;
		
		$accurateBounceRateTimer = $this->getOption( self::PARAM_ACCURATE_BOUNCE_RATE_TIMER );
		$accurateBounceRateEventCategory = $this->getOption( self::PARAM_ACCURATE_BOUNCE_RATE_EVENT_CATEGORY );
		$accurateBounceRateEventAction = $this->getOption( self::PARAM_ACCURATE_BOUNCE_RATE_EVENT_ACTION );
		
		$code = "document.referrer&&0==document.referrer.split('/')[2].indexOf(location.hostname)||setTimeout(function(){ga('send','event','{$accurateBounceRateEventCategory}','{$accurateBounceRateEventAction}',location.pathname)},{$accurateBounceRateTimer});" . PHP_EOL;
		
		echo apply_filters( 'ina_ga_accurate_bounce_rate', $code );
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
		
		<div class="field-row">
			<label for="demographics"><?php esc_html_e( 'Demographics and Interests Reports', INA_TEXT_DOMAIN)?></label>
			<input id="demographics" name="demographics" type="checkbox" value="1" <?php checked( $this->getOption( self::PARAM_DEMOGRAPHICS ), 1 ); ?> />
				<?php esc_html_e( 'Use the Demographics reports to start with a high-level view of your audience.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://support.google.com/analytics/answer/2819950?hl=en', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
		</div>

		<div class="field-row">
			<label for="enhancedLinkAttribution"><?php esc_html_e( 'Enhanced Link Attribution', INA_TEXT_DOMAIN)?></label>
			<input id="enhancedLinkAttribution" name="enhancedLinkAttribution" type="checkbox" value="1" <?php checked( $this->getOption( self::PARAM_ENHANCED_LINK_ATTRIBUTION ), 1 ); ?> />
				<?php esc_html_e( 'Enhanced Link Attribution improves the accuracy of your In-Page Analytics report by automatically differentiating between multiple links to one page.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://developers.google.com/analytics/devguides/collection/analyticsjs/enhanced-link-attribution', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
		</div>

		<div class="field-row">
			<label for="accurateBounceRate"><?php esc_html_e( 'Accurate Bounce Rate', INA_TEXT_DOMAIN)?></label>
			<input id="accurateBounceRate" name="accurateBounceRate" type="checkbox" value="1" <?php checked( $this->getOption( self::PARAM_ACCURATE_BOUNCE_RATE ), 1 ); ?> />
				<?php esc_html_e( 'Accurating bounce rate metric by additional event by timer.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://ivannikitin.com/2011/09/11/accurance-bounce-rate-google-analytics/', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
		</div>	

		<?php
			$accurateBounceRateTimer = $this->getOption( self::PARAM_ACCURATE_BOUNCE_RATE_TIMER );
			if ( empty( $accurateBounceRateTimer ) ) 
				$accurateBounceRateTimer = self::PARAM_ACCURATE_BOUNCE_RATE_TIMER_DEFAULT;
		?>
		<div class="field-row">
			<label for="accurateBounceRateTimer"><?php esc_html_e( 'Bounce Rate Timer', INA_TEXT_DOMAIN)?></label>
			<input id="accurateBounceRateTimer" name="accurateBounceRateTimer" type="text" value="<?php echo esc_attr( $accurateBounceRateTimer )?>" />
			<p>
				<?php esc_html_e( 'Specify the accurate bounce rate timer value in milliseconds.', INA_TEXT_DOMAIN)?>			
			</p>
		</div>	


		<?php
			$accurateBounceRateEventCategory = $this->getOption( self::PARAM_ACCURATE_BOUNCE_RATE_EVENT_CATEGORY );
			if ( empty( $accurateBounceRateEventCategory ) ) 
				$accurateBounceRateEventCategory = self::PARAM_ACCURATE_BOUNCE_RATE_EVENT_CATEGORY_DEFAULT;
		?>		
		<div class="field-row">
			<label for="accurateBounceRateEventCategory"><?php esc_html_e( 'Bounce Rate Event Category', INA_TEXT_DOMAIN)?></label>
			<input id="accurateBounceRateEventCategory" name="accurateBounceRateEventCategory" type="text" value="<?php echo esc_attr( $accurateBounceRateEventCategory )?>" />
			<p>
				<?php esc_html_e( 'Specify the event category for session without bounce.', INA_TEXT_DOMAIN)?>			
			</p>
		</div>

		
		<?php
			$accurateBounceRateEventAction = $this->getOption( self::PARAM_ACCURATE_BOUNCE_RATE_EVENT_ACTION );
			if ( empty( $accurateBounceRateEventAction ) ) 
				$accurateBounceRateEventAction = self::PARAM_ACCURATE_BOUNCE_RATE_EVENT_ACTION_DEFAULT;
		?>		
		<div class="field-row">
			<label for="accurateBounceRateEventAction"><?php esc_html_e( 'Bounce Rate Event Action', INA_TEXT_DOMAIN)?></label>
			<input id="accurateBounceRateEventAction" name="accurateBounceRateEventAction" type="text" value="<?php echo esc_attr( $accurateBounceRateEventAction )?>" />
			<p>
				<?php esc_html_e( 'Specify the event action for session without bounce.', INA_TEXT_DOMAIN)?>			
			</p>
		</div>
		
		<div class="field-row">
			<label for="accurateBounceRate"><?php esc_html_e( 'Accurate Bounce Rate', INA_TEXT_DOMAIN)?></label>
			<input id="accurateBounceRate" name="accurateBounceRate" type="checkbox" value="1" <?php checked( $this->getOption( self::PARAM_ACCURATE_BOUNCE_RATE ), 1 ); ?> />
				<?php esc_html_e( 'Accurating bounce rate metric by additional event by timer.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://ivannikitin.com/2011/09/11/accurance-bounce-rate-google-analytics/', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
		</div>			

		<div class="field-row">
			<label for="openstat"><?php esc_html_e( 'Openstat Tag Parser', INA_TEXT_DOMAIN)?></label>
			<input id="openstat" name="openstat" type="checkbox" value="1" <?php checked( $this->getOption( self::PARAM_OPENSTAT ), 1 ); ?> />
				<?php esc_html_e( 'Use Openstat tag parser for authomatic marking user sessions from all Yandex ads.', INA_TEXT_DOMAIN)?>				
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
		
		$demographics = isset( $_POST['demographics'] ) ? (bool) sanitize_text_field( $_POST['adminTracking'] ) : false;
		$this->setOption( self::PARAM_DEMOGRAPHICS, $demographics );	

		$enhancedLinkAttribution = isset( $_POST['enhancedLinkAttribution'] ) ? (bool) sanitize_text_field( $_POST['enhancedLinkAttribution'] ) : false;
		$this->setOption( self::PARAM_ENHANCED_LINK_ATTRIBUTION, $enhancedLinkAttribution );

		$accurateBounceRate = isset( $_POST['accurateBounceRate'] ) ? (bool) sanitize_text_field( $_POST['accurateBounceRate'] ) : false;
		$this->setOption( self::PARAM_ACCURATE_BOUNCE_RATE, $accurateBounceRate );
		
		$accurateBounceRateTimer = isset( $_POST['accurateBounceRateTimer'] ) ? sanitize_text_field( $_POST['accurateBounceRateTimer'] ) : self::PARAM_ACCURATE_BOUNCE_RATE_TIMER_DEFAULT;
		$this->setOption( self::PARAM_ACCURATE_BOUNCE_RATE_TIMER, $accurateBounceRateTimer );
		
		$accurateBounceRateEventCategory = isset( $_POST['accurateBounceRateEventCategory'] ) ? sanitize_text_field( $_POST['accurateBounceRateEventCategory'] ) : self::PARAM_ACCURATE_BOUNCE_RATE_EVENT_CATEGORY_DEFAULT;
		$this->setOption( self::PARAM_ACCURATE_BOUNCE_RATE_EVENT_CATEGORY, $accurateBounceRateEventCategory );

		$accurateBounceRateEventAction = isset( $_POST['accurateBounceRateEventAction'] ) ? sanitize_text_field( $_POST['accurateBounceRateEventAction'] ) : self::PARAM_ACCURATE_BOUNCE_RATE_EVENT_ACTION_DEFAULT;
		$this->setOption( self::PARAM_ACCURATE_BOUNCE_RATE_EVENT_ACTION, $accurateBounceRateEventAction );
		
		$openstat = isset( $_POST['openstat'] ) ? (bool) sanitize_text_field( $_POST['openstat'] ) : false;
		$this->setOption( self::PARAM_OPENSTAT, $openstat );		
		
	}		
	
}