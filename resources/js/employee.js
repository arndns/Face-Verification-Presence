am4core.ready(function () {
    // Themes begin
    am4core.useTheme(am4themes_animated);
    // Themes end

    var chart = am4core.create("chartdiv", am4charts.PieChart3D);
    chart.hiddenState.properties.opacity = 0; // this creates initial fade-in

    chart.legend = new am4charts.Legend();

    chart.data = [
        {
            status: "Hadir",
            value: 20,
        },
        {
            status: "Sakit",
            value: 2,
        },
        {
            status: "Izin",
            value: 1,
        },
        {
            status: "Terlambat",
            value: 3,
        },
    ];

    var series = chart.series.push(new am4charts.PieSeries3D());
    series.dataFields.value = "value";
    series.dataFields.category = "status";
    series.alignLabels = false;
    series.labels.template.text = "{value.percent.formatNumber('#.0')}%";
    series.labels.template.radius = am4core.percent(-40);
    series.labels.template.fill = am4core.color("white");
    series.colors.list = [
        am4core.color("#37db63"), // Hijau untuk Hadir
        am4core.color("#fca903"), // Kuning untuk Sakit
        am4core.color("#1171ba"), // Biru untuk Izin
        am4core.color("#ba113b"), // Merah untuk Terlambat
    ];
});
