<?php
defined('ABSPATH') || exit;

function bfb_render_edit_submission_page() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $submission_id = isset($_GET['submission_id']) ? intval($_GET['submission_id']) : 0;
    
    if (!$submission_id) {
        wp_die('شناسه ثبت‌نام مشخص نشده است.');
    }
    
    $submissions_table = $wpdb->prefix . 'bfb_submissions';
    $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $submissions_table WHERE id = %d AND user_id = %d", $submission_id, $current_user_id));
    
    if (!$submission) {
        wp_die('ثبت‌نام یافت نشد یا شما مجاز به ویرایش آن نیستید.');
    }
    
    $forms_table = $wpdb->prefix . 'bfb_forms';
    $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $submission->form_id));
    
    // پردازش فرم ویرایش
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bfb_edit_submission'])) {
        $submitted_data = maybe_unserialize($submission->submitted_data);
        $updated_data = [];
        
        foreach ($submitted_data as $key => $value) {
            $field_key = 'bfb_field_' . sanitize_key($key);
            if (isset($_POST[$field_key])) {
                $updated_data[$key] = sanitize_text_field($_POST[$field_key]);
            } else {
                $updated_data[$key] = $value;
            }
        }
        
        $wpdb->update($submissions_table, [
            'submitted_data' => serialize($updated_data)
        ], ['id' => $submission_id]);
        
        echo '<div class="updated"><p>ثبت‌نام با موفقیت به‌روزرسانی شد.</p></div>';
        
        // به‌روزرسانی داده‌ها برای نمایش
        $submission->submitted_data = serialize($updated_data);
    }
    
    $data = maybe_unserialize($submission->submitted_data);
    
    echo '<div class="wrap"><h1>ویرایش ثبت‌نام</h1>';
    echo '<p><strong>فرم:</strong> ' . esc_html($form ? $form->form_name : 'نامشخص') . '</p>';
    echo '<p><strong>تاریخ:</strong> ' . esc_html($submission->created_at) . '</p>';
    
    echo '<form method="post">';
    echo '<table class="widefat fixed striped bfb-table">';
    echo '<thead><tr><th>فیلد</th><th>مقدار</th></tr></thead><tbody>';
    
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            echo '<tr>';
            echo '<td><strong>' . esc_html($key) . '</strong></td>';
            echo '<td>';
            
            if (strpos($value, 'http') === 0) {
                // فایل آپلود شده
                echo '<a href="' . esc_url($value) . '" target="_blank">مشاهده فایل</a>';
                echo '<br><small>فایل آپلود شده - قابل ویرایش نیست</small>';
            } else {
                echo '<input type="text" name="bfb_field_' . sanitize_key($key) . '" value="' . esc_attr($value) . '" class="regular-text">';
            }
            
            echo '</td>';
            echo '</tr>';
        }
    }
    
    echo '</tbody></table>';
    echo '<p><input type="submit" name="bfb_edit_submission" class="button-primary" value="ذخیره تغییرات"></p>';
    echo '</form>';
    
    echo '<p><a href="' . admin_url('admin.php?page=bfb-submissions') . '" class="button">بازگشت به لیست</a></p>';
    echo '</div>';
} 