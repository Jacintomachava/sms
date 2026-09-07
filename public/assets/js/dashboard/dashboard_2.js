// tables
const datatable = new simpleDatatables.DataTable("#order-status", {
  paging: false,
  tabIndex: 1,
});

const stock_table = new simpleDatatables.DataTable("#stock-status", {
  paging: false,
  tabIndex: 1,
});

(function () {
  var options_revenue_order = {
    series: [
      {
        name: "Earning",
        type: "line",
        data: [330, 60, 370, 240, 250, 70, 280, 60, 185, 60],
      },
      {
        name: "Order",
        type: "line",
        data: [70, 330, 60, 200, 100, 250, 100, 350, 110, 300],
      },
    ],
    chart: {
      height: 225,
      type: "line",
      stacked: false,
      toolbar: {
        show: false,
      },
      dropShadow: {
        enabled: true,
        top: 2,
        left: 0,
        blur: 4,
        color: "#000",
        opacity: 0.08,
      },
    },
    stroke: {
      width: [2, 2, 2],
      curve: "smooth",
    },
    grid: {
      show: false,
      padding: {
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
      },
    },
    plotOptions: {
      bar: {
        columnWidth: "20%",
      },
    },
    colors: ["#009DB5", "#83BF6E"],
    fill: {
      opacity: 1,
      type: "solid",
    },
    labels: [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
    ],
    xaxis: {
      type: "category",
      tickAmount: 8,
      tickPlacement: "between",
      tooltip: {
        enabled: false,
      },
      axisBorder: {
        color: "var(--chart-border)",
      },
      axisTicks: {
        show: false,
      },
    },

    markers: {
      discrete: [
        {
          seriesIndex: 0,
          dataPointIndex: 3,
          fillColor: "#009DB5",
          strokeColor: "var(--white)",
          size: 6,
          sizeOffset: 2,
        },
      ],
      hover: {
        size: 6,
        sizeOffset: 0,
      },
    },

    legend: {
      show: false,
    },
    yaxis: {
      min: 0,
      tickAmount: 4,
      tickPlacement: "between",
      labels: {
        formatter: function (val) {
          return val + "K";
        },
        offsetX: -5,
      },
    },
    tooltip: {
      shared: false,
      intersect: false,
    },
    responsive: [
      {
        breakpoint: 1365,
        options: {
          chart: {
            height: 140,
          },
        },
      },
      {
        breakpoint: 1200,
        options: {
          chart: {
            height: 225,
          },
        },
      },
      {
        breakpoint: 781,
        options: {
          chart: {
            height: 235,
          },
        },
      },
      {
        breakpoint: 400,
        options: {
          chart: {
            height: 170,
          },
        },
      },
    ],
  };

  var revenue_overview = new ApexCharts(
    document.querySelector("#revenue-order"),
    options_revenue_order
  );
  revenue_overview.render();

  // 2 chart
  var options_weekly_visitor = {
    series: [
      {
        name: "Male",
        type: "line",
        data: [70, 90, 70, 90, 100],
      },
      {
        name: "Female",
        type: "line",
        data: [90, 40, 100, 50, 40],
      },
    ],
    chart: {
      height: 110,
      type: "line",
      stacked: false,
      toolbar: {
        show: false,
      },
      dropShadow: {
        enabled: true,
        top: 2,
        left: 0,
        blur: 4,
        color: "#000",
        opacity: 0.08,
      },
    },
    stroke: {
      width: [2, 2],
      curve: "smooth",
    },
    grid: {
      show: false,
      // borderColor: "var(--chart-border)",
      // strokeDashArray: 0,
      // position: "back",
      // xaxis: {
      //   lines: {
      //     show: true,
      //   },
      // },
      padding: {
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
      },
    },
    plotOptions: {
      bar: {
        columnWidth: "20%",
      },
    },
    colors: ["#009DB5", "#83BF6E"],
    fill: {
      opacity: 1,
      type: "solid",
    },
    labels: ["1", "2", "3", "4", "5"],
    xaxis: {
      type: "category",
      tickAmount: 8,
      tickPlacement: "between",
      tooltip: {
        enabled: false,
      },
      axisBorder: {
        color: "var(--chart-border)",
      },
      axisTicks: {
        show: false,
      },
    },

    yaxis: {
      show: false,
    },

    legend: {
      show: false,
    },

    tooltip: {
      shared: false,
      intersect: false,
    },
    responsive: [
      {
        breakpoint: 1365,
        options: {
          chart: {
            height: 90,
          },
        },
      },
      {
        breakpoint: 1200,
        options: {
          chart: {
            height: 135,
          },
        },
      },
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 125,
            width: "100%",
          },
        },
      },
      // {
      //   breakpoint: 733,
      //   options: {
      //     chart: {
      //       height: 125,
      //       width: 480,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 714,
      //   options: {
      //     chart: {
      //       height: 125,
      //       width: 460,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 685,
      //   options: {
      //     chart: {
      //       height: 125,
      //       width: 440,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 650,
      //   options: {
      //     chart: {
      //       offsetX: -40,
      //       height: 125,
      //       width: 420,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 628,
      //   options: {
      //     chart: {
      //       offsetX: -40,
      //       height: 125,
      //       width: 400,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 600,
      //   options: {
      //     chart: {
      //       offsetX: -30,
      //       height: 125,
      //       width: 370,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 576,
      //   options: {
      //     chart: {
      //       offsetX: -30,
      //       height: 125,
      //       width: 550,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 565,
      //   options: {
      //     chart: {
      //       height: 125,
      //       width: 530,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 550,
      //   options: {
      //     chart: {
      //       height: 125,
      //       width: 500,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 520,
      //   options: {
      //     chart: {
      //       height: 125,
      //       width: 460,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 485,
      //   options: {
      //     chart: {
      //       height: 125,
      //       width: 420,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 445,
      //   options: {
      //     chart: {
      //       height: 125,
      //       width: 400,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 425,
      //   options: {
      //     chart: {
      //       offsetX: 0,
      //       height: 125,
      //       width: 340,
      //     },
      //   },
      // },
      // {
      //   breakpoint: 420,
      //   options: {
      //     chart: {
      //       offsetX: 0,
      //       height: 125,
      //       width: "100%",
      //     },
      //   },
      // },
    ],
  };

  var weeklyVisitor_overview = new ApexCharts(
    document.querySelector("#weekly-visitor"),
    options_weekly_visitor
  );
  weeklyVisitor_overview.render();

  // polar-area chart

  var options_polar_area = {
    series: [14, 23, 21, 19, 17, 14, 12, 10],
    chart: {
      type: "polarArea",
      height: 250,
    },
    stroke: {
      colors: ["#fff"],
    },
    colors: ["#009DB5", "#F99B0D", "#83BF6E"],
    fill: {
      opacity: 0.8,
    },
    legend: {
      show: false,
    },
  };

  var polar_area = new ApexCharts(
    document.querySelector("#polar-area"),
    options_polar_area
  );
  polar_area.render();
})();

