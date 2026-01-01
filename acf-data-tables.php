<?php
/**
 * Plugin Name: ACF Data Tables
 * Description: Create and manage data tables using ACF Pro with Excel-like editing and CSV/HTML import
 * Version: 1.0.0
 * Author: Gaurav Tiwari
 * Requires Plugins: advanced-custom-fields-pro
 */

defined('ABSPATH') || exit;

class ACF_Data_Tables {
    
    private static $instance = null;
    
    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'register_post_type']);
        add_action('acf/init', [$this, 'register_acf_fields']);
        add_action('admin_enqueue_scripts', [$this, 'admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'frontend_assets']);
        add_action('wp_ajax_acf_dt_import_csv', [$this, 'ajax_import_csv']);
        add_action('wp_ajax_acf_dt_import_html', [$this, 'ajax_import_html']);
        add_action('add_meta_boxes', [$this, 'add_import_metabox']);
        add_shortcode('acf_table', [$this, 'render_shortcode']);
        add_filter('manage_acf_data_table_posts_columns', [$this, 'admin_columns']);
        add_action('manage_acf_data_table_posts_custom_column', [$this, 'admin_column_content'], 10, 2);
    }
    
    /**
     * Register Custom Post Type
     */
    public function register_post_type() {
        register_post_type('acf_data_table', [
            'labels' => [
                'name'               => 'Data Tables',
                'singular_name'      => 'Data Table',
                'add_new'            => 'Add New Table',
                'add_new_item'       => 'Add New Data Table',
                'edit_item'          => 'Edit Data Table',
                'new_item'           => 'New Data Table',
                'view_item'          => 'View Data Table',
                'search_items'       => 'Search Data Tables',
                'not_found'          => 'No data tables found',
                'not_found_in_trash' => 'No data tables found in trash',
                'menu_name'          => 'Data Tables',
            ],
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-editor-table',
            'supports'            => ['title'],
            'has_archive'         => false,
            'exclude_from_search' => true,
        ]);
    }
    
    /**
     * Register ACF Fields
     */
    public function register_acf_fields() {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }
        
        acf_add_local_field_group([
            'key'      => 'group_acf_data_table',
            'title'    => 'Table Data',
            'fields'   => [
                // Table Settings
                [
                    'key'          => 'field_dt_settings_tab',
                    'label'        => 'Settings',
                    'type'         => 'tab',
                ],
                [
                    'key'          => 'field_dt_has_header',
                    'label'        => 'First Row is Header',
                    'name'         => 'dt_has_header',
                    'type'         => 'true_false',
                    'default_value' => 1,
                    'ui'           => 1,
                ],
                [
                    'key'          => 'field_dt_striped',
                    'label'        => 'Striped Rows',
                    'name'         => 'dt_striped',
                    'type'         => 'true_false',
                    'default_value' => 1,
                    'ui'           => 1,
                ],
                [
                    'key'          => 'field_dt_hover',
                    'label'        => 'Hover Effect',
                    'name'         => 'dt_hover',
                    'type'         => 'true_false',
                    'default_value' => 1,
                    'ui'           => 1,
                ],
                [
                    'key'          => 'field_dt_responsive',
                    'label'        => 'Responsive (horizontal scroll on mobile)',
                    'name'         => 'dt_responsive',
                    'type'         => 'true_false',
                    'default_value' => 1,
                    'ui'           => 1,
                ],
                [
                    'key'          => 'field_dt_sortable',
                    'label'        => 'Enable Sorting',
                    'name'         => 'dt_sortable',
                    'type'         => 'true_false',
                    'default_value' => 0,
                    'ui'           => 1,
                ],
                [
                    'key'          => 'field_dt_searchable',
                    'label'        => 'Enable Search',
                    'name'         => 'dt_searchable',
                    'type'         => 'true_false',
                    'default_value' => 0,
                    'ui'           => 1,
                ],
                [
                    'key'          => 'field_dt_custom_class',
                    'label'        => 'Custom CSS Class',
                    'name'         => 'dt_custom_class',
                    'type'         => 'text',
                    'placeholder'  => 'my-custom-table',
                ],
                
                // Column Definitions
                [
                    'key'          => 'field_dt_columns_tab',
                    'label'        => 'Columns',
                    'type'         => 'tab',
                ],
                [
                    'key'          => 'field_dt_columns',
                    'label'        => 'Column Definitions',
                    'name'         => 'dt_columns',
                    'type'         => 'repeater',
                    'min'          => 1,
                    'max'          => 20,
                    'layout'       => 'table',
                    'button_label' => 'Add Column',
                    'sub_fields'   => [
                        [
                            'key'       => 'field_dt_col_key',
                            'label'     => 'Key',
                            'name'      => 'col_key',
                            'type'      => 'text',
                            'required'  => 1,
                            'wrapper'   => ['width' => '20'],
                        ],
                        [
                            'key'       => 'field_dt_col_label',
                            'label'     => 'Label',
                            'name'      => 'col_label',
                            'type'      => 'text',
                            'required'  => 1,
                            'wrapper'   => ['width' => '30'],
                        ],
                        [
                            'key'       => 'field_dt_col_type',
                            'label'     => 'Type',
                            'name'      => 'col_type',
                            'type'      => 'select',
                            'choices'   => [
                                'text'     => 'Text',
                                'number'   => 'Number',
                                'currency' => 'Currency',
                                'percent'  => 'Percentage',
                                'link'     => 'Link',
                                'image'    => 'Image URL',
                                'html'     => 'HTML',
                            ],
                            'default_value' => 'text',
                            'wrapper'   => ['width' => '20'],
                        ],
                        [
                            'key'       => 'field_dt_col_align',
                            'label'     => 'Align',
                            'name'      => 'col_align',
                            'type'      => 'select',
                            'choices'   => [
                                'left'   => 'Left',
                                'center' => 'Center',
                                'right'  => 'Right',
                            ],
                            'default_value' => 'left',
                            'wrapper'   => ['width' => '15'],
                        ],
                        [
                            'key'       => 'field_dt_col_width',
                            'label'     => 'Width',
                            'name'      => 'col_width',
                            'type'      => 'text',
                            'placeholder' => 'auto',
                            'wrapper'   => ['width' => '15'],
                        ],
                    ],
                ],
                
                // Table Data
                [
                    'key'          => 'field_dt_data_tab',
                    'label'        => 'Table Data',
                    'type'         => 'tab',
                ],
                [
                    'key'          => 'field_dt_rows',
                    'label'        => 'Rows',
                    'name'         => 'dt_rows',
                    'type'         => 'repeater',
                    'min'          => 0,
                    'max'          => 1000,
                    'layout'       => 'block',
                    'button_label' => 'Add Row',
                    'sub_fields'   => [
                        [
                            'key'       => 'field_dt_row_data',
                            'label'     => 'Row Data (JSON)',
                            'name'      => 'row_data',
                            'type'      => 'textarea',
                            'rows'      => 2,
                            'instructions' => 'JSON object with column keys',
                        ],
                    ],
                ],
            ],
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'acf_data_table',
                    ],
                ],
            ],
            'menu_order' => 0,
            'position'   => 'normal',
            'style'      => 'default',
            'label_placement' => 'top',
        ]);
    }
    
    /**
     * Add Import Metabox
     */
    public function add_import_metabox() {
        add_meta_box(
            'acf_dt_import',
            'Import Data',
            [$this, 'render_import_metabox'],
            'acf_data_table',
            'side',
            'default'
        );
        
        add_meta_box(
            'acf_dt_shortcode',
            'Shortcode',
            [$this, 'render_shortcode_metabox'],
            'acf_data_table',
            'side',
            'high'
        );
        
        add_meta_box(
            'acf_dt_excel_editor',
            'Excel-Style Editor',
            [$this, 'render_excel_editor_metabox'],
            'acf_data_table',
            'normal',
            'high'
        );
    }
    
    /**
     * Render Import Metabox
     */
    public function render_import_metabox($post) {
        wp_nonce_field('acf_dt_import', 'acf_dt_import_nonce');
        ?>
        <div class="acf-dt-import-box">
            <p><strong>Import from CSV</strong></p>
            <input type="file" id="acf-dt-csv-file" accept=".csv">
            <button type="button" class="button" id="acf-dt-import-csv">Import CSV</button>
            
            <hr>
            
            <p><strong>Import from HTML Table</strong></p>
            <textarea id="acf-dt-html-input" rows="4" placeholder="Paste HTML table here..."></textarea>
            <button type="button" class="button" id="acf-dt-import-html">Import HTML</button>
            
            <div id="acf-dt-import-status" style="margin-top: 10px;"></div>
        </div>
        <?php
    }
    
    /**
     * Render Shortcode Metabox
     */
    public function render_shortcode_metabox($post) {
        if ($post->post_status !== 'publish') {
            echo '<p>Publish this table to get the shortcode.</p>';
            return;
        }
        ?>
        <div class="acf-dt-shortcode-box">
            <input type="text" readonly value='[acf_table id="<?php echo $post->ID; ?>"]' onclick="this.select();" style="width: 100%;">
            <p class="description">Copy and paste into your post or page.</p>
            
            <p style="margin-top: 10px;"><strong>Additional Options:</strong></p>
            <code style="font-size: 11px; display: block; margin-top: 5px;">[acf_table id="<?php echo $post->ID; ?>" class="my-class"]</code>
        </div>
        <?php
    }
    
    /**
     * Render Excel-Style Editor Metabox
     */
    public function render_excel_editor_metabox($post) {
        $columns = get_field('dt_columns', $post->ID) ?: [];
        $rows = get_field('dt_rows', $post->ID) ?: [];
        
        // Convert rows to usable format
        $table_data = [];
        foreach ($rows as $row) {
            $row_data = json_decode($row['row_data'], true);
            if ($row_data) {
                $table_data[] = $row_data;
            }
        }
        ?>
        <div class="acf-dt-excel-wrapper">
            <div class="acf-dt-toolbar">
                <button type="button" class="button" id="acf-dt-add-row">+ Add Row</button>
                <button type="button" class="button" id="acf-dt-add-col">+ Add Column</button>
                <button type="button" class="button" id="acf-dt-delete-row">Delete Selected Rows</button>
                <button type="button" class="button button-primary" id="acf-dt-save-data">Save Table Data</button>
                <span id="acf-dt-save-status" style="margin-left: 10px;"></span>
            </div>
            
            <div class="acf-dt-editor-container">
                <table class="acf-dt-editor" id="acf-dt-editor">
                    <thead>
                        <tr>
                            <th class="acf-dt-select-col"><input type="checkbox" id="acf-dt-select-all"></th>
                            <?php foreach ($columns as $col) : ?>
                                <th data-key="<?php echo esc_attr($col['col_key']); ?>">
                                    <?php echo esc_html($col['col_label']); ?>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($table_data)) : ?>
                            <tr class="acf-dt-empty-row">
                                <td colspan="<?php echo count($columns) + 1; ?>">
                                    No data yet. Add rows above or import data.
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($table_data as $row_index => $row) : ?>
                                <tr data-row="<?php echo $row_index; ?>">
                                    <td class="acf-dt-select-col">
                                        <input type="checkbox" class="acf-dt-row-select">
                                    </td>
                                    <?php foreach ($columns as $col) : ?>
                                        <td>
                                            <input type="text" 
                                                   class="acf-dt-cell" 
                                                   data-col="<?php echo esc_attr($col['col_key']); ?>"
                                                   value="<?php echo esc_attr($row[$col['col_key']] ?? ''); ?>">
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <input type="hidden" id="acf-dt-post-id" value="<?php echo $post->ID; ?>">
            <input type="hidden" id="acf-dt-columns-json" value='<?php echo esc_attr(json_encode($columns)); ?>'>
        </div>
        <?php
    }
    
    /**
     * Admin Assets
     */
    public function admin_assets($hook) {
        global $post_type;
        
        if ($post_type !== 'acf_data_table') {
            return;
        }
        
        wp_enqueue_style(
            'acf-dt-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.css',
            [],
            '1.0.0'
        );
        
        wp_enqueue_script(
            'acf-dt-admin',
            plugin_dir_url(__FILE__) . 'assets/admin.js',
            ['jquery'],
            '1.0.0',
            true
        );
        
        wp_localize_script('acf-dt-admin', 'acfDT', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('acf_dt_ajax'),
        ]);
    }
    
    /**
     * Frontend Assets
     */
    public function frontend_assets() {
        wp_register_style(
            'acf-dt-frontend',
            plugin_dir_url(__FILE__) . 'assets/frontend.css',
            [],
            '1.0.0'
        );
        
        wp_register_script(
            'acf-dt-frontend',
            plugin_dir_url(__FILE__) . 'assets/frontend.js',
            [],
            '1.0.0',
            true
        );
    }
    
    /**
     * AJAX: Import CSV
     */
    public function ajax_import_csv() {
        check_ajax_referer('acf_dt_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        $csv_data = isset($_POST['csv_data']) ? sanitize_textarea_field($_POST['csv_data']) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (empty($csv_data) || !$post_id) {
            wp_send_json_error('Invalid data');
        }
        
        $lines = array_filter(explode("\n", $csv_data));
        if (count($lines) < 2) {
            wp_send_json_error('CSV must have at least a header row and one data row');
        }
        
        // Parse header
        $header = str_getcsv(array_shift($lines));
        $columns = [];
        
        foreach ($header as $index => $label) {
            $label = trim($label);
            $key = sanitize_title($label);
            if (empty($key)) {
                $key = 'col_' . ($index + 1);
            }
            $columns[] = [
                'col_key'   => $key,
                'col_label' => $label,
                'col_type'  => 'text',
                'col_align' => 'left',
                'col_width' => '',
            ];
        }
        
        // Parse rows
        $rows = [];
        foreach ($lines as $line) {
            $values = str_getcsv($line);
            $row_data = [];
            
            foreach ($columns as $index => $col) {
                $row_data[$col['col_key']] = isset($values[$index]) ? trim($values[$index]) : '';
            }
            
            $rows[] = ['row_data' => json_encode($row_data)];
        }
        
        // Update ACF fields
        update_field('dt_columns', $columns, $post_id);
        update_field('dt_rows', $rows, $post_id);
        
        wp_send_json_success([
            'message' => sprintf('Imported %d columns and %d rows', count($columns), count($rows)),
            'columns' => $columns,
            'rows'    => count($rows),
        ]);
    }
    
    /**
     * AJAX: Import HTML
     */
    public function ajax_import_html() {
        check_ajax_referer('acf_dt_ajax', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Permission denied');
        }
        
        $html = isset($_POST['html']) ? wp_kses_post($_POST['html']) : '';
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        
        if (empty($html) || !$post_id) {
            wp_send_json_error('Invalid data');
        }
        
        // Parse HTML table
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        
        $tables = $dom->getElementsByTagName('table');
        if ($tables->length === 0) {
            wp_send_json_error('No table found in HTML');
        }
        
        $table = $tables->item(0);
        $header_row = null;
        $data_rows = [];
        
        // Try to find header in thead
        $theads = $table->getElementsByTagName('thead');
        if ($theads->length > 0) {
            $ths = $theads->item(0)->getElementsByTagName('th');
            if ($ths->length > 0) {
                $header_row = [];
                foreach ($ths as $th) {
                    $header_row[] = trim($th->textContent);
                }
            }
        }
        
        // Get all rows
        $trs = $table->getElementsByTagName('tr');
        foreach ($trs as $tr) {
            // Check if this is a header row
            $ths = $tr->getElementsByTagName('th');
            if ($ths->length > 0 && !$header_row) {
                $header_row = [];
                foreach ($ths as $th) {
                    $header_row[] = trim($th->textContent);
                }
                continue;
            }
            
            // Data row
            $tds = $tr->getElementsByTagName('td');
            if ($tds->length > 0) {
                $row = [];
                foreach ($tds as $td) {
                    $row[] = trim($td->textContent);
                }
                $data_rows[] = $row;
            }
        }
        
        if (!$header_row || empty($data_rows)) {
            wp_send_json_error('Could not parse table structure');
        }
        
        // Build columns
        $columns = [];
        foreach ($header_row as $index => $label) {
            $key = sanitize_title($label);
            if (empty($key)) {
                $key = 'col_' . ($index + 1);
            }
            $columns[] = [
                'col_key'   => $key,
                'col_label' => $label,
                'col_type'  => 'text',
                'col_align' => 'left',
                'col_width' => '',
            ];
        }
        
        // Build rows
        $rows = [];
        foreach ($data_rows as $data_row) {
            $row_data = [];
            foreach ($columns as $index => $col) {
                $row_data[$col['col_key']] = isset($data_row[$index]) ? $data_row[$index] : '';
            }
            $rows[] = ['row_data' => json_encode($row_data)];
        }
        
        // Update ACF fields
        update_field('dt_columns', $columns, $post_id);
        update_field('dt_rows', $rows, $post_id);
        
        wp_send_json_success([
            'message' => sprintf('Imported %d columns and %d rows', count($columns), count($rows)),
            'columns' => $columns,
            'rows'    => count($rows),
        ]);
    }
    
    /**
     * Render Shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'id'    => 0,
            'class' => '',
        ], $atts);
        
        $post_id = intval($atts['id']);
        if (!$post_id || get_post_type($post_id) !== 'acf_data_table') {
            return '<!-- ACF Data Table: Invalid ID -->';
        }
        
        // Enqueue assets
        wp_enqueue_style('acf-dt-frontend');
        wp_enqueue_script('acf-dt-frontend');
        
        // Get settings
        $has_header = get_field('dt_has_header', $post_id);
        $striped = get_field('dt_striped', $post_id);
        $hover = get_field('dt_hover', $post_id);
        $responsive = get_field('dt_responsive', $post_id);
        $sortable = get_field('dt_sortable', $post_id);
        $searchable = get_field('dt_searchable', $post_id);
        $custom_class = get_field('dt_custom_class', $post_id);
        
        // Get data
        $columns = get_field('dt_columns', $post_id) ?: [];
        $rows = get_field('dt_rows', $post_id) ?: [];
        
        if (empty($columns)) {
            return '<!-- ACF Data Table: No columns defined -->';
        }
        
        // Build table data
        $table_data = [];
        foreach ($rows as $row) {
            $row_data = json_decode($row['row_data'], true);
            if ($row_data) {
                $table_data[] = $row_data;
            }
        }
        
        // Build classes
        $classes = ['acf-dt-table'];
        if ($striped) $classes[] = 'acf-dt-striped';
        if ($hover) $classes[] = 'acf-dt-hover';
        if ($sortable) $classes[] = 'acf-dt-sortable';
        if ($searchable) $classes[] = 'acf-dt-searchable';
        if ($custom_class) $classes[] = sanitize_html_class($custom_class);
        if ($atts['class']) $classes[] = sanitize_html_class($atts['class']);
        
        // Start output
        ob_start();
        
        if ($responsive) {
            echo '<div class="acf-dt-responsive-wrapper">';
        }
        
        if ($searchable) {
            echo '<div class="acf-dt-search-wrapper">';
            echo '<input type="text" class="acf-dt-search" placeholder="Search table...">';
            echo '</div>';
        }
        
        echo '<table class="' . esc_attr(implode(' ', $classes)) . '" data-table-id="' . $post_id . '">';
        
        // Header
        if ($has_header) {
            echo '<thead><tr>';
            foreach ($columns as $col) {
                $style = '';
                if (!empty($col['col_width'])) {
                    $style = 'width: ' . esc_attr($col['col_width']) . ';';
                }
                $align_class = 'acf-dt-align-' . ($col['col_align'] ?? 'left');
                
                echo '<th class="' . $align_class . '" style="' . $style . '" data-col="' . esc_attr($col['col_key']) . '">';
                echo esc_html($col['col_label']);
                if ($sortable) {
                    echo '<span class="acf-dt-sort-icon"></span>';
                }
                echo '</th>';
            }
            echo '</tr></thead>';
        }
        
        // Body
        echo '<tbody>';
        foreach ($table_data as $row) {
            echo '<tr>';
            foreach ($columns as $col) {
                $value = $row[$col['col_key']] ?? '';
                $align_class = 'acf-dt-align-' . ($col['col_align'] ?? 'left');
                
                echo '<td class="' . $align_class . '">';
                echo $this->format_cell_value($value, $col['col_type'] ?? 'text');
                echo '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';
        
        echo '</table>';
        
        if ($responsive) {
            echo '</div>';
        }
        
        return ob_get_clean();
    }
    
    /**
     * Format Cell Value Based on Type
     */
    private function format_cell_value($value, $type) {
        switch ($type) {
            case 'number':
                return is_numeric($value) ? number_format((float)$value) : esc_html($value);
                
            case 'currency':
                return is_numeric($value) ? '$' . number_format((float)$value, 2) : esc_html($value);
                
            case 'percent':
                return is_numeric($value) ? number_format((float)$value, 1) . '%' : esc_html($value);
                
            case 'link':
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    $display = parse_url($value, PHP_URL_HOST) ?: $value;
                    return '<a href="' . esc_url($value) . '" target="_blank" rel="noopener">' . esc_html($display) . '</a>';
                }
                return esc_html($value);
                
            case 'image':
                if (filter_var($value, FILTER_VALIDATE_URL)) {
                    return '<img src="' . esc_url($value) . '" alt="" class="acf-dt-image">';
                }
                return esc_html($value);
                
            case 'html':
                return wp_kses_post($value);
                
            default:
                return esc_html($value);
        }
    }
    
    /**
     * Admin Columns
     */
    public function admin_columns($columns) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['shortcode'] = 'Shortcode';
        $new_columns['rows'] = 'Rows';
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Admin Column Content
     */
    public function admin_column_content($column, $post_id) {
        switch ($column) {
            case 'shortcode':
                echo '<code>[acf_table id="' . $post_id . '"]</code>';
                break;
                
            case 'rows':
                $rows = get_field('dt_rows', $post_id) ?: [];
                echo count($rows);
                break;
        }
    }
}

// AJAX handler for saving data from Excel editor
add_action('wp_ajax_acf_dt_save_data', function() {
    check_ajax_referer('acf_dt_ajax', 'nonce');
    
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Permission denied');
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $columns = isset($_POST['columns']) ? json_decode(stripslashes($_POST['columns']), true) : [];
    $rows = isset($_POST['rows']) ? json_decode(stripslashes($_POST['rows']), true) : [];
    
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }
    
    // Format rows for ACF
    $acf_rows = [];
    foreach ($rows as $row) {
        $acf_rows[] = ['row_data' => json_encode($row)];
    }
    
    // Update fields
    update_field('dt_columns', $columns, $post_id);
    update_field('dt_rows', $acf_rows, $post_id);
    
    wp_send_json_success([
        'message' => 'Table saved successfully',
        'rows'    => count($acf_rows),
    ]);
});

// Initialize
ACF_Data_Tables::instance();
