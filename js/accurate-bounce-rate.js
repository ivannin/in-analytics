/* Accurate bounce rate by time */
if (!document.referrer || document.referrer.split('/')[2].indexOf(location.hostname) != 0)
	setTimeout(function(){
		ga('send', 'event', '%CATEGORY%', '%ACTION%', location.pathname);
	}, '%TIMEOUT%');
