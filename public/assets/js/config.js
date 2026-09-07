(function () {
  var primary = localStorage.getItem("primary") || "#009DB5";
  var secondary = localStorage.getItem("secondary") || "#F94C8E";

  window.yuriAdminConfig = {
    // Theme Primary Color
    primary: primary,
    // theme secondary color
    secondary: secondary,
  };
})();
