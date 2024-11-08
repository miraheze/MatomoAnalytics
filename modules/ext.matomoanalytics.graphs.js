// eslint-disable-next-line no-undef
Chart.register({
	id: 'noData',
	afterDraw: function (chart) {
		var datasets = chart.data.datasets;

		// Improved data check to exclude NaN, null, undefined, or 0 values
		var hasData = datasets.some(dataset => 
			dataset.data.length > 0 && 
			dataset.data.some(value => value !== null && value !== undefined && value !== 0 && !Number.isNaN(value))
		);

		// Display the message if there's no valid data
		if (!hasData) {
			var ctx = chart.ctx;
			var width = chart.width;
			var height = chart.height;

			// Clear the chart canvas
			ctx.save();
			ctx.clearRect(0, 0, width, height);

			// Set text properties and display the message
			ctx.textAlign = 'center';
			ctx.textBaseline = 'middle';
			ctx.font = '16px Arial';
			ctx.fillStyle = 'gray';
			ctx.fillText('No data to display', width / 2, height / 2);

			ctx.restore();
		}
	},
} );

function extractDataAndMakeChart( fieldset ) {
	// Get the chart canvas inside the current fieldset
	var canvas = fieldset.querySelector('[class^="matomoanalytics-chart"]');

	if ( !canvas ) {
		return; // If no canvas is found, skip this fieldset
	}

	// Set the canvas display to "block" initially
	canvas.style.display = 'block';

	// Determine the chart type based on the class of the canvas
	var chartType = 'bar'; // default to 'bar'
	if ( canvas.classList.contains( 'matomoanalytics-chart-line' ) ) {
		chartType = 'line';
	} else if ( canvas.classList.contains( 'matomoanalytics-chart-bubble' ) ) {
		chartType = 'bubble';
	} else if ( canvas.classList.contains( 'matomoanalytics-chart-pie' ) ) {
		chartType = 'pie';
	} else if ( canvas.classList.contains( 'matomoanalytics-chart-doughnut' ) ) {
		chartType = 'doughnut';
	} else if ( canvas.classList.contains( 'matomoanalytics-chart-polarArea' ) ) {
		chartType = 'polarArea';
	} else if ( canvas.classList.contains( 'matomoanalytics-chart-scatter' ) ) {
		chartType = 'scatter';
	} else if ( canvas.classList.contains( 'matomoanalytics-chart-radar' ) ) {
		chartType = 'radar';
	}

	// Get all the elements with class 'oo-ui-fieldLayout-body' inside the current fieldset
	var fieldLayouts = fieldset.querySelectorAll( '.oo-ui-fieldLayout-body' );

	// Initialize empty arrays for labels and data
	var labels = [];
	var data = [];

	// Loop through each field layout to extract data
	fieldLayouts.forEach( ( fieldLayout ) => {
		// Extract the label (inside the header span) and value (inside the field span)
		var labelElement = fieldLayout.querySelector( '.oo-ui-fieldLayout-header label:not([id*="-info"])' );
		var dataElement = fieldLayout.querySelector( '.oo-ui-fieldLayout-field label:not([id*="-info"])' );

		if ( labelElement && dataElement ) {
			var label = labelElement.textContent.trim();
			var value = parseInt(dataElement.textContent.trim(), 10);

			if ( !isNaN( value ) ) {
				labels.push( label );
				data.push( value );
			}
		}
	});

	// Pass the extracted labels and data to the makeChart function
	makeChart( canvas, labels, data, chartType );
}

// Function to create the chart
function makeChart( canvas, labels, data, chartType ) {
	// eslint-disable-next-line
	new Chart( canvas, {
		type: chartType, // Dynamic chart type based on custom class
		data: {
			labels: labels,
			datasets: [{
				data: data,
				borderWidth: 1
			}]
		},
		options: {
			plugins: {
				legend: {
					display: false
				}
			}
		}
	});
}

// Loop through all fieldsets and apply the chart
// eslint-disable-next-line mediawiki/no-nodelist-unsupported-methods
document.querySelectorAll( 'fieldset' ).forEach( ( fieldset ) => {
	extractDataAndMakeChart( fieldset );
} );
