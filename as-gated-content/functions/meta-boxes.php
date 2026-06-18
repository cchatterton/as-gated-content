<?php
/**
 * ACF local field groups and validation.
 *
 * @package AS_Gated_Content
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('acf/init', 'asgc_register_acf_fields');
add_filter('acf/load_field/name=asgc_gate_gravity_form_id', 'asgc_load_gravity_form_choices');
add_filter('acf/load_field/name=asgc_content_gate_id', 'asgc_load_gate_choices');
add_filter('acf/load_field/name=asgc_rule_gate_id', 'asgc_load_gate_choices');
add_filter('acf/load_field/name=asgc_rule_post_type', 'asgc_load_public_post_type_choices');
add_filter('acf/validate_value/name=asgc_gate_gravity_form_id', 'asgc_validate_gravity_form_field', 10, 4);
add_filter('acf/validate_value/name=asgc_content_gate_id', 'asgc_validate_gate_reference_field', 10, 4);
add_filter('acf/validate_value/name=asgc_rule_gate_id', 'asgc_validate_gate_reference_field', 10, 4);
add_filter('acf/validate_value/name=asgc_rule_post_type', 'asgc_validate_gate_rule_post_type_field', 10, 4);
add_filter('acf/validate_value/name=asgc_rule_post_type', 'asgc_validate_unique_gate_rule_field', 20, 4);
add_filter('acf/validate_value/name=asgc_content_trigger', 'asgc_validate_trigger_field', 10, 4);
add_filter('acf/validate_value/name=asgc_rule_trigger', 'asgc_validate_trigger_field', 10, 4);
add_filter('acf/validate_value/name=asgc_content_delay', 'asgc_validate_non_negative_integer_field', 10, 4);
add_filter('acf/validate_value/name=asgc_rule_delay', 'asgc_validate_non_negative_integer_field', 10, 4);
add_filter('acf/validate_value/name=asgc_content_threshold', 'asgc_validate_non_negative_integer_field', 10, 4);
add_filter('acf/validate_value/name=asgc_rule_threshold', 'asgc_validate_non_negative_integer_field', 10, 4);
add_filter('acf/update_value/name=asgc_gate_gravity_form_id', 'asgc_update_integer_value', 10, 3);
add_filter('acf/update_value/name=asgc_content_gate_id', 'asgc_update_integer_value', 10, 3);
add_filter('acf/update_value/name=asgc_rule_gate_id', 'asgc_update_integer_value', 10, 3);
add_filter('acf/update_value/name=asgc_content_delay', 'asgc_update_non_negative_integer_value', 10, 3);
add_filter('acf/update_value/name=asgc_rule_delay', 'asgc_update_non_negative_integer_value', 10, 3);
add_filter('acf/update_value/name=asgc_content_threshold', 'asgc_update_non_negative_integer_value', 10, 3);
add_filter('acf/update_value/name=asgc_rule_threshold', 'asgc_update_non_negative_integer_value', 10, 3);
add_filter('acf/update_value/name=asgc_content_trigger', 'asgc_update_trigger_value', 10, 3);
add_filter('acf/update_value/name=asgc_rule_trigger', 'asgc_update_trigger_value', 10, 3);
add_filter('acf/update_value/name=asgc_rule_post_type', 'asgc_update_post_type_value', 10, 3);

function asgc_register_acf_fields(): void
{
    if (!asgc_is_acf_available()) {
        return;
    }

    acf_add_local_field_group(
        array(
            'key'      => 'group_asgc_gate_settings',
            'title'    => __('Gate Settings', 'as-gated-content'),
            'fields'   => array(
                array(
                    'key'           => 'field_asgc_gate_gravity_form_id',
                    'label'         => __('Gravity Form', 'as-gated-content'),
                    'name'          => 'asgc_gate_gravity_form_id',
                    'type'          => 'select',
                    'required'      => 1,
                    'choices'       => array(),
                    'allow_null'    => 1,
                    'ui'            => 1,
                    'return_format' => 'value',
                ),
                array(
                    'key'           => 'field_asgc_gate_overlay_mode',
                    'label'         => __('Overlay Mode', 'as-gated-content'),
                    'name'          => 'asgc_gate_overlay_mode',
                    'type'          => 'hidden',
                    'default_value' => 'fullscreen',
                ),
                array(
                    'key'     => 'field_asgc_gate_entries_link',
                    'label'   => __('View Entries', 'as-gated-content'),
                    'name'    => 'asgc_gate_entries_link',
                    'type'    => 'message',
                    'message' => asgc_get_gate_entries_message(),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'gate',
                    ),
                ),
            ),
        )
    );

    acf_add_local_field_group(
        array(
            'key'      => 'group_asgc_gate_rule_settings',
            'title'    => __('Gate Rule Settings', 'as-gated-content'),
            'fields'   => asgc_get_gate_configuration_fields('rule'),
            'location' => array(
                array(
                    array(
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => 'gate_rule',
                    ),
                ),
            ),
        )
    );

    $content_locations = array();

    foreach (array_keys(asgc_get_public_content_post_types()) as $post_type) {
        $content_locations[] = array(
            array(
                'param'    => 'post_type',
                'operator' => '==',
                'value'    => $post_type,
            ),
        );
    }

    if (!empty($content_locations)) {
        acf_add_local_field_group(
            array(
                'key'      => 'group_asgc_content_gate_settings',
                'title'    => __('Gate Settings', 'as-gated-content'),
                'fields'   => asgc_get_gate_configuration_fields('content'),
                'location' => $content_locations,
            )
        );
    }
}

function asgc_get_gate_configuration_fields(string $context): array
{
    $is_rule = 'rule' === $context;
    $prefix = $is_rule ? 'asgc_rule' : 'asgc_content';
    $fields = array();

    if ($is_rule) {
        $fields[] = array(
            'key'           => 'field_asgc_rule_post_type',
            'label'         => __('Applies to content type', 'as-gated-content'),
            'name'          => 'asgc_rule_post_type',
            'type'          => 'select',
            'required'      => 1,
            'choices'       => array(),
            'allow_null'    => 1,
            'ui'            => 1,
            'return_format' => 'value',
        );
    }

    $fields[] = array(
        'key'           => 'field_' . $prefix . '_gate_id',
        'label'         => __('Gate', 'as-gated-content'),
        'name'          => $prefix . '_gate_id',
        'type'          => 'select',
        'required'      => $is_rule ? 1 : 0,
        'choices'       => array(),
        'allow_null'    => 1,
        'ui'            => 1,
        'return_format' => 'value',
    );

    $conditional_logic = $is_rule ? 0 : array(
        array(
            array(
                'field'    => 'field_asgc_content_gate_id',
                'operator' => '!=empty',
            ),
        ),
    );

    $fields[] = array(
        'key'               => 'field_' . $prefix . '_trigger',
        'label'             => __('Trigger', 'as-gated-content'),
        'name'              => $prefix . '_trigger',
        'type'              => 'select',
        'required'          => $is_rule ? 1 : 0,
        'choices'           => array(
            'entrance' => __('On entrance', 'as-gated-content'),
            'exit'     => __('On exit intent', 'as-gated-content'),
        ),
        'default_value'     => 'entrance',
        'allow_null'        => 0,
        'return_format'     => 'value',
        'conditional_logic' => $conditional_logic,
    );

    $fields[] = array(
        'key'               => 'field_' . $prefix . '_delay',
        'label'             => __('Delay before showing', 'as-gated-content'),
        'name'              => $prefix . '_delay',
        'type'              => 'number',
        'required'          => $is_rule ? 1 : 0,
        'default_value'     => 0,
        'min'               => 0,
        'step'              => 1,
        'conditional_logic' => $conditional_logic,
    );

    $fields[] = array(
        'key'               => 'field_' . $prefix . '_threshold',
        'label'             => __('Trigger threshold', 'as-gated-content'),
        'name'              => $prefix . '_threshold',
        'type'              => 'number',
        'required'          => $is_rule ? 1 : 0,
        'default_value'     => 0,
        'min'               => 0,
        'step'              => 1,
        'instructions'      => __('Trigger threshold controls how many trigger events occur before the gate appears. 0 means show on the first trigger.', 'as-gated-content'),
        'conditional_logic' => $conditional_logic,
    );

    return $fields;
}

function asgc_get_gate_entries_message(): string
{
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
    $form_id = $post_id > 0 ? asgc_get_gate_form_id($post_id) : 0;

    if ($form_id <= 0) {
        return esc_html__('Select and save a Gravity Form to view entries.', 'as-gated-content');
    }

    $url = admin_url('admin.php?page=gf_entries&id=' . $form_id);

    return sprintf(
        '<a href="%s">%s</a>',
        esc_url($url),
        esc_html__('View Entries', 'as-gated-content')
    );
}

function asgc_load_gravity_form_choices(array $field): array
{
    $field['choices'] = asgc_get_gravity_form_choices();

    return $field;
}

function asgc_load_gate_choices(array $field): array
{
    $field['choices'] = asgc_get_gate_choices();

    return $field;
}

function asgc_load_public_post_type_choices(array $field): array
{
    $field['choices'] = asgc_get_public_content_post_types();

    return $field;
}

function asgc_validate_gravity_form_field($valid, $value, array $field, string $input)
{
    if (true !== $valid) {
        return $valid;
    }

    $form_id = absint($value);

    if ($form_id <= 0 || !asgc_gravity_form_exists($form_id)) {
        return __('Select an active Gravity Form.', 'as-gated-content');
    }

    return true;
}

function asgc_validate_gate_reference_field($valid, $value, array $field, string $input)
{
    if (true !== $valid || empty($value)) {
        return $valid;
    }

    $gate_id = absint($value);

    if ($gate_id <= 0 || 'gate' !== get_post_type($gate_id)) {
        return __('Select a valid Gate.', 'as-gated-content');
    }

    return true;
}

function asgc_validate_gate_rule_post_type_field($valid, $value, array $field, string $input)
{
    if (true !== $valid) {
        return $valid;
    }

    if (!array_key_exists((string) $value, asgc_get_public_content_post_types())) {
        return __('Select a valid public content type.', 'as-gated-content');
    }

    return true;
}

function asgc_validate_unique_gate_rule_field($valid, $value, array $field, string $input)
{
    if (true !== $valid || empty($value)) {
        return $valid;
    }

    $current_post_id = isset($_POST['post_ID']) ? absint($_POST['post_ID']) : 0;
    $existing_rules = get_posts(
        array(
            'post_type'      => 'gate_rule',
            'post_status'    => array('publish', 'draft', 'pending', 'private'),
            'posts_per_page' => 1,
            'post__not_in'   => $current_post_id > 0 ? array($current_post_id) : array(),
            'meta_query'     => array(
                array(
                    'key'   => 'asgc_rule_post_type',
                    'value' => sanitize_key($value),
                ),
            ),
            'fields'         => 'ids',
        )
    );

    if (!empty($existing_rules)) {
        return __('A Gate Rule already exists for this content type.', 'as-gated-content');
    }

    return true;
}

function asgc_validate_trigger_field($valid, $value, array $field, string $input)
{
    if (true !== $valid || asgc_content_gate_context_is_empty($field)) {
        return $valid;
    }

    if (!in_array($value, array('entrance', 'exit'), true)) {
        return __('Select a valid trigger.', 'as-gated-content');
    }

    return true;
}

function asgc_validate_non_negative_integer_field($valid, $value, array $field, string $input)
{
    if (true !== $valid || asgc_content_gate_context_is_empty($field)) {
        return $valid;
    }

    if ('' === $value || !is_numeric($value) || (int) $value < 0) {
        return __('Enter a number greater than or equal to 0.', 'as-gated-content');
    }

    return true;
}

function asgc_content_gate_context_is_empty(array $field): bool
{
    if (!str_starts_with((string) ($field['name'] ?? ''), 'asgc_content_')) {
        return false;
    }

    $content_gate_key = 'field_asgc_content_gate_id';
    $acf_values = isset($_POST['acf']) && is_array($_POST['acf']) ? $_POST['acf'] : array();

    return empty($acf_values[$content_gate_key]);
}

function asgc_update_integer_value($value, int $post_id, array $field): int
{
    return absint($value);
}

function asgc_update_non_negative_integer_value($value, int $post_id, array $field): int
{
    return asgc_sanitize_non_negative_integer($value);
}

function asgc_update_trigger_value($value, int $post_id, array $field): string
{
    return asgc_sanitize_trigger($value);
}

function asgc_update_post_type_value($value, int $post_id, array $field): string
{
    return sanitize_key($value);
}
