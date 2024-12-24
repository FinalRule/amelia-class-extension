<?php
// templates/report-template.php
?>
<div class="wrap">
    <h2>Class Report</h2>
    
    <?php if ($report_type === 'attendance' || $report_type === 'comprehensive'): ?>
        <div class="card">
            <h3>Attendance Summary</h3>
            <div id="attendance_chart"></div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Total Sessions</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $student): ?>
                        <tr>
                            <td><?php echo esc_html($student->firstName . ' ' . $student->lastName); ?></td>
                            <td><?php echo esc_html($student->total_sessions); ?></td>
                            <td><?php echo esc_html($student->present_count); ?></td>
                            <td><?php echo esc_html($student->absent_count); ?></td>
                            <td><?php echo esc_html($student->late_count); ?></td>
                            <td><?php echo esc_html($student->attendance_rate); ?>%</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <?php if ($report_type === 'progress' || $report_type === 'comprehensive'): ?>
        <div class="card">
            <h3>Progress Summary</h3>
            <div id="progress_chart"></div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Average Progress</th>
                        <th>Progress Trend</th>
                        <th>Last Updated</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($progress_data as $student): ?>
                        <tr>
                            <td><?php echo esc_html($student->firstName . ' ' . $student->lastName); ?></td>
                            <td><?php echo esc_html(round($student->average_progress, 2)); ?>%</td>
                            <td>
                                <?php 
                                $trend = $student->max_progress - $student->min_progress;
                                $trend_icon = $trend > 0 ? '↑' : ($trend < 0 ? '↓' : '→');
                                echo esc_html($trend_icon . ' ' . abs($trend) . '%');
                                ?>
                            </td>
                            <td><?php echo esc_html($student->last_updated); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
    
    <?php if ($report_type === 'comprehensive'): ?>
        <div class="card">
            <h3>Overall Performance Summary</h3>
            <div id="performance_chart"></div>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Overall Score</th>
                        <th>Attendance Score</th>
                        <th>Progress Score</th>
                        <th>Performance Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_data as $student): ?>
                        <?php
                        $overall_score = ($student['attendance_rate'] + $student['average_progress']) / 2;
                        $rating = $overall_score >= 90 ? 'Excellent' :
                                ($overall_score >= 80 ? 'Good' :
                                ($overall_score >= 70 ? 'Satisfactory' : 'Needs Improvement'));
                        ?>
                        <tr>
                            <td><?php echo esc_html($student['name']); ?></td>
                            <td><?php echo esc_html(round($overall_score, 2)); ?>%</td>
                            <td><?php echo esc_html($student['attendance_rate']); ?>%</td>
                            <td><?php echo esc_html($student['average_progress']); ?>%</td>
                            <td><?php echo esc_html($rating); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
