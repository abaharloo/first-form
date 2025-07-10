<?php
defined('ABSPATH') || exit;

function bfb_handle_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bfb_field_1'])) {
        global $wpdb;
        $fields_table = $wpdb->prefix . 'bfb_fields';
        $form_table = $wpdb->prefix . 'bfb_forms';
        $submissions_table = $wpdb->prefix . 'bfb_submissions';

        $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
        if (!$form_id) return;

        // گرفتن فیلدها
        $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM $fields_table WHERE form_id = %d", $form_id));
        $submitted_data = [];

        foreach ($fields as $field) {
            $key = 'bfb_field_' . $field->id;
            if ($field->field_type === 'file') {
                if (isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK) {
                    if ($_FILES[$key]['size'] > 2097152) {
                        $submitted_data[$field->field_label] = 'حجم فایل بیش از ۲ مگابایت است';
                    } else {
                        $upload = wp_handle_upload($_FILES[$key], ['test_form' => false]);
                        if (!isset($upload['error'])) {
                            $submitted_data[$field->field_label] = $upload['url'];
                        } else {
                            $submitted_data[$field->field_label] = 'خطا در آپلود: ' . $upload['error'];
                        }
                    }
                } else {
                    $submitted_data[$field->field_label] = '';
                }
            } else if (isset($_POST[$key])) {
                $submitted_data[$field->field_label] = sanitize_text_field($_POST[$key]);
            }
        }

        // ذخیره ثبت‌نام
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $form_table WHERE id = %d", $form_id));
        $wpdb->insert($submissions_table, [
            'form_name' => $form->form_name,
            'submitted_data' => maybe_serialize($submitted_data)
        ]);

        // بررسی شماره موبایل (برای یوزرنیم و پسورد)
        $mobile = '';
        foreach ($fields as $field) {
            if (str_contains($field->field_label, 'شماره')) {
                $key = 'bfb_field_' . $field->id;
                if (isset($_POST[$key])) {
                    $mobile = sanitize_text_field($_POST[$key]);
                    break;
                }
            }
        }

        // بررسی وضعیت ثبت‌نام خودکار فرم
        $auto_register = $form && isset($form->auto_register) ? intval($form->auto_register) : 0;

        if ($auto_register && $mobile && !username_exists($mobile)) {
            $user_id = wp_create_user($mobile, $mobile);
            if (!is_wp_error($user_id)) {
                wp_update_user([
                    'ID' => $user_id,
                    'display_name' => $submitted_data['نام و نام خانوادگی'] ?? $mobile
                ]);
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                wp_redirect(home_url('/panel'));
                exit;
            }
        }
    }
}
add_action('init', 'bfb_handle_form_submission'); 