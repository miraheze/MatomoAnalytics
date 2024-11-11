// Add Chart.js plugin for displaying "No data to display" if no valid data
Chart.register({
	id: 'noData',
	afterDraw: function (chart) {
		var datasets = chart.data.datasets;
		var hasData = datasets.some(dataset => 
			dataset.data.length > 0 && 
			dataset.data.some(value => value !== null && value !== undefined && value !== 0 && !Number.isNaN(value))
		);

		if (!hasData) {
			var ctx = chart.ctx;
			var width = chart.width;
			var height = chart.height;

			ctx.save();
			ctx.clearRect(0, 0, width, height);
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.font = '16px Arial';
			ctx.fillStyle = 'gray';
			ctx.fillText('No data to display', width / 2, height / 2);
			ctx.restore();
		}
	},
});

// Function to parse JSON data from the second <td> tag and create a chart
function parseJSONAndCreateChart() {
	const trElement = document.getElementById("mw-matomoanalytics-labels-rawdata");
	if (!trElement) return;

	// Select the second <td> element within the
	const tdElements = trElement.querySelectorAll("td");
	if (tdElements.length < 2) return;
	const jsonData = JSON.parse(tdElements[1].textContent.trim());

	const labels = Object.keys(jsonData);
	const data = Object.values(jsonData).map(value => value === "-" ? null : Number(value));

	// Create a popup window with Codex for the chart
	const popup = new OO.ui.WindowManager();
	document.body.appendChild(popup.$element[0]);
	const chartWindow = new OO.ui.MessageDialog();
	popup.addWindows([chartWindow]);

	// Open the window and directly render the chart on success
	popup.openWindow(chartWindow, {
		title: 'Data Chart',
		message: $('<canvas id="matomoanalytics-chart-info"></canvas>'),
		size: 'large'
	});

	const canvas = document.getElementById("matomoanalytics-chart-info");
	if (canvas) {
		new Chart(canvas, {
			type: 'line',
			data: {
				labels: labels,
				datasets: [{
					label: 'Data over Time',
					data: data,
					borderWidth: 1,
					borderColor: 'blue',
					backgroundColor: 'lightblue',
					fill: true
				}]
			},
			options: {
				plugins: {
					legend: {
						display: false
					}
				},
				scales: {
					y: {
						beginAtZero: true
					}
				}
			}
		});
	}
}

// Add a clickable link to trigger the chart display
document.querySelectorAll('#mw-matomoanalytics-labels-pastmonth').forEach(element => {
	element.style.cursor = 'pointer';
	element.addEventListener('click', parseJSONAndCreateChart);
});
