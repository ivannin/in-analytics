/**
 * Отслеживание скачивания файлов через регистрацию виртуальных страниц
 * и переходов по внешним ссылкам через события
 */
jQuery(function($){
	var downloadTrackingEnabled	= %DOWNLOAD_ENABLED%,
		outboundLinksTrackingEnabled = %OUTBOUND_LINKS_ENABLED%;
	// Если нет ga ничего не делаем!
	if (typeof ga === 'undefined') return;
	// Проверим каждую ссылку	
	$('a').each(function(i,objA)
	{
		var link = $(objA);
		
		// Это ссылка на скачивание?
		if (downloadTrackingEnabled && /(%EXTENSIONS%)$/.test(objA.href))
			link.click(function(event){
				event.preventDefault();
				ga('send', 'pageview', {
					'page'			: objA.pathname,
					'hitCallback'	: function() {
						location.assign(objA.href);
					}
				});
			});
		// Это внешняя ссылка?
		if (outboundLinksTrackingEnabled && location.hostname != objA.hostname)
			link.click(function(event){
				event.preventDefault();
				ga('send', 'event', {
					'eventCategory'	: '%CATEGORY%',
					'eventAction'	: '%ACTION%',
					'eventLabel'	: objA.href,
					'hitCallback'	: function() {
						location.assign(objA.href);
					}
				});
			});
	});
});