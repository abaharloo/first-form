<?php
defined('ABSPATH') || exit;

function bfb_handle_form_submission() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    if (!isset($_POST['form_id']) || !isset($_POST['shortcode'])) {
        return;
    }

    global $wpdb;
    $form_id = intval($_POST['form_id']);
    $shortcode = sanitize_text_field($_POST['shortcode']);
    $current_user_id = get_current_user_id();
    
    // بررسی وجود فرم
    $forms_table = $wpdb->prefix . 'bfb_forms';
    $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d AND shortcode = %s", $form_id, $shortcode));
    
    if (!$form) {
        wp_die('فرم یافت نشد.');
    }
    
    // اگر کاربر لاگین نیست، اجازه ارسال فرم را بده
    $form_owner_id = $form->user_id;
    
    $fields_table = $wpdb->prefix . 'bfb_fields';
    $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM $fields_table WHERE form_id = %d ORDER BY field_order ASC", $form_id));
    
    $submitted_data = [];
    $errors = [];
    $uploaded_files = [];
    $user_registration_data = [];

    // پردازش فیلدها
    foreach ($fields as $field) {
        $field_name = 'bfb_field_' . $field->id;
        
        if ($field->field_type === 'file') {
            if (isset($_FILES[$field_name]) && $_FILES[$field_name]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$field_name];
                
                // بررسی نوع فایل
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
                if (!in_array($file['type'], $allowed_types)) {
                    $errors[] = 'نوع فایل مجاز نیست. فقط تصاویر و PDF مجاز است.';
                    continue;
                }
                
                // بررسی حجم فایل (2MB)
                if ($file['size'] > 2097152) {
                    $errors[] = 'حجم فایل بیش از ۲ مگابایت است.';
                    continue;
                }
                
                // آپلود فایل
                $upload_dir = wp_upload_dir();
                $bfb_upload_dir = $upload_dir['basedir'] . '/bfb-uploads/' . $form_owner_id . '/' . $form_id;
                
                if (!file_exists($bfb_upload_dir)) {
                    wp_mkdir_p($bfb_upload_dir);
                }
                
                $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = uniqid() . '.' . $file_extension;
                $file_path = $bfb_upload_dir . '/' . $file_name;
                
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    $uploaded_files[$field_name] = $upload_dir['baseurl'] . '/bfb-uploads/' . $form_owner_id . '/' . $form_id . '/' . $file_name;
                    $submitted_data[$field->field_label] = $uploaded_files[$field_name];
                } else {
                    $errors[] = 'خطا در آپلود فایل.';
                }
            } elseif ($field->is_required) {
                $errors[] = 'فیلد ' . $field->field_label . ' الزامی است.';
            }
        } elseif ($field->field_type === 'checkbox') {
            if (isset($_POST[$field_name]) && is_array($_POST[$field_name])) {
                $submitted_data[$field->field_label] = implode(', ', $_POST[$field_name]);
            } elseif ($field->is_required) {
                $errors[] = 'فیلد ' . $field->field_label . ' الزامی است.';
            }
        } else {
            $value = isset($_POST[$field_name]) ? sanitize_text_field($_POST[$field_name]) : '';
            
            if ($field->is_required && empty($value)) {
                $errors[] = 'فیلد ' . $field->field_label . ' الزامی است.';
            } elseif (!empty($value)) {
                $submitted_data[$field->field_label] = $value;
                
                // جمع‌آوری اطلاعات برای ثبت‌نام کاربر
                if (strpos($field->field_label, 'شماره تماس') !== false || strpos($field->field_label, 'تلفن') !== false) {
                    $user_registration_data['phone'] = $value;
                } elseif (strpos($field->field_label, 'ایمیل') !== false) {
                    $user_registration_data['email'] = $value;
                } elseif (strpos($field->field_label, 'نام') !== false) {
                    $user_registration_data['name'] = $value;
                }
            }
        }
    }

    if (!empty($errors)) {
        wp_die(implode('<br>', $errors));
    }

    // ذخیره ثبت‌نام
    $submissions_table = $wpdb->prefix . 'bfb_submissions';
    $wpdb->insert($submissions_table, [
        'form_id' => $form_id,
        'user_id' => $form_owner_id, // مالک فرم
        'form_name' => $form->form_name,
        'submitted_data' => serialize($submitted_data),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT']
    ]);

    // ثبت‌نام خودکار کاربر (اگر فعال باشد و کاربر لاگین نیست)
    $new_user_id = null;
    if ($form->auto_register && !is_user_logged_in() && !empty($user_registration_data['phone'])) {
        $new_user_id = bfb_auto_register_user($user_registration_data['phone'], $user_registration_data['email'] ?? '', $user_registration_data['name'] ?? '');
        
        if ($new_user_id) {
            // لاگین کردن کاربر جدید
            wp_set_current_user($new_user_id);
            wp_set_auth_cookie($new_user_id);
            
            // پیام خوشامدگویی
            echo '<div class="bfb-success">';
            echo '<h3>ثبت‌نام موفق!</h3>';
            echo '<p>حساب کاربری شما با موفقیت ایجاد شد و وارد سایت شدید.</p>';
            if (!empty($user_registration_data['email'])) {
                echo '<p>اطلاعات ورود به ایمیل شما ارسال شد.</p>';
            }
            echo '</div>';
        }
    }

    // پیام موفقیت
    echo '<div class="bfb-success">';
    echo '<h3>فرم با موفقیت ارسال شد!</h3>';
    echo '<p>اطلاعات شما با موفقیت ثبت شد.</p>';
    if ($new_user_id) {
        echo '<p>حساب کاربری شما نیز ایجاد شد و وارد سایت شدید.</p>';
    }
    echo '</div>';
    
    echo '<script>setTimeout(function(){ window.location.reload(); }, 3000);</script>';
}
add_action('init', 'bfb_handle_form_submission');

function bfb_auto_register_user($phone, $email = '', $name = '') {
    // بررسی وجود کاربر با شماره تلفن
    $existing_user = get_users([
        'meta_key' => 'phone_number',
        'meta_value' => $phone,
        'number' => 1
    ]);

    if (!empty($existing_user)) {
        return $existing_user[0]->ID;
    }

    // بررسی وجود کاربر با ایمیل
    if ($email) {
        $existing_user = get_user_by('email', $email);
        if ($existing_user) {
            // به‌روزرسانی شماره تلفن کاربر موجود
            update_user_meta($existing_user->ID, 'phone_number', $phone);
            return $existing_user->ID;
        }
    }

    // ایجاد نام کاربری منحصر به فرد
    $username = 'user_' . time() . '_' . rand(1000, 9999);
    $password = wp_generate_password(12, false); // رمز عبور قوی‌تر

    // ایجاد کاربر جدید
    $user_data = [
        'user_login' => $username,
        'user_pass' => $password,
        'user_email' => $email ?: $username . '@example.com',
        'display_name' => $name ?: $username,
        'first_name' => $name ?: '',
        'role' => 'subscriber'
    ];
    
    $user_id = wp_insert_user($user_data);

    if (!is_wp_error($user_id)) {
        // ذخیره شماره تلفن
        update_user_meta($user_id, 'phone_number', $phone);
        
        // ارسال ایمیل خوشامدگویی
        if ($email) {
            $subject = 'خوش آمدید به ' . get_bloginfo('name');
            $message = "سلام $name،\n\n";
            $message .= "حساب کاربری شما با موفقیت ایجاد شد.\n\n";
            $message .= "اطلاعات ورود:\n";
            $message .= "نام کاربری: $username\n";
            $message .= "رمز عبور: $password\n\n";
            $message .= "لطفاً پس از ورود، رمز عبور خود را تغییر دهید.\n\n";
            $message .= "با تشکر\n";
            $message .= get_bloginfo('name');
            
            wp_mail($email, $subject, $message);
        }

        return $user_id;
    }

    return false;
} 