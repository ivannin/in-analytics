<?php
/**
 * Абстрактный класс модудя аналитики
 *
 * Реализует интерфейс класса и общие методы
 * Все модули должны наследоваться от этого класса 
 * @abstract
 */
abstract class InaModule
{
	/**
	 * Метод возвращает доступность модуля
     * @return bool	 
	 */   
	abstract public function isEnabled();

	/**
	 * Метод создает страницу параметров модуля
	 */   
	abstract public function adminMenu();
	
	/**
	 * Формирует страницу в меню администратора
	 * @param string	$page			The slug name of the page whose settings sections you want to output. This should match the page name used in add_settings_section()	 
	 * @param string	$option_group	The settings group name. This should match the group name used in register_setting()	 
	 * @param string	$title			The page title	 
	 */   
	 public static function showOptionPage($page='', $option_group='', $title='')
	{
		echo '<form action="options.php" method="post">';
		if (!empty($title)) echo '<h2>', $title, '</h2>';
		settings_fields($option_group);
		do_settings_sections($page);
		submit_button();
		echo '</form>';
	}	
	
	/**
	 * Метод возвращает JS код модуля
	 *
	 * @param string	$templateFile 	Имя файла шаблона
     * @return string	 
	 */   
	protected function getJS($templateFile)
	{
		
		if (empty($templateFile))
			if (WP_DEBUG)	
				return '/*! File not found !*/';
			else
				return '';
		$templateMinimizedFile = str_replace('.js', '.min.js', $templateFile);
		$js = '';
		if (file_exists($templateMinimizedFile))
			$js = file_get_contents($templateMinimizedFile);
		elseif (file_exists($templateFile))
			$js = file_get_contents($templateFile);
		else
		{
			if (WP_DEBUG)	
				return '/*! File ' . $templateFile . ' not found !*/';
			else
				return '';
		}
		return trim($js);
	}

	/**
	 * Метод возвращает JS код модуля для HEAD
	 *
	 * @param string	$templateFile 	Имя файла шаблона
     * @return string	 
	 */   
	public function getHeaderJS($templateFile='')
	{
		return $this->getJS($templateFile);
	}
	
	/**
	 * Метод возвращает JS код модуля для Footer
	 *
	 * @param string	$templateFile 	Имя файла шаблона	 
	 */   
	public function getFooterJS($templateFile='')
	{
		return '';
	}	
}