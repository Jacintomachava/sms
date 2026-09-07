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
  };

  var chart_revenue = new ApexCharts(
    document.querySelector("#revenue-chart"),
    options_revenue
  );
  chart_revenue.render();

  // pipeline chart
  var options_pipeline = {
    series: [10, 60, 30],
    labels: ["Store", "Ad", "Search"],
    chart: {
      width: 290,
      height: 290,
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
              fontSize: "14px",
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
        breakpoint: 1750,
        options: {
          chart: {
            offsetX: 10,
          },
          legend: {
            show: false,
          },
        },
      },
      {
        breakpoint: 1800,
        options: {
          chart: {
            width: 154,
            height: 154,
            offsetX: 40,
          },
          legend: {
            show: false,
          },
        },
      },
    ],
  };

  var chart_pipeline = new ApexCharts(
    document.querySelector("#pipeline-chart"),
    options_pipeline
  );
  chart_pipeline.render();

  var swiper = new Swiper(".revenue-swiper", {
    pagination: {
      el: ".swiper-pagination",
      clickable: true,
    },
  });

  // profit chart
  var options_profit = {
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
      height: 255,
      type: "line",
      toolbar: {
        show: false,
      },
      dropShadow: {
        enabled: true,
        top: 4,
        left: 0,
        blur: 2,
        colors: ["var(--theme-default)", "#83BF6E", "#F99B0D"],
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
    colors: ["var(--theme-default)", "#83BF6E", "#F99B0D"],
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
    ],
  };

  var chart_profit = new ApexCharts(
    document.querySelector("#profit_chart"),
    options_profit
  );
  chart_profit.render();

  // earning reports
  var options_earning = {
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
      height: 245,
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
    colors: ["var(--theme-default)", "#F99B0D"],
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
        breakpoint: 1200,
        options: {
          chart: {
            height: 272,
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

  var chart_earning = new ApexCharts(
    document.querySelector("#earning-chart"),
    options_earning
  );
  chart_earning.render();

  // total transactions
  var options_total = {
    series: [
      {
        name: "transaction",
        data: [1.5, 2.1, 2.9, 3.8, 3.2, 2.1],
      },
      {
        name: "traffic",
        data: [-1.4, -2.2, -2.85, -3.7, -3, -2.2],
      },
    ],
    chart: {
      type: "bar",
      height: 200,
      stacked: true,
      toolbar: {
        show: false,
      },
    },
    colors: ["#83BF6E", "var(--theme-default)"],
    plotOptions: {
      bar: {
        horizontal: true,
        barHeight: "40%",
      },
    },
    dataLabels: {
      enabled: false,
    },
    stroke: {
      width: 1,
      colors: ["#fff"],
    },

    grid: {
      borderColor: "var(--chart-border)",
      strokeDashArray: 2,
      xaxis: {
        lines: {
          show: true,
        },
      },
      yaxis: {
        lines: {
          show: false,
        },
      },
    },
    yaxis: {
      min: -5,
      max: 5,
      labels: {
        show: false,
      },
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
    xaxis: {
      categories: ["85+", "80-84", "75-79", "70-74", "65-69", "60-64"],
      position: "top",
      axisTicks: {
        show: false,
      },
      axisBorder: {
        show: false,
      },
    },
    legend: {
      show: false,
    },

    responsive: [
      {
        breakpoint: 1441,
        options: {
          chart: {
            height: 180,
          },
        },
      },
    ],
  };

  var chart_total = new ApexCharts(
    document.querySelector("#total-transaction-chart"),
    options_total
  );
  chart_total.render();
})();
