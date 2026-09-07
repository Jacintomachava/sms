(function ($) {
  if (localStorage.getItem("color")) $("#color").attr("href", "../assets/css/" + localStorage.getItem("color") + ".css");
  if (localStorage.getItem("dark")) $("body").attr("class", "dark-only");
  $(
    '<div class="customizer-links1"><div class="flex-column nac-pills nav" id="c-pills-tab" role="tablist" aria-orientation="vertical"><a class="nav-link" data-original-title="" href="#c-pills-home" aria-controls="c-pills-home" aria-selected="false" data-bs-toggle="pill" id="c-pills-home-tab" role="tab"><div class="settings"><img src="../assets/images/customizer/brush.png" alt=""></div><span>Quick option</span></a><a class="nav-link" data-original-title="" href="https://support.pixelstrap.com/" target="_blank"><div><img src="../assets/images/customizer/medical-kit.png" alt=""></div><span>Support</span></a><a class="nav-link" data-original-title="" href="https://docs.pixelstrap.net/admin/yuri/document/" target="_blank" target="_blank"><div><img src="../assets/images/customizer/setting-2.png" alt=""></div><span>Document</span></a><a class="nav-link" data-original-title="" href="https://admin.pixelstrap.net/yuri/template/landing-page.html" target="_blank"><div><img src="../assets/images/customizer/setting-5.png" alt=""></div><span>Check features</span></a><a class="nav-link" data-original-title="" href="https://themeforest.net/user/pixelstrap/portfolio" target="_blank"><div><img src="../assets/images/customizer/like-tag.png" alt=""></div><span>Buy now</span></a></div></div><div class="customizer-contain"><div class="tab-content" id="c-pills-tabContent"><div class="customizer-header"><i class="icofont-close icon-close"></i><h5>Preview Settings</h5><p class="mb-0">Try It Real Time<i class="fa fa-thumbs-o-up txt-primary"></i></div><div class="custom-scrollbar customizer-body"><div class="fade tab-pane" id="c-pills-home" role="tabpanel" aria-labelledby="c-pills-home-tab"><h6>Layout Type</h6><ul class="layout-grid main-layout"><li class="active" data-attr="ltr"><div class="bg-light header"><ul><li><li><li></ul></div><div class="body"><ul><li class="bg-light sidebar"><li class="bg-light body"><span class="badge badge-primary">LTR</span></ul></div><li data-attr="rtl"><div class="bg-light header"><ul><li><li><li></ul></div><div class="body"><ul><li class="bg-light body"><span class="badge badge-primary">RTL</span><li class="bg-light sidebar"></ul></div><li class="box-layout px-3" data-attr="ltr"><div class="bg-light header"><ul><li><li><li></ul></div><div class="body"><ul><li class="bg-light sidebar"><li class="bg-light body"><span class="badge badge-primary">Box</span></ul></div></ul><h6>Sidebar Type</h6><ul class="layout-grid sidebar-type"><li data-attr="normal-sidebar"><div class="bg-light header"><ul><li><li><li></ul></div><div class="body"><ul><li class="sidebar bg-dark"><li class="bg-light body"></ul></div><li data-attr="compact-sidebar"><div class="bg-light header"><ul><li><li><li></ul></div><div class="body"><ul><li class="sidebar bg-dark compact"><li class="bg-light body"></ul></div></ul><h6>Sidebar Icon</h6><ul class="layout-grid sidebar-setting"><li class="active" data-attr="stroke-svg"><div class="bg-light header"><ul><li><li><li></ul></div><div class="bg-light body"><span class="badge badge-primary">Stroke</span></div></ul><h6>Unlimited Color</h6><ul class="layout-grid unlimited-color-layout"><input id="ColorPicker1" name="Background" type="color" value="#009DB5"> <input id="ColorPicker2" name="Background" type="color" value="#F94C8E"><button class="color-apply-btn color-apply-btn btn btn-primary" type="button">Apply</button></ul><h6>Light layout</h6><ul class="layout-grid customizer-color"><li class="color-layout" data-attr="color-1" data-primary="#009DB5" data-secondary="#F94C8E"><div></div><li class="color-layout" data-attr="color-2" data-primary="#898121" data-secondary="#4C4B16"><div></div><li class="color-layout" data-attr="color-3" data-primary="#31C6D4" data-secondary="#EF6262"><div></div><li class="color-layout" data-attr="color-4" data-primary="#FF7676" data-secondary="#FF4B91"><div></div><li class="color-layout" data-attr="color-5" data-primary="#8fa552" data-secondary="#8e49dd"><div></div><li class="color-layout" data-attr="color-6" data-primary="#BA704F" data-secondary="#C8AE7D"><div></div></ul><h6>Dark Layout</h6><ul class="layout-grid customizer-color dark"><li class="color-layout" data-attr="color-1" data-primary="#009DB5" data-secondary="#F94C8E"><div></div><li class="color-layout" data-attr="color-2" data-primary="#898121" data-secondary="#4C4B16"><div></div><li class="color-layout" data-attr="color-3" data-primary="#31C6D4" data-secondary="#EF6262"><div></div><li class="color-layout" data-attr="color-4" data-primary="#FF7676" data-secondary="#FF4B91"><div></div><li class="color-layout" data-attr="color-5" data-primary="#8fa552" data-secondary="#8e49dd"><div></div><li class="color-layout" data-attr="color-6" data-primary="#BA704F" data-secondary="#C8AE7D"><div></div></ul><h6>Mix Layout</h6><ul class="layout-grid customizer-mix"><li class="color-layout active" data-attr="light-only"><div class="bg-light header"><ul><li><li><li></ul></div><div class="body"><ul><li class="bg-light sidebar"><li class="bg-light body"></ul></div><li class="color-layout" data-attr="dark-sidebar"><div class="bg-light header"><ul><li><li><li></ul></div><div class="body"><ul><li class="sidebar bg-dark"><li class="bg-light body"></ul></div><li class="color-layout" data-attr="dark-only"><div class="header bg-dark"><ul><li><li><li></ul></div><div class="body"><ul><li class="sidebar bg-dark"><li class="body bg-dark"></ul></div></ul></div></div></div></div>'
  ).appendTo($("body"));
  (function () {})();
  //live customizer js
  $(document).ready(function () {
    $(".customizer-color li").on("click", function () {
      $(".customizer-color li").removeClass("active");
      $(this).addClass("active");
      var color = $(this).attr("data-attr");
      var primary = $(this).attr("data-primary");
      var secondary = $(this).attr("data-secondary");
      localStorage.setItem("color", color);
      localStorage.setItem("primary", primary);
      localStorage.setItem("secondary", secondary);
      localStorage.removeItem("dark");
      $("#color").attr("href", "../assets/css/" + color + ".css");
      $(".dark-only").removeClass("dark-only");
      location.reload(true);
    });

    $(".customizer-color.dark li").on("click", function () {
      $(".customizer-color.dark li").removeClass("active");
      $(this).addClass("active");
      $("body").attr("class", "dark-only");
      localStorage.setItem("dark", "dark-only");
    });

    if (localStorage.getItem("primary") != null) {
      document.documentElement.style.setProperty("--theme-default", localStorage.getItem("primary"));
    }
    if (localStorage.getItem("secondary") != null) {
      document.documentElement.style.setProperty("--theme-secondary", localStorage.getItem("secondary"));
    }
    $(".customizer-links1 #c-pills-home-tab, .customizer-links1 #c-pills-layouts-tab").click(function () {
      $(".customizer-contain").addClass("open");
      $(".customizer-links1").addClass("open");
    });

    $(".close-customizer-btn").on("click", function () {
      $(".floated-customizer-panel").removeClass("active");
    });

    $(".customizer-contain .icon-close").on("click", function () {
      $(".customizer-contain").removeClass("open");
      $(".customizer-links1").removeClass("open");
    });

    $(".color-apply-btn").click(function () {
      location.reload(true);
    });

    var primary = document.getElementById("ColorPicker1").value;
    document.getElementById("ColorPicker1").onchange = function () {
      primary = this.value;
      localStorage.setItem("primary", primary);
      document.documentElement.style.setProperty("--theme-primary", primary);
    };

    var secondary = document.getElementById("ColorPicker2").value;
    document.getElementById("ColorPicker2").onchange = function () {
      secondary = this.value;
      localStorage.setItem("secondary", secondary);
      document.documentElement.style.setProperty("--theme-secondary", secondary);
    };

    $(".customizer-color.dark li").on("click", function () {
      $(".customizer-color.dark li").removeClass("active");
      $(this).addClass("active");
      $("body").attr("class", "dark-only");
      localStorage.setItem("dark", "dark-only");
    });

    $(".customizer-mix li").on("click", function () {
      $(".customizer-mix li").removeClass("active");
      $(this).addClass("active");
      var mixLayout = $(this).attr("data-attr");
      $("body").attr("class", mixLayout);
    });

    $(".sidebar-setting li").on("click", function () {
      $(".sidebar-setting li").removeClass("active");
      $(this).addClass("active");
      var sidebar = $(this).attr("data-attr");
      $(".sidebar-wrapper").attr("data-sidebar-layout", sidebar);
    });

    $(".sidebar-main-bg-setting li").on("click", function () {
      $(".sidebar-main-bg-setting li").removeClass("active");
      $(this).addClass("active");
      var bg = $(this).attr("data-attr");
      $(".sidebar-wrapper").attr("class", "sidebar-wrapper " + bg);
    });

    $(".sidebar-type li").on("click", function () {
      $("body").append("");
      console.log("test");
      var type = $(this).attr("data-attr");

      var boxed = "";
      if ($(".page-wrapper").hasClass("box-layout")) {
        boxed = "box-layout";
      }
      switch (type) {
        case "compact-sidebar": {
          $(".page-wrapper").attr("class", "page-wrapper compact-wrapper " + boxed);
          $(this).addClass("active");
          localStorage.setItem("page-wrapper-yuri", "compact-wrapper");
          break;
        }
        case "normal-sidebar": {
          $(".page-wrapper").attr("class", "page-wrapper horizontal-wrapper " + boxed);
          $(".logo-wrapper").find("img").attr("src", "../assets/images/logo/logo.png");
          localStorage.setItem("page-wrapper-yuri", "horizontal-wrapper");
          break;
        }
        case "default-body": {
          $(".page-wrapper").attr("class", "page-wrapper  only-body" + boxed);
          localStorage.setItem("page-wrapper-yuri", "only-body");
          break;
        }
        case "dark-sidebar": {
          $(".page-wrapper").attr("class", "page-wrapper compact-wrapper dark-sidebar" + boxed);
          localStorage.setItem("page-wrapper-yuri", "compact-wrapper dark-sidebar");
          break;
        }
        case "compact-wrap": {
          $(".page-wrapper").attr("class", "page-wrapper compact-sidebar" + boxed);
          localStorage.setItem("page-wrapper-yuri", "compact-sidebar");
          break;
        }
        case "color-sidebar": {
          $(".page-wrapper").attr("class", "page-wrapper compact-wrapper color-sidebar" + boxed);
          localStorage.setItem("page-wrapper-yuri", "compact-wrapper color-sidebar");
          break;
        }
        case "compact-small": {
          $(".page-wrapper").attr("class", "page-wrapper compact-sidebar compact-small" + boxed);
          localStorage.setItem("page-wrapper-yuri", "compact-sidebar compact-small");
          break;
        }
        case "box-layout": {
          $(".page-wrapper").attr("class", "page-wrapper compact-wrapper box-layout " + boxed);
          localStorage.setItem("page-wrapper-yuri", "compact-wrapper box-layout");
          break;
        }
        case "enterprice-type": {
          $(".page-wrapper").attr("class", "page-wrapper horizontal-wrapper enterprice-type" + boxed);
          localStorage.setItem("page-wrapper-yuri", "horizontal-wrapper enterprice-type");
          break;
        }
        case "modern-layout": {
          $(".page-wrapper").attr("class", "page-wrapper compact-wrapper modern-type" + boxed);
          localStorage.setItem("page-wrapper-yuri", "compact-wrapper modern-type");
          break;
        }
        case "material-layout": {
          $(".page-wrapper").attr("class", "page-wrapper horizontal-wrapper material-type" + boxed);
          localStorage.setItem("page-wrapper-yuri", "horizontal-wrapper material-type");

          break;
        }
        case "material-icon": {
          $(".page-wrapper").attr("class", "page-wrapper compact-sidebar compact-small material-icon" + boxed);
          localStorage.setItem("page-wrapper-yuri", "compact-sidebar compact-small material-icon");

          break;
        }
        case "advance-type": {
          $(".page-wrapper").attr("class", "page-wrapper horizontal-wrapper enterprice-type advance-layout" + boxed);
          localStorage.setItem("page-wrapper-yuri", "horizontal-wrapper enterprice-type advance-layout");

          break;
        }
        default: {
          $(".page-wrapper").attr("class", "page-wrapper compact-wrapper " + boxed);
          localStorage.setItem("page-wrapper-yuri", "compact-wrapper");
          break;
        }
      }
      location.reload(true);
    });

    $(".main-layout li").on("click", function () {
      $(".main-layout li").removeClass("active");
      $(this).addClass("active");
      var layout = $(this).attr("data-attr");
      $("body").attr("class", layout);
      $("html").attr("dir", layout);
    });

    $(".main-layout .box-layout").on("click", function () {
      $(".main-layout .box-layout").removeClass("active");
      $(this).addClass("active");
      var layout = $(this).attr("data-attr");
      $("body").attr("class", "box-layout");
      $("html").attr("dir", layout);
    });
  });
})(jQuery);
