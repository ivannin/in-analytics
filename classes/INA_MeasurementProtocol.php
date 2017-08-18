<?php
/**
 * Класс реализации передачи данных Measurement Protocol
 */
class INA_MeasurementProtocol
{
    /**
     * @var string      Идентификатор отслеживания
     */
    public $gaID;	
	
    /**
     * @var string      Анонимный идентификатор пользователя CID
     */
    public $cid;
	
    /**
     * @var string      Идентификатор пользователя UID
     */
    public $uid;	
	
    /**
     * Конструктор класса
     * @param string $gaID	Идентификатор отслеживания
     * @param string $uid	Идентификатор пользователя UID
     */
    public function __construct( $gaID, $uid = '' ) 
    {
		// Инициализиуем свойства
		$this->gaID = $gaID;
		
		// CID
		$this->cid = $this->getCID();		
		if ( empty( $this->cid ) )
			$this->cid = $this->generateCID();
		
		// UID
		$this->uid = $uid;
    }

	/**
	 * Возвразщает значение cid из cookie
	 */   
	public function getCID()
	{
		// Проверяем значение куки
		if ( ! isset($_COOKIE['_ga'] ) )
			return false;
		
		// Читаем CID
        list( $version, $domainDepth, $cid1, $cid2 ) = explode( '.', $_COOKIE['_ga'], 4 );
		return $cid1 . '.' . $cid2;
	}

	/**
	 * Генерирует новый cid по RFC4122
	 * Благодарности Stu Miller
	 * Generate UUID v4 function - needed to generate a CID when one isn't available
	 * http://www.stumiller.me/implementing-google-analytics-measurement-protocol-in-php-and-wordpress/
	 */   
	public function generateCID()
	{	
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,

			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
			);
	}
	
	/**
	 * Посылает хит в Google Analytics
	 * https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
	 *
     * @param mixed $data	Ассоциативный массив c с параметрами	 
	 */	
	protected function fireHit( $data = null ) 
	{
		if ($data) 
		{
			// Стандартные параметры
			$params = array(
				'v' 	=> 1,				// Версия протокола
				'tid' 	=> $this->gaID,		// Идентификтор отслеживания
				'cid' 	=> $this->cid		// CID пользователя
			);
			
			// Если указан UID, добавляем его к стандартным данным
			if ( ! empty( $this->uid  ) )
				$params['uid'] = $this->uid;
			
			// Добавляем данные для передачи
			$payload = array_merge( $params, $data );
			WP_DEBUG && file_put_contents( INA_FOLDER . strtolower( get_class( $this ) ) . '.log', var_export($payload, true) . PHP_EOL, FILE_APPEND );
			
			$getString = 'https://ssl.google-analytics.com/collect';
			$getString .= '?payload_data&';
			$getString .= http_build_query( $payload );
			$result = wp_remote_get( $getString );
			return $result;
		}
		return false;
	}	
	
	/**
	 * Посылает хит "Просмотр страницы" в Google Analytics
	 *
     * @param string $slug		относительный URL страницы от корня
     * @param string $title		Название страницы
	 */	
	public function sendPageView( $slug, $title = '' ) 
	{
		return $this->fireHit( array(
			't' => 'pageview',
			'dt' => $slug,
			'dp' => $title			
		));
	}	
	
	/**
	 * Посылает хит "Просмотр страницы" в Google Analytics
	 *
     * @param string $slug		относительный URL страницы от корня
     * @param string $title		Название страницы
	 */	
	public function sendEvent( $categoty, $action, $label = '', $value = 0 ) 
	{
		return $this->fireHit( array(
			't' => 'event',
			'ec' => $categoty,
			'ea' => $action,			
			'el' => $label,			
			'ev' => (int) $value,			
		));
	}

	/**
	 * Посылает хит "Транзакция" в Google Analytics
	 *
     * @param string $orderId		Уникальный идентификатор транзакции
     * @param string $affiliate		Аффилированность транзакции
     * @param float  $revenue		Доход от транзакции		
     * @param float  $shipping		Стоимость доставки
     * @param float  $tax			Налог с транзакции
	 */	
	public function sendTrans( $orderId, $affiliate='', $revenue=0, $shipping=0, $tax=0) 
	{
		return $this->fireHit( array(
			't' => 'transaction',
			'ti' => $orderId,
			'ta' => $affiliate,			
			'tr' => (float) $revenue,			
			'ts' => (float) $shipping,			
			'tt' => (float) $tax,			
		));
	}
	
	/**
	 * Посылает хит "Элемент транзакции" в Google Analytics
	 *
     * @param string $orderId		Уникальный идентификатор транзакции
     * @param string $name			Название товара
     * @param float  $price			Цена товара		
     * @param int    $quo			Количество единиц товара
     * @param string $sku			Код товара
     * @param string $category		Категория товара
	 */	
	public function sendTransItem( $orderId, $name, $price=0, $quo=1, $sku='', $category='') 
	{
		return $this->fireHit( array(
			't' => 'item',
			'ti' => $orderId,			
			'in' => $name,
			'ip' => (float) $price,			
			'iq' => (int) $quo,			
			'ic' => $sku,			
			'iv' => $category,			
		));
	}	
	
}