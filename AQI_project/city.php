<?php
require __DIR__ . "/inc/functions.inc.php";

// Initialize the $city variable.
$city = null;
// If the 'city' parameter exists in the URL and is not empty
if (!empty($_GET["city"])) {
    // Set $city to the value passed in the 'city' query parameter.
    $city = $_GET["city"];
}

// Variables to hold the city filename and city-specific information.
$fileName = null;
$cityInformation = [];

// If a city is provided, fetch the city information from the JSON file.
if (!empty($city)) {
    // Load the cities from the index.json file and decode them into an associative array.
    $cities = json_decode(
        file_get_contents(__DIR__ . "/../data/index.json"),
        true // true parameter converts the JSON into an associative array
    );

    // Iterate through the cities to find the matching city and extract the file name and city details.
    foreach ($cities as $currentCity) {
        // "If some city in our file matches the one provided by the user..."
        if ($currentCity["city"] === $city) {
            // Assign the filename of the matching city to be used for further data processing.
            $fileName = $currentCity["filename"];
            // Store the entire city information (such as name, country, and flag) for later use.
            $cityInformation = $currentCity;
            // Break the loop since the city has been found and no further iterations are needed.
            break;
        }
    }
}

// If a valid file is found for the city, load and process its air quality data.
if (!empty($fileName)) {
    // Load and decode the city's air quality data, which is stored in a bzip2-compressed JSON file.
    // PHP supports bzip2 compression natively through the bzip2 stream wrapper.
    // Use bzip2 compression to open and decode the city's air quality data file and store it in the $results array.
    $results = json_decode(
        file_get_contents("compress.bzip2://" . __DIR__ . "/../data/" . $fileName),
        true
    )["results"];

    // Initialize units for air pollutants pm25 and pm10.
    $units = ["pm25" => null, "pm10" => null];

    // Iterate through the results to extract the units for pm25 and pm10.
    foreach ($results as $result) {
        // Stop searching once both units are found.
        if (!empty($units["pm25"]) && !empty($units["pm10"])) break;

        // Check if the current result's parameter is "pm25"
        if ($result["parameter"] === "pm25") {
            // assign the value of "unit" from the result to $units["pm25"]
            $units["pm25"] = $result["unit"];
        }
        // Check if the current result's parameter is "pm10"
        if ($result["parameter"] === "pm10") {
            // assign the value of "unit" from the result to $units["pm10"]
            $units["pm10"] = $result["unit"];
        }
    }

    // Initialize an array to store the air quality statistics for each month.
    $stats = [];
    foreach ($results as $result) {
        // If the parameter of the result is neither 'pm25' nor 'pm10', skip the current iteration and move to the next one.
        if ($result["parameter"] !== "pm25" && $result["parameter"] !== "pm10") continue;
        // If the value of the result is less than or equal to zero, skip the current iteration and move to the next one.
        if ($result["value"] <= 0) continue;

        // // Extract the month from the date string in YYYY-MM format.
        $month = substr($result["date"]["local"], 0, 7);

        // Check if the statistics for the extracted month are already set; if not, initialize them.
        if (!isset($stats[$month])) {
            $stats[$month] = ["pm25" => [], "pm10" => []];
        }

        // Add the value of the current parameter (pm25 or pm10) to the set of statistics for the specified month.
        $stats[$month][$result["parameter"]][] = $result["value"];
    }
}
?>

<?php
require __DIR__ . "/views/header.inc.php";
?>

<?php if (empty($city)): ?>
    <!-- If no city is provided or found, show an error message. -->
    <p>❌ Error: The city could not be loaded.</p>
