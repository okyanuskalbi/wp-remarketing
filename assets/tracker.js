/* WP Remarketing — first-party pageview/UTM beacon */
(function () {
	if (!window.WPREM_TRACK || !WPREM_TRACK.url) {
		return;
	}

	function param(name) {
		var m = new RegExp('[?&]' + name + '=([^&]*)').exec(location.search);
		return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : '';
	}

	function getCookie(name) {
		var m = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
		return m ? m.pop() : '';
	}

	function setCookie(name, value, days) {
		document.cookie =
			name + '=' + value + ';path=/;max-age=' + days * 86400 + ';SameSite=Lax';
	}

	function randomId() {
		return (Date.now().toString(36) + Math.random().toString(36).slice(2, 12)).slice(0, 32);
	}

	// Persistent session id.
	var sid = getCookie('wprem_sid');
	if (!sid) {
		sid = randomId();
		setCookie('wprem_sid', sid, 180);
	}

	// First-touch UTM: capture from URL, else fall back to stored attribution.
	var keys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
	var utm = {};
	var has = false;
	keys.forEach(function (k) {
		utm[k] = param(k);
		if (utm[k]) {
			has = true;
		}
	});

	if (has) {
		setCookie('wprem_utm', encodeURIComponent(JSON.stringify(utm)), 180);
	} else {
		var stored = getCookie('wprem_utm');
		if (stored) {
			try {
				utm = JSON.parse(decodeURIComponent(stored));
			} catch (e) {}
		}
	}

	var payload = {
		sid: sid,
		path: location.pathname,
		referrer: document.referrer,
		utm_source: utm.utm_source || '',
		utm_medium: utm.utm_medium || '',
		utm_campaign: utm.utm_campaign || '',
		utm_term: utm.utm_term || '',
		utm_content: utm.utm_content || ''
	};

	try {
		var body = JSON.stringify(payload);
		if (navigator.sendBeacon) {
			navigator.sendBeacon(WPREM_TRACK.url, new Blob([body], { type: 'application/json' }));
		} else {
			fetch(WPREM_TRACK.url, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: body,
				keepalive: true,
				credentials: 'same-origin'
			});
		}
	} catch (e) {}
})();
