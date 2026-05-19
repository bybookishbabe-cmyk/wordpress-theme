(function () {
  if (!window.BBBSiteData) return;

  window.shopUrl = window.BBBSiteData.shopUrl || window.location.origin;
  window.BBBReaderAccount = window.BBBSiteData.BBBReaderAccount || {};
  window.routes = window.BBBSiteData.routes || {};
  window.cartStrings = window.BBBSiteData.cartStrings || {};
  window.variantStrings = window.BBBSiteData.variantStrings || {};
  window.quickOrderListStrings = window.BBBSiteData.quickOrderListStrings || {};
  window.accessibilityStrings = window.BBBSiteData.accessibilityStrings || {};
  window.bbbData = window.BBBSiteData.bbbData || {};
})();
