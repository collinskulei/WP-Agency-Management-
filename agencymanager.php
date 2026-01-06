<?php
/**
 * Plugin Name: Agency Services & Projects Manager
 * Description: A fully dynamic management system for Services and Projects with dual-filter project archives and comprehensive UI/UX customization.
 * Version: 3.1.2
 * Author: Collins Kulei
 */

if (!defined('ABSPATH')) exit;

class Agency_Service_Manager {

    public function __construct() {
        // Core Registration
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
        
        // Admin UI & Settings
        add_action('admin_menu', [$this, 'add_plugin_settings_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('add_meta_boxes', [$this, 'add_agency_meta_boxes']);
        add_action('save_post', [$this, 'save_agency_meta']);
        
        // Assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_head', [$this, 'hide_classic_editor_ui']); 

        // Frontend Displays
        add_shortcode('agency_projects_grid', [$this, 'render_projects_grid']);
        add_shortcode('agency_services_archive', [$this, 'render_services_archive']);
        
        // Filters & SEO Compatibility
        add_filter('template_include', [$this, 'template_loader']);
        add_filter('upload_mimes', [$this, 'allow_font_uploads']);
        add_filter('the_content', [$this, 'inject_custom_meta_into_content'], 1);
        add_action('pre_get_posts', [$this, 'modify_service_archive_query']);
    }

    public function modify_service_archive_query($query) {
        if (!is_admin() && $query->is_main_query() && is_post_type_archive('agency_service')) {
            $query->set('orderby', 'menu_order');
            $query->set('order', 'ASC');
        }
    }

    public function hide_classic_editor_ui() {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'agency_service') {
            echo '<style>
                .post-type-agency_service #postexcerpt { display: none !important; }
            </style>';
        }
    }

    public function inject_custom_meta_into_content($content) {
        if (!empty($content) && (strpos($content, 'fl-builder-content') !== false || strpos($content, 'elementor') !== false)) {
            return $content;
        }
        if (!is_singular(['agency_service', 'agency_project'])) return $content;
        if (get_post_type() === 'agency_service' && !empty($content)) return $content;

        $post_id = get_the_ID();
        $custom_content = (get_post_type($post_id) === 'agency_service') 
            ? get_post_meta($post_id, '_service_full_description', true) 
            : get_post_meta($post_id, '_project_full_description', true);

        return (empty($content) && !empty($custom_content)) ? wpautop($custom_content) : $content;
    }

    public function allow_font_uploads($mimes) {
        $mimes['woff'] = 'application/x-font-woff';
        $mimes['woff2'] = 'application/x-font-woff2';
        $mimes['ttf'] = 'application/x-font-ttf';
        return $mimes;
    }

    public function enqueue_admin_assets($hook) {
        if (!in_array($hook, ['post.php', 'post-new.php', 'agency-manager_page_agency-settings'])) return;
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
    }

    public function enqueue_public_assets() {
        add_action('wp_head', function() {
            echo '<style>' . $this->get_ui_styles() . '</style>';
        });
    }

    public function register_post_types() {
        register_post_type('agency_service', [
            'labels' => ['name' => 'Services', 'singular_name' => 'Service'],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'thumbnail', 'editor', 'revisions', 'page-attributes'],
            'menu_icon' => 'dashicons-rest-api',
            'rewrite' => ['slug' => 'services'],
        ]);

        register_post_type('agency_project', [
            'labels' => ['name' => 'Projects', 'singular_name' => 'Project'],
            'public' => true,
            'has_archive' => true,
            'show_in_rest' => true,
            'supports' => ['title', 'thumbnail', 'editor', 'revisions'],
            'menu_icon' => 'dashicons-portfolio',
            'rewrite' => ['slug' => 'projects'],
        ]);
    }

    public function register_taxonomies() {
        register_taxonomy('project_industry', 'agency_project', [
            'label' => 'Industries',
            'hierarchical' => true,
            'show_in_rest' => true,
            'show_admin_column' => true,
        ]);
    }

    public function add_plugin_settings_menu() {
        add_menu_page('Agency Manager', 'Agency Manager', 'manage_options', 'agency-settings', [$this, 'render_settings_page'], 'dashicons-admin-generic');
    }

    public function register_settings() {
        $defaults = [
            'agency_font_main' => 'sans-serif',
            'agency_radius' => '20px',
            'agency_shadow' => 'rgba(0,0,0,0.1)',
            'agency_custom_fonts' => [],
            'contact_url' => '#',
            'contact_btn_text' => 'Get Started',
            'explore_btn_text' => 'Explore',
            'color_primary' => '#000000',
            'color_secondary' => '#666666',
            'color_btn_text' => '#ffffff',
            'color_accent_bg' => '#f0f0f0',
            'size_card_title' => '1.4rem',
            'size_card_desc' => '0.95rem',
            'size_single_title' => '2.8rem',
            'size_single_body' => '1.1rem',
            'weight_titles' => '700',
        ];
        foreach ($defaults as $key => $default) {
            register_setting('agency_settings_group', $key);
            if (get_option($key) === false) update_option($key, $default);
        }
    }

    private function get_ui_styles() {
        $ff = get_option('agency_font_main');
        $custom_fonts = get_option('agency_custom_fonts', []);
        $font_face = "";
        foreach($custom_fonts as $f) {
            $name = pathinfo($f['url'], PATHINFO_FILENAME);
            $ext = pathinfo($f['url'], PATHINFO_EXTENSION);
            $font_face .= "@font-face { font-family: '{$name}'; src: url('{$f['url']}') format('".($ext=='ttf'?'truetype':$ext)."'); font-display: swap; }\n";
        }

        return $font_face . "
        :root {
            --ag-ff: $ff;
            --ag-rad: ".get_option('agency_radius').";
            --ag-shd: ".get_option('agency_shadow').";
            --ag-pri: ".get_option('color_primary').";
            --ag-sec: ".get_option('color_secondary').";
            --ag-btxt: ".get_option('color_btn_text').";
            --ag-acc: ".get_option('color_accent_bg').";
            --ag-ts-ct: ".get_option('size_card_title').";
            --ag-ts-cd: ".get_option('size_card_desc').";
            --ag-ts-st: ".get_option('size_single_title').";
            --ag-ts-sb: ".get_option('size_single_body').";
            --ag-fw: ".get_option('weight_titles').";
        }
        .agency-portfolio-container, .services-archive-grid { font-family: var(--ag-ff); color: var(--ag-sec); }
        .agency-card, .service-archive-card { background: #fff; border-radius: var(--ag-rad); box-shadow: 0 10px 30px var(--ag-shd); overflow: hidden; transition: 0.3s; display: flex; flex-direction: column; height: 100%; }
        .agency-card:hover, .service-archive-card:hover { transform: translateY(-5px); }
        .ag-img-wrapper { height: 240px; overflow: hidden; cursor: pointer; }
        .ag-img-wrapper img { width: 100%; height: 100%; object-fit: cover; transition: 0.5s; }
        .ag-img-wrapper:hover img { transform: scale(1.05); }
        .ag-content { padding: 25px; flex-grow: 1; display: flex; flex-direction: column; }
        .ag-content h2, .ag-content h3 { margin: 0 0 12px; color: var(--ag-pri); font-size: var(--ag-ts-ct); font-weight: var(--ag-fw); line-height: 1.2; }
        .seo-description { font-size: var(--ag-ts-cd); line-height: 1.6; margin-bottom: 20px; flex-grow: 1; }
        .ag-btn-group { display: flex; gap: 10px; flex-wrap: wrap; margin-top: auto; }
        .ag-btn { padding: 10px 22px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.3s; text-align: center; border: none; cursor: pointer; }
        .ag-btn-pri { background: var(--ag-pri); color: var(--ag-btxt); }
        .ag-btn-sec { background: var(--ag-acc); color: var(--ag-pri); }
        .ag-btn:hover { opacity: 0.85; transform: translateY(-1px); }
        .agency-single-layout h1 { font-size: var(--ag-ts-st); color: var(--ag-pri); font-weight: var(--ag-fw); margin-bottom: 25px; }
        .single-content { font-size: var(--ag-ts-sb); line-height: 1.8; color: var(--ag-sec); }
        .filter-section { margin-bottom: 20px; }
        .filter-title { font-size: 0.75rem; text-transform: uppercase; font-weight: 800; color: #aaa; margin-bottom: 10px; text-align: center; letter-spacing: 1px; }
        .filter-nav { display: flex; flex-wrap: wrap; justify-content: center; gap: 10px; }
        .filter-btn { background: var(--ag-acc); border: none; padding: 8px 18px; border-radius: 50px; cursor: pointer; font-weight: 600; color: var(--ag-pri); transition: 0.3s; font-size: 0.85rem; }
        .filter-btn.active { background: var(--ag-pri); color: var(--ag-btxt); }
        .agency-grid { min-height: 400px; }
        ";
    }

    public function render_settings_page() {
        ?>
        <div class="wrap agency-admin-wrap">
            <h1>Agency Manager Customizer</h1>
            <form method="post" action="options.php">
                <?php settings_fields('agency_settings_group'); ?>
                <div class="agency-settings-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-top: 20px;">
                    <div>
                        <h3>Global Branding</h3>
                        <table class="form-table">
                            <tr><th>Primary Color</th><td><input type="text" class="ag-color-field" name="color_primary" value="<?php echo esc_attr(get_option('color_primary')); ?>" /></td></tr>
                            <tr><th>Secondary Text</th><td><input type="text" class="ag-color-field" name="color_secondary" value="<?php echo esc_attr(get_option('color_secondary')); ?>" /></td></tr>
                            <tr><th>Button Text Color</th><td><input type="text" class="ag-color-field" name="color_btn_text" value="<?php echo esc_attr(get_option('color_btn_text')); ?>" /></td></tr>
                            <tr><th>Accent Background</th><td><input type="text" class="ag-color-field" name="color_accent_bg" value="<?php echo esc_attr(get_option('color_accent_bg')); ?>" /></td></tr>
                            <tr><th>Border Radius</th><td><input type="text" name="agency_radius" value="<?php echo esc_attr(get_option('agency_radius')); ?>" /></td></tr>
                        </table>
                    </div>
                    <div>
                        <h3>Typography & Content</h3>
                        <table class="form-table">
                            <tr><th>Font Family</th><td><input type="text" name="agency_font_main" value="<?php echo esc_attr(get_option('agency_font_main')); ?>" /></td></tr>
                            <tr><th>Title Weight</th><td><input type="number" name="weight_titles" value="<?php echo esc_attr(get_option('weight_titles')); ?>" step="100" /></td></tr>
                            <tr><th>Contact URL</th><td><input type="text" name="contact_url" value="<?php echo esc_attr(get_option('contact_url')); ?>" class="regular-text" /></td></tr>
                            <tr><th>Explore Label</th><td><input type="text" name="explore_btn_text" value="<?php echo esc_attr(get_option('explore_btn_text')); ?>" /></td></tr>
                        </table>
                    </div>
                </div>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>jQuery(document).ready(function($){ $('.ag-color-field').wpColorPicker(); });</script>
        <?php
    }

    public function add_agency_meta_boxes() {
        add_meta_box('project_details', 'Project Details', [$this, 'render_project_metabox'], 'agency_project', 'normal', 'high');
        add_meta_box('service_details', 'Service Details', [$this, 'render_service_metabox'], 'agency_service', 'normal', 'high');
    }

    public function render_project_metabox($post) {
        $seo = get_post_meta($post->ID, '_project_seo_caption', true);
        $url = get_post_meta($post->ID, '_project_live_url', true);
        $linked = get_post_meta($post->ID, '_linked_service_id', true);
        $services = get_posts(['post_type' => 'agency_service', 'posts_per_page' => -1]);
        wp_nonce_field('save_ag_meta', 'ag_nonce');
        ?>
        <p><strong>Linked Service (Required for Filtering):</strong><br>
        <select name="linked_service_id" style="width:100%">
            <option value="">None</option>
            <?php foreach($services as $s): ?>
                <option value="<?php echo $s->ID; ?>" <?php selected($linked, $s->ID); ?>><?php echo esc_html($s->post_title); ?></option>
            <?php endforeach; ?>
        </select></p>
        <p><strong>Live URL:</strong><br><input type="url" name="project_live_url" style="width:100%" value="<?php echo esc_url($url); ?>" /></p>
        <p><strong>SEO Summary:</strong><br><textarea name="project_seo_caption" style="width:100%; height:80px;"><?php echo esc_textarea($seo); ?></textarea></p>
        <?php
    }

    public function render_service_metabox($post) {
        $seo = get_post_meta($post->ID, '_service_seo_paragraph', true);
        $custom_link = get_post_meta($post->ID, '_service_custom_link', true);
        wp_nonce_field('save_ag_meta', 'ag_nonce');
        ?>
        <p><strong>SEO Summary:</strong><br><textarea name="service_seo_paragraph" style="width:100%; height:80px;"><?php echo esc_textarea($seo); ?></textarea></p>
        <p><strong>Custom Redirect URL (Explore Button):</strong><br>
        <input type="url" name="service_custom_link" value="<?php echo esc_url($custom_link); ?>" style="width:100%" placeholder="https://example.com (Leaves empty for single service page)" />
        <br><small>If filled, the "Explore" button will link here instead of the default single page.</small></p>
        <?php
    }

    public function save_agency_meta($post_id) {
        if (!isset($_POST['ag_nonce']) || !wp_verify_nonce($_POST['ag_nonce'], 'save_ag_meta')) return;
        if (get_post_type($post_id) === 'agency_project') {
            update_post_meta($post_id, '_project_live_url', esc_url_raw($_POST['project_live_url']));
            update_post_meta($post_id, '_project_seo_caption', sanitize_textarea_field($_POST['project_seo_caption']));
            update_post_meta($post_id, '_linked_service_id', sanitize_text_field($_POST['linked_service_id']));
        } elseif (get_post_type($post_id) === 'agency_service') {
            update_post_meta($post_id, '_service_seo_paragraph', sanitize_textarea_field($_POST['service_seo_paragraph']));
            update_post_meta($post_id, '_service_custom_link', esc_url_raw($_POST['service_custom_link']));
        }
    }

    public function render_projects_grid() {
        ob_start(); 
        $industries = get_terms(['taxonomy' => 'project_industry', 'hide_empty' => true]);
        $services = get_posts(['post_type' => 'agency_service', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']);
        $explore_txt = get_option('explore_btn_text');
        ?>
        <div class="agency-portfolio-container" id="ag-project-archive">
            <div class="archive-filters" style="margin-bottom: 50px;">
                <div class="filter-section">
                    <div class="filter-title">Filter by Industry</div>
                    <div class="filter-nav industry-nav">
                        <button class="filter-btn active" data-filter-type="industry" data-value="all">All Industries</button>
                        <?php foreach($industries as $ind): ?>
                            <button class="filter-btn" data-filter-type="industry" data-value="ind-<?php echo esc_attr($ind->slug); ?>"><?php echo esc_html($ind->name); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-section">
                    <div class="filter-title">Filter by Service</div>
                    <div class="filter-nav service-nav">
                        <button class="filter-btn active" data-filter-type="service" data-value="all">All Services</button>
                        <?php foreach($services as $s): ?>
                            <button class="filter-btn" data-filter-type="service" data-value="ser-<?php echo $s->ID; ?>"><?php echo esc_html($s->post_title); ?></button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="agency-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px;">
                <?php 
                $p = new WP_Query(['post_type' => 'agency_project', 'posts_per_page' => -1]);
                while($p->have_posts()): $p->the_post(); 
                    $terms = get_the_terms(get_the_ID(), 'project_industry');
                    $ind_classes = $terms ? array_map(fn($t) => 'ind-' . $t->slug, $terms) : [];
                    $linked_ser = get_post_meta(get_the_ID(), '_linked_service_id', true);
                    $ser_class = $linked_ser ? 'ser-' . $linked_ser : '';
                    $all_classes = implode(' ', array_merge($ind_classes, [$ser_class]));
                ?>
                    <div class="agency-card project-item <?php echo esc_attr($all_classes); ?>" 
                         data-industries='<?php echo json_encode($ind_classes); ?>' 
                         data-service='<?php echo esc_attr($ser_class); ?>'>
                        <div class="ag-img-wrapper" onclick="window.location.href='<?php the_permalink(); ?>'">
                            <?php the_post_thumbnail('large'); ?>
                        </div>
                        <div class="ag-content">
                            <h3><?php the_title(); ?></h3>
                            <p class="seo-description"><?php echo esc_html(get_post_meta(get_the_ID(), '_project_seo_caption', true)); ?></p>
                            <div class="ag-btn-group">
                                <a href="<?php the_permalink(); ?>" class="ag-btn ag-btn-pri"><?php echo esc_html($explore_txt); ?></a>
                            </div>
                        </div>
                    </div>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($){
            var activeIndustry = 'all';
            var activeService = 'all';

            function applyFilters() {
                $('.project-item').each(function(){
                    var $item = $(this);
                    var itemIndustries = $item.data('industries') || [];
                    var itemService = $item.data('service') || '';
                    
                    var industryMatch = (activeIndustry === 'all' || itemIndustries.indexOf(activeIndustry) !== -1);
                    var serviceMatch = (activeService === 'all' || itemService === activeService);

                    if(industryMatch && serviceMatch) {
                        $item.fadeIn(300);
                    } else {
                        $item.hide();
                    }
                });
            }

            $('.filter-btn').on('click', function(){
                var $btn = $(this);
                var type = $btn.data('filter-type');
                var val = $btn.data('value');

                // Update UI state
                $btn.closest('.filter-nav').find('.filter-btn').removeClass('active');
                $btn.addClass('active');

                // Update Logic state
                if(type === 'industry') activeIndustry = val;
                if(type === 'service') activeService = val;

                applyFilters();
            });
        });
        </script>
        <?php return ob_get_clean();
    }

    public function render_services_archive() {
        ob_start(); 
        $explore_txt = get_option('explore_btn_text');
        $start_txt = get_option('contact_btn_text');
        $contact_url = get_option('contact_url');
        ?>
        <div class="services-archive-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px;">
            <?php 
            $s = new WP_Query(['post_type' => 'agency_service', 'posts_per_page' => -1, 'orderby' => 'menu_order', 'order' => 'ASC']);
            while($s->have_posts()): $s->the_post(); 
                $custom_url = get_post_meta(get_the_ID(), '_service_custom_link', true);
                $final_explore_url = !empty($custom_url) ? $custom_url : get_permalink();
                ?>
                <div class="service-archive-card">
                    <div class="ag-img-wrapper" onclick="window.location.href='<?php echo esc_url($final_explore_url); ?>'">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                    <div class="ag-content">
                        <h2><?php the_title(); ?></h2>
                        <p class="seo-description"><?php echo esc_html(get_post_meta(get_the_ID(), '_service_seo_paragraph', true)); ?></p>
                        <div class="ag-btn-group">
                            <a href="<?php echo esc_url($final_explore_url); ?>" class="ag-btn ag-btn-pri"><?php echo esc_html($explore_txt); ?></a>
                            <a href="<?php echo esc_url($contact_url); ?>" class="ag-btn ag-btn-sec"><?php echo esc_html($start_txt); ?></a>
                        </div>
                    </div>
                </div>
            <?php endwhile; wp_reset_postdata(); ?>
        </div>
        <?php return ob_get_clean();
    }

    public function template_loader($template) {
        if (is_singular(['agency_service', 'agency_project'])) {
            add_action('wp_footer', function() { exit; }); 
            $this->render_single_page();
            return '';
        }
        return $template;
    }

    private function render_single_page() {
        get_header(); 
        $start_txt = get_option('contact_btn_text');
        $contact_url = get_option('contact_url');
        ?>
        <div class="agency-single-layout" style="max-width: 1200px; margin: 80px auto; padding: 0 25px; display: grid; grid-template-columns: 1fr 320px; gap: 60px;">
            <div class="main-column">
                <h1><?php the_title(); ?></h1>
                <div class="single-content"><?php the_content(); ?></div>
                <div style="margin-top: 50px;">
                    <a href="<?php echo esc_url($contact_url); ?>" class="ag-btn ag-btn-pri" style="padding: 16px 40px; font-size: 1.1rem; border-radius: 12px;"><?php echo esc_html($start_txt); ?></a>
                </div>
            </div>
            <div class="sidebar-column">
                <div style="background: var(--ag-acc); padding: 30px; border-radius: var(--ag-rad);">
                    <h3 style="margin-top:0; color:var(--ag-pri);">Recent <?php echo (get_post_type()==='agency_service'?'Services':'Projects'); ?></h3>
                    <ul style="list-style:none; padding:0; margin: 20px 0 0;">
                        <?php 
                        $items = get_posts(['post_type' => get_post_type(), 'posts_per_page' => 5, 'post__not_in' => [get_the_ID()]]);
                        foreach($items as $item): ?>
                            <li style="margin-bottom:12px;"><a href="<?php echo get_permalink($item->ID); ?>" style="text-decoration:none; color:var(--ag-pri); font-weight:500;"><?php echo esc_html($item->post_title); ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php get_footer();
    }
}
new Agency_Service_Manager();
