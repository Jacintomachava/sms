(function () {
  // revenue chart js
  var options_revenue = {
    series: [
      {
        name: "Sales",
        data: [5, 25, 3, 20, 15],
      },
      {
        name: "Revenue",
        data: [5, 15, 3, 14, 15],
      },
    ],
    chart: {
      height: 140,
      type: "line",
      toolbar: {
        show: false,
      },
    },
    stroke: {
      width: 2,
      curve: "smooth",
    },
    xaxis: {
      type: "category",
      categories: ["Sat", "Sun", "Mon", "Tue", "Wed"],
      tickAmount: 6,
      axisBorder: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      labels: {
        style: {
          colors: ["var(--body-light-font-color)"],
        },
      },
    },
    grid: {
      show: true,
      borderColor: "var(--chart-border)",
      strokeDashArray: 6,
      position: "back",
    },
    colors: ["#80be70", "#c8e7e5"],
    fill: {
      type: "gradient",
      gradient: {
        shade: "dark",
        gradientToColors: ["#029eb4"],
        shadeIntensity: 1,
        type: "horizontal",
        opacityFrom: 1,
        opacityTo: 1,
        stops: [0, 100, 100, 100],
      },
    },
    legend: {
      show: false,
    },
    yaxis: {
      min: 0,
      max: 30,
      tickAmount: 3,
    },

    responsive: [
      {
        breakpoint: 1296,
        options: {
          chart: {
            width: "100%",
          },
          xaxis: {
            labels: {
              show: false,
            },
          },
          yaxis: {
            labels: {
              show: false,
            },
          },
        },
      },
      {
        breakpoint: 1200,
        options: {
          chart: {
            width: "100%",
            height: 120,
          },
        },
      },
    ],
  };

  var chart_revenue = new ApexCharts(
    document.querySelector("#revenue-chart1"),
    options_revenue
  );
  chart_revenue.render();

  // pipeline chart
  var options_pipeline = {
    series: [10, 60, 30],
    labels: ["Store", "Ad", "Search"],
    chart: {
      width: 270,
      height: 270,
      type: "donut",
    },
    plotOptions: {
      pie: {
        startAngle: -90,
        endAngle: 270,
        donut: {
          labels: {
            show: true,
            name: {
              offsetY: 4,
            },
            total: {
              show: true,
              fontSize: "12px",
              fontFamily: "Inter, sans-serif",
              fontWeight: 600,
              label: "88%",
              color: "#000",
            },
          },
        },
      },
    },
    dataLabels: {
      enabled: false,
    },
    colors: ["#f99b0d", "#009DB5", "#7fbe71"],
    fill: {
      type: "gradient",
    },
    legend: {
      formatter: function (val, opts) {
        return val + " - " + opts.w.globals.series[opts.seriesIndex];
      },
    },
    responsive: [
      {
        breakpoint: 480,
        options: {
          chart: {
            width: 200,
          },
          legend: {
            position: "bottom",
          },
        },
      },
      {
        breakpoint: 680,
        options: {
          chart: {
            width: "100%",
            height: 155,
          },
          legend: {
            show: false,
          },
        },
      },
      {
        breakpoint: 735,
        options: {
          chart: {
            width: 165,
            height: 165,
          },
          legend: {
            show: false,
          },
        },
      },
      {
        breakpoint: 1263,
        options: {
          chart: {
            offsetX: 10,
            width: 150,
            height: 150,
          },
          legend: {
            show: false,
          },
        },
      },
      {
        breakpoint: 1466,
        options: {
          chart: {
            offsetX: 10,
            width: 140,
            height: 140,
          },
          legend: {
            show: false,
          },
        },
      },
    ],
  };

  var chart_pipeline = new ApexCharts(
    document.querySelector("#pipeline-chart1"),
    options_pipeline
  );
  chart_pipeline.render();

  var swiper = new Swiper(".revenue-swiper1", {
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
  });

  // profit chart
  var options_profit1 = {
    series: [
      {
        name: "Income",
        type: "line",
        data: [60, 80, 30, 60, 30, 90],
      },
      {
        name: "Earnings",
        type: "line",
        data: [55, 65, 55, 80, 40, 65],
      },
      {
        name: "Profit",
        type: "line",
        data: [50, 40, 70, 40, 100, 70],
      },
    ],
    chart: {
      height: 300,
      type: "line",
      toolbar: {
        show: false,
      },
      dropShadow: {
        enabled: true,
        top: 4,
        left: 0,
        blur: 2,
        colors: ["#009DB5", "#83BF6E", "#F99B0D"],
        opacity: 0.02,
      },
    },
    grid: {
      show: true,
      borderColor: "var(--chart-border)",
      strokeDashArray: 6,
      position: "back",
      xaxis: {
        lines: {
          show: false,
        },
      },
      padding: {
        top: 0,
        right: 0,
        bottom: 0,
        left: 0,
      },
    },
    colors: ["#009DB5", "#83BF6E", "#F99B0D"],
    stroke: {
      width: 3,
      curve: "smooth",
      opacity: 1,
    },
    markers: {
      discrete: [
        {
          seriesIndex: 1,
          dataPointIndex: 3,
          fillColor: "#54BA4A",
          strokeColor: "var(--white)",
          size: 6,
        },
      ],
    },
    tooltip: {
      shared: false,
      intersect: false,
      marker: {
        width: 5,
        height: 5,
      },
    },
    xaxis: {
      type: "category",
      categories: ["Jan 02", "Jan 05", "Jan 08", "Jan 11", "Jan 14", "Jan 17"],
      min: 0.9,
      max: undefined,
      crosshairs: {
        show: false,
      },
      labels: {
        style: {
          colors: "var(--chart-text-color)",
          fontSize: "12px",
          fontFamily: "Rubik, sans-serif",
          fontWeight: 400,
        },
      },
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
      tooltip: {
        enabled: false,
      },
    },
    fill: {
      opacity: 1,
      type: "gradient",
      gradient: {
        shade: "light",
        type: "horizontal",
        shadeIntensity: 1,
        opacityFrom: 0.95,
        opacityTo: 1,
        stops: [0, 90, 100],
      },
    },
    yaxis: {
      tickAmount: 5,
    },
    legend: {
      show: false,
    },
    responsive: [
      {
        breakpoint: 651,
        options: {
          chart: {
            height: 210,
          },
        },
      },
      {
        breakpoint: 992,
        options: {
          chart: {
            height: 255,
          },
        },
      },
      {
        breakpoint: 1221,
        options: {
          chart: {
            height: 290,
          },
        },
      },
      {
        breakpoint: 1378,
        options: {
          chart: {
            height: 300,
          },
        },
      },
      {
        breakpoint: 1576,
        options: {
          chart: {
            height: 270,
          },
        },
      },
    ],
  };

  var chart_profit = new ApexCharts(
    document.querySelector("#profit_chart1"),
    options_profit1
  );
  chart_profit.render();

  // earning reports
  var options_earning1 = {
    series: [
      {
        name: "Net Profit",
        data: [90, 40, 114, 56, 90, 80, 90],
      },
      {
        name: "Revenue",
        data: [60, 75, 90, 80, 61, 30, 70],
      },
    ],
    chart: {
      type: "bar",
      height: 220,
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        borderRadius: 4,
        horizontal: false,
        columnWidth: "40%",
        endingShape: "rounded",
      },
    },
    grid: {
      show: true,
      borderColor: "var(--chart-border)",
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
    dataLabels: {
      enabled: false,
    },
    stroke: {
      show: false,
      width: 0,
    },
    xaxis: {
      categories: ["Mo", "Tu", "We", "Th", "Fr", "Sa", "Su"],
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
    yaxis: {
      labels: {
        show: false,
      },
    },
    colors: ["#009DB5", "#F99B0D"],
    fill: {
      type: ["solid", "gradient"],
      opacity: 1,
    },
    legend: {
      show: false,
    },
    responsive: [
      {
        breakpoint: 1531,
        options: {
          chart: {
            height: 255,
          },
        },
      },
      {
        breakpoint: 1401,
        options: {
          chart: {
            height: 230,
          },
        },
      },
      {
        breakpoint: 1200,
        options: {
          chart: {
            height: 308,
          },
        },
      },
      {
        breakpoint: 992,
        options: {
          chart: {
            height: 265,
          },
        },
      },
      {
        breakpoint: 963,
        options: {
          chart: {
            height: 272,
          },
        },
      },
    ],
  };

  var chart_earning1 = new ApexCharts(
    document.querySelector("#earning-chart1"),
    options_earning1
  );
  chart_earning1.render();

  // project overview chart
  var options_overview1 = {
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
      height: 290,
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

  var chart_overview1 = new ApexCharts(
    document.querySelector("#project-overview1"),
    options_overview1
  );
  chart_overview1.render();

  // bar overview chart
  var options_bar1 = {
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

  var chart_bar1 = new ApexCharts(
    document.querySelector("#project-bar1"),
    options_bar1
  );
  chart_bar1.render();

  // 2 chart
  var options_weekly_visitor1 = {
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
            offsetY: 20,
          },
        },
      },
      {
        breakpoint: 1200,
        options: {
          chart: {
            height: 135,
            width: "100%",
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
    ],
  };

  var weeklyVisitor_overview = new ApexCharts(
    document.querySelector("#weekly-visitor1"),
    options_weekly_visitor1
  );
  weeklyVisitor_overview.render();

  // activity chart
  var options_earning1 = {
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
    document.querySelector("#earnings-chart1"),
    options_earning1
  );
  chart_earning.render();

  // total client chart
  var options_client1 = {
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
    document.querySelector("#total-client1"),
    options_client1
  );
  chart_client.render();
})();
