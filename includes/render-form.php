<?php
defined('ABSPATH') || exit;

function bfb_render_registration_form($atts) {
    global $wpdb;
    $fields_table = $wpdb->prefix . 'bfb_fields';

    $atts = shortcode_atts(['id' => 0], $atts, 'business_registration_form');
    $form_id = intval($atts['id']);
    if (!$form_id) return '<p>فرم یافت نشد.</p>';

    $fields = $wpdb->get_results($wpdb->prepare("SELECT * FROM $fields_table WHERE form_id = %d", $form_id));

    ob_start(); ?>
    <form method="post" class="bfb-form" enctype="multipart/form-data">
        <?php foreach ($fields as $field): ?>
            <div class="bfb-field">
                <label><?php echo esc_html($field->field_label); ?><?php if ($field->is_required) echo ' *'; ?></label><br>
                <?php if ($field->field_type === 'text'): ?>
                    <input type="text" name="bfb_field_<?php echo $field->id; ?>" <?php if ($field->is_required) echo 'required'; ?>>
                <?php elseif ($field->field_type === 'dropdown'): 
                    $options = explode(',', $field->field_options); ?>
                    <select name="bfb_field_<?php echo $field->id; ?>" <?php if ($field->is_required) echo 'required'; ?>>
                        <?php foreach ($options as $opt): ?>
                            <option value="<?php echo esc_attr(trim($opt)); ?>"><?php echo esc_html(trim($opt)); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($field->field_type === 'radio'): 
                    $options = explode(',', $field->field_options); ?>
                    <?php foreach ($options as $opt): ?>
                        <label>
                            <input type="radio" name="bfb_field_<?php echo $field->id; ?>" value="<?php echo esc_attr(trim($opt)); ?>" <?php if ($field->is_required) echo 'required'; ?>>
                            <?php echo esc_html(trim($opt)); ?>
                        </label><br>
                    <?php endforeach; ?>
                <?php elseif ($field->field_type === 'file'): ?>
                    <input type="file" name="bfb_field_<?php echo $field->id; ?>" accept="image/*,.jpg,.jpeg,.png,.gif,.pdf" <?php if ($field->is_required) echo 'required'; ?> onchange="if(this.files[0]&&this.files[0].size>2097152){alert('حداکثر حجم مجاز ۲ مگابایت است');this.value='';}">
                <?php endif; ?>
            </div><br>
        <?php endforeach; ?>
        <input type="submit" value="ارسال فرم">
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('business_registration_form', 'bfb_render_registration_form'); 