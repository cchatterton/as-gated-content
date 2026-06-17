<?php
/**
 * Shared helpers for AS Gated Content.
 *
 * @package AS_Gated_Content
 */

if (!defined('ABSPATH')) {
    exit;
}

function asgc_is_acf_available(): bool
{
    return function_exists('acf_add_local_field_group');
}

function asgc_is_gravity_forms_available(): bool
{
    return class_exists('GFAPI') && function_exists('gravity_form');
}

function asgc_get_public_content_post_types(): array
{
    $post_types = get_post_types(
        array(
            'public' => true,
        ),
        'objects'
    );

    unset($post_types['attachment'], $post_types['gate'], $post_types['gate_rule']);

    $choices = array();

    foreach ($post_types as $post_type => $object) {
        $choices[$post_type] = $object->labels->singular_name ?: $object->label;
    }

    return $choices;
}

function asgc_get_gate_choices(bool $valid_only = false): array
{
    $gates = get_posts(
        array(
            'post_type'      => 'gate',
            'post_status'    => array('publish', 'draft', 'private'),
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        )
    );

    $choices = array();

    foreach ($gates as $gate_id) {
        if ($valid_only && !asgc_is_valid_gate((int) $gate_id)) {
            continue;
        }

        $choices[$gate_id] = get_the_title($gate_id) ?: sprintf(__('Gate #%d', 'as-gated-conten'), $gate_id);
    }

    return $choices;
}

function asgc_get_gravity_form_choices(): array
{
    if (!class_exists('GFAPI')) {
        return array();
    }

    $forms = GFAPI::get_forms(true, false, 'title');
    $choices = array();

    foreach ($forms as $form) {
        $form_id = isset($form['id']) ? absint($form['id']) : 0;

        if ($form_id <= 0) {
            continue;
        }

        $choices[$form_id] = isset($form['title']) ? sanitize_text_field((string) $form['title']) : sprintf(__('Form #%d', 'as-gated-conten'), $form_id);
    }

    return $choices;
}

function asgc_gravity_form_exists(int $form_id): bool
{
    if ($form_id <= 0 || !class_exists('GFAPI')) {
        return false;
    }

    $form = GFAPI::get_form($form_id);

    return is_array($form) && empty($form['is_trash']) && !empty($form['is_active']);
}

function asgc_sanitize_trigger($value): string
{
    return in_array($value, array('entrance', 'exit'), true) ? $value : 'entrance';
}

function asgc_sanitize_non_negative_integer($value): int
{
    return max(0, absint($value));
}

function asgc_get_gate_form_id(int $gate_id): int
{
    if ($gate_id <= 0 || 'gate' !== get_post_type($gate_id)) {
        return 0;
    }

    return absint(get_field('asgc_gate_gravity_form_id', $gate_id));
}

function asgc_is_valid_gate(int $gate_id): bool
{
    if ($gate_id <= 0 || 'gate' !== get_post_type($gate_id) || 'publish' !== get_post_status($gate_id)) {
        return false;
    }

    return asgc_gravity_form_exists(asgc_get_gate_form_id($gate_id));
}

function asgc_sanitize_gate_id($value): int
{
    $gate_id = absint($value);

    if ($gate_id <= 0 || 'gate' !== get_post_type($gate_id)) {
        return 0;
    }

    return $gate_id;
}