// swiper product
var swiper = new Swiper(".deal-swiper", {
  pagination: {
    el: ".swiper-pagination",
    clickable: true,
  },
  spaceBetween: 10,
});

// const category_swiper3 = new Swiper(".sales-overview-slider", {
//   slidesPerView: 4,
//   spaceBetween: 0,
//   loop: true,
//   // autoplay: {
//   //   delay: 2000,
//   // },
//   breakpoints: {
//     0: {
//       slidesPerView: 2,
//       spaceBetween: 12,
//     },
//     400: {
//       slidesPerView: 3,
//       spaceBetween: 20,
//     },
//     500: {
//       slidesPerView: 3,
//       spaceBetween: 20,
//     },
//     768: {
//       slidesPerView: 3,
//       spaceBetween: 20,
//     },
//     1100: {
//       slidesPerView: 4,
//       spaceBetween: 20,
//     },
//     1200: {
//       slidesPerView: 4,
//       spaceBetween: 12,
//     },

//     1235: {
//       slidesPerView: 4,
//       spaceBetween: 12,
//     },
//     1400: {
//       slidesPerView: 4,
//     },
//     1530: {
//       slidesPerView: 4,
//     },
//     1890: {
//       slidesPerView: 4,
//       spaceBetween: 0,
//     },
//   },
// });
