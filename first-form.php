<?php
/*
Plugin Name: اولین فرم
<<<<<<< HEAD
Plugin URI: https://github.com/abaharloo/first-form
Update URI: https://github.com/abaharloo/first-form/archive/refs/tags/v1.1.0.zip
Description: افزونه ساخت فرم برای ثبت‌نام کسب‌وکارها با قابلیت‌های پیشرفته
Version: 1.1.0
Author: Amir Baharloo
Author URI: https://github.com/abaharloo
License: GPL v2 or later
Text Domain: first-form
*/

defined('ABSPATH') || exit;

// تعریف ثابت‌های افزونه
define('BFB_VERSION', '1.1.0');
define('BFB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('BFB_PLUGIN_PATH', plugin_dir_path(__FILE__));

// لود کردن فایل‌های مورد نیاز
require_once BFB_PLUGIN_PATH . 'includes/fields-table.php';
require_once BFB_PLUGIN_PATH . 'includes/manage-fields.php';
require_once BFB_PLUGIN_PATH . 'includes/render-form.php';
require_once BFB_PLUGIN_PATH . 'includes/handle-form-submission.php';
require_once BFB_PLUGIN_PATH . 'includes/view-submissions.php';
require_once BFB_PLUGIN_PATH . 'includes/edit-submission.php';

// فعال‌سازی افزونه
function bfb_activate() {
    bfb_create_all_tables();
    
    // به‌روزرسانی نسخه در دیتابیس
    update_option('bfb_version', BFB_VERSION);
    
    // ایجاد دایرکتوری آپلود
    $upload_dir = wp_upload_dir();
    $bfb_upload_dir = $upload_dir['basedir'] . '/bfb-uploads';
    if (!file_exists($bfb_upload_dir)) {
        wp_mkdir_p($bfb_upload_dir);
    }
    
    // ایجاد فایل .htaccess برای محافظت از فایل‌ها
    $htaccess_content = "Order Deny,Allow\nDeny from all\nAllow from localhost";
    file_put_contents($bfb_upload_dir . '/.htaccess', $htaccess_content);
}
register_activation_hook(__FILE__, 'bfb_activate');

// غیرفعال‌سازی افزونه
function bfb_deactivate() {
    // حذف جداول دیتابیس (اختیاری)
    // global $wpdb;
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bfb_forms");
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bfb_fields");
    // $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}bfb_submissions");
}
register_deactivation_hook(__FILE__, 'bfb_deactivate');

// بررسی به‌روزرسانی
function bfb_check_update() {
    $current_version = get_option('bfb_version', '1.0.0');
    
    if (version_compare($current_version, BFB_VERSION, '<')) {
        // اجرای به‌روزرسانی‌ها
        if (version_compare($current_version, '1.1.0', '<')) {
            bfb_update_to_1_1_0();
        }
        
        update_option('bfb_version', BFB_VERSION);
    }
}
add_action('plugins_loaded', 'bfb_check_update');

// به‌روزرسانی به نسخه 1.1.0
function bfb_update_to_1_1_0() {
    global $wpdb;
    
    // اضافه کردن ستون‌های جدید به جدول فرم‌ها
    $forms_table = $wpdb->prefix . 'bfb_forms';
    $wpdb->query("ALTER TABLE $forms_table ADD COLUMN IF NOT EXISTS user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 1");
    $wpdb->query("ALTER TABLE $forms_table ADD COLUMN IF NOT EXISTS shortcode VARCHAR(50) NULL");
    $wpdb->query("ALTER TABLE $forms_table ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    $wpdb->query("ALTER TABLE $forms_table ADD INDEX IF NOT EXISTS user_id (user_id)");
    
    // اضافه کردن ستون‌های جدید به جدول ثبت‌نام‌ها
    $submissions_table = $wpdb->prefix . 'bfb_submissions';
    $wpdb->query("ALTER TABLE $submissions_table ADD COLUMN IF NOT EXISTS form_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 1");
    $wpdb->query("ALTER TABLE $submissions_table ADD COLUMN IF NOT EXISTS user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 1");
    $wpdb->query("ALTER TABLE $submissions_table ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45) NULL");
    $wpdb->query("ALTER TABLE $submissions_table ADD COLUMN IF NOT EXISTS user_agent TEXT NULL");
    $wpdb->query("ALTER TABLE $submissions_table ADD INDEX IF NOT EXISTS form_id (form_id)");
    $wpdb->query("ALTER TABLE $submissions_table ADD INDEX IF NOT EXISTS user_id (user_id)");
    
    // اضافه کردن ستون ترتیب به جدول فیلدها
    $fields_table = $wpdb->prefix . 'bfb_fields';
    $wpdb->query("ALTER TABLE $fields_table ADD COLUMN IF NOT EXISTS field_order INT DEFAULT 0");
    
    // به‌روزرسانی فرم‌های موجود
    $existing_forms = $wpdb->get_results("SELECT * FROM $forms_table WHERE user_id = 0 OR user_id IS NULL");
    foreach ($existing_forms as $form) {
        $shortcode = 'first_form_' . time() . '_' . $form->id;
        $wpdb->update($forms_table, [
            'user_id' => 1,
            'shortcode' => $shortcode
        ], ['id' => $form->id]);
    }
    
    // به‌روزرسانی ثبت‌نام‌های موجود
    $existing_submissions = $wpdb->get_results("SELECT * FROM $submissions_table WHERE user_id = 0 OR user_id IS NULL");
    foreach ($existing_submissions as $submission) {
        $wpdb->update($submissions_table, [
            'user_id' => 1,
            'form_id' => 1
        ], ['id' => $submission->id]);
    }
}

// لود کردن استایل‌ها و اسکریپت‌ها
function bfb_enqueue_scripts() {
    wp_enqueue_style('bfb-form-style', BFB_PLUGIN_URL . 'assets/form-style.css', [], BFB_VERSION);
}
add_action('wp_enqueue_scripts', 'bfb_enqueue_scripts');

<<<<<<< HEAD
// اضافه کردن لینک تنظیمات به صفحه افزونه‌ها
function bfb_plugin_action_links($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=bfb-dashboard') . '">تنظیمات افزونه</a>';
    array_unshift($links, $settings_link);
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'bfb_plugin_action_links');

// نمایش اطلاعات نسخه در صفحه افزونه‌ها
function bfb_plugin_row_meta($links, $file) {
    if (plugin_basename(__FILE__) === $file) {
        $row_meta = [
            'docs' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://github.com/your-username/first-form', 'مستندات'),
            'support' => sprintf('<a href="%s" target="_blank">%s</a>', 'https://github.com/your-username/first-form/issues', 'پشتیبانی')
        ];
        return array_merge($links, $row_meta);
    }
    return $links;
}
add_filter('plugin_row_meta', 'bfb_plugin_row_meta', 10, 2);

// تابع کمکی برای بررسی دسترسی کاربر
function bfb_user_can_manage_forms() {
    return current_user_can('manage_options');
}

// تابع کمکی برای دریافت فرم‌های کاربر
function bfb_get_user_forms($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    global $wpdb;
    $forms_table = $wpdb->prefix . 'bfb_forms';
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM $forms_table WHERE user_id = %d ORDER BY created_at DESC", $user_id));
}

// تابع کمکی برای دریافت فیلدهای فرم
function bfb_get_form_fields($form_id) {
    global $wpdb;
    $fields_table = $wpdb->prefix . 'bfb_fields';
    return $wpdb->get_results($wpdb->prepare("SELECT * FROM $fields_table WHERE form_id = %d ORDER BY field_order ASC", $form_id));
}

// تابع کمکی برای تولید شورت‌کد منحصر به فرد
function bfb_generate_shortcode($user_id) {
    return 'first_form_' . time() . '_' . $user_id . '_' . rand(1000, 9999);
}

// تابع کمکی برای اعتبارسنجی شماره تلفن
function bfb_validate_phone($phone) {
    // حذف کاراکترهای غیرمجاز
    $phone = preg_replace('/[^0-9+\-\s()]/', '', $phone);
    return $phone;
}

// تابع کمکی برای اعتبارسنجی ایمیل
function bfb_validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// تابع کمکی برای آپلود فایل
function bfb_upload_file($file, $user_id, $form_id) {
    $upload_dir = wp_upload_dir();
    $bfb_upload_dir = $upload_dir['basedir'] . '/bfb-uploads/' . $user_id . '/' . $form_id;
    
    if (!file_exists($bfb_upload_dir)) {
        wp_mkdir_p($bfb_upload_dir);
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . '.' . $file_extension;
    $file_path = $bfb_upload_dir . '/' . $file_name;
    
    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        return $upload_dir['baseurl'] . '/bfb-uploads/' . $user_id . '/' . $form_id . '/' . $file_name;
    }
    
    return false;
}

// تابع کمکی برای ارسال ایمیل
function bfb_send_email($to, $subject, $message) {
    $headers = ['Content-Type: text/html; charset=UTF-8'];
    return wp_mail($to, $subject, $message, $headers);
}

// تابع کمکی برای فرمت کردن تاریخ
function bfb_format_date($date) {
    return date('Y/m/d H:i:s', strtotime($date));
}

// تابع کمکی برای محدود کردن متن
function bfb_truncate_text($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

// تابع کمکی برای تولید آمار
function bfb_get_statistics($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    global $wpdb;
    $forms_table = $wpdb->prefix . 'bfb_forms';
    $submissions_table = $wpdb->prefix . 'bfb_submissions';
    
    $stats = [
        'total_forms' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $forms_table WHERE user_id = %d", $user_id)),
        'total_submissions' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $submissions_table WHERE user_id = %d", $user_id)),
        'today_submissions' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $submissions_table WHERE user_id = %d AND DATE(created_at) = CURDATE()", $user_id)),
        'this_month_submissions' => $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $submissions_table WHERE user_id = %d AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())", $user_id))
    ];
    
    return $stats;
}

// تابع کمکی برای خروجی CSV
function bfb_export_csv($data, $filename = 'export.csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // اضافه کردن BOM برای پشتیبانی از فارسی
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    return $output;
}

// تابع کمکی برای اعتبارسنجی فرم
function bfb_validate_form_data($data, $required_fields) {
    $errors = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $errors[] = "فیلد $field الزامی است.";
        }
    }
    
    return $errors;
}

