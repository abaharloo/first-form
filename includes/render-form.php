<?php
defined('ABSPATH') || exit;

function bfb_render_registration_form($atts) {
    global $wpdb;
    $fields_table = $wpdb->prefix . 'bfb_fields';
    $forms_table = $wpdb->prefix . 'bfb_forms';

    $atts = shortcode_atts(['shortcode' => ''], $atts, 'first_form');
    $shortcode = sanitize_text_field($atts['shortcode']);
    
    if (!$shortcode) return '<p>شورت‌کد فرم مشخص نشده است.</p>';

    // پیدا کردن فرم بر اساس شورت‌کد
    $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE shortcode = %s", $shortcode));
    if (!$form) return '<p>فرم یافت نشد.</p>';

    $form_id = $form->id;
    $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM $fields_table WHERE form_id = %d ORDER BY field_order ASC", $form_id));

    // لود کردن استایل‌ها
    wp_enqueue_style('bfb-form-style', plugin_dir_url(__FILE__) . '../assets/form-style.css');

    ob_start(); ?>
    <div class="bfb-form-container">
        <form method="post" class="bfb-form" enctype="multipart/form-data" action="">
            <input type="hidden" name="form_id" value="<?php echo $form_id; ?>">
            <input type="hidden" name="shortcode" value="<?php echo esc_attr($shortcode); ?>">
            
            <?php if ($form->form_description): ?>
                <div class="bfb-form-description">
                    <p><?php echo esc_html($form->form_description); ?></p>
                </div>
            <?php endif; ?>

            <?php foreach ($fields as $field): ?>
                <div class="bfb-field">
                    <label for="bfb_field_<?php echo $field->id; ?>">
                        <?php echo esc_html($field->field_label); ?>
                        <?php if ($field->is_required) echo ' <span class="required">*</span>'; ?>
                    </label>
                    
                    <?php if ($field->field_type === 'text'): ?>
                        <input type="text" 
                               id="bfb_field_<?php echo $field->id; ?>" 
                               name="bfb_field_<?php echo $field->id; ?>" 
                               <?php if ($field->is_required) echo 'required'; ?>>
                    
                    <?php elseif ($field->field_type === 'tel'): ?>
                        <input type="tel" 
                               id="bfb_field_<?php echo $field->id; ?>" 
                               name="bfb_field_<?php echo $field->id; ?>" 
                               pattern="[0-9+\-\s()]*"
                               <?php if ($field->is_required) echo 'required'; ?>>
                    
                    <?php elseif ($field->field_type === 'email'): ?>
                        <input type="email" 
                               id="bfb_field_<?php echo $field->id; ?>" 
                               name="bfb_field_<?php echo $field->id; ?>" 
                               <?php if ($field->is_required) echo 'required'; ?>>
                    
                    <?php elseif ($field->field_type === 'dropdown'): 
                        $options = explode(',', $field->field_options); ?>
                        <select id="bfb_field_<?php echo $field->id; ?>" 
                                name="bfb_field_<?php echo $field->id; ?>" 
                                <?php if ($field->is_required) echo 'required'; ?>>
                            <option value="">انتخاب کنید...</option>
                            <?php foreach ($options as $opt): ?>
                                <option value="<?php echo esc_attr(trim($opt)); ?>">
                                    <?php echo esc_html(trim($opt)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    
                    <?php elseif ($field->field_type === 'radio'): 
                        $options = explode(',', $field->field_options); ?>
                        <div class="bfb-radio-group">
                            <?php foreach ($options as $index => $opt): ?>
                                <label class="bfb-radio-label">
                                    <input type="radio" 
                                           id="bfb_field_<?php echo $field->id; ?>_<?php echo $index; ?>" 
                                           name="bfb_field_<?php echo $field->id; ?>" 
                                           value="<?php echo esc_attr(trim($opt)); ?>" 
                                           <?php if ($field->is_required) echo 'required'; ?>>
                                    <span class="bfb-radio-text"><?php echo esc_html(trim($opt)); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    
                    <?php elseif ($field->field_type === 'checkbox'): 
                        $options = explode(',', $field->field_options); ?>
                        <div class="bfb-checkbox-group">
                            <?php foreach ($options as $index => $opt): ?>
                                <label class="bfb-checkbox-label">
                                    <input type="checkbox" 
                                           id="bfb_field_<?php echo $field->id; ?>_<?php echo $index; ?>" 
                                           name="bfb_field_<?php echo $field->id; ?>[]" 
                                           value="<?php echo esc_attr(trim($opt)); ?>">
                                    <span class="bfb-checkbox-text"><?php echo esc_html(trim($opt)); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    
                    <?php elseif ($field->field_type === 'file'): ?>
                        <input type="file" 
                               id="bfb_field_<?php echo $field->id; ?>" 
                               name="bfb_field_<?php echo $field->id; ?>" 
                               accept="image/*,.jpg,.jpeg,.png,.gif,.pdf" 
                               <?php if ($field->is_required) echo 'required'; ?> 
                               onchange="if(this.files[0]&&this.files[0].size>2097152){alert('حداکثر حجم مجاز ۲ مگابایت است');this.value='';}">
                        <small class="bfb-file-hint">حداکثر حجم: ۲ مگابایت</small>
                    
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="bfb-submit-container">
                <button type="submit" class="bfb-submit-btn">ارسال فرم</button>
            </div>
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('first_form', 'bfb_render_registration_form');

// شورت‌کد قدیمی برای سازگاری
function bfb_render_old_form($atts) {
    $atts['shortcode'] = 'business_registration_form';
    return bfb_render_registration_form($atts);
}
add_shortcode('business_registration_form', 'bfb_render_old_form'); 