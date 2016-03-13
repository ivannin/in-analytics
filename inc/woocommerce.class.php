<?php
/** Hooks 
 * https://docs.woothemes.com/wc-apidocs/source-class-WC_Abstract_Order.html#2284
 * 
 * 
 */
if (get_option(InaWooCommerce::OPTION_ECOMMERCE_ENABLED))
	add_action('woocommerce_order_status_changed', 'InaWooCommerce::orderProccessing', 10, 3);

/**
 * Модуль Интеграция с формами
 *
 * Реализует отслеживание передачи контактных форм
 */
class InaWooCommerce extends InaMeasurementProtocol
{
	/**#@+
	* Параметры опций модуля
	* @const
	*/
	const MENU_SLUG					= 'in-analytics-woocommerce.php';
	const SECTION					= 'ina_woocommerce';
	const OPTION_ECOMMERCE_ENABLED	= 'ina_ecommerce_enabled';



	/**
	 * Конструктор класса
	 */
	public function __construct()
	{
		// Инициализируем параметры
		add_action('admin_init', 'InaWooCommerce::initSettings');
	}
	
	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		add_submenu_page(
			InaManager::MENU_SLUG,		 				// parent_slug - The slug name for the parent menu
			__('WooCommerce Intergation Options', 'inanalytics'), // page_title - The text to be displayed in the title tags of the page when the menu is selected
			__('WooCommerce', 'inanalytics'), 		// menu_title - The text to be used for the menu
			'manage_options', 							// capability - The capability required for this menu to be displayed to the user.
			self::MENU_SLUG, 							// menu_slug - The slug name to refer to this menu by
			'InaWooCommerce::showOptionPage'			// function - The function to be called to output the content for this page
		);
	}
	
	/**
	 * Формирует страницу в меню администратора
	 */   
	 public static function showOptionPage($page='', $option_group='', $title='')
	{
		// Create the option page
		parent::showOptionPage(
			self::MENU_SLUG,	// The slug name of the page for settings sections
			self::MENU_SLUG		// The settings group name! ВАЖНО! ЭТО КОСЯК, ПО ДРУГОМУ НЕ РАБОТАЕТ
		);
	}	
	
	/**
	 * Инициализирует параметры модуля
	 */   
	 public static function initSettings()
	{
		// Создает секцию параметров
		add_settings_section(
			self::SECTION,								// id - String for use in the 'id' attribute of tags
			__('E-commerce Options', 'inanalytics'), 	// title -  Title of the section
			'InaWooCommerce::showSectionDescription',	// callback - Function that fills the section with the desired content
			self::MENU_SLUG								// page - The menu page on which to display this section. Should match $menu_slug
		);
		// Параметр: режим E-Commerce
		register_setting(self::MENU_SLUG, self::OPTION_ECOMMERCE_ENABLED);
		add_settings_field( 
			self::OPTION_ECOMMERCE_ENABLED,				// id - String for use in the 'id' attribute of tags
			__('WooCommerce Intergation enabled', 'inanalytics' ),	// Title of the field
			'InaWooCommerce::showECommerceEnabled',		// callback - Function that fills the field with the desired inputs
			self::MENU_SLUG, 							// page - The menu page on which to display this field
			self::SECTION 								// section - The section of the settings page
		);
	
	}

	/**
	 * Формирует страницу в меню администратора
	 */   
	 public static function showSectionDescription()
	{
		_e('This module implements integration with WooCommerce plugin.', 'inanalytics');
	}	
	
	/**
	 * Показывает поле интеграции с CF7
	 */   
	 public static function showECommerceEnabled()
	{
		$name = self::OPTION_ECOMMERCE_ENABLED;
		$value = get_option($name);
		$checked = checked($value, 1, false);
		echo "<input type='checkbox' name='{$name}' {$checked} value='1'>&nbsp;&nbsp;";
		_e('Check this for enabling WooCommerce integration. Read more here', 'inanalytics');
	}
	
	/**
	 * Метод возвращает доступность модуля
     * @return bool	 
	 */   
	public function isEnabled()
	{
		return (bool) 
			get_option(self::OPTION_ECOMMERCE_ENABLED);
	}
	
	/**
	 * Метод возвращает JS код модуля
	 */   
	public function getHeaderJS($templateFile='')
	{
		return '';
	}
	
	// ------------------------------ RUNTIME ------------------------------
	
	/**
	 * Обработка заказа
	 * $order_id - код заказа 
	 * $old_status - старый статус 
	 * $new_status - новый статус
	 * 
	 */   
	public static function orderProccessing($order_id, $old_status, $new_status)
	{
		
		global $woocommerce;

		try
		{
			$order = new WC_Order( $order_id );
			$items = $order->get_items();
			$shipping = $order->get_items('shipping');
			$firstShipping = reset($shipping);
			$shippingCost = ($firstShipping) ? $firstShipping['cost'] : 0;

			/* DEBUG 
			file_put_contents(INA_FOLDER.'/order_status.txt', $old_status . ' -> ' . $new_status);		
			file_put_contents(INA_FOLDER.'/order_id.txt', var_export($order_id, true));		
			file_put_contents(INA_FOLDER.'/order.txt', var_export($order, true));		
			file_put_contents(INA_FOLDER.'/items.txt', var_export($items, true));
			file_put_contents(INA_FOLDER.'/shipping.txt', var_export($shipping, true));
			*/
			
			// При размещении заказа старый статус - pending
			if ($old_status == 'pending')
			{
				// передаем транзакцию
				$transaction_id = $order->id;
				
				// Элементы заказа
				$orderItems = array();
				$totalSum = 0;
				foreach ($items as $item) 
				{
					// Категория товара
					$catTerms = wp_get_object_terms($item['product_id'], 'product_cat');
					$category = (count(catTerms) > 0) ? $catTerms[0]->name : '';
					
					// Добавим товар в список для передачи
					$orderItems[] = array(
						'sku'		=> $item['product_id'],
						'name'		=> $item['name'],
						'category'	=> $category,
						'price'		=> $item['line_total'],
						'quo'		=> $item['qty']
					);
					
					// Считаем общую сумму
					$totalSum += $item['line_total'] * $item['qty'];
				}				
				
			}
			
				
				
			if (get_option(InaAnalytics::OPTION_ENABLED))
			{
				// Передача обычной транзакции через Measurement Protocol	
				$transaction = array(
					'transaction_id'	=> $transaction_id,
					'revenue'			=> $totalSum,
					'shipping'			=> $shippingCost,
					'items'				=> $orderItems
				);						
				InaMeasurementProtocol::sendHit(InaMeasurementProtocol::HIT_TRANSACTION, $transaction);
			}								
		}
		catch (Exception $e) 
		{
			if (WP_DEBUG === true)
			{
				error_log('IN-Analytics: InaWooCommerce::orderProccessing: '.print_r($e, true));
				file_put_contents(INA_FOLDER.'/errors-orderProccessing.log', var_export($items, true));				
			}
				
		}
		
		return $order_id;
	}

	
}