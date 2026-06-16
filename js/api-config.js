(function () {
  const currentPath = window.location.pathname || '';
  const isInPagesFolder = currentPath.includes('/pages/');
  const apiBaseUrl = window.API_BASE_URL || (isInPagesFolder ? '../api' : 'api');

  window.API_BASE_URL = apiBaseUrl;
  window.API_CONFIG = {
    baseUrl: apiBaseUrl,
    database: 'MySQL'
  };
})();

