function extractDataAndMakeChart(fieldset) {
    // Get the chart canvas inside the current fieldset
    const canvas = fieldset.querySelector('#matomoanalytics-chart');
    
    if (!canvas) return; // If no canvas is found, skip this fieldset

    // Determine the chart type based on the class of the canvas
    let chartType = 'bar'; // default to 'bar'
    if (canvas.classList.contains('chart-line')) {
        chartType = 'line';
    } else if (canvas.classList.contains('chart-pie')) {
        chartType = 'pie';
    } else if (canvas.classList.contains('chart-doughnut')) {
        chartType = 'doughnut';
    }

    // Get all the elements with class 'oo-ui-fieldLayout-body' inside the current fieldset
    const fieldLayouts = fieldset.querySelectorAll('.oo-ui-fieldLayout-body');
    
    // Initialize empty objects for labels and data
    const labels = {};
    const data = {};

    // Loop through each field layout to extract data
    fieldLayouts.forEach((fieldLayout) => {
        // Extract the label (inside the header span) and value (inside the field span)
        const labelElement = fieldLayout.querySelector('.oo-ui-fieldLayout-header label');
        const dataElement = fieldLayout.querySelector('.oo-ui-fieldLayout-field label');

        if (labelElement && dataElement) {
            const label = labelElement.textContent.trim();
            const value = parseInt(dataElement.textContent.trim(), 10);

            // Assign label as key and value as data
            labels[label] = label;
            data[label] = value;
        }
    });

    // Pass the extracted labels and data to the makeChart function
    makeChart(canvas, labels, data, chartType);
}

// Function to create the chart
function makeChart(canvas, labels, data, chartType) {
    new Chart(canvas, {
        type: chartType, // Dynamic chart type based on custom class
        data: {
            labels: Object.keys(labels),
            datasets: [{
                label: 'Placeholder',
                data: Object.values(data),
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Loop through all fieldsets and apply the chart
document.querySelectorAll('fieldset').forEach((fieldset) => {
    extractDataAndMakeChart(fieldset);
});
