(function () {
  Chart.defaults.global = {
    animation: true,
    animationSteps: 60,
    animationEasing: "easeOutIn",
    showScale: true,
    scaleOverride: false,
    scaleSteps: null,
    scaleStepWidth: null,
    scaleStartValue: null,
    scaleLineColor: "#eeeeee",
    scaleLineWidth: 1,
    scaleShowLabels: true,
    scaleLabel: "<%=value%>",
    scaleIntegersOnly: true,
    scaleBeginAtZero: false,
    scaleFontSize: 12,
    scaleFontStyle: "normal",
    scaleFontColor: "#717171",
    responsive: true,
    maintainAspectRatio: true,
    showTooltips: true,
    multiTooltipTemplate: "<%= value %>",
    tooltipFillColor: "#333333",
    tooltipEvents: ["mousemove", "touchstart", "touchmove"],
    tooltipTemplate: "<%if (label){%><%=label%>: <%}%><%= value %>",
    tooltipFontSize: 14,
    tooltipFontStyle: "normal",
    tooltipFontColor: "#fff",
    tooltipTitleFontSize: 16,
    TitleFontStyle: "Raleway",
    tooltipTitleFontStyle: "bold",
    tooltipTitleFontColor: "#ffffff",
    tooltipYPadding: 10,
    tooltipXPadding: 10,
    tooltipCaretSize: 8,
    tooltipCornerRadius: 6,
    tooltipXOffset: 5,
    onAnimationProgress: function () {},
    onAnimationComplete: function () {},
  };
  var barData = {
    labels: ["January", "February", "March", "April", "May", "June", "July"],
    datasets: [
      {
        label: "My First dataset",
        fillColor: "rgba(0, 157 ,181, 0.4)",
        strokeColor: yuriAdminConfig.primary,
        highlightFill: "rgba(0, 157 ,181, 0.6)",
        highlightStroke: yuriAdminConfig.primary,
        data: [35, 59, 80, 81, 56, 55, 40],
      },
      {
        label: "My Second dataset",
        fillColor: "rgba(249, 76, 142, 0.4)",
        strokeColor: yuriAdminConfig.secondary,
        highlightFill: "rgba(249, 76, 142, 0.6)",
        highlightStroke: yuriAdminConfig.secondary,
        data: [28, 48, 40, 19, 86, 27, 90],
      },
    ],
  };
  var barOptions = {
    scaleBeginAtZero: true,
    scaleShowGridLines: true,
    scaleGridLineColor: "rgba(0,0,0,0.1)",
    scaleGridLineWidth: 1,
    scaleShowHorizontalLines: true,
    scaleShowVerticalLines: true,
    barShowStroke: true,
    barStrokeWidth: 2,
    barValueSpacing: 5,
    barDatasetSpacing: 1,
  };
  var barCtx = document.getElementById("myBarGraph").getContext("2d");
  var myBarChart = new Chart(barCtx).Bar(barData, barOptions);
  var polarData = [
    {
      value: 300,
      color: yuriAdminConfig.primary,
      highlight: yuriAdminConfig.primary,
      label: "Yellow",
    },
    {
      value: 50,
      color: "#F99B0D",
      highlight: "#F99B0D",
      label: "Sky",
    },
    {
      value: 100,
      color: "#83BF6E",
      highlight: "#333",
      label: "Black",
    },
    {
      value: 40,
      color: "#25C5F7",
      highlight: "#25C5F7",
      label: "Grey",
    },
    {
      value: 120,
      color: yuriAdminConfig.secondary,
      highlight: yuriAdminConfig.secondary,
      label: "Dark Grey",
    },
  ];
  var polarOptions = {
    scaleShowLabelBackdrop: true,
    scaleBackdropColor: "rgba(255,255,255,0.75)",
    scaleBeginAtZero: true,
    scaleBackdropPaddingY: 2,
    scaleBackdropPaddingX: 2,
    scaleShowLine: true,
    segmentShowStroke: true,
    segmentStrokeColor: "#fff",
    segmentStrokeWidth: 2,
    animationSteps: 100,
    animationEasing: "easeOutBounce",
    animateRotate: true,
    animateScale: false,
    legendTemplate:
      '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>',
  };
  var polarCtx = document.getElementById("myPolarGraph").getContext("2d");
  var myPolarChart = new Chart(polarCtx).PolarArea(polarData, polarOptions);
  var lineGraphData = {
    labels: ["January", "February", "March", "April", "May", "June", "July"],
    datasets: [
      {
        label: "My First dataset",
        fillColor: "rgba(0, 157 ,181, 0.3)",
        strokeColor: yuriAdminConfig.primary,
        pointColor: yuriAdminConfig.primary,
        pointStrokeColor: "#fff",
        pointHighlightFill: "#fff",
        pointHighlightStroke: "#000",
        data: [10, 59, 80, 81, 56, 55, 40],
      },
      {
        label: "My Second dataset",
        fillColor: "rgba(249, 76, 142, 0.3)",
        strokeColor: yuriAdminConfig.secondary,
        pointColor: yuriAdminConfig.secondary,
        pointStrokeColor: "#fff",
        pointHighlightFill: "#000",
        pointHighlightStroke: yuriAdminConfig.secondary,
        data: [28, 48, 40, 19, 86, 27, 90],
      },
    ],
  };
  var lineGraphOptions = {
    scaleShowGridLines: true,
    scaleGridLineColor: "rgba(0,0,0,.05)",
    scaleGridLineWidth: 1,
    scaleShowHorizontalLines: true,
    scaleShowVerticalLines: true,
    bezierCurve: true,
    bezierCurveTension: 0.4,
    pointDot: true,
    pointDotRadius: 4,
    pointDotStrokeWidth: 1,
    pointHitDetectionRadius: 20,
    datasetStroke: true,
    datasetStrokeWidth: 2,
    datasetFill: true,
    legendTemplate:
      '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].strokeColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
  };
  var lineCtx = document.getElementById("myGraph").getContext("2d");
  var myLineCharts = new Chart(lineCtx).Line(lineGraphData, lineGraphOptions);
  var radarData = {
    labels: ["Ford", "Chevy", "Toyota", "Honda", "Mazda"],
    datasets: [
      {
        label: "My First dataset",
        fillColor: "rgba(0, 157 ,181, 0.4)",
        strokeColor: yuriAdminConfig.primary,
        pointColor: yuriAdminConfig.primary,
        pointStrokeColor: yuriAdminConfig.primary,
        pointHighlightFill: yuriAdminConfig.primary,
        pointHighlightStroke: "rgba(0, 157 ,181, 0.4)",
        data: [12, 3, 5, 18, 7],
      },
    ],
  };
  var radarOptions = {
    scaleShowGridLines: true,
    scaleGridLineColor: "rgba(0,0,0,.2)",
    scaleGridLineWidth: 1,
    scaleShowHorizontalLines: true,
    scaleShowVerticalLines: true,
    bezierCurve: true,
    bezierCurveTension: 0.4,
    pointDot: true,
    pointDotRadius: 3,
    pointDotStrokeWidth: 1,
    pointHitDetectionRadius: 20,
    datasetStroke: true,
    datasetStrokeWidth: 2,
    datasetFill: true,
    legendTemplate:
      '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<datasets.length; i++){%><li><span style="background-color:<%=datasets[i].strokeColor%>"></span><%if(datasets[i].label){%><%=datasets[i].label%><%}%></li><%}%></ul>',
  };
  var radarCtx = document.getElementById("myRadarGraph").getContext("2d");
  var myRadarChart = new Chart(radarCtx).Radar(radarData, radarOptions);
  var pieData = [
    {
      value: 300,
      color: "#D0E8EB",
      highlight: "#D0E8EB",
      label: "Primary",
    },
    {
      value: 50,
      color: "#F5DBE5",
      highlight: "#F5DBE5",
      label: "Secondary",
    },
    {
      value: 100,
      color: "#F5DAD9",
      highlight: "#F5DAD9",
      label: "Danger",
    },
  ];
  var pieOptions = {
    segmentShowStroke: true,
    segmentStrokeColor: "#fff",
    segmentStrokeWidth: 2,
    percentageInnerCutout: 0,
    animationSteps: 100,
    animationEasing: "easeOutBounce",
    animateRotate: true,
    animateScale: false,
    legendTemplate:
      '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>',
  };

  var doughnutData = [
    {
      value: 300,
      color: yuriAdminConfig.primary,
      highlight: yuriAdminConfig.primary,
      label: "Primary",
    },
    {
      value: 50,
      color: yuriAdminConfig.secondary,
      highlight: yuriAdminConfig.secondary,
      label: "Secondary",
    },
    {
      value: 100,
      color: "#83BF6E",
      highlight: "#83BF6E",
      label: "Success",
    },
  ];
  var doughnutOptions = {
    segmentShowStroke: true,
    segmentStrokeColor: "#fff",
    segmentStrokeWidth: 2,
    percentageInnerCutout: 50,
    animationSteps: 100,
    animationEasing: "easeOutBounce",
    animateRotate: true,
    animateScale: false,
    legendTemplate:
      '<ul class="<%=name.toLowerCase()%>-legend"><% for (var i=0; i<segments.length; i++){%><li><span style="background-color:<%=segments[i].fillColor%>"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>',
  };
  var doughnutCtx = document.getElementById("myDoughnutGraph").getContext("2d");
  var myDoughnutChart = new Chart(doughnutCtx).Doughnut(
    doughnutData,
    doughnutOptions
  );
  var myLineChart = {
    labels: ["", "10", "20", "30", "40", "50", "60", "70", "80"],
    datasets: [
      {
        fillColor: "rgba(81, 187, 37, 0.2)",
        strokeColor: "#83BF6E",
        pointColor: "#83BF6E",
        data: [10, 20, 40, 30, 0, 20, 10, 30, 10],
      },
      {
        fillColor: "rgba(249, 76, 142, 0.2)",
        strokeColor: yuriAdminConfig.secondary,
        pointColor: yuriAdminConfig.secondary,
        data: [20, 40, 10, 20, 40, 30, 40, 10, 20],
      },
      {
        fillColor: "rgba(0, 157 ,181, 0.2)",
        strokeColor: yuriAdminConfig.primary,
        pointColor: yuriAdminConfig.primary,
        data: [60, 10, 40, 30, 80, 30, 20, 90, 0],
      },
    ],
  };
  var ctx = document.getElementById("myLineCharts").getContext("2d");
  var LineChartDemo = new Chart(ctx).Line(myLineChart, {
    pointDotRadius: 2,
    pointDotStrokeWidth: 5,
    pointDotStrokeColor: "#ffffff",
    bezierCurve: false,
    scaleShowVerticalLines: false,
    scaleGridLineColor: "#eeeeee",
  });
})();
