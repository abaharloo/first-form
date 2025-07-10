<?php
defined('ABSPATH') || exit;

function bfb_render_edit_submission_page() {
    global $wpdb;
    $submissions_table = $wpdb->prefix . 'bfb_submissions';

    if (!isset($_GET['submission_id'])) {
        echo '<div class="notice notice-error"><p>آی‌دی ثبت‌نام مشخص نیست.</p></div>';
        return;
    }

    $submission_id = intval($_GET['submission_id']);
    $submission = $wpdb->get_row($wpdb->prepare("SELECT * FROM $submissions_table WHERE id = %d", $submission_id));

    if (!$submission) {
        echo '<div class="notice notice-error"><p>اطلاعات یافت نشد.</p></div>';
        return;
    }

    $data = maybe_unserialize($submission->submitted_data);

    if (isset($_POST['bfb_save_submission'])) {
        check_admin_referer('bfb_edit_submission');

        foreach ($_POST['submission_data'] as $key => $value) {
            $data[$key] = sanitize_text_field($value);
        }

        $wpdb->update(
            $submissions_table,
            ['submitted_data' => maybe_serialize($data)],
            ['id' => $submission_id]
        );

        echo '<div class="updated"><p>اطلاعات با موفقیت ویرایش شد.</p></div>';
    }

    echo '<div class="wrap"><h1>ویرایش اطلاعات ثبت‌نام</h1>';
    echo '<form method="post">';
    wp_nonce_field('bfb_edit_submission');

    foreach ($data as $key => $value) {
        echo '<p><label><strong>' . esc_html($key) . '</strong><br>';
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            echo '<a href="' . esc_url($value) . '" target="_blank">دانلود فایل</a><br>';
            echo '<input type="text" name="submission_data[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" class="regular-text" /></label></p>';
        } else {
            echo '<input type="text" name="submission_data[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" class="regular-text" /></label></p>';
        }
    }

    echo '<p><input type="submit" name="bfb_save_submission" class="button-primary" value="ذخیره" /></p>';
    echo '</form></div>';
} 