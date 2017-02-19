# in-analytics
Плагин подключения Google Analytics к сайту WordPress
Плагин реализован в виде набора модулей. Каждый модуль отвечает за определенные функции

##Модуль Google Analytics
Реализует формирование кода GA на сайте
Формирование кода выполняется следующими действиями (хуками)
* ina_ga_before_init 		- перед кодом инициализации GA
* ina_ga_init 				- код инициализации GA
* ina_ga_after_init 		- после кода инициализации GA
* ina_ga_before_tracking 	- перед кодом отслеживания GA
* ina_ga_tracking			- код отслеживания GA
* ina_ga_after_tracking		- после кода ослеживания GA

Можно использовать фильтры
* ina_ga_function 		- код функии GA
* ina_ga_create 		- код создания трекера
* ina_ga_hit_options 	- код переменной параметров хита
* ina_ga_pageview 		- код отправки pageview