// تابع کمکی برای پاکسازی داده‌ها
function bfb_sanitize_form_data($data) {
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $sanitized[$key] = array_map('sanitize_text_field', $value);
        } else {
            $sanitized[$key] = sanitize_text_field($value);
        }
    }
    
    return $sanitized;
}

// تابع کمکی برای بررسی دسترسی به فایل
function bfb_check_file_access($file_path) {
    $upload_dir = wp_upload_dir();
    $bfb_upload_dir = $upload_dir['basedir'] . '/bfb-uploads';
    
    return strpos(realpath($file_path), realpath($bfb_upload_dir)) === 0;
}

// تابع کمکی برای حذف فایل‌های قدیمی
function bfb_cleanup_old_files() {
    $upload_dir = wp_upload_dir();
    $bfb_upload_dir = $upload_dir['basedir'] . '/bfb-uploads';
    
    if (!is_dir($bfb_upload_dir)) {
        return;
    }
    
    $files = glob($bfb_upload_dir . '/**/*');
    $now = time();
    
    foreach ($files as $file) {
        if (is_file($file) && ($now - filemtime($file)) > (30 * 24 * 60 * 60)) { // 30 روز
            unlink($file);
        }
    }
}

// اجرای پاکسازی هفتگی
if (!wp_next_scheduled('bfb_cleanup_files')) {
    wp_schedule_event(time(), 'weekly', 'bfb_cleanup_files');
}
add_action('bfb_cleanup_files', 'bfb_cleanup_old_files');

