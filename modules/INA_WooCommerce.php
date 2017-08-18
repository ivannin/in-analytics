<?php
/**
 * Модуль интеграции с WooCommerce
 */
class INA_WooCommerce extends INA_ModuleBase
{
	/* -------------------- Параметры модуля -------------------- */
    /**
     * @const          Параметр "Тип режима E-Commerce в Google Analytics"
     */    
    const PARAM_GA_ECOMMERCE_MODE 				= 'ecommerce_mode';

    /**
     * @const          Параметр "Стандартный режим"
     */    
    const PARAM_GA_ECOMMERCE_MODE_STANDART		= 'standart';
	
    /**
     * @const          Параметр "Расширенный режим"
     */    
    const PARAM_GA_ECOMMERCE_MODE_ENHANCED		= 'enhanced';	
	
	
    /**
     * @var string      Режим электронной коммерации
     */
    public $ecommerceMode;
	
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
		$this->title 		= __( 'WooCommerce Integration', INA_TEXT_DOMAIN );
		$this->description	= __( 'WooCommerce Integration', INA_TEXT_DOMAIN );
		$this->menuTitle	= __( 'WooCommerce', INA_TEXT_DOMAIN );
		$this->menuOrder 	= 30;
		
		// Режим электронной коммерации
		$this->ecommerceMode = ( ! empty( $this->getOption( self::PARAM_GA_ECOMMERCE_MODE ) ) ) ? $this->getOption( self::PARAM_GA_ECOMMERCE_MODE ) : self::PARAM_GA_ECOMMERCE_MODE_STANDART;
		
		WP_DEBUG && file_put_contents( INA_FOLDER . strtolower( get_class( $this ) ) . '.log', 'Init ' . date('d.n.Y H:i:s') . PHP_EOL, FILE_APPEND );

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
			if ( ! empty( $gaId ) )
			{				
				// Если пользователь авторизван и не в админке, запоминаем его UID
				// Так сделано потому, чтобы не запоминать UID менеджера магазина при ручном оформлении заказа
				$uid = ( is_user_logged_in() && ! is_admin() ) ? get_current_user_id() : false;
				
				// Оъект Measurement Protocol хранит и CID и UID
				$this->measurementProtocol = new INA_MeasurementProtocol( $gaId, $uid );
			}
		}		
		
		// Новый заказ
		add_action( 'woocommerce_new_order', array( $this, 'newOrder' ));
    }
	
    /**
     * Обработка нового заказа
	 * Передача заказа через Measurement Protocol, что позволяет передавать любые заказы, включая заказы из админки
	 * @param int	$orderId	Номер нового заказа	 
     */    
    public function newOrder( $orderId )
	{
		// ОБЯЗАТЕЛЬНО В БЛОКЕ TRY-CATCH. Если будут ошибки, не должно ломаться
		try
		{
			$order = new WC_Order( $orderId );					// Заказ
			$orderUser = $order->get_user();					// Пользователь заказа (false для гостя)
			$orderTotal = $order->get_total();					// Сумма заказа
			$orderShipping = $order->get_total_shipping();		// Сумма доставки
			WP_DEBUG && file_put_contents( $this->manager->baseDir . 'wc-order.log', var_export( $order, true ) . PHP_EOL, FILE_APPEND );
			
			$orderItems = $order->get_items();
			WP_DEBUG && file_put_contents( $this->manager->baseDir . 'wc-items.log', var_export( $items, true ) . PHP_EOL, FILE_APPEND );			
			
			// Работа с Google Analytics
			if ( $this->measurementProtocol )
			{
				// Передаем транзакцию
				$this->measurementProtocol->sendTrans(
					$orderId,									// Уникальный идентификатор транзакции
					'',											// Аффилированность транзакции
					$orderTotal,								// Доход от транзакции
					$orderShipping,								// Стоимость доставки
					0											// Налог с транзакции
				);
				
				// Передаем элементы заказа
				foreach( $items as $item_id => $item ) 
				{
					$name = $item['name'];													// Get the product name
					$quo = $order->get_item_meta($item_id, '_qty', true);					// Get the item quantity
					$item_total = $order->get_item_meta($item_id, '_line_total', true);		// Get the item line total
					
					// Check if product has variation
					$product_variation_id = $item['variation_id'];
					if ( $product_variation_id ) 
					{
					  $product = new WC_Product($item['variation_id']);
					} 
					else 
					{
					  $product = new WC_Product($item['product_id']);
					}

					// Get SKU
					$sku = $product->get_sku();
					$price = $product->get_price();
					
					// Передача 
					$this->measurementProtocol->sendTransItem(
						$orderId,									// Уникальный идентификатор транзакции
						$name,										// Название товара
						$price,										// Цена товара
						$quo,										// Количество единиц товара
						$sku										// Код товара
					);					
				}				
				
				
				
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
		
		<div class="field-row">
			<label for="ecommerce_mode"><?php esc_html_e( 'E-commerce mode', INA_TEXT_DOMAIN) ?></label>
			<select id="ecommerce_mode" name="ecommerce_mode" size="1">
				<option value="<?php echo self::PARAM_GA_ECOMMERCE_MODE_STANDART?>" <?php selected( $this->ecommerceMode, self::PARAM_GA_ECOMMERCE_MODE_STANDART )?>><?php _e( 'Standard E-Commerce Mode', INA_TEXT_DOMAIN );?></option>
				<option value="<?php echo self::PARAM_GA_ECOMMERCE_MODE_ENHANCED?>" <?php selected( $this->ecommerceMode, self::PARAM_GA_ECOMMERCE_MODE_ENHANCED )?>><?php _e( 'Enhanced E-Commerce Mode', INA_TEXT_DOMAIN );?></option>
			</select>
			<p>
				<?php esc_html_e( 'Specify E-commerce mode for Google Analytics.', INA_TEXT_DOMAIN)?>
				<a href="<?php /* translators: Replace this link to one in necessary language */ esc_html_e( 'https://ivannikitin.com/2011/09/11/accurance-bounce-rate-google-analytics/', INA_TEXT_DOMAIN)?>" target="_blank">
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

		if ( isset( $_POST['ecommerce_mode'] ) )
		{
			$this->ecommerceMode = sanitize_text_field( $_POST['ecommerce_mode'] );
			if ( $this->ecommerceMode != self::PARAM_GA_ECOMMERCE_MODE_ENHANCED)
				$this->ecommerceMode = self::PARAM_GA_ECOMMERCE_MODE_STANDART;
			$this->setOption( self::PARAM_GA_ECOMMERCE_MODE, $this->ecommerceMode );
		}
		
	}
}