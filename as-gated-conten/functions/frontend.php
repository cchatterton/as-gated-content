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
    $page_gate_id = asgc_sanitize_gate_id(get_field('asgc_content_gate_id', $post_id));

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

    $rule_id = asgc_get_gate_rule_for_post_type((string) get_post_type($post_id));

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
            <button class="asgc-gate__close" type="button" data-asgc-close aria-label="<?php esc_attr_e('Close gate', 'as-gated-conten'); ?>">
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
