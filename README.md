# AQI Project

This is an Air Quality Index (AQI) web application that displays air quality data for various cities. The application reads compressed data files containing city-specific air quality measurements (PM2.5 and PM10) and renders them on the front end with a chart and a table for detailed viewing.

## Overview

The AQI Project provides an intuitive interface to view the air quality statistics (PM2.5 and PM10) for selected cities. It processes compressed data files using PHP’s bzip2 support and displays monthly averages for air pollutants in a chart, using the Chart.js library, as well as in a tabular format.

## Features

- **City Selection:** Dynamically loads air quality data for different cities based on user input.
- **Data Compression:** Uses bzip2 compression to handle large datasets efficiently.
- **Graphical Display:** Visualizes air quality data in the form of line charts.
- **Data Table:** Provides a table of monthly average concentrations for PM2.5 and PM10.

## Folder Structure

```bash
AQI_project
│   city.php                   # Main logic for city data processing and display
│   index.php                  # Entry point for the project
│   .gitignore                 # Git ignore file
│   package.json               # Node package manager file for JS libraries
├───inc                        # Contains helper files such as functions
├───styles                     # Contains CSS stylesheets
├───views                      # Contains header and footer templates
├───data                       # Contains compressed air quality data
└───scripts                    # Contains Chart.js and other script files
