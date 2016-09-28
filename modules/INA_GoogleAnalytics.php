<?php
/**
 * Базовый класс модуля
 */
class INA_GoogleAnalytics extends INA_ModuleBase
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
		$this->description	= __( 'Basic settings of Google Analytics tracker', INA_TEXT_DOMAIN );
		$this->menuOrder 	= 1;
		
		// Подготовка скрипта для head
		$this->jsHead = $this->prepareJsHead();
    }
	
    /**
     * @const          Параметр "GA ID"
     */    
    const PARAM_GA_ID = 'ga_id';		

    /**
     * @const          Параметр "Домен для куки"
     */    
    const PARAM_COOKIE_DOMAIN = 'cookie_domain';

    /**
     * @const          Параметр "Режим User ID"
     */    
    const PARAM_USER_ID_ENABLED = 'user_id_enabled';


	
    /**
     * Подготавливает скрипт для HEAD
     * @return bool     
     */    
    protected function prepareJsHead()
    {
        $js = $this->loadJs( 'google-analytics.js' );
		
		// Параметры для замены
		$params = array();		
		
		// Google Analytics ID
		$gaId = '123123';//$this->getOption( self::PARAM_GA_ID );
		if ( empty ( $gaId ))
			return ( WP_DEBUG ) ? '/* INA_GoogleAnalytics: Parameter Google Analytics ID not specified! */' : '';
		$params['/*GOOGLE_ID*/'] = $gaId;
		
		// CookieDomain
		$cookieDomain = $this->getOption( self::PARAM_COOKIE_DOMAIN );
		$params['/*COOKIEDOMAIN*/'] = ( empty( $cookieDomain ) ) ? 'auto' : $cookieDomain;
		
		// User ID
		global $current_user; // Информация о пользователе и его ролях
		$userId = $current_user->ID;
		$userLogin = $current_user->user_login;
		$userRoles = $current_user->roles;
		$userRoleId = array_shift($userRoles);
		
		// Получаем название роли пользователя 
		global $wp_roles;
		$user_role = (!empty($userId)) ? translate_user_role($wp_roles->roles[$userRoleId]['name']) : '';
		
		if ( $this->getOption( self::PARAM_USER_ID_ENABLED ) && $userId != 0)
		{
			$params['/*USER_ID*/'] = "gaOpt.userId = '$userId';";
		}
		else
		{
			// Стираем с удалением строки из исходного файла
			$params["/*USER_ID*/\r\n"] = '';
		}
		
		
		
		// Замена и возврат результата
		return str_replace ( array_keys( $params ), array_values( $params )  , $js );
    }	
}