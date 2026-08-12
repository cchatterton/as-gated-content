<?php
/**
 * Front-end gate resolution and rendering.
 *
 * @package AS_Gated_Content
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('wp', 'asgc_prepare_frontend_gate');
add_action('wp_enqueue_scripts', 'asgc_enqueue_frontend_assets');
add_action('wp_footer', 'asgc_render_frontend_gate');

function asgc_prepare_frontend_gate(): void
{
    if (is_admin() || !is_singular() || !function_exists('get_field')) {
        return;
    }

    $post_id = get_queried_object_id();

    if ($post_id <= 0) {
        return;
    }

    $gate_config = asgc_resolve_gate_for_post($post_id);

    if (empty($gate_config)) {
        return;
    }

    $GLOBALS['asgc_frontend_gate'] = $gate_config;
}

function asgc_resolve_gate_for_post(int $post_id): array
{
    $raw_gate_behavior = get_field('asgc_content_gate_behavior', $post_id);
    $existing_page_gate_id = asgc_sanitize_gate_id(get_field('asgc_content_gate_id', $post_id));
    $gate_behavior = asgc_sanitize_content_gate_behavior($raw_gate_behavior);

    if (($raw_gate_behavior === null || $raw_gate_behavior === '' || $raw_gate_behavior === false) && $existing_page_gate_id > 0) {
        $gate_behavior = 'override';
    }

    if ('disable' === $gate_behavior) {
        return array();
    }

    $page_gate_id = 'override' === $gate_behavior ? $existing_page_gate_id : 0;

    if ($page_gate_id > 0 && asgc_is_valid_gate($page_gate_id)) {
        return array(
            'source'    => 'post',
            'post_id'   => $post_id,
            'gate_id'   => $page_gate_id,
            'form_id'   => asgc_get_gate_form_id($page_gate_id),
            'trigger'   => asgc_sanitize_trigger(get_field('asgc_content_trigger', $post_id)),
            'delay'     => asgc_sanitize_non_negative_integer(get_field('asgc_content_delay', $post_id)),
            'threshold' => asgc_sanitize_non_negative_integer(get_field('asgc_content_threshold', $post_id)),
        );
    }

    $rule_id = asgc_get_matching_gate_rule_for_post($post_id);

    if ($rule_id <= 0) {
        return array();
    }

    $rule_gate_id = asgc_sanitize_gate_id(get_field('asgc_rule_gate_id', $rule_id));

    if ($rule_gate_id <= 0 || !asgc_is_valid_gate($rule_gate_id)) {
        return array();
    }

    return array(
        'source'    => 'rule',
        'post_id'   => $post_id,
        'rule_id'   => $rule_id,
        'gate_id'   => $rule_gate_id,
        'form_id'   => asgc_get_gate_form_id($rule_gate_id),
        'trigger'   => asgc_sanitize_trigger(get_field('asgc_rule_trigger', $rule_id)),
        'delay'     => asgc_sanitize_non_negative_integer(get_field('asgc_rule_delay', $rule_id)),
        'threshold' => asgc_sanitize_non_negative_integer(get_field('asgc_rule_threshold', $rule_id)),
    );
}

function asgc_get_gate_rule_for_post_type(string $post_type): int
{
    return asgc_get_matching_gate_rule_for_post_type($post_type);
}

function asgc_get_matching_gate_rule_for_post_type(string $post_type): int
{
    if (!array_key_exists($post_type, asgc_get_public_content_post_types())) {
        return 0;
    }

    $rules = get_posts(
        array(
            'post_type'      => 'gate_rule',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'   => 'asgc_rule_post_type',
                    'value' => sanitize_key($post_type),
                ),
            ),
            'fields'         => 'ids',
        )
    );

    return empty($rules) ? 0 : absint($rules[0]);
}

function asgc_get_matching_gate_rule_for_post(int $post_id): int
{
    $post_type = (string) get_post_type($post_id);

    if (!array_key_exists($post_type, asgc_get_public_content_post_types())) {
        return 0;
    }

    $rules = get_posts(
        array(
            'post_type'      => 'gate_rule',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'   => 'asgc_rule_post_type',
                    'value' => sanitize_key($post_type),
                ),
            ),
            'fields'         => 'ids',
        )
    );

    $matching_rules = array();

    foreach ($rules as $rule_id) {
        $rule_id = absint($rule_id);

        if (!asgc_rule_matches_post($rule_id, $post_id)) {
            continue;
        }

        $matching_rules[] = array(
            'id'       => $rule_id,
            'priority' => (int) get_field('asgc_rule_priority', $rule_id),
            'modified' => (string) get_post_modified_time('U', true, $rule_id),
        );
    }

    if (empty($matching_rules)) {
        return 0;
    }

    usort(
        $matching_rules,
        static function (array $first, array $second): int {
            if ($first['priority'] === $second['priority']) {
                return (int) $second['modified'] <=> (int) $first['modified'];
            }

            return $second['priority'] <=> $first['priority'];
        }
    );

    return absint($matching_rules[0]['id']);
}

function asgc_rule_matches_post(int $rule_id, int $post_id): bool
{
    if (!$rule_id || !$post_id || 'gate_rule' !== get_post_type($rule_id)) {
        return false;
    }

    if (!asgc_rule_is_active($rule_id)) {
        return false;
    }

    if (sanitize_key((string) get_field('asgc_rule_post_type', $rule_id)) !== (string) get_post_type($post_id)) {
        return false;
    }

    if (!asgc_rule_category_conditions_match($rule_id, $post_id)) {
        return false;
    }

    return asgc_rule_meta_conditions_match($rule_id, $post_id);
}

function asgc_rule_is_active(int $rule_id): bool
{
    if (!metadata_exists('post', $rule_id, 'asgc_rule_active')) {
        return true;
    }

    $active = get_field('asgc_rule_active', $rule_id);

    return (bool) $active;
}

function asgc_rule_category_conditions_match(int $rule_id, int $post_id): bool
{
    if ('post' !== get_post_type($post_id)) {
        return true;
    }

    $category_ids = asgc_get_rule_category_condition_ids($rule_id);

    if (empty($category_ids)) {
        return true;
    }

    $post_category_ids = wp_get_post_categories($post_id, array('fields' => 'ids'));

    return !empty(array_intersect($category_ids, array_map('absint', $post_category_ids)));
}

function asgc_get_rule_category_condition_ids(int $rule_id): array
{
    $category_ids = array();
    $conditions = get_field('asgc_rule_category_conditions', $rule_id);

    if (is_array($conditions)) {
        foreach ($conditions as $condition) {
            if (!empty($condition['category'])) {
                $category_ids[] = absint($condition['category']);
            }
        }
    }

    if (!empty($category_ids)) {
        return array_values(array_unique(array_filter($category_ids)));
    }

    return array_values(array_unique(array_filter(array_map('absint', (array) get_field('asgc_rule_post_categories', $rule_id)))));
}

function asgc_rule_meta_conditions_match(int $rule_id, int $post_id): bool
{
    $conditions = get_field('asgc_rule_meta_conditions', $rule_id);

    if (empty($conditions) || !is_array($conditions)) {
        return true;
    }

    $mode = asgc_sanitize_condition_mode(get_field('asgc_rule_condition_mode', $rule_id));
    $results = array();

    foreach ($conditions as $condition) {
        $meta_key = isset($condition['key']) ? trim(sanitize_text_field((string) $condition['key'])) : '';

        if ('' === $meta_key) {
            continue;
        }

        $results[] = asgc_meta_condition_matches_post(
            $post_id,
            $meta_key,
            asgc_sanitize_meta_operator($condition['operator'] ?? 'equals'),
            isset($condition['value']) ? (string) $condition['value'] : ''
        );
    }

    if (empty($results)) {
        return true;
    }

    return 'any' === $mode ? in_array(true, $results, true) : !in_array(false, $results, true);
}

function asgc_meta_condition_matches_post(int $post_id, string $meta_key, string $operator, string $expected_value): bool
{
    $meta_exists = metadata_exists('post', $post_id, $meta_key);
    $actual_value = get_post_meta($post_id, $meta_key, true);

    if ('exists' === $operator) {
        return $meta_exists;
    }

    if ('not_exists' === $operator) {
        return !$meta_exists;
    }

    if (!$meta_exists) {
        return false;
    }

    $actual = asgc_normalize_meta_value_for_comparison($actual_value);
    $expected = trim($expected_value);

    switch ($operator) {
        case 'not_equals':
            return $actual !== $expected;
        case 'contains':
            return str_contains($actual, $expected);
        case 'not_contains':
            return !str_contains($actual, $expected);
        case 'greater_than':
            return is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected;
        case 'less_than':
            return is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected;
        case 'equals':
        default:
            return $actual === $expected;
    }
}

function asgc_normalize_meta_value_for_comparison($value): string
{
    if (is_scalar($value) || $value === null) {
        return trim((string) $value);
    }

    return trim(wp_json_encode($value) ?: '');
}

function asgc_enqueue_frontend_assets(): void
{
    if (empty($GLOBALS['asgc_frontend_gate'])) {
        return;
    }

    wp_enqueue_style(
        'asgc-gate',
        ASGC_PLUGIN_URL . 'assets/gate.css',
        array(),
        ASGC_VERSION
    );

    wp_enqueue_script(
        'asgc-gate',
        ASGC_PLUGIN_URL . 'assets/gate.js',
        array(),
        ASGC_VERSION,
        true
    );
}

function asgc_render_frontend_gate(): void
{
    if (empty($GLOBALS['asgc_frontend_gate']) || !is_array($GLOBALS['asgc_frontend_gate'])) {
        return;
    }

    $config = $GLOBALS['asgc_frontend_gate'];
    $gate_id = absint($config['gate_id']);
    $form_id = absint($config['form_id']);

    if ($gate_id <= 0 || $form_id <= 0) {
        return;
    }

    $GLOBALS['asgc_current_gate_id'] = $gate_id;
    ?>
    <div
        class="asgc-gate"
        data-asgc-gate
        data-gate-id="<?php echo esc_attr((string) $gate_id); ?>"
        data-post-id="<?php echo esc_attr((string) absint($config['post_id'])); ?>"
        data-form-id="<?php echo esc_attr((string) $form_id); ?>"
        data-trigger="<?php echo esc_attr($config['trigger']); ?>"
        data-delay="<?php echo esc_attr((string) absint($config['delay'])); ?>"
        data-threshold="<?php echo esc_attr((string) absint($config['threshold'])); ?>"
        aria-hidden="true"
    >
        <div class="asgc-gate__dialog" role="dialog" aria-modal="true" aria-labelledby="asgc-gate-title-<?php echo esc_attr((string) $gate_id); ?>" tabindex="-1">
            <button class="asgc-gate__close" type="button" data-asgc-close aria-label="<?php esc_attr_e('Close gate', 'as-gated-content'); ?>">
                <span aria-hidden="true">&times;</span>
            </button>
            <div class="asgc-gate__content">
                <h2 class="screen-reader-text" id="asgc-gate-title-<?php echo esc_attr((string) $gate_id); ?>">
                    <?php echo esc_html(get_the_title($gate_id)); ?>
                </h2>
                <?php echo wp_kses_post(apply_filters('the_content', get_post_field('post_content', $gate_id))); ?>
            </div>
            <div class="asgc-gate__form">
                <?php gravity_form($form_id, false, false, false, array('asgc_gate_id' => $gate_id), true); ?>
            </div>
        </div>
    </div>
    <?php
    unset($GLOBALS['asgc_current_gate_id']);
}
