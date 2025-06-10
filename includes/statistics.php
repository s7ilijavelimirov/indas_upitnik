<?php
// includes/statistics.php

if (!defined('ABSPATH')) {
    exit;
}

class Survey_Statistics
{
    public static function get_feedback_statistics()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'course_feedback';

        // Osnovne statistike
        $total_feedback = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $standard_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE feedback_type = 'standard'");
        $inhouse_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE feedback_type = 'inhouse'");

        // Prosečne ocene
        $avg_ratings = $wpdb->get_row("
            SELECT 
                ROUND(AVG(expectations_level), 1) as avg_expectations,
                ROUND(AVG(lecture_quality), 1) as avg_lecture,
                ROUND(AVG(lecturer_quality), 1) as avg_lecturer,
                ROUND(AVG(practical_application), 1) as avg_practical,
                ROUND(AVG(literature), 1) as avg_literature,
                ROUND(AVG(premises), 1) as avg_premises,
                ROUND(AVG(food), 1) as avg_food,
                ROUND(AVG(cooperation), 1) as avg_cooperation
            FROM $table_name
        ");

        // Distribucija ocena po kategorijama
        $rating_distribution = [];
        $rating_fields = ['expectations_level', 'lecture_quality', 'lecturer_quality', 'practical_application', 'literature'];

        foreach ($rating_fields as $field) {
            $distribution = $wpdb->get_results("
                SELECT $field as rating, COUNT(*) as count 
                FROM $table_name 
                WHERE $field IS NOT NULL 
                GROUP BY $field 
                ORDER BY $field
            ");
            $rating_distribution[$field] = $distribution;
        }

        // Broj popunjenih komentara
        $comment_stats = $wpdb->get_row("
            SELECT 
                COUNT(CASE WHEN advanced_step7 != '' THEN 1 END) as step7_filled,
                COUNT(CASE WHEN other_courses != '' THEN 1 END) as courses_filled,
                COUNT(CASE WHEN improvements != '' THEN 1 END) as improvements_filled,
                COUNT(CASE WHEN additional_comments != '' THEN 1 END) as comments_filled
            FROM $table_name
        ");

        // Očekivanja ispunjena (da/ne)
        $expectations_met = $wpdb->get_results("
            SELECT expectations_met, COUNT(*) as count 
            FROM $table_name 
            GROUP BY expectations_met
        ");

        return [
            'total_feedback' => $total_feedback,
            'standard_count' => $standard_count,
            'inhouse_count' => $inhouse_count,
            'avg_ratings' => $avg_ratings,
            'rating_distribution' => $rating_distribution,
            'comment_stats' => $comment_stats,
            'expectations_met' => $expectations_met
        ];
    }

    public static function render_statistics_widget()
    {
        $stats = self::get_feedback_statistics();

        if ($stats['total_feedback'] == 0) {
            echo '<p>Nema podataka za prikaz statistika.</p>';
            return;
        }

?>
        <div class="statistics-container">
            <!-- Osnovni pregled -->
            <div class="quick-stats">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['total_feedback']; ?></span>
                    <span class="stat-label">Ukupno upitnika</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['standard_count']; ?></span>
                    <span class="stat-label">Standard upitnici</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['inhouse_count']; ?></span>
                    <span class="stat-label">In-house upitnici</span>
                </div>
                <?php if (!empty($stats['expectations_met'])): ?>
                    <?php
                    $positive_expectations = 0;
                    foreach ($stats['expectations_met'] as $exp) {
                        if ($exp->expectations_met === 'da') {
                            $positive_expectations = $exp->count;
                        }
                    }
                    $positive_percentage = round(($positive_expectations / $stats['total_feedback']) * 100, 1);
                    ?>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $positive_percentage; ?>%</span>
                        <span class="stat-label">Ispunjena očekivanja</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Charts sekcija -->
            <div class="charts-container">
                <!-- Prosečne ocene -->
                <div class="postbox chart-widget">
                    <h3>Prosečne ocene</h3>
                    <div class="chart-content">
                        <canvas id="ratingsChart" width="300" height="300"></canvas>
                    </div>
                </div>

                <!-- Popunjenost komentara -->
                <div class="postbox chart-widget">
                    <h3>Popunjenost komentara</h3>
                    <div class="chart-content">
                        <canvas id="commentsChart" width="300" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart.js -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>

        <style>
            .statistics-container .postbox {
                margin-bottom: 20px;
            }

            .statistics-container .postbox h3 {
                margin: 0 0 15px 0;
                padding: 10px 15px;
                background: #f6f7f7;
                border-bottom: 1px solid #c3c4c7;
                font-size: 14px;
                font-weight: 600;
            }

            /* Charts container */
            .charts-container {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 20px;
                margin-top: 20px;
            }

            .chart-widget {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
            }

            .chart-content {
                padding: 20px;
                display: flex;
                justify-content: center;
                align-items: center;
            }

            .chart-content canvas {
                max-width: 300px;
                max-height: 300px;
            }

            /* Responsive design */
            @media (max-width: 768px) {
                .charts-container {
                    grid-template-columns: 1fr;
                    gap: 15px;
                }

                .chart-content canvas {
                    max-width: 250px;
                    max-height: 250px;
                }
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                // Prosečne ocene - Radar chart
                const ratingsCtx = document.getElementById('ratingsChart').getContext('2d');
                const ratingsChart = new Chart(ratingsCtx, {
                    type: 'radar',
                    data: {
                        labels: [
                            <?php
                            $rating_labels = [
                                'avg_expectations' => 'Očekivanja',
                                'avg_lecture' => 'Predavanja',
                                'avg_lecturer' => 'Predavač',
                                'avg_practical' => 'Primenjivost',
                                'avg_literature' => 'Literatura',
                                'avg_premises' => 'Prostorije',
                                'avg_food' => 'Ishrana',
                                'avg_cooperation' => 'Saradnja'
                            ];
                            $first = true;
                            foreach ($rating_labels as $key => $label) {
                                $value = $stats['avg_ratings']->$key ?? null;
                                if ($value !== null && $value > 0) {
                                    if (!$first) echo ', ';
                                    echo "'" . $label . "'";
                                    $first = false;
                                }
                            }
                            ?>
                        ],
                        datasets: [{
                            label: 'Prosečne ocene',
                            data: [
                                <?php
                                $first = true;
                                foreach ($rating_labels as $key => $label) {
                                    $value = $stats['avg_ratings']->$key ?? null;
                                    if ($value !== null && $value > 0) {
                                        if (!$first) echo ', ';
                                        echo $value;
                                        $first = false;
                                    }
                                }
                                ?>
                            ],
                            fill: true,
                            backgroundColor: 'rgba(0, 115, 170, 0.2)',
                            borderColor: '#0073aa',
                            pointBackgroundColor: '#0073aa',
                            pointBorderColor: '#fff',
                            pointHoverBackgroundColor: '#fff',
                            pointHoverBorderColor: '#0073aa'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        elements: {
                            line: {
                                borderWidth: 3
                            }
                        },
                        scales: {
                            r: {
                                angleLines: {
                                    display: false
                                },
                                suggestedMin: 0,
                                suggestedMax: 5,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });

                // Popunjenost komentara - Doughnut chart
                const commentsCtx = document.getElementById('commentsChart').getContext('2d');
                const commentsChart = new Chart(commentsCtx, {
                    type: 'doughnut',
                    data: {
                        labels: [
                            <?php
                            $comment_labels = [
                                'step7_filled' => 'Napredni STEP7',
                                'courses_filled' => 'Drugi kursevi',
                                'improvements_filled' => 'Poboljšanja',
                                'comments_filled' => 'Dodatni komentari'
                            ];
                            $first = true;
                            foreach ($comment_labels as $key => $label) {
                                if (!$first) echo ', ';
                                echo "'" . $label . "'";
                                $first = false;
                            }
                            ?>
                        ],
                        datasets: [{
                            data: [
                                <?php
                                $first = true;
                                foreach ($comment_labels as $key => $label) {
                                    $count = $stats['comment_stats']->$key ?? 0;
                                    if (!$first) echo ', ';
                                    echo $count;
                                    $first = false;
                                }
                                ?>
                            ],
                            backgroundColor: [
                                '#0073aa',
                                '#00a32a',
                                '#d63638',
                                '#dba617'
                            ],
                            borderWidth: 2,
                            borderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    usePointStyle: true
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const total = <?php echo $stats['total_feedback']; ?>;
                                        const percentage = Math.round((context.parsed / total) * 100);
                                        return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                    }
                                }
                            }
                        }
                    }
                });
            });
        </script>
<?php
    }
}
