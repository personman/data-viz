<?php

class Report
{
    public function getExecutiveBarChart($config)
    {
        $yourCollegeLabel = 'Your College';
        $chartXCategories = array();

        $colorConfig = $colors = array(
            'seriesColors' => array(
                '#9cc03e', // '#005595' lightened 40%
                '#3366B4', // '#519548' lightened 30%
            ),
            'yourCollegeColors' => array(
                '#507400',
                '#001A68'
            )
        );
        // What color will the bar be?
        $seriesColors = $colorConfig['seriesColors'];
        $yourCollegeColors = $colorConfig['yourCollegeColors'];



        $series = array();
        $i = 0;
        foreach ($config['benchmarks'] as $dbColumn => $label) {
            $benchmark = $this->getBenchmarkModel()
                ->findOneByDbColumnAndStudy($dbColumn, $this->getStudy()->getId());

            // Get the college's reported value
            $reportedValue = $this->getObservation()->get($dbColumn);

            $format = $this->getFormat($benchmark);
            $roundedFormat = $this->getFormat($benchmark, 0);

            $chartValues = array($yourCollegeLabel => $reportedValue);

            // Load the percentiles
            $percentiles = $this->getPercentileModel()
                ->findByBenchmarkAndYear($benchmark, $this->getYear());

            $percentileData = array();
            foreach ($percentiles as $percentile) {
                $percentileData[$percentile->getPercentile()] =
                    floatval($percentile->getValue());
            }
            unset($percentileData['N']);

            $chartValues = $chartValues + $percentileData;

            $chartData = array();

            foreach ($chartValues as $key => $value) {
                $dataPoint = array(
                    'name' => $label,
                    'y' => floatval($value),
                    'color' => $seriesColors[$i],
                    'dataLabels' => array(
                        'format' => $roundedFormat,
                        'enabled' => false
                    )
                );

                // The First bar: your college
                if ($key == $yourCollegeLabel) {
                    // Show the value as a dataLabel for Your College
                    $dataPoint['dataLabels']['enabled'] = true;
                    $dataPoint['color'] = $yourCollegeColors[$i];

                    // Don't show them for stacked bars (we'll show the total)
                    if (!empty($config['stacked'])) {
                        $dataPoint['dataLabels']['enabled'] = false;
                    }
                }

                $chartData[] = $dataPoint;
            }

            // Set up the categories
            if (empty($chartXCategories)) {
                foreach ($chartValues as $key => $chartValue) {
                    //$label = nccbp_report_presidents_x_label($key);
                    $chartXCategories[] = $key;
                }
            }

            $series[] = array(
                'name' => $config['benchmarks'][$dbColumn],
                'data' => $chartData,
                'color' => $seriesColors[$i]
            );

            $i++;

        }

        $chartTitle = $config['title'];

        $highChartsConfig = array(
            'id' => rand(1, 10000),
            'chart' => array(
                'type' => 'column',
                'events' => array(
                    'load' => 'chartLoaded'
                )
            ),
            'title' => array(
                'text' => $chartTitle,
            ),
            'xAxis' => array(
                'categories' => $chartXCategories,
                'tickLength' => 0,
                'title' => array(
                    'text' => 'Percentiles'
                )
            ),
            'yAxis' => array(
                'title' => false,
                'gridLineWidth' => 0,
                'stackLabels' => array(
                    'enabled' => true,
                    'format' => str_replace('y', 'total', $roundedFormat)
                ),
                'labels' => array(
                    'format' => str_replace('y', 'value', $format)
                )
            ),
            'tooltip' => array(
                'pointFormat' => str_replace('y', 'point.y', $format)
            ),
            'series' => $series,
            'credits' => array(
                'enabled' => false
            ),
            'plotOptions' => array(
                'series' => array(
                    'animation' => false,
                    'dataLabels' => array(
                        'overflow' => 'none',
                        'crop' => false
                    )
                )
            )
        );

        if (!empty($config['stacked'])) {
            $highChartsConfig['plotOptions']['column'] = array(
                'stacking' => 'normal'
            );
        }

        if (!empty($config['percent'])) {
            $highChartsConfig['yAxis']['max'] = 100;
            $highChartsConfig['yAxis']['labels']['format'] = '{value}%';
            $highChartsConfig['yAxis']['tickInterval'] = 25;
        }

        if (!empty($config['dollars'])) {
            $highChartsConfig['yAxis']['labels']['format'] =  '${value}';
        }

        return array(
            'chart' => $highChartsConfig,
            'description' => $config['description']
        );
    }


}
