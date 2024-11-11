// Portions of code derived from PageViewInfo extension by Wikimedia
( function ( $, mw ) {
	$( function () {
		var $count = $( '#mw-matomoanalytics-labels-rawdata' ),
			count = $count.text();

		// Turn it into an <a> tag so it's obvious you can click on it
		$count.html( mw.html.element( 'a', { href: '#' }, count ) );

		$count.click( function ( e ) {
			var dialog, windowManager;
			e.preventDefault();
			function MatomoInfoPane( config ) {
				MatomoInfoPane.parent.call( this, config );
			}
			OO.inheritClass( MatomoInfoPane, OO.ui.ProcessDialog );

			MatomoInfoPane.static.title = mw.msg( 'matomoanalytics-label-pane-title', info.start, info.end );
			MatomoInfoPane.static.name = 'MatomoAnalytics';
			MatomoInfoPane.static.actions = [
				{ label: mw.msg( 'matomoanalytics-label-pane-close' ), flags: 'safe' }
			];

			MatomoInfoPane.prototype.initialize = function () {
				MatomoInfoPane.parent.prototype.initialize.apply( this, arguments );
				this.content = new OO.ui.PanelLayout( { padded: true, expanded: false } );
				this.$body.append( this.content.$element );

				// Parse the JSON data inside the <tr> tag with ID "mw-matomoanalytics-labels-rawdata"
				var jsonData = JSON.parse($('#mw-matomoanalytics-labels-rawdata').text());
				
				// Prepare labels and data for the chart
				var labels = Object.keys(jsonData);
				var data = Object.values(jsonData).map(value => value === '-' ? null : parseInt(value, 10));

				// Create a canvas for the chart
				var canvas = $('<canvas>').get(0);
				this.content.$element.append(canvas);

				// Generate the chart with parsed data
				makeChart(canvas, labels, data);
			};
			MatomoInfoPane.prototype.getActionProcess = function ( action ) {
				var dialog = this;
				if ( action ) {
					return new OO.ui.Process( function () {
						dialog.close( { action: action } );
					} );
				}
				return MatomoInfoPane.parent.prototype.getActionProcess.call( this, action );
			};

			windowManager = new OO.ui.WindowManager();
			$( 'body' ).append( windowManager.$element );

			dialog = new MatomoInfoPane( { size: 'large' } );
			windowManager.addWindows( [ dialog ] );
			windowManager.openWindow( dialog );
		} );

	} );
}( jQuery, mediaWiki ) );

// Chart.js plugin registration and functions
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
});

function makeChart( canvas, labels, data ) {
	// eslint-disable-next-line
	new Chart(canvas, {
		type: 'line',
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
