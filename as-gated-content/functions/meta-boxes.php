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
add_filter('acf/validate_value/name=asgc_content_gate_behavior', 'asgc_validate_content_gate_behavior_field', 10, 4);
add_filter('acf/validate_value/name=asgc_rule_condition_mode', 'asgc_validate_condition_mode_field', 10, 4);
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
add_filter('acf/update_value/name=asgc_rule_priority', 'asgc_update_integer_value', 10, 3);
add_filter('acf/update_value/name=asgc_content_trigger', 'asgc_update_trigger_value', 10, 3);
add_filter('acf/update_value/name=asgc_rule_trigger', 'asgc_update_trigger_value', 10, 3);
add_filter('acf/update_value/name=asgc_rule_post_type', 'asgc_update_post_type_value', 10, 3);
add_filter('acf/update_value/name=asgc_content_gate_behavior', 'asgc_update_content_gate_behavior_value', 10, 3);
add_filter('acf/update_value/name=asgc_rule_condition_mode', 'asgc_update_condition_mode_value', 10, 3);

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
            'key'       => 'field_asgc_rule_tab_rule',
            'label'     => __('Rule', 'as-gated-content'),
            'name'      => '',
            'type'      => 'tab',
            'placement' => 'top',
        );
        $fields[] = array(
            'key'           => 'field_asgc_rule_active',
            'label'         => __('Rule active', 'as-gated-content'),
            'name'          => 'asgc_rule_active',
            'type'          => 'true_false',
            'default_value' => 1,
            'ui'            => 1,
            'wrapper'       => array('width' => '20'),
        );
        $fields[] = array(
            'key'           => 'field_asgc_rule_priority',
            'label'         => __('Rule priority', 'as-gated-content'),
            'name'          => 'asgc_rule_priority',
            'type'          => 'number',
            'default_value' => 0,
            'step'          => 1,
            'wrapper'       => array('width' => '20'),
        );
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
            'wrapper'       => array('width' => '30'),
        );
    }

    if (!$is_rule) {
        $fields[] = array(
            'key'       => 'field_asgc_content_tab_gate_behavior',
            'label'     => __('Gate Behaviour', 'as-gated-content'),
            'name'      => '',
            'type'      => 'tab',
            'placement' => 'top',
        );
        $fields[] = array(
            'key'           => 'field_asgc_content_gate_behavior',
            'label'         => __('Gate behaviour', 'as-gated-content'),
            'name'          => 'asgc_content_gate_behavior',
            'type'          => 'radio',
            'choices'       => array(
                'inherit'  => __('Inherit gate rules', 'as-gated-content'),
                'override' => __('Use a specific gate', 'as-gated-content'),
                'disable'  => __('Disable gates for this content', 'as-gated-content'),
            ),
            'default_value' => 'inherit',
            'layout'        => 'vertical',
            'return_format' => 'value',
        );
        $fields[] = array(
            'key'     => 'field_asgc_content_effective_gate',
            'label'   => __('Effective gate', 'as-gated-content'),
            'name'    => 'asgc_content_effective_gate',
            'type'    => 'message',
            'message' => asgc_get_content_effective_gate_message(),
        );
    }

    if ($is_rule) {
        $fields[] = array(
            'key'       => 'field_asgc_rule_tab_conditions',
            'label'     => __('Conditions', 'as-gated-content'),
            'name'      => '',
            'type'      => 'tab',
            'placement' => 'top',
        );
        $fields[] = array(
            'key'           => 'field_asgc_rule_condition_mode',
            'label'         => __('Condition mode', 'as-gated-content'),
            'name'          => 'asgc_rule_condition_mode',
            'type'          => 'select',
            'choices'       => array(
                'all' => __('Match all conditions', 'as-gated-content'),
                'any' => __('Match any condition', 'as-gated-content'),
            ),
            'default_value' => 'all',
            'return_format' => 'value',
            'ui'            => 0,
        );
        $fields[] = array(
            'key'          => 'field_asgc_rule_meta_conditions',
            'label'        => __('Meta conditions', 'as-gated-content'),
            'name'         => 'asgc_rule_meta_conditions',
            'type'         => 'repeater',
            'layout'       => 'table',
            'button_label' => __('Add meta condition', 'as-gated-content'),
            'sub_fields'   => array(
                array(
                    'key'   => 'field_asgc_rule_meta_condition_key',
                    'label' => __('Meta key', 'as-gated-content'),
                    'name'  => 'key',
                    'type'  => 'text',
                ),
                array(
                    'key'           => 'field_asgc_rule_meta_condition_operator',
                    'label'         => __('Operator', 'as-gated-content'),
                    'name'          => 'operator',
                    'type'          => 'select',
                    'choices'       => array(
                        'equals'           => __('equals', 'as-gated-content'),
                        'not_equals'       => __('does not equal', 'as-gated-content'),
                        'contains'         => __('contains', 'as-gated-content'),
                        'not_contains'     => __('does not contain', 'as-gated-content'),
                        'exists'           => __('exists', 'as-gated-content'),
                        'not_exists'       => __('does not exist', 'as-gated-content'),
                        'greater_than'     => __('greater than', 'as-gated-content'),
                        'less_than'        => __('less than', 'as-gated-content'),
                    ),
                    'default_value' => 'equals',
                    'return_format' => 'value',
                ),
                array(
                    'key'   => 'field_asgc_rule_meta_condition_value',
                    'label' => __('Value', 'as-gated-content'),
                    'name'  => 'value',
                    'type'  => 'text',
                ),
            ),
        );
        $fields[] = array(
            'key'               => 'field_asgc_rule_category_conditions',
            'label'             => __('Category conditions', 'as-gated-content'),
            'name'              => 'asgc_rule_category_conditions',
            'type'              => 'repeater',
            'layout'            => 'table',
            'button_label'      => __('Add category condition', 'as-gated-content'),
            'conditional_logic' => array(
                array(
                    array(
                        'field'    => 'field_asgc_rule_post_type',
                        'operator' => '==',
                        'value'    => 'post',
                    ),
                ),
            ),
            'sub_fields'        => array(
                array(
                    'key'           => 'field_asgc_rule_category_condition_category',
                    'label'         => __('Category', 'as-gated-content'),
                    'name'          => 'category',
                    'type'          => 'taxonomy',
                    'taxonomy'      => 'category',
                    'field_type'    => 'select',
                    'return_format' => 'id',
                    'add_term'      => 0,
                    'save_terms'    => 0,
                    'load_terms'    => 0,
                    'allow_null'    => 1,
                ),
            ),
        );
    }

    if ($is_rule) {
        $fields[] = array(
            'key'       => 'field_asgc_rule_tab_gate',
            'label'     => __('Gate', 'as-gated-content'),
            'name'      => '',
            'type'      => 'tab',
            'placement' => 'top',
        );
    } else {
        $fields[] = array(
            'key'               => 'field_asgc_content_tab_override_gate',
            'label'             => __('Override Gate', 'as-gated-content'),
            'name'              => '',
            'type'              => 'tab',
            'placement'         => 'top',
            'conditional_logic' => array(
                array(
                    array(
                        'field'    => 'field_asgc_content_gate_behavior',
                        'operator' => '==',
                        'value'    => 'override',
                    ),
                ),
            ),
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
        'conditional_logic' => $is_rule ? 0 : array(
            array(
                array(
                    'field'    => 'field_asgc_content_gate_behavior',
                    'operator' => '==',
                    'value'    => 'override',
                ),
            ),
        ),
    );

    $conditional_logic = $is_rule ? 0 : array(
        array(
            array(
                'field'    => 'field_asgc_content_gate_behavior',
                'operator' => '==',
                'value'    => 'override',
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
    if (true !== $valid) {
        return $valid;
    }

    if (empty($value)) {
        if ('asgc_content_gate_id' === ($field['name'] ?? '') && asgc_content_gate_behavior_from_request() === 'override') {
            return __('Select a Gate for this content override.', 'as-gated-content');
        }

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

function asgc_validate_content_gate_behavior_field($valid, $value, array $field, string $input)
{
    if (true !== $valid) {
        return $valid;
    }

    if (!in_array($value, array('inherit', 'override', 'disable'), true)) {
        return __('Select a valid gate behaviour.', 'as-gated-content');
    }

    return true;
}

function asgc_validate_condition_mode_field($valid, $value, array $field, string $input)
{
    if (true !== $valid) {
        return $valid;
    }

    if (!in_array($value, array('all', 'any'), true)) {
        return __('Select a valid condition mode.', 'as-gated-content');
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

    return asgc_content_gate_behavior_from_request() !== 'override';
}

function asgc_content_gate_behavior_from_request(): string
{
    $acf_values = isset($_POST['acf']) && is_array($_POST['acf']) ? $_POST['acf'] : array();
    $value = isset($acf_values['field_asgc_content_gate_behavior']) ? sanitize_key($acf_values['field_asgc_content_gate_behavior']) : 'inherit';

    return in_array($value, array('inherit', 'override', 'disable'), true) ? $value : 'inherit';
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

function asgc_update_content_gate_behavior_value($value, int $post_id, array $field): string
{
    return asgc_sanitize_content_gate_behavior($value);
}

function asgc_update_condition_mode_value($value, int $post_id, array $field): string
{
    return in_array($value, array('all', 'any'), true) ? $value : 'all';
}

function asgc_get_content_effective_gate_message(): string
{
    $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;

    if ($post_id <= 0) {
        return esc_html__('Save this content to preview the effective gate.', 'as-gated-content');
    }

    if (asgc_sanitize_content_gate_behavior(get_field('asgc_content_gate_behavior', $post_id)) === 'disable') {
        return esc_html__('Gates are disabled for this content.', 'as-gated-content');
    }

    if (!function_exists('asgc_resolve_gate_for_post')) {
        return esc_html__('The effective gate is calculated on the front end.', 'as-gated-content');
    }

    $config = asgc_resolve_gate_for_post($post_id);

    if (empty($config)) {
        return esc_html__('No gate will apply to this content.', 'as-gated-content');
    }

    $gate_title = isset($config['gate_id']) ? get_the_title(absint($config['gate_id'])) : '';

    if ('post' === ($config['source'] ?? '')) {
        return sprintf(
            esc_html__('This content overrides inherited rules and uses: %s.', 'as-gated-content'),
            esc_html($gate_title ?: __('Untitled gate', 'as-gated-content'))
        );
    }

    return sprintf(
        esc_html__('This content inherits: %1$s from Gate Rule #%2$d.', 'as-gated-content'),
        esc_html($gate_title ?: __('Untitled gate', 'as-gated-content')),
        absint($config['rule_id'] ?? 0)
    );
}
