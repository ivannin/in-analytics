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
     * @var string      User Login
     */
    public $userLogin;	
	
    /**
     * @var string      User Role
     */
    public $userRole;	
	
	/* -------------------- Параметры модуля -------------------- */
    /**
     * @const          Параметр "User ID в произвольные параметры"
     */    
    const PARAM_DIMENSION_USER_ID 				= 'dimension_user_id';
    const PARAM_DIMENSION_USER_ID_ENABLED 		= 'dimension_user_id_enabled';
	
    /**
     * @const          Параметр "User Role в произвольные параметры"
     */    
    const PARAM_DIMENSION_USER_ROLE 			= 'dimension_user_role';
    const PARAM_DIMENSION_USER_ROLE_ENABLED 	= 'dimension_user_role_enabled';
	
    /**
     * @const          Параметр "User ID в Метрику"
     */    
    const PARAM_METRIKA_USER_ID 				= 'metrika_user_id';
    const PARAM_METRIKA_USER_ID_ENABLED 		= 'metrika_user_id_enabled';
	
    /**
     * @const          Параметр "User Role в Метрику"
     */    
    const PARAM_METRIKA_USER_ROLE 				= 'metrika_user_role';
    const PARAM_METRIKA_USER_ROLE_ENABLED 		= 'metrika_user_role_enabled';	
	
    /**
     * Конструктор класса
     * @param ModuleManager $manager    Менеджер модулей
     */
    public function __construct(INA_ModuleManager $manager) 
    {
		parent::__construct( $manager );
		
		// Свойства модуля
		$this->title 		= __( 'User ID', INA_TEXT_DOMAIN );
		$this->description	= __( 'User ID tracking', INA_TEXT_DOMAIN );
		$this->menuTitle	= __( 'User ID', INA_TEXT_DOMAIN );
		$this->menuOrder 	= 10;
		
		// User Params 
		$this->userId = get_current_user_id();
		$userInfo = get_userdata( $this->userId );
		$this->userLogin = $userInfo->user_login;
		$this->userRole = ( count( $userInfo->roles ) > 0 ) ? $userInfo->roles[0] : '';
    }
	
    /**
     * Устанавливаем обработчики
     */    
    public function handle()
    {
		// Вызываем родителя
		parent::handle();
		
		// Ставим наши обработчики
		add_action( 'ina_ga_before_init', 		array( $this, 'showGAUserIdCode' ) );
		add_action( 'ina_ga_before_tracking', 	array( $this, 'showGAUserDimesionsCode' ) );
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
     * Формирует код пользовательских определений для Google Analytics
	 * @param INA_GoogleAnalytics	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showGAUserDimesionsCode( $tracker )
	{
		if ( empty( $this->userId ) )
			return;
		
		$code = '';
		
		if ( $this->getOption( self::PARAM_DIMENSION_USER_ID_ENABLED ) && ! empty ( $this->getOption( self::PARAM_DIMENSION_USER_ID ) ) )
		{
			$dimUserLogin = $this->getOption( self::PARAM_DIMENSION_USER_ID );
			$code .= "ga('set', '{$dimUserLogin}', '{$this->userLogin}');" . PHP_EOL;			
		}
		
		if ( $this->getOption( self::PARAM_DIMENSION_USER_ROLE_ENABLED ) && ! empty ( $this->getOption( self::PARAM_DIMENSION_USER_ROLE ) ) )
		{
			$dimUserRole = $this->getOption( self::PARAM_DIMENSION_USER_ROLE );
			$code .= "ga('set', '{$dimUserRole}', '{$this->userRole}');" . PHP_EOL;			
		}
		
		echo apply_filters( 'ina_ga_user_dimensions', $code );		
	}	

    /**
     * Формирует код User ID для Метрики
	 * https://yandex.ru/support/metrika/objects/set-user-id.xml
	 * @param INA_YandexMetrika	$tracker	Ссылка на объект модуля (для расширений)
     */    
    public function showMetrikaUserIdCode( $tracker )
	{
		if ( empty( $this->userId ) )
			return;
		
		$metrika = $tracker->getMetrikaVar();
		$code =  "$metrika.setUserID('{$this->userId}');" . PHP_EOL;
		
		$userParams = array();
		if ( $this->getOption( self::PARAM_METRIKA_USER_ID_ENABLED ) && ! empty ( $this->getOption( self::PARAM_METRIKA_USER_ID ) ) )
		{
			$userParams[] = "'" . $this->getOption( self::PARAM_METRIKA_USER_ID ) . "':'" . $this->userLogin . "'";			
		}
		
		if ( $this->getOption( self::PARAM_METRIKA_USER_ROLE_ENABLED ) && ! empty ( $this->getOption( self::PARAM_METRIKA_USER_ROLE ) ) )
		{
			$userParams[] = "'" . $this->getOption( self::PARAM_METRIKA_USER_ROLE ) . "':'" . $this->userRole . "'";		
		}		
		
		if ( count( $userParams ) > 0 )
		{
			$code .= "$metrika.userParams({" . implode(',', $userParams ) . '});' . PHP_EOL;
		}
		
		echo apply_filters( 'ina_metrika_user_id', $code );		
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
			<label for="dimension_user_id_enabled"><?php esc_html_e( 'Send User Login to custom dimension', INA_TEXT_DOMAIN)?></label>
			<input id="dimension_user_id_enabled" name="dimension_user_id_enabled" type="checkbox" value="1" <?php checked( $this->getOption( self::PARAM_DIMENSION_USER_ID_ENABLED ), 1 ); ?> />
				<?php esc_html_e( 'Check this box if you want to send User Login to custom dimension.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://support.google.com/analytics/answer/2709828?hl=en', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
		</div>
		
		<div class="field-row">
			<label for="dimension_user_id"><?php esc_html_e( 'User Login Dimension', INA_TEXT_DOMAIN)?></label>
			<input id="dimension_user_id" name="dimension_user_id" type="text" value="<?php echo esc_attr( $this->getOption( self::PARAM_DIMENSION_USER_ID ) )?>" />
			<p>
				<?php esc_html_e( 'Specify the User Login custom dimension.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://support.google.com/analytics/answer/2709828?hl=en', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
			</p>
		</div>		

		<div class="field-row">
			<label for="dimension_user_role_enabled"><?php esc_html_e( 'Send User Role to custom dimension', INA_TEXT_DOMAIN)?></label>
			<input id="dimension_user_role_enabled" name="dimension_user_role_enabled" type="checkbox" value="1" <?php checked( $this->getOption( self::PARAM_DIMENSION_USER_ROLE_ENABLED ), 1 ); ?> />
				<?php esc_html_e( 'Check this box if you want to send User Role to custom dimension.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://support.google.com/analytics/answer/2709828?hl=en', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
		</div>
		
		<div class="field-row">
			<label for="dimension_user_role"><?php esc_html_e( 'User Role Dimension', INA_TEXT_DOMAIN)?></label>
			<input id="dimension_user_role" name="dimension_user_role" type="text" value="<?php echo esc_attr( $this->getOption( self::PARAM_DIMENSION_USER_ROLE ) )?>" />
			<p>
				<?php esc_html_e( 'Specify the User Role custom dimension.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://support.google.com/analytics/answer/2709828?hl=en', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
			</p>
		</div>	

		<h4><?php esc_html_e( 'Yandex.Metrika', INA_TEXT_DOMAIN)?></h4>
		
		<div class="field-row">
			<label for="metrika_user_id_enabled"><?php esc_html_e( 'Send User Login to Yandex.Metrika', INA_TEXT_DOMAIN)?></label>
			<input id="metrika_user_id_enabled" name="metrika_user_id_enabled" type="checkbox" value="1" <?php checked( $this->getOption( self::PARAM_METRIKA_USER_ID_ENABLED ), 1 ); ?> />
				<?php esc_html_e( 'Check this box if you want to send User Login to custom dimension.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://yandex.ru/support/metrika/data/user-params.xml', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
		</div>
		
		<div class="field-row">
			<label for="metrika_user_id"><?php esc_html_e( 'Yandex.Metrika param for User Login', INA_TEXT_DOMAIN)?></label>
			<input id="metrika_user_id" name="metrika_user_id" type="text" value="<?php echo esc_attr( $this->getOption( self::PARAM_METRIKA_USER_ID ) )?>" />
			<p>
				<?php esc_html_e( 'Specify the User Login custom dimension.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://yandex.ru/support/metrika/data/user-params.xml', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
			</p>
		</div>		

		<div class="field-row">
			<label for="metrika_user_role_enabled"><?php esc_html_e( 'Send User Login to Yandex.Metrika', INA_TEXT_DOMAIN)?></label>
			<input id="metrika_user_role_enabled" name="metrika_user_role_enabled" type="checkbox" value="1" <?php checked( $this->getOption( self::PARAM_METRIKA_USER_ROLE_ENABLED ), 1 ); ?> />
				<?php esc_html_e( 'Check this box if you want to send User Role to custom dimension.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://yandex.ru/support/metrika/data/user-params.xml', INA_TEXT_DOMAIN)?>" target="_blank">
					<?php esc_html_e( 'Read more here', INA_TEXT_DOMAIN)?>
				</a>				
		</div>
		
		<div class="field-row">
			<label for="metrika_user_role"><?php esc_html_e( 'Yandex.Metrika param for User Role', INA_TEXT_DOMAIN)?></label>
			<input id="metrika_user_role" name="metrika_user_role" type="text" value="<?php echo esc_attr( $this->getOption( self::PARAM_METRIKA_USER_ROLE ) )?>" />
			<p>
				<?php esc_html_e( 'Specify the User Role custom dimension.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://yandex.ru/support/metrika/data/user-params.xml', INA_TEXT_DOMAIN)?>" target="_blank">
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

		$dimension_user_id_enabled = isset( $_POST['dimension_user_id_enabled'] ) ? (bool) sanitize_text_field( $_POST['dimension_user_id_enabled'] ) : false;
		$this->setOption( self::PARAM_DIMENSION_USER_ID_ENABLED, $dimension_user_id_enabled );
		
		$dimension_user_id = isset( $_POST['dimension_user_id'] ) ? sanitize_text_field( $_POST['dimension_user_id'] ) : '';
		$this->setOption( self::PARAM_DIMENSION_USER_ID, $dimension_user_id );	

		$dimension_user_role_enabled = isset( $_POST['dimension_user_role_enabled'] ) ? (bool) sanitize_text_field( $_POST['dimension_user_role_enabled'] ) : false;
		$this->setOption( self::PARAM_DIMENSION_USER_ROLE_ENABLED, $dimension_user_role_enabled );
		
		$dimension_user_role = isset( $_POST['dimension_user_id'] ) ? sanitize_text_field( $_POST['dimension_user_role'] ) : '';
		$this->setOption( self::PARAM_DIMENSION_USER_ROLE, $dimension_user_role );	
		
		$metrika_user_id_enabled = isset( $_POST['metrika_user_id_enabled'] ) ? (bool) sanitize_text_field( $_POST['metrika_user_id_enabled'] ) : false;
		$this->setOption( self::PARAM_METRIKA_USER_ID_ENABLED, $metrika_user_id_enabled );
		
		$metrika_user_id = isset( $_POST['metrika_user_id'] ) ? sanitize_text_field( $_POST['metrika_user_id'] ) : '';
		$this->setOption( self::PARAM_METRIKA_USER_ID, $metrika_user_id );	

		$metrika_user_role_enabled = isset( $_POST['metrika_user_role_enabled'] ) ? (bool) sanitize_text_field( $_POST['metrika_user_role_enabled'] ) : false;
		$this->setOption( self::PARAM_METRIKA_USER_ROLE_ENABLED, $metrika_user_role_enabled );
		
		$metrika_user_role = isset( $_POST['metrika_user_id'] ) ? sanitize_text_field( $_POST['metrika_user_role'] ) : '';
		$this->setOption( self::PARAM_METRIKA_USER_ROLE, $metrika_user_role );		
	}	
}