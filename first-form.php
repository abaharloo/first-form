<?php
/*
Plugin Name: اولین فرم
Description: فرم‌ساز با امکان اتصال به ثبت نام وردپرس
Version: 1.0.2
Author: Amir Baharloo
Plugin URI: https://github.com/abaharloo/first-form
Update URI: https://github.com/abaharloo/first-form/archive/refs/tags/v1.0.0.zip
*/

defined('ABSPATH') || exit;

// تنظیمات آپدیت خودکار
function bfb_check_for_updates($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_slug = basename(dirname(__FILE__));
    $plugin_file = basename(__FILE__);
    
    // بررسی نسخه فعلی
    $current_version = '1.0.2';
    
    // در اینجا می‌توانی API خودت را برای بررسی نسخه جدید اضافه کنی
    // فعلاً به صورت نمونه، نسخه جدید را 1.0.3 فرض می‌کنیم
    $latest_version = '1.0.3';
    $download_url = 'https://github.com/amirbaharloo/first-form/releases/latest/download/first-form.zip';
    
    if (version_compare($current_version, $latest_version, '<')) {
        $transient->response[$plugin_slug . '/' . $plugin_file] = (object) array(
            'slug' => $plugin_slug,
            'new_version' => $latest_version,
            'url' => 'https://github.com/amirbaharloo/first-form',
            'package' => $download_url,
            'requires' => '5.0',
            'requires_php' => '7.4',
            'tested' => '6.0',
            'last_updated' => date('Y-m-d'),
            'sections' => array(
                'description' => 'فرم‌ساز با امکان اتصال به ثبت نام وردپرس - نسخه جدید با امکانات بیشتر',
                'changelog' => 'نسخه 1.0.3: بهبود عملکرد و رفع باگ‌ها'
            )
        );
    }
    
    return $transient;
}
add_filter('pre_set_site_transient_update_plugins', 'bfb_check_for_updates');

// نمایش پیام آپدیت در پیشخوان
function bfb_admin_notice_update() {
    $current_version = '1.0.2';
    $latest_version = '1.0.3';
    
    if (version_compare($current_version, $latest_version, '<')) {
        echo '<div class="notice notice-info is-dismissible">';
        echo '<p><strong>اولین فرم:</strong> نسخه جدید ' . $latest_version . ' در دسترس است. <a href="' . admin_url('plugins.php') . '">بررسی آپدیت‌ها</a></p>';
        echo '</div>';
    }
}
add_action('admin_notices', 'bfb_admin_notice_update');

// تنظیمات آپدیت خودکار (اختیاری)
function bfb_auto_update_plugins($update, $item) {
    $plugins = array(
        'first-form/first-form.php' // مسیر نسبی افزونه
    );
    
    if (in_array($item->plugin, $plugins)) {
        return true; // آپدیت خودکار فعال
    }
    
    return $update;
}
add_filter('auto_update_plugin', 'bfb_auto_update_plugins', 10, 2);

require_once plugin_dir_path(__FILE__) . 'includes/fields-table.php';
require_once plugin_dir_path(__FILE__) . 'includes/manage-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/render-form.php';
require_once plugin_dir_path(__FILE__) . 'includes/handle-form-submission.php';
require_once plugin_dir_path(__FILE__) . 'includes/view-submissions.php';
require_once plugin_dir_path(__FILE__) . 'includes/edit-submission.php';

register_activation_hook(__FILE__, 'bfb_create_all_tables'); 
