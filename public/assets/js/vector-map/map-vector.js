"use strict";
!(function (maps) {
  "use strict";
  var b = function () {};
  (b.prototype.init = function () {
    maps("#world-map").vectorMap({
      map: "world_mill_en",
      scaleColors: ["#2196F3", "#25C5F7"],
      normalizeFunction: "polynomial",
      hoverOpacity: 0.7,
      hoverColor: !1,
      regionStyle: {
        initial: {
          fill: "#009DB5",
        },
      },
      backgroundColor: "transparent",
    }),
      maps("#asia").vectorMap({
        map: "asia_mill",
        backgroundColor: "transparent",
        regionStyle: {
          initial: {
            fill: "#F99B0D",
          },
        },
      }),
      maps("#india").vectorMap({
        map: "in_mill",
        backgroundColor: "transparent",
        regionStyle: {
          initial: {
            fill: "#25C5F7",
          },
        },
      }),
      maps("#usa").vectorMap({
        map: "us_aea_en",
        backgroundColor: "transparent",
        regionStyle: {
          initial: {
            fill: "#52526c",
          },
        },
      }),
      maps("#uk").vectorMap({
        map: "uk_mill_en",
        backgroundColor: "transparent",
        regionStyle: {
          initial: {
            fill: "#F94C8E",
          },
        },
      }),
      maps("#canada").vectorMap({
        map: "uk_mill_en",
        backgroundColor: "transparent",
        regionStyle: {
          initial: {
            fill: "#83BF6E",
          },
        },
      }),
      maps("#chicago").vectorMap({
        map: "us-il-chicago_mill_en",
        backgroundColor: "transparent",
        regionStyle: {
          initial: {
            fill: "#F6463A",
          },
        },
      }),
      maps("#australia").vectorMap({
        map: "au_mill",
        backgroundColor: "transparent",
        regionStyle: {
          initial: {
            fill: "#2c323f",
          },
        },
      });
  }),
    (maps.VectorMap = new b()),
    (maps.VectorMap.Constructor = b);
})(window.jQuery),
  (function (maps) {
    "use strict";
    maps.VectorMap.init();
  })(window.jQuery);
