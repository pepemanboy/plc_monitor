<!DOCTYPE HTML>
<html>
<head>
<script>
window.onload = function () {

var limit = 10000;    //increase number of dataPoints by increasing the limit
var y = 100;    
var data = [];
var dataSeries = { type: "line" };
var dataPoints = [];
for (var i = 0; i < limit; i += 1) {
	y += Math.round(Math.random() * 10 - 5);
	dataPoints.push({
		x: i,
		y: y
	});
}
dataSeries.dataPoints = dataPoints;
data.push(dataSeries);

//Better to construct options first and then pass it as a parameter
var options = {
	zoomEnabled: true,
	animationEnabled: true,
	title: {
		text: "Try Zooming - Panning"
	},
	axisY: {
		includeZero: false
	},
	data: data  // random data
};

$("#chartContainer").CanvasJSChart(options);

}
</script>
</head>
<body>
<div id="chartContainer" style="height: 370px; width: 100%;"></div>
<script src="https://canvasjs.com/assets/script/jquery-1.11.1.min.js"></script>
<script src="https://canvasjs.com/assets/script/jquery.canvasjs.min.js"></script>
</body>
</html>