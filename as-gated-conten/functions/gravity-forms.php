<?php
/**
 * Gravity Forms integration.
 *
 * @package AS_Gated_Content
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_notices', 'asgc_show_dependency_admin_notices');
add_filter('gform_pre_render', 'asgc_add_gate_context_hidden_field');
add_filter('gform_pre_validation', 'asgc_add_gate_context_hidden_field');
add_filter('gform_pre_submission_filter', 'asgc_add_gate_context_hidden_field');
add_filter('gform_admin_pre_render', 'asgc_add_gate_context_hidden_field');

function asgc_show_dependency_admin_notices(): void
{
    if (!current_user_can('activate_plugins')) {
        return;
    }

    if (!asgc_is_acf_available()) {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('AS Gated Content requires ACF Pro. Fields are registered from code when ACF is active.', 'as-gated-conten')
        );
    }

    if (!asgc_is_gravity_forms_available()) {
        printf(
            '<div class="notice notice-error"><p>%s</p></div>',
            esc_html__('AS Gated Content requires Gravity Forms. Gates are invalid until Gravity Forms is active and a form is selected.', 'as-gated-conten')
        );
    }
}

function asgc_add_gate_context_hidden_field(array $form): array
{
    if (!class_exists('GF_Fields') || empty($GLOBALS['asgc_current_gate_id'])) {
        return $form;
    }

    foreach ($form['fields'] as $field) {
        if (isset($field->inputName) && 'asgc_gate_id' === $field->inputName) {
            return $form;
        }
    }

    $hidden_field = GF_Fields::create(
        array(
            'type'       => 'hidden',
            'id'         => asgc_get_next_gravity_form_field_id($form),
            'formId'     => isset($form['id']) ? absint($form['id']) : 0,
            'label'      => __('Gate ID', 'as-gated-conten'),
            'inputName'  => 'asgc_gate_id',
            'allowsPrepopulate' => true,
        )
    );

    $hidden_field->defaultValue = absint($GLOBALS['asgc_current_gate_id']);
    $form['fields'][] = $hidden_field;

    return $form;
}

function asgc_get_next_gravity_form_field_id(array $form): int
{
    $max_id = 0;

    foreach ($form['fields'] as $field) {
        $max_id = max($max_id, absint($field->id));
    }

    return $max_id + 1;
}
