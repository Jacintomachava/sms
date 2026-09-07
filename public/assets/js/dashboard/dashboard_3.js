const datatable = new simpleDatatables.DataTable("#project-status", {
  paging: false,
  tabIndex: 1,
});

var project_status = document.querySelector(".datatable-container");
project_status.className += " custom-scrollbar";

(function () {
  // activity chart
  var options_earning = {
    series: [
      {
        name: "Earning",
        data: [4, 3, 3, 3, 4, 3, 3, 4, 5, 3.5, 2.5, 2.5],
      },
    ],
    chart: {
      height: 100,
      type: "bar",
      toolbar: {
        show: false,
      },
      dropShadow: {
        enabled: true,
        top: 10,
        left: 0,
        blur: 3,
        color: "var(--theme-default)",
        opacity: 0.25,
      },
    },
    plotOptions: {
      bar: {
        borderRadius: 2,
        borderRadiusApplication: "around",
        borderRadiusWhenStacked: "last",
        columnWidth: "25%",
      },
    },
    dataLabels: {
      enabled: false,
    },
    xaxis: {
      categories: ["S", "M", "T", "W", "T", "F", "S", "s", "m", "t", "w", "t"],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      tooltip: {
        enabled: false,
      },
    },
    yaxis: {
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      labels: {
        show: false,
      },
    },
    grid: {
      show: false,
    },
    colors: ["var(--theme-default)"],
  };

  var chart_earning = new ApexCharts(
    document.querySelector("#earnings-chart"),
    options_earning
  );
  chart_earning.render();

  // total client chart
  var options_client = {
    series: [
      {
        data: [0, 15, 15, 10, 10, 20, 20, 25, 25, 25],
      },
    ],
    chart: {
      type: "area",
      height: 145,
      toolbar: {
        show: false,
      },
    },
    stroke: {
      curve: "smooth",
      width: 2,
    },
    xaxis: {
      type: "category",
      categories: [
        "jan",
        "feb",
        "mar",
        "apr",
        "may",
        "jun",
        "july",
        "aug",
        "sep",
        "oct",
      ],
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      tooltip: {
        enabled: false,
      },
    },
    grid: {
      show: false,
      padding: {
        left: -60,
      },
    },
    yaxis: {
      show: false,
    },
    dataLabels: {
      enabled: false,
    },
    markers: {
      hover: {
        sizeOffset: 4,
      },
    },
    colors: ["#F99B0D"],
    fill: {
      type: "gradient",
      gradient: {
        shade: "light",
        type: "vertical",
        shadeIntensity: 0.1,
        inverseColors: true,
        opacityFrom: 0.5,
        opacityTo: 0,
        stops: [0, 100],
      },
    },
  };

  var chart_client = new ApexCharts(
    document.querySelector("#total-client"),
    options_client
  );
  chart_client.render();

  // project overview chart
  var options_overview = {
    series: [
      {
        name: "Earning",
        type: "line",
        data: [120, 250, 70, 330, 140, 230, 90, 280, 40, 150, 350, 150, 350],
      },
      {
        name: "Order",
        type: "line",
        data: [80, 200, 150, 200, 100, 150, 110, 200, 110, 200, 150, 80, 30],
      },
    ],
    chart: {
      height: 240,
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
      show: true,
      borderColor: "var(--chart-border)",
      strokeDashArray: 0,
      position: "back",
      xaxis: {
        lines: {
          show: true,
        },
      },
      padding: {
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
      },
    },
    plotOptions: {
      bar: {
        columnWidth: "50%",
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
      "Nov",
    ],
    xaxis: {
      type: "category",
      tickAmount: 4,
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
        breakpoint: 1200,
        options: {
          chart: {
            height: 250,
          },
        },
      },
      {
        breakpoint: 1023,
        options: {
          chart: {
            height: 260,
          },
        },
      },
      {
        breakpoint: 1008,
        options: {
          chart: {
            height: 268,
          },
        },
      },
      {
        breakpoint: 1007,
        options: {
          chart: {
            height: 250,
          },
        },
      },
      {
        breakpoint: 992,
        options: {
          chart: {
            height: 240,
          },
        },
      },
    ],
  };

  var chart_overview = new ApexCharts(
    document.querySelector("#project-overview"),
    options_overview
  );

  chart_overview.render();

  window.addEventListener("resize", function (event) {
    document.querySelector("#project-overview").innerHTML = "";
    var chart_overview = new ApexCharts(
      document.querySelector("#project-overview"),
      options_overview
    );
    chart_overview.render();
  });
  // bar overview chart
  var options_bar = {
    series: [
      {
        name: "Revenue",
        data: [
          30, 40, 18, 25, 18, 10, 20, 35, 22, 40, 30, 38, 20, 35, 11, 28, 40,
          11, 28, 40, 11, 28, 40, 11, 28, 40, 11, 11, 28, 40, 11, 28, 40, 11,
          28, 40, 11,
        ],
      },
    ],
    chart: {
      type: "bar",
      height: 180,
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        horizontal: false,
        columnWidth: "55%",
      },
    },
    colors: ["var(--chart-dashed-border)"],
    grid: {
      show: false,
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      show: true,
      width: 2,
      colors: ["transparent"],
    },
    xaxis: {
      categories: [
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
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    yaxis: {
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
    },
    fill: {
      opacity: 0.7,
    },
    tooltip: {
      enabled: false,
    },
    states: {
      normal: {
        filter: {
          type: "none",
        },
      },
      hover: {
        filter: {
          type: "none",
        },
      },
      active: {
        allowMultipleDataPointsSelection: false,
        filter: {
          type: "none",
        },
      },
    },
    responsive: [
      {
        breakpoint: 405,
        options: {
          chart: {
            height: 150,
          },
        },
      },
    ],
  };

  var chart_bar = new ApexCharts(
    document.querySelector("#project-bar"),
    options_bar
  );
  chart_bar.render();

  // swiper slider
  var swiper = new Swiper(".recent-slider", {
    slidesPerView: 2,
    grid: {
      rows: 2,
    },
    spaceBetween: 20,
    navigation: {
      nextEl: ".next",
      prevEl: ".prev",
    },
    breakpoints: {
      0: {
        slidesPerView: 1,
        spaceBetween: 15,
        grid: {
          rows: 2,
        },
      },
      500: {
        slidesPerView: 2,
        spaceBetween: 20,
        grid: {
          rows: 2,
        },
      },
      1200: {
        slidesPerView: 1,
        grid: {
          rows: 2,
        },
      },
      1735: {
        slidesPerView: 2,
        spaceBetween: 20,
        grid: {
          rows: 2,
        },
      },
    },
  });

  // earning chart
  var options_earning = {
    series: [
      {
        data: [20, 40, 60, 20, 100, 60, 20, 80, 40, 10, 80, 100, 100],
      },
    ],
    chart: {
      type: "line",
      height: 160,
      toolbar: {
        show: false,
      },
      dropShadow: {
        enabled: true,
        top: 0,
        left: 0,
        blur: 20,
        color: "var(--theme-default)",
        opacity: 0.5,
      },
    },
    grid: {
      show: true,
      borderColor: "var(--chart-border)",
      strokeDashArray: 6,
      position: "back",
      xaxis: {
        lines: {
          show: true,
        },
      },
      padding: {
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
      },
    },
    stroke: {
      curve: "stepline",
      width: 2,
    },
    dataLabels: {
      enabled: false,
    },
    xaxis: {
      labels: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      tooltip: {
        enabled: false,
      },
    },
    yaxis: {
      labels: {
        show: false,
      },
      min: 0,
      tickAmount: 5,
      tickPlacement: "between",
    },
    markers: {
      size: 3,
      colors: "var(--white)",
      strokeColors: "#009DB5",
      strokeWidth: 2,
      strokeOpacity: 0.9,
      strokeDashArray: 0,
      fillOpacity: 1,
      shape: "circle",
      offsetX: 2,
      offsetY: 2,
      radius: 2,
      hover: {
        size: 3,
      },
    },
    colors: ["#009DB5"],

    responsive: [
      {
        breakpoint: 1200,
        options: {
          chart: {
            height: 122,
          },
        },
      },
      {
        breakpoint: 768,
        options: {
          chart: {
            height: 160,
          },
        },
      },
    ],
  };

  var chart_earning = new ApexCharts(
    document.querySelector("#earning-average"),
    options_earning
  );
  chart_earning.render();
})();
