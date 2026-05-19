document.addEventListener('DOMContentLoaded', function() {
	document.querySelectorAll('[data-memberdash-target]').forEach(function(btn) {
		btn.addEventListener('click', function() {
			var map = { moodboard: 'sss-moodboard', ritual: 'sss-cipher', reset: 'sss-journal' };
			var el = document.getElementById(map[this.dataset.memberdashTarget]);
			if (el) el.scrollIntoView({ behavior: 'smooth' });
		});
	});
});
