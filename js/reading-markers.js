/**
 * События "прочтено" на середине и конце контентного блока статьи
 */
jQuery(function($){
	// Статья
	var article = $('%SELECTOR%');
	// Маркеры
	window.readMarkers = {};
	window.readMarkers.start = new Date().getTime();
	// Параграфы статьи
	var pArr = article.find('p');
	// Если статья маленькая, менее 4 параграфов, ничего не делаем
	if (pArr.length < 4) return;
	// Находим серединный параграф
	var middleIndex = Math.floor(pArr.length / 2);
	// Находим последний параграф
	var lastIndex = pArr.length - 1;
	// Вешаем маркеры на появление в области просмотра
	$(pArr[middleIndex]).waypoint(function(direction){track('%MIDDLE%',this)});
	$(pArr[lastIndex]).waypoint(function(direction){track('%END%',this)});
	// Функция отслеживания
	function track(id, obj)
	{
		// Маркер уже отслеживался?
		if (window.readMarkers[id]) return;
		// Флаг отслеживания
		window.readMarkers[id] = true;
		// Текущий таймстамп
		var now = new Date().getTime();
		// Событие
		ga('send', 'event', '%CATEGORY%', id, document.title);
		// Тайминг
		ga('send', 'timing', '%CATEGORY%', id, now - window.readMarkers.start, document.title);
	}
});