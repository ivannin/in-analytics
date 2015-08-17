<?php
/**
 * Абстрактный класс модудя аналитики
 *
 * Реализует передачу данных Measurement Protocol
 * @abstract
 */
abstract class InaMeasurementProtocol extends InaModule
{
	/**
	 * Возвразщает значение cid из cookie
	 * Благодарности Matt Clarke
	 * http://habrahabr.ru/post/222169/
	 */   
	public static function getCID()
	{
		if(!isset($_COOKIE["_ga"])) 
			return self::genUUID();
        list($version,$domainDepth, $cid1, $cid2) = split('[\.]', $_COOKIE["_ga"],4);
		return $cid1 . '.' . $cid2;
	}

	/**
	 * Генерирует новый cid по RFC4122
	 * Благодарности Stu Miller
	 * Generate UUID v4 function - needed to generate a CID when one isn't available
	 * http://www.stumiller.me/implementing-google-analytics-measurement-protocol-in-php-and-wordpress/
	 */	
	public static function genUUID() 
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
	 * Возвразщает значение uid
	 */   
	public static function getUID()
	{
		// Информация о пользователе
		global $user_ID;
		get_currentuserinfo();
		return $user_ID;
	}

	
	/**#@+
	* Типы хитов
	* @const
	*/
	const HIT_PAGEVIEW		= 'pageview';
	const HIT_EVENT			= 'event';
	const HIT_TRANSACTION	= 'transaction';

	
	/**
	 * Формирует и отпрваляет хит в Google Analytics
	 * http://www.stumiller.me/implementing-google-analytics-measurement-protocol-in-php-and-wordpress/
	 */	
	public static function sendHit($method=null, $info=null) 
	{
		if ( $method && $info) 
		{
			// Standard params
			$v = 1;
			$tid = get_option(InaAnalytics::OPTION_ID);
			$cid = self::getCID();
			$uid = self::getUID();

			// Register a PAGEVIEW
			if ($method === self::HIT_PAGEVIEW) 
			{
				// Send PageView hit
				$data = array(
					'v' => $v,
					'tid' => $tid,
					'cid' => $cid,
					't' => self::HIT_PAGEVIEW,
					'dt' => $info['title'],
					'dp' => $info['slug']
				);
				if (!empty($uid)) $data['uid'] = $uid;
				self::fireHit($data);
			} // end pageview method
			
			// Register a EVENT
			if ($method === self::HIT_EVENT) 
			{
				// Send PageView hit
				$data = array(
					'v' => $v,
					'tid' => $tid,
					'cid' => $cid,
					't' => self::HIT_EVENT,
					'ec' => $info['category'],
					'ea' => $info['action'],
					'el' => $info['label'],
					'ev' => $info['value'],
				);
				if (!empty($info['label'])) $data['el'] = $info['label'];
				if (!empty($info['ev'])) $data['ev'] = intval($info['value']);
				if (!empty($uid)) $data['uid'] = $uid;
				self::fireHit($data);
			} // end pageview method			

			// Register an ECOMMERCE TRANSACTION (and an associated ITEM)
			else if ($method === 'ecommerce') 
			{
				// Set up Transaction params
				$ti = uniqid(); // Transaction ID
				$ta = 'SI';
				$tr = $info['price']; // transaction value (native currency)
				$cu = $info['cc']; // currency code

				// Send Transaction hit
				$data = array(
					'v' => $v,
					'tid' => $tid,
					'cid' => $cid,
					't' => 'transaction',
					'ti' => $ti,
					'ta' => $ta,
					'tr' => $tr,
					'cu' => $cu
				);
				if (!empty($uid)) $data['uid'] = $uid;
				self::fireHit($data);

				// Set up Item params
				$in = urlencode($info['info']->product_name); // item name;
				$ip = $tr;
				$iq = 1;
				$ic = urlencode($info['info']->product_id); // item SKU
				$iv = urlencode('SI'); // Product Category - we use 'SI' in all cases, you may not want to

				// Send Item hit
				$data = array(
					'v' => $v,
					'tid' => $tid,
					'cid' => $cid,
					't' => 'item',
					'ti' => $ti,
					'in' => $in,
					'ip' => $ip,
					'iq' => $iq,
					'ic' => $ic,
					'iv' => $iv,
					'cu' => $cu
				);
				if (!empty($uid)) $data['uid'] = $uid;
				self::fireHit($data);

			} // end ecommerce method
		}
	}

	/**
	 * Посылает хит в Google Analytics
	 * https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide
	 */	
	protected static function fireHit($data=null) 
	{
		if ($data) 
		{
			$getString = 'https://ssl.google-analytics.com/collect';
			$getString .= '?payload_data&';
			$getString .= http_build_query($data);
			$result = wp_remote_get( $getString );

			#$sendlog = error_log($getString, 1, "error@in-analytics.com"); // comment this in and change your email to get an log sent to your email

			return $result;
		}
		return false;
	}
}
