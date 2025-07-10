<?php
defined('ABSPATH') || exit;

function bfb_admin_menu() {
    add_menu_page(
        'اولین فرم',
        'اولین فرم',
        'manage_options',
        'bfb-dashboard',
        'bfb_render_dashboard_page',
        'dashicons-feedback',
        25
    );

    add_submenu_page(
        'bfb-dashboard',
        'داشبورد',
        'داشبورد',
        'manage_options',
        'bfb-dashboard',
        'bfb_render_dashboard_page'
    );

    add_submenu_page(
        'bfb-dashboard',
        'افزودن فرم',
        'افزودن فرم',
        'manage_options',
        'bfb-add-form',
        'bfb_render_add_form_page'
    );

    add_submenu_page(
        'bfb-dashboard',
        'فرم‌های من',
        'فرم‌های من',
        'manage_options',
        'bfb-my-forms',
        'bfb_render_my_forms_page'
    );

    add_submenu_page(
        'bfb-dashboard',
        'مدیریت فیلدها',
        'مدیریت فیلدها',
        'manage_options',
        'bfb-manage-fields',
        'bfb_render_manage_fields_page'
    );

    add_submenu_page(
        'bfb-dashboard',
        'ثبت‌نام‌ها',
        'ثبت‌نام‌ها',
        'manage_options',
        'bfb-submissions',
        'bfb_render_submissions_page'
    );

    add_submenu_page(
        'bfb-dashboard',
        'مشاهده آمار',
        'مشاهده آمار',
        'manage_options',
        'bfb-statistics',
        'bfb_render_statistics_page'
    );

    add_submenu_page(
        'bfb-dashboard',
        'خروجی اکسل',
        'خروجی اکسل',
        'manage_options',
        'bfb-export',
        'bfb_render_export_page'
    );

    add_submenu_page(
        null,
        'ویرایش ثبت‌نام',
        'ویرایش ثبت‌نام',
        'manage_options',
        'bfb-edit-submission',
        'bfb_render_edit_submission_page'
    );
}
add_action('admin_menu', 'bfb_admin_menu');

// صفحه داشبورد اصلی
function bfb_render_dashboard_page() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $forms_table = $wpdb->prefix . 'bfb_forms';
    $submissions_table = $wpdb->prefix . 'bfb_submissions';
    
    // آمار کلی
    $total_forms = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $forms_table WHERE user_id = %d", $current_user_id));
    $total_submissions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $submissions_table WHERE user_id = %d", $current_user_id));
    $recent_forms = $wpdb->get_results($wpdb->prepare("SELECT * FROM $forms_table WHERE user_id = %d ORDER BY created_at DESC LIMIT 5", $current_user_id));
    
    echo '<div class="wrap"><h1>داشبورد اولین فرم</h1>';
    
    // کارت‌های آمار
    echo '<div style="display: flex; gap: 20px; margin: 20px 0;">';
    echo '<div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex: 1;">';
    echo '<h3>کل فرم‌ها</h3><p style="font-size: 24px; color: #ff3800; font-weight: bold;">' . $total_forms . '</p>';
    echo '</div>';
    echo '<div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); flex: 1;">';
    echo '<h3>کل ثبت‌نام‌ها</h3><p style="font-size: 24px; color: #0073ff; font-weight: bold;">' . $total_submissions . '</p>';
    echo '</div>';
    echo '</div>';
    
    // فرم‌های اخیر
    echo '<h2>فرم‌های اخیر</h2>';
    if ($recent_forms) {
        echo '<table class="widefat fixed striped">';
        echo '<thead><tr><th>نام فرم</th><th>شورت‌کد</th><th>تاریخ ایجاد</th><th>عملیات</th></tr></thead><tbody>';
        foreach ($recent_forms as $form) {
            echo '<tr>';
            echo '<td>' . esc_html($form->form_name) . '</td>';
            echo '<td><code>' . esc_html($form->shortcode) . '</code></td>';
            echo '<td>' . esc_html($form->created_at) . '</td>';
            echo '<td><a href="' . admin_url('admin.php?page=bfb-manage-fields&form_id=' . $form->id) . '" class="button">مدیریت فیلدها</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>هنوز فرمی نساخته‌اید. <a href="' . admin_url('admin.php?page=bfb-add-form') . '" class="button button-primary">فرم جدید بسازید</a></p>';
    }
    
    echo '</div>';
}

