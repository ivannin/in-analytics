<?php
/**
 * Модуль кода отслеживания страницы
 *
 * Должен вызываться последним в менеджере
 */
class InaPageTracking extends InaModule
{
	/**
	 * Метод возвращает доступность модуля
     * @return bool	 
	 */   
	public function isEnabled()
	{
		// Модуль доступен, если включен Google Analytics
		return (bool) get_option(InaAnalytics::OPTION_ENABLED);
	}

	/**
	 * Метод создает страницу параметров модуля
	 */   
	public function adminMenu()
	{
		// настроек и страницы нет
	}	
	
	/**
	 * Метод возвращает JS код модуля
	 */   
	public function getHeaderJS($templateFile='')
	{
		return "ga('send', 'pageview', gaOpt);";
	}

}