<?php
// templates/reports-page.php
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap ace-container">
    <h1 class="wp-heading-inline"><?php echo esc_html__('Class Reports', 'amelia-class-extension'); ?></h1>
    
    <div class="ace-filters">
        <div class="filter-group">
            <label for="class_select"><?php echo esc_html__('Select Class', 'amelia-class-extension'); ?></label>
            <select id="class_select" class="regular-text">
                <?php
                $classes = get_posts(array('post_type' => 'amelia_class', 'posts_per_page' => -1));
                foreach ($classes as $class) {
                    echo sprintf(
                        '<option value="%d">%s</option>',
                        esc_attr($class->ID),
                        esc_html($class->post_title)
                    );
                }
                ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="report_type"><?php echo esc_html__('Report Type', 'amelia-class-extension'); ?></label>
            <select id="report_type" class="regular-text">
                <option value="attendance"><?php echo esc_html__('Attendance Report', 'amelia-class-extension'); ?></option>
                <option value="progress"><?php echo esc_html__('Progress Report', 'amelia-class-extension'); ?></option>
                <option value="comprehensive"><?php echo esc_html__('Comprehensive Report', 'amelia-class-extension'); ?></option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="date_range"><?php echo esc_html__('Date Range', 'amelia-class-extension'); ?></label>
            <input type="text" id="date_range" class="regular-text" />
        </div>
        
        <div class="filter-group">
            <button id="generate_report" class="button button-primary">
                <?php echo esc_html__('Generate Report', 'amelia-class-extension'); ?>
            </button>
        </div>
    </div>
    
    <div id="report_container" class="ace-report-container">
        <!-- Report content will be loaded here -->
    </div>
    
    <div id="export_actions" class="ace-bulk-actions" style="display: none;">
        <button id="export_pdf" class="button">
            <?php echo esc_html__('Export as PDF', 'amelia-class-extension'); ?>
        </button>
        <button id="export_excel" class="button">
            <?php echo esc_html__('Export as Excel', 'amelia-class-extension'); ?>
        </button>
    </div>
</div>
