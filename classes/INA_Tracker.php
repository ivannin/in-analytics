<?php
/**
 * Базовый класс модуля трекера (Google Analytics, Метрика)
 * Реализует общие для трекеров хуки
 */
class INA_Tracker extends INA_ModuleBase
{
    /**
     * @const          Параметр "Tracker ID"
     */    
    const PARAM_TRACKER_ID = 'tracker_id';	
	
    /**
     * Возвращает Tracker ID
     */    
    public function getId()
    {
		return $this->getOption( self::PARAM_TRACKER_ID );
	}
	
    /**
     * @const          Параметр "расположение кода трекера"
     */    
    const PARAM_LOCATION = 'location';
	
    /**
     * @const          Значение параметра "расположение кода трекера в HEAD"
     */    
    const PARAM_LOCATION_HEAD = 'head';
	
    /**
     * @const          Значение параметра "расположение кода трекера в Footer"
     */    
    const PARAM_LOCATION_FOOTER = 'footer';		
	
    /**
     * @const          Значение параметра "Отслеживать в админке"
     */    
    const PARAM_ADMIN_TRACKING = 'admin_tracking';	
	
    /**
     * Устанавдивает обработчики на head или foot
     */    
    public function handle()
    {
		// Расположение кода GA
		if ( $this->getOption( self::PARAM_LOCATION ) != self::PARAM_LOCATION_FOOTER )
		{
			// Размещение кода GA в HEAD
			add_action( 'wp_head', array( $this, 'showCode' ) );
			
			// Размещение кода в админке
			if ( $this->getOption( self::PARAM_ADMIN_TRACKING ) )
				add_action( 'admin_head', array( $this, 'showCode' ) );
			
		}
		else
		{
			// Размещение кода GA в FOOTER			
			add_action( 'wp_footer', array( $this, 'showCode' ) );
			
			// Размещение кода в админке
			if ( $this->getOption( self::PARAM_ADMIN_TRACKING ) )
				add_action( 'admin_footer', array( $this, 'showCode' ) );			
		}
    }	
	
    /**
     * Формирует код трекера
     */    
    public function showCode()
    {
		// Ничего. Перекрывается реальным трекером
	}
	
	/* ------------ Параметры модуля ------------ */
    /**
     * Формирует содержимое формы настроек модуля
     */    
    public function showOptionForm() 
	{ ?>
		<h2><?php echo $this->title?></h2>
		<p><?php echo $this->description?></p>	
		
		<div class="field-row">
			<label for="tracker_id"><?php esc_html_e( 'Tracking ID', INA_TEXT_DOMAIN)?></label>
			<input id="tracker_id" name="tracker_id" type="text" value="<?php echo esc_attr( $this->getId() )?>" />
			<p>
				<?php echo esc_html( __( 'Specify the ID of', INA_TEXT_DOMAIN ) . ' ' . $this->title )?>				
			</p>
		</div>		

		<div class="field-row">
			<label for="codeLocation"><?php echo esc_html( $this->title . ' ' . __( 'tracking code location', INA_TEXT_DOMAIN ) )?></label>
			<select id="codeLocation" name="codeLocation">
				<option value="<?php echo esc_attr( self::PARAM_LOCATION_HEAD )?>" <?php selected( $this->getOption( self::PARAM_LOCATION ), self::PARAM_LOCATION_HEAD) ?>><?php esc_html_e( 'HEAD', INA_TEXT_DOMAIN)?></option>
				<option value="<?php echo esc_attr( self::PARAM_LOCATION_FOOTER )?>" <?php selected( $this->getOption( self::PARAM_LOCATION ), self::PARAM_LOCATION_FOOTER) ?>><?php esc_html_e( 'Footer', INA_TEXT_DOMAIN)?></option>
			</select>
		</div>
		
		<div class="field-row">
			<label for="adminTracking"><?php esc_html_e( 'Tracking Wordpress Admin pages', INA_TEXT_DOMAIN)?></label>
			<input id="adminTracking" name="adminTracking" type="checkbox" value="1" <?php checked( $this->getOption( self::PARAM_ADMIN_TRACKING ), 1 ); ?> />
				<?php esc_html_e( 'Put tracking code at all Wordpress Admin pages.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( '#', INA_TEXT_DOMAIN)?>" target="_blank">
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
		$tracker_id = isset( $_POST['tracker_id'] ) ? sanitize_text_field( $_POST['tracker_id'] ) : '';
		$this->setOption( self::PARAM_TRACKER_ID, $tracker_id );
		
		$codeLocation = isset( $_POST['codeLocation'] ) ? sanitize_text_field( $_POST['codeLocation'] ) : self::PARAM_LOCATION_HEAD;
		$this->setOption( self::PARAM_LOCATION, $codeLocation );
		
		$adminTracking = isset( $_POST['adminTracking'] ) ? (bool) sanitize_text_field( $_POST['adminTracking'] ) : false;
		$this->setOption( self::PARAM_ADMIN_TRACKING, $adminTracking );		
		
	}		
}
