(function ($) {
  var updatingChart = $(".updating-chart").peity("line");

  setInterval(function () {
    var random = Math.round(Math.random() * 10);
    var values = updatingChart.text().split(",");
    values.shift();
    values.push(random);

    updatingChart.text(values.join(",")).change();
  }, 1000);

  $(".line").peity("line");

  $(".bar").peity("bar");

  $(".donut").peity("donut");

  $(".data-attributes span").peity("donut");

  $("span.pie").peity("pie");

  $(".bar-colours-1").peity("bar", {
    fill: [yuriAdminConfig.primary, yuriAdminConfig.secondary, "#83BF6E"],
    width: "100",
    height: "82",
  });

  $(".bar-colours-2").peity("bar", {
    fill: function (value) {
      return value > 0 ? yuriAdminConfig.primary : yuriAdminConfig.secondary;
    },
    width: "100",
    height: "82",
  });

  $(".bar-colours-3").peity("bar", {
    fill: function (_, i, all) {
      var g = parseInt((i / all.length) * 157);
      return "rgb(0, " + g + ", 181)";
    },
    width: "100",
    height: "82",
  });

  $(".pie-colours-1").peity("pie", {
    fill: [
      yuriAdminConfig.primary,
      yuriAdminConfig.secondary,
      "#83BF6E",
      "#F99B0D",
    ],
    width: "100",
    height: "82",
  });
})(jQuery);
