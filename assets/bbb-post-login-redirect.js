(function () {
  if (!window.localStorage) return;

  var stored = null;

  try {
    stored = JSON.parse(window.localStorage.getItem('bbbPostLoginReturn') || 'null');
  } catch (error) {
    stored = null;
  }

  if (!stored || !stored.path) return;

  var isFresh = stored.createdAt && Date.now() - stored.createdAt < 15 * 60 * 1000;
  if (!isFresh) {
    window.localStorage.removeItem('bbbPostLoginReturn');
    return;
  }

  var currentPath = window.location.pathname || '';
  if (currentPath === stored.path) {
    window.localStorage.removeItem('bbbPostLoginReturn');
    return;
  }

  var isAccountPage = currentPath.indexOf('/my-account') === 0;
  if (window.BBBReaderAccount && window.BBBReaderAccount.loggedIn && isAccountPage) {
    window.localStorage.removeItem('bbbPostLoginReturn');
    window.location.replace(stored.path);
  }
})();