// صفحه افزودن فرم جدید
function bfb_render_add_form_page() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $forms_table = $wpdb->prefix . 'bfb_forms';

    if (isset($_POST['bfb_add_form'])) {
        $form_name = sanitize_text_field($_POST['form_name']);
        $form_description = sanitize_textarea_field($_POST['form_description']);
        $auto_register = isset($_POST['auto_register']) ? 1 : 0;
        
        // تولید شورت‌کد منحصر به فرد
        $shortcode = 'first_form_' . time() . '_' . $current_user_id;
        
        $wpdb->insert($forms_table, [
            'user_id' => $current_user_id,
            'form_name' => $form_name,
            'form_description' => $form_description,
            'auto_register' => $auto_register,
            'shortcode' => $shortcode
        ]);
        
        $form_id = $wpdb->insert_id;
        
        // افزودن فیلدهای اجباری
        $fields_table = $wpdb->prefix . 'bfb_fields';
        $required_fields = [
            ['field_label' => 'نام و نام خانوادگی مالک کسب‌وکار', 'field_type' => 'text', 'field_order' => 1],
            ['field_label' => 'شماره تماس', 'field_type' => 'tel', 'field_order' => 2],
            ['field_label' => 'ایمیل', 'field_type' => 'email', 'field_order' => 3],
        ];
        
        foreach ($required_fields as $field) {
            $wpdb->insert($fields_table, [
                'form_id' => $form_id,
                'field_label' => $field['field_label'],
                'field_type' => $field['field_type'],
                'is_required' => 1,
                'field_order' => $field['field_order']
            ]);
        }
        
        echo '<div class="updated"><p>فرم جدید با موفقیت ساخته شد!</p></div>';
        echo '<div class="notice notice-info"><p><strong>شورت‌کد فرم شما:</strong> <code>[' . $shortcode . ']</code></p></div>';
    }

    echo '<div class="wrap"><h1>افزودن فرم جدید</h1>';
    echo '<form method="post">';
    echo '<p><label>نام فرم<br><input type="text" name="form_name" required class="regular-text"></label></p>';
    echo '<p><label>توضیحات فرم<br><textarea name="form_description" class="large-text" rows="3"></textarea></label></p>';
    echo '<p><label><input type="checkbox" name="auto_register" value="1"> ثبت‌نام خودکار کاربر هنگام ارسال فرم</label></p>';
    echo '<p><input type="submit" name="bfb_add_form" class="button-primary" value="ساخت فرم"></p>';
    echo '</form></div>';
}

// صفحه فرم‌های من
function bfb_render_my_forms_page() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $forms_table = $wpdb->prefix . 'bfb_forms';
    
    $forms = $wpdb->get_results($wpdb->prepare("SELECT * FROM $forms_table WHERE user_id = %d ORDER BY created_at DESC", $current_user_id));
    
    echo '<div class="wrap"><h1>فرم‌های من</h1>';
    echo '<a href="' . admin_url('admin.php?page=bfb-add-form') . '" class="button button-primary">فرم جدید</a>';
    
    if ($forms) {
        echo '<table class="widefat fixed striped" style="margin-top: 20px;">';
        echo '<thead><tr><th>نام فرم</th><th>شورت‌کد</th><th>تاریخ ایجاد</th><th>عملیات</th></tr></thead><tbody>';
        foreach ($forms as $form) {
            echo '<tr>';
            echo '<td>' . esc_html($form->form_name) . '</td>';
            echo '<td><code>' . esc_html($form->shortcode) . '</code></td>';
            echo '<td>' . esc_html($form->created_at) . '</td>';
            echo '<td>';
            echo '<a href="' . admin_url('admin.php?page=bfb-manage-fields&form_id=' . $form->id) . '" class="button">مدیریت فیلدها</a> ';
            echo '<a href="' . admin_url('admin.php?page=bfb-submissions&form_id=' . $form->id) . '" class="button">مشاهده ثبت‌نام‌ها</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>هنوز فرمی نساخته‌اید.</p>';
    }
    echo '</div>';
}

// صفحه آمار
function bfb_render_statistics_page() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $forms_table = $wpdb->prefix . 'bfb_forms';
    $submissions_table = $wpdb->prefix . 'bfb_submissions';
    
    // آمار کلی
    $total_forms = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $forms_table WHERE user_id = %d", $current_user_id));
    $total_submissions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $submissions_table WHERE user_id = %d", $current_user_id));
    $today_submissions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $submissions_table WHERE user_id = %d AND DATE(created_at) = CURDATE()", $current_user_id));
    $this_month_submissions = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $submissions_table WHERE user_id = %d AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())", $current_user_id));
    
    // آمار فرم‌ها
    $forms_stats = $wpdb->get_results($wpdb->prepare("
        SELECT f.id, f.form_name, f.shortcode, f.created_at, f.auto_register,
               COUNT(s.id) as submission_count
        FROM $forms_table f
        LEFT JOIN $submissions_table s ON f.id = s.form_id
        WHERE f.user_id = %d
        GROUP BY f.id
        ORDER BY f.created_at DESC
    ", $current_user_id));
    
    // آمار ماهانه (آخرین 6 ماه)
    $monthly_stats = $wpdb->get_results($wpdb->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
               COUNT(*) as count
        FROM $submissions_table
        WHERE user_id = %d
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
    ", $current_user_id));
    
    echo '<div class="wrap"><h1>آمار و گزارشات</h1>';
    
    // کارت‌های آمار کلی
    echo '<div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">';
    echo '<div class="bfb-stat-card" style="flex: 1; min-width: 200px;">';
    echo '<h3>کل فرم‌ها</h3><p style="font-size: 24px; color: #ff3800; font-weight: bold;">' . $total_forms . '</p>';
    echo '</div>';
    echo '<div class="bfb-stat-card" style="flex: 1; min-width: 200px;">';
    echo '<h3>کل ثبت‌نام‌ها</h3><p style="font-size: 24px; color: #0073ff; font-weight: bold;">' . $total_submissions . '</p>';
    echo '</div>';
    echo '<div class="bfb-stat-card" style="flex: 1; min-width: 200px;">';
    echo '<h3>ثبت‌نام‌های امروز</h3><p style="font-size: 24px; color: #0c0c0c; font-weight: bold;">' . $today_submissions . '</p>';
    echo '</div>';
    echo '<div class="bfb-stat-card" style="flex: 1; min-width: 200px;">';
    echo '<h3>ثبت‌نام‌های این ماه</h3><p style="font-size: 24px; color: #28a745; font-weight: bold;">' . $this_month_submissions . '</p>';
    echo '</div>';
    echo '</div>';
    
    // آمار تفصیلی فرم‌ها
    echo '<h2>آمار فرم‌ها</h2>';
    if ($forms_stats) {
        echo '<table class="widefat fixed striped bfb-table">';
        echo '<thead><tr><th>نام فرم</th><th>شورت‌کد</th><th>تاریخ ایجاد</th><th>ثبت‌نام خودکار</th><th>تعداد ثبت‌نام</th><th>عملیات</th></tr></thead><tbody>';
        foreach ($forms_stats as $form) {
            echo '<tr>';
            echo '<td>' . esc_html($form->form_name) . '</td>';
            echo '<td><code>' . esc_html($form->shortcode) . '</code></td>';
            echo '<td>' . esc_html($form->created_at) . '</td>';
            echo '<td>' . ($form->auto_register ? 'فعال' : 'غیرفعال') . '</td>';
            echo '<td>' . $form->submission_count . '</td>';
            echo '<td>';
            echo '<a href="' . admin_url('admin.php?page=bfb-manage-fields&form_id=' . $form->id) . '" class="button">مدیریت فیلدها</a> ';
            echo '<a href="' . admin_url('admin.php?page=bfb-submissions&form_id=' . $form->id) . '" class="button">مشاهده ثبت‌نام‌ها</a>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>هنوز فرمی نساخته‌اید.</p>';
    }
    
    // نمودار آمار ماهانه
    if ($monthly_stats) {
        echo '<h2>آمار ماهانه (آخرین 6 ماه)</h2>';
        echo '<div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">';
        echo '<div style="display: flex; align-items: end; gap: 10px; height: 200px; border-bottom: 2px solid #0c0c0c; padding-bottom: 10px;">';
        
        $max_count = max(array_column($monthly_stats, 'count'));
        
        foreach ($monthly_stats as $stat) {
            $height_percent = $max_count > 0 ? ($stat->count / $max_count) * 100 : 0;
            $month_name = date('M Y', strtotime($stat->month . '-01'));
            
            echo '<div style="display: flex; flex-direction: column; align-items: center; flex: 1;">';
            echo '<div style="background: linear-gradient(135deg, #ff3800, #0073ff); width: 30px; height: ' . $height_percent . '%; border-radius: 4px 4px 0 0; margin-bottom: 5px;"></div>';
            echo '<small style="font-size: 12px; color: #666;">' . $month_name . '</small>';
            echo '<small style="font-size: 11px; color: #999;">' . $stat->count . '</small>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    // آمار برترین فرم‌ها
    $top_forms = $wpdb->get_results($wpdb->prepare("
        SELECT f.form_name, COUNT(s.id) as submission_count
        FROM $forms_table f
        LEFT JOIN $submissions_table s ON f.id = s.form_id
        WHERE f.user_id = %d
        GROUP BY f.id
        HAVING submission_count > 0
        ORDER BY submission_count DESC
        LIMIT 5
    ", $current_user_id));
    
    if ($top_forms) {
        echo '<h2>برترین فرم‌ها (بر اساس تعداد ثبت‌نام)</h2>';
        echo '<div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin: 20px 0;">';
        foreach ($top_forms as $index => $form) {
            $percentage = $total_submissions > 0 ? round(($form->submission_count / $total_submissions) * 100, 1) : 0;
            echo '<div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #eee;">';
            echo '<div>';
            echo '<strong>' . esc_html($form->form_name) . '</strong>';
            echo '<div style="background: #f0f0f0; height: 8px; border-radius: 4px; margin-top: 5px; overflow: hidden;">';
            echo '<div style="background: linear-gradient(90deg, #ff3800, #0073ff); height: 100%; width: ' . $percentage . '%;"></div>';
            echo '</div>';
            echo '</div>';
            echo '<div style="text-align: right;">';
            echo '<strong>' . $form->submission_count . '</strong> ثبت‌نام';
            echo '<br><small style="color: #666;">' . $percentage . '%</small>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    // آمار ثبت‌نام‌های اخیر
    $recent_submissions = $wpdb->get_results($wpdb->prepare("
        SELECT s.*, f.form_name
        FROM $submissions_table s
        LEFT JOIN $forms_table f ON s.form_id = f.id
        WHERE s.user_id = %d
        ORDER BY s.created_at DESC
        LIMIT 10
    ", $current_user_id));
    
    if ($recent_submissions) {
        echo '<h2>آخرین ثبت‌نام‌ها</h2>';
        echo '<table class="widefat fixed striped bfb-table">';
        echo '<thead><tr><th>تاریخ</th><th>فرم</th><th>IP</th><th>عملیات</th></tr></thead><tbody>';
        foreach ($recent_submissions as $submission) {
            echo '<tr>';
            echo '<td>' . esc_html($submission->created_at) . '</td>';
            echo '<td>' . esc_html($submission->form_name) . '</td>';
            echo '<td>' . esc_html($submission->ip_address) . '</td>';
            echo '<td><a href="' . admin_url('admin.php?page=bfb-edit-submission&submission_id=' . $submission->id) . '" class="button">مشاهده</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    
    echo '</div>';
}

// صفحه خروجی اکسل
function bfb_render_export_page() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $forms_table = $wpdb->prefix . 'bfb_forms';
    
    $forms = $wpdb->get_results($wpdb->prepare("SELECT * FROM $forms_table WHERE user_id = %d ORDER BY created_at DESC", $current_user_id));
    
    echo '<div class="wrap"><h1>خروجی اکسل</h1>';
    
    if ($forms) {
        echo '<p>فرم مورد نظر را انتخاب کنید تا ثبت‌نام‌های آن را به صورت CSV دانلود کنید:</p>';
        echo '<form method="post">';
        echo '<select name="form_id" required>';
        echo '<option value="">انتخاب فرم...</option>';
        foreach ($forms as $form) {
            echo '<option value="' . $form->id . '">' . esc_html($form->form_name) . '</option>';
        }
        echo '</select>';
        echo '<input type="submit" name="export_csv" class="button button-primary" value="دانلود CSV">';
        echo '</form>';
        
        if (isset($_POST['export_csv']) && $_POST['form_id']) {
            bfb_export_form_submissions($_POST['form_id']);
        }
    } else {
        echo '<p>هنوز فرمی نساخته‌اید.</p>';
    }
    
    echo '</div>';
}

function bfb_export_form_submissions($form_id) {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $submissions_table = $wpdb->prefix . 'bfb_submissions';
    
    $submissions = $wpdb->get_results($wpdb->prepare("SELECT * FROM $submissions_table WHERE form_id = %d AND user_id = %d ORDER BY created_at DESC", $form_id, $current_user_id));
    
    if ($submissions) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="form_submissions.csv"');
        $output = fopen('php://output', 'w');
        
        // هدرها
        fputcsv($output, ['تاریخ', 'فیلدها']);
        
        foreach ($submissions as $submission) {
            $data = maybe_unserialize($submission->submitted_data);
            $fields = [];
            foreach ($data as $key => $value) {
                $fields[] = $key . ': ' . $value;
            }
            fputcsv($output, [
                $submission->created_at,
                implode(' | ', $fields)
            ]);
        }
        
        fclose($output);
        exit;
    }
} 

// صفحه مدیریت فیلدها
function bfb_render_manage_fields_page() {
    global $wpdb;
    $current_user_id = get_current_user_id();
    $form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
    $fields_table = $wpdb->prefix . 'bfb_fields';
    $forms_table = $wpdb->prefix . 'bfb_forms';
    $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d AND user_id = %d", $form_id, $current_user_id));
    if (!$form) {
        echo '<div class="notice notice-error"><p>فرم یافت نشد یا شما مجاز به مدیریت آن نیستید.</p></div>';
        return;
    }
    
    // افزودن فیلد جدید
    if (isset($_POST['bfb_add_field'])) {
        $field_label = sanitize_text_field($_POST['field_label']);
        $field_type = sanitize_text_field($_POST['field_type']);
        $is_required = isset($_POST['is_required']) ? 1 : 0;
        $field_options = isset($_POST['field_options']) ? sanitize_text_field($_POST['field_options']) : '';
        $field_order = intval($_POST['field_order']);
        
        $wpdb->insert($fields_table, [
            'form_id' => $form_id,
            'field_label' => $field_label,
            'field_type' => $field_type,
            'is_required' => $is_required,
            'field_options' => $field_options,
            'field_order' => $field_order
        ]);
        echo '<div class="updated"><p>فیلد جدید اضافه شد.</p></div>';
    }
    
    // حذف فیلد
    if (isset($_GET['delete_field'])) {
        $field_id = intval($_GET['delete_field']);
        $wpdb->delete($fields_table, ['id' => $field_id, 'form_id' => $form_id]);
        echo '<div class="updated"><p>فیلد حذف شد.</p></div>';
    }
    
    $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM $fields_table WHERE form_id = %d ORDER BY field_order ASC", $form_id));
    
    echo '<div class="wrap"><h1>مدیریت فیلدهای فرم: ' . esc_html($form->form_name) . '</h1>';
    
    // فرم افزودن فیلد جدید
    echo '<h2>افزودن فیلد جدید</h2>';
    echo '<form method="post">';
    echo '<table class="form-table">';
    echo '<tr><th><label>برچسب فیلد</label></th><td><input type="text" name="field_label" required class="regular-text"></td></tr>';
    echo '<tr><th><label>نوع فیلد</label></th><td>';
    echo '<select name="field_type" required>';
    echo '<option value="text">متنی</option>';
    echo '<option value="tel">شماره تماس</option>';
    echo '<option value="email">ایمیل</option>';
    echo '<option value="dropdown">لیست کشویی</option>';
    echo '<option value="radio">دکمه رادیویی</option>';
    echo '<option value="checkbox">چک‌باکس</option>';
    echo '<option value="file">آپلود فایل/عکس (حداکثر ۲ مگابایت)</option>';
    echo '</select>';
    echo '</td></tr>';
    echo '<tr><th><label>اجباری</label></th><td><input type="checkbox" name="is_required" value="1"></td></tr>';
    echo '<tr><th><label>گزینه‌ها (برای لیست، رادیویی، چک‌باکس)</label></th><td><input type="text" name="field_options" class="regular-text" placeholder="هر گزینه با کاما جدا شود"></td></tr>';
    echo '<tr><th><label>ترتیب نمایش</label></th><td><input type="number" name="field_order" value="' . (count($fields) + 1) . '" min="1" style="width:80px;"></td></tr>';
    echo '</table>';
    echo '<p><input type="submit" name="bfb_add_field" class="button-primary" value="افزودن فیلد"></p>';
    echo '</form>';
    
    // لیست فیلدها
    echo '<h2>فیلدهای فعلی</h2>';
    if ($fields) {
        echo '<table class="widefat fixed striped bfb-table">';
        echo '<thead><tr><th>برچسب</th><th>نوع</th><th>اجباری</th><th>گزینه‌ها</th><th>ترتیب</th><th>عملیات</th></tr></thead><tbody>';
        foreach ($fields as $field) {
            echo '<tr>';
            echo '<td>' . esc_html($field->field_label) . '</td>';
            echo '<td>' . esc_html($field->field_type) . '</td>';
            echo '<td>' . ($field->is_required ? 'بله' : 'خیر') . '</td>';
            echo '<td>' . esc_html($field->field_options) . '</td>';
            echo '<td>' . intval($field->field_order) . '</td>';
            echo '<td><a href="' . admin_url('admin.php?page=bfb-manage-fields&form_id=' . $form_id . '&delete_field=' . $field->id) . '" class="button" onclick="return confirm(\'آیا مطمئن هستید؟\')">حذف</a></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<p>هنوز فیلدی اضافه نشده است.</p>';
    }
    echo '</div>';
} 