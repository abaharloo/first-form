<?php
defined('ABSPATH') || exit;

function bfb_render_submissions_page() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $submissions_table = $wpdb->prefix . 'bfb_submissions';
    $forms_table = $wpdb->prefix . 'bfb_forms';
    
    // فیلتر بر اساس فرم
    $form_filter = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
    $where_clause = $form_filter ? "AND form_id = $form_filter" : "";
    
    $submissions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $submissions_table WHERE user_id = %d $where_clause ORDER BY created_at DESC", $current_user_id));
    $forms = $wpdb->get_results($wpdb->prepare("SELECT * FROM $forms_table WHERE user_id = %d ORDER BY created_at DESC", $current_user_id));
    
    echo '<div class="wrap"><h1>ثبت‌نام‌ها</h1>';
    
    // فیلتر فرم‌ها
    if ($forms) {
        echo '<div style="margin-bottom: 20px;">';
        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="bfb-submissions">';
        echo '<select name="form_id" onchange="this.form.submit()">';
        echo '<option value="">همه فرم‌ها</option>';
        foreach ($forms as $form) {
            $selected = ($form_filter == $form->id) ? 'selected' : '';
            echo '<option value="' . $form->id . '" ' . $selected . '>' . esc_html($form->form_name) . '</option>';
        }
        echo '</select>';
        echo '</form>';
        echo '</div>';
    }
    
    if ($submissions) {
        echo '<table class="widefat fixed striped bfb-table">';
        echo '<thead><tr><th>تاریخ</th><th>فرم</th><th>اطلاعات ثبت‌نام</th><th>عملیات</th></tr></thead><tbody>';
        
        foreach ($submissions as $submission) {
            $data = maybe_unserialize($submission->submitted_data);
            $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $submission->form_id));
            
            echo '<tr>';
            echo '<td>' . esc_html($submission->created_at) . '</td>';
            echo '<td>' . esc_html($form ? $form->form_name : 'نامشخص') . '</td>';
            echo '<td>';
            
            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    if (strpos($value, 'http') === 0) {
                        // فایل آپلود شده
                        echo '<strong>' . esc_html($key) . ':</strong> <a href="' . esc_url($value) . '" target="_blank">مشاهده فایل</a><br>';
                    } else {
                        echo '<strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '<br>';
                    }
                }
            }
            
            echo '</td>';
            echo '<td>';
            echo '<a href="' . admin_url('admin.php?page=bfb-edit-submission&submission_id=' . $submission->id) . '" class="button">ویرایش</a>';
            echo '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
        
        // دکمه خروجی CSV
        if ($form_filter) {
            echo '<div style="margin-top: 20px;">';
            echo '<a href="' . admin_url('admin.php?page=bfb-export&form_id=' . $form_filter) . '" class="button button-primary">دانلود CSV</a>';
            echo '</div>';
        }
    } else {
        echo '<p>هنوز ثبت‌نامی وجود ندارد.</p>';
    }
    
    echo '</div>';
} 