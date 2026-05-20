document.addEventListener('DOMContentLoaded', function() {
	if (document.getElementById('sssFinderTropeOne')) return;

	var dataNode = document.getElementById('sssFinderData');
	var books = [];
	try {
		books = dataNode ? JSON.parse(dataNode.textContent || '[]') : (window.SSSFinderData || []);
	} catch (error) {
		books = [];
	}

	var shelf = document.getElementById('sssFinderShelf');
	var trope = document.getElementById('sssFinderTrope');
	var spice = document.getElementById('sssFinderSpice');
	var submit = document.getElementById('sssFinderSubmit');
	var result = document.getElementById('sssFinderResult');

	function fillSelect(select, values) {
		if (!select) return;
		values.sort().forEach(function(value) {
			if (!value) return;
			var option = document.createElement('option');
			option.value = value;
			option.textContent = value;
			select.appendChild(option);
		});
	}

	fillSelect(shelf, Array.from(new Set(books.map(function(book) { return book.shelf_name || book.shelf; }))));
	fillSelect(trope, Array.from(new Set(books.flatMap(function(book) { return book.tropes || []; }))));

	if (submit) {
		submit.addEventListener('click', function() {
			var matches = books.filter(function(book) {
				var shelfValue = shelf && shelf.value;
				var tropeValue = trope && trope.value;
				var spiceValue = spice && spice.value;
				return (!shelfValue || shelfValue === book.shelf || shelfValue === book.shelf_name)
					&& (!tropeValue || (book.tropes || []).indexOf(tropeValue) !== -1)
					&& (!spiceValue || parseInt(spiceValue, 10) === parseInt(book.spice_level || 0, 10));
			});
			var pick = matches[Math.floor(Math.random() * matches.length)] || books[Math.floor(Math.random() * books.length)];
			if (!pick || !result) return;
			document.getElementById('sssFinderCover').src = pick.cover_url || '';
			document.getElementById('sssFinderCover').alt = pick.title || '';
			document.getElementById('sssFinderBookTitle').textContent = pick.title || '';
			document.getElementById('sssFinderAuthor').textContent = pick.author || '';
			document.getElementById('sssFinderWhy').textContent = pick.why || pick.mini || '';
			result.hidden = false;
		});
	}
});