<?php else: ?>
    <!-- Display the city name and its flag. -->
    <h1><?php echo e($cityInformation["city"]); ?> <?php echo e($cityInformation["flag"]); ?></h1>

    <?php if (!empty($stats)) : ?>
        <!-- Create a canvas for the air quality index (AQI) chart. -->
        <canvas style="width: 300px; height :200px;" id="aqi-chart"></canvas>

        <!-- Load the Chart.js library for rendering charts. -->
        <script src="../scripts/chart.umd.js"></script>

        <?php
        // Extract and sort months to be used as chart labels.
        $labels = array_keys($stats);
        sort($labels);

        // Initialize arrays for the air quality data of pm25 and pm10.
        $pm25 = [];
        $pm10 = [];
        foreach ($labels as $label) {
            $measurements = $stats[$label];

            // Check if the set of measurements for PM2.5 has any values.
            if (count($measurements["pm25"]) !== 0) {
                // Calculate the average values for pm25 for each month and add it to the $pm25 array.
                $pm25[] = array_sum($measurements["pm25"]) / count($measurements["pm25"]);
            } else {
                // If there are no measurements for PM2.5, add 0 to the $pm25 array.
                $pm25[] = 0;
            }
            // Check if the set of measurements for PM10 has any values.
            if (count($measurements["pm10"]) !== 0) {
                // Calculate the average values for pm10 for each month and add it to the $pm10 array.
                $pm10[] = array_sum($measurements["pm10"]) / count($measurements["pm10"]);
            } else {
                // If there are no measurements for PM2.5, add 0 to the $pm10 array.
                $pm10[] = 0;
            }
        }

        // Initialize an empty array to hold datasets for the chart.
        $datasets = [];
        // if the total sum of PM2.5 values is greater than zero.
        if (array_sum($pm25) > 0) {
            // If there are PM2.5 values, create a dataset for the AQI chart.
            $datasets[] = [
                "label" => "AQI , PM2.5 in {$units["pm25"]}", // Set the label with the unit of PM2.5.
                "data" => $pm25, // Assign the PM2.5 data to this dataset.
                "fill" => false, // Do not fill the area under the line.
                "borderColor" => 'rgb(75, 192, 192)', // Set the color of the line.
                "tension" => 0.1 // Set the tension of the line for smoothing.
            ];
        }
        if (array_sum($pm10) > 0) {            // If there are PM10 values, append another array of dataset for the AQI chart to the $datasets[].

            $datasets[] = [
                "label" => "AQI , PM10 in {$units["pm10"]}",
                "data" => $pm10,
                "fill" => false,
                "borderColor" => 'rgb(255, 75, 192)',
                "tension" => 0.1
            ];
        }
        ?>


        <script>
            // Once the DOM is ready, render the chart using Chart.js.
            document.addEventListener("DOMContentLoaded", function() {
                const ctx = document.getElementById("aqi-chart");
                const chart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($labels); ?>, // Use PHP to inject the labels (months) into the chart.
                        datasets: <?php echo json_encode($datasets); ?>
                    }
                });
            })
        </script>

        <!-- Display the air quality statistics in a table format. -->
        <table>
            <thead>
                <th>Month</th>
                <th>PM 2.5 concentration</th>
                <th>PM 10 concentration</th>
            </thead>
            <tbody>
                <?php
                // Iterate through the monthly statistics to display data for each month.
                foreach ($stats as $month => $measurements) : ?>
                    <tr>
                        <th><?php echo e($month); ?></th>
                        <td> <?php if (count($measurements["pm25"]) !== 0): ?>
                                <!-- Calculate and display the average PM 2.5 concentration for the month. -->
                                <?php echo e(round(array_sum($measurements["pm25"]) / count($measurements["pm25"]), 2)); ?>
                                <?php echo e($units["pm25"]); ?>
                            <?php else: ?>
                                <i>No data available</i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (count($measurements["pm10"]) !== 0): ?>
                                <!-- Calculate and display the average PM 10 concentration for the month. -->
                                <?php echo e(round(array_sum($measurements["pm10"]) / count($measurements["pm10"]), 2)); ?>
                                <?php echo e($units["pm10"]); ?>
                            <?php else: ?>
                                <i>No data available</i>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

    <?php endif; ?>
<?php endif; ?>

<?php
require __DIR__ . "/views/footer.inc.php";
?>