<?php
defined('ABSPATH') || exit;

function bfb_render_submissions_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'bfb_submissions';
    $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC");

    // دکمه خروجی اکسل
    echo '<div class="wrap"><h1>لیست ثبت‌نام‌ها</h1>';
    echo '<a href="' . admin_url('admin.php?page=bfb-submissions&bfb_export_excel=1') . '" class="button button-primary" style="margin-bottom:15px;">خروجی اکسل</a>';

    // خروجی اکسل
    if (isset($_GET['bfb_export_excel'])) {
        bfb_export_submissions_excel($results);
        exit;
    }

    echo '<table class="widefat fixed striped">';
    echo '<thead><tr><th>نام فرم</th><th>تاریخ</th><th>اطلاعات</th><th>عملیات</th></tr></thead><tbody>';

    foreach ($results as $row) {
        $data = maybe_unserialize($row->submitted_data);
        echo '<tr>';
        echo '<td>' . esc_html($row->form_name) . '</td>';
        echo '<td>' . esc_html($row->created_at) . '</td>';
        echo '<td><ul>';
        foreach ($data as $key => $value) {
            if (filter_var($value, FILTER_VALIDATE_URL)) {
                echo '<li><strong>' . esc_html($key) . ':</strong> <a href="' . esc_url($value) . '" target="_blank">دانلود فایل</a></li>';
            } else {
                echo '<li><strong>' . esc_html($key) . ':</strong> ' . esc_html($value) . '</li>';
            }
        }
        echo '</ul></td>';
        echo '<td><a href="' . admin_url('admin.php?page=bfb-edit-submission&submission_id=' . $row->id) . '" class="button">ویرایش</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

// تابع خروجی اکسل
function bfb_export_submissions_excel($results) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="submissions.csv"');
    $output = fopen('php://output', 'w');
    // هدرها
    $headers = ['نام فرم', 'تاریخ', 'فیلدها'];
    fputcsv($output, $headers);
    foreach ($results as $row) {
        $data = maybe_unserialize($row->submitted_data);
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = $key . ': ' . $value;
        }
        fputcsv($output, [
            $row->form_name,
            $row->created_at,
            implode(' | ', $fields)
        ]);
    }
    fclose($output);
    exit;
} 