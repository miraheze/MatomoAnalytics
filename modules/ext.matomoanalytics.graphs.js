function extractDataAndMakeChart(canvas) {
    // Get the chart container div (assuming it has a custom class like 'chart-bar')
    const chartContainer = document.querySelector('.matomoanalytics-chart');

    // Determine the chart type based on the class (e.g., chart-bar, chart-line, etc.)
    let chartType = 'bar'; // default to 'bar'
    if (chartContainer.classList.contains('chart-line')) {
        chartType = 'line';
    } else if (chartContainer.classList.contains('chart-pie')) {
        chartType = 'pie';
    } else if (chartContainer.classList.contains('chart-doughnut')) {
        chartType = 'doughnut';
    }

    // Get all the elements with class 'oo-ui-fieldLayout-body'
    const fieldLayouts = document.querySelectorAll('.oo-ui-fieldLayout-body');
    
    // Initialize empty objects for labels and data
    const labels = {};
    const data = {};

    // Loop through each field layout
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

// Call the function to generate the chart
const canvas = document.getElementById('matomoanalytics-chart-browser');
extractDataAndMakeChart(canvas);