// تابع کمکی برای نمایش پیام‌ها
function bfb_show_message($message, $type = 'success') {
    $class = $type === 'error' ? 'bfb-error' : 'bfb-success';
    return '<div class="' . $class . '">' . esc_html($message) . '</div>';
}

// تابع کمکی برای نمایش فرم‌های کاربر
function bfb_display_user_forms($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $forms = bfb_get_user_forms($user_id);
    
    if (empty($forms)) {
        return '<p>هنوز فرمی نساخته‌اید.</p>';
    }
    
    $output = '<div class="bfb-forms-grid">';
    foreach ($forms as $form) {
        $output .= '<div class="bfb-form-card">';
        $output .= '<h3>' . esc_html($form->form_name) . '</h3>';
        $output .= '<p><strong>شورت‌کد:</strong> <code>' . esc_html($form->shortcode) . '</code></p>';
        $output .= '<p><strong>تاریخ ایجاد:</strong> ' . bfb_format_date($form->created_at) . '</p>';
        $output .= '<div class="bfb-form-actions">';
        $output .= '<a href="' . admin_url('admin.php?page=bfb-manage-fields&form_id=' . $form->id) . '" class="button">مدیریت فیلدها</a>';
        $output .= '<a href="' . admin_url('admin.php?page=bfb-submissions&form_id=' . $form->id) . '" class="button">مشاهده ثبت‌نام‌ها</a>';
        $output .= '</div>';
        $output .= '</div>';
    }
    $output .= '</div>';
    
    return $output;
}

// تابع کمکی برای نمایش آمار
function bfb_display_statistics($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $stats = bfb_get_statistics($user_id);
    
    $output = '<div class="bfb-stats-grid">';
    $output .= '<div class="bfb-stat-card">';
    $output .= '<h3>کل فرم‌ها</h3>';
    $output .= '<p class="bfb-stat-number">' . $stats['total_forms'] . '</p>';
    $output .= '</div>';
    
    $output .= '<div class="bfb-stat-card">';
    $output .= '<h3>کل ثبت‌نام‌ها</h3>';
    $output .= '<p class="bfb-stat-number">' . $stats['total_submissions'] . '</p>';
    $output .= '</div>';
    
    $output .= '<div class="bfb-stat-card">';
    $output .= '<h3>ثبت‌نام‌های امروز</h3>';
    $output .= '<p class="bfb-stat-number">' . $stats['today_submissions'] . '</p>';
    $output .= '</div>';
    
    $output .= '<div class="bfb-stat-card">';
    $output .= '<h3>ثبت‌نام‌های این ماه</h3>';
    $output .= '<p class="bfb-stat-number">' . $stats['this_month_submissions'] . '</p>';
    $output .= '</div>';
    $output .= '</div>';
    
    return $output;
} 
=======
register_activation_hook(__FILE__, 'bfb_create_all_tables'); 
>>>>>>> 73b15bfbe45eb4adfe6d0cce2f364ea869841845
