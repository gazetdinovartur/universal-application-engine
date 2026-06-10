<?php
/*
Plugin Name: Universal Application Engine Bridge
Description: Bootstrap 5 WordPress bridge for Symfony registration/payment API.
Version: 0.1.0
*/

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('UAE_API_BASE')) {
    define('UAE_API_BASE', 'https://api.хануманфест.рф/api');
}

if (!defined('UAE_PRODUCT_SLUG')) {
    define('UAE_PRODUCT_SLUG', 'hanuman-fest-2026');
}

register_activation_hook(__FILE__, static function (): void {
    uae_register_rewrite_rules();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, static function (): void {
    flush_rewrite_rules();
});

add_action('init', 'uae_register_rewrite_rules');
function uae_register_rewrite_rules(): void
{
    add_rewrite_tag('%uae_token%', '([^&]+)');
    add_rewrite_rule('^pay/([^/]+)/?$', 'index.php?pagename=pay&uae_token=$matches[1]', 'top');
}

add_filter('query_vars', static function (array $vars): array {
    $vars[] = 'uae_token';

    return $vars;
});

add_action('wp_enqueue_scripts', static function (): void {
    wp_register_script(
        'uae-bridge',
        plugins_url('assets/uae-bridge.js', __FILE__),
        [],
        '0.1.0',
        true
    );

    wp_localize_script('uae-bridge', 'uaeBridgeConfig', [
        'apiBase' => UAE_API_BASE,
        'productSlug' => UAE_PRODUCT_SLUG,
        'returnUrl' => home_url('/return/'),
    ]);
});

function uae_enqueue_bridge_assets(): void
{
    wp_enqueue_script('uae-bridge');
}

add_shortcode('uae_registration', static function (array $atts = []): string {
    uae_enqueue_bridge_assets();

    $atts = shortcode_atts([
        'product_slug' => UAE_PRODUCT_SLUG,
    ], $atts);

    ob_start();
    ?>
    <div class="container py-4" data-uae-widget="registration" data-product-slug="<?php echo esc_attr($atts['product_slug']); ?>">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-3">Регистрация на Hanuman Fest</h3>
                        <p class="text-muted" data-uae-active-period></p>

                        <form class="row g-3" data-uae-form>
                            <div class="col-md-6">
                                <label class="form-label">Имя</label>
                                <input class="form-control" name="name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input class="form-control" type="email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Телефон</label>
                                <input class="form-control" type="tel" name="phone" placeholder="+7900..." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Вариант участия</label>
                                <select class="form-select" name="participationOptionCode" required></select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Количество взрослых</label>
                                <input class="form-control" type="number" min="1" step="1" name="adultsCount" value="1" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Количество детей до 16 лет</label>
                                <input class="form-control" type="number" min="0" step="1" name="childrenCount" value="0">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Вариант оплаты</label>
                                <select class="form-select" name="paymentFactor">
                                    <option value="0.5">Предоплата 50%</option>
                                    <option value="1">Полная оплата</option>
                                </select>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="transferIncluded" id="uae-transfer">
                                    <label class="form-check-label" for="uae-transfer">Трансфер туда-обратно (+600 ₽/чел)</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="alert alert-light border" data-uae-pricing>
                                    <div>Стоимость участия: <strong data-uae-total>—</strong></div>
                                    <div>К оплате сейчас: <strong data-uae-now>—</strong></div>
                                    <div class="small text-muted" data-uae-meta></div>
                                </div>
                            </div>

                            <div class="col-12 d-flex gap-2 align-items-center">
                                <button class="btn btn-primary" type="submit" data-uae-submit>Зарегистрироваться и оплатить</button>
                                <span class="text-muted small" data-uae-status></span>
                            </div>

                            <div class="col-12">
                                <div class="alert alert-danger d-none" data-uae-error></div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
});

add_shortcode('uae_payment', static function (array $atts = []): string {
    uae_enqueue_bridge_assets();

    $atts = shortcode_atts([
        'token' => '',
    ], $atts);

    $token = $atts['token'];
    if ($token === '') {
        $token = (string) get_query_var('uae_token', '');
    }

    ob_start();
    ?>
    <div class="container py-4" data-uae-widget="payment" data-token="<?php echo esc_attr($token); ?>">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-3">Оплата второй половины</h3>
                        <div class="alert alert-light border" data-uae-payment-info>Загрузка данных заявки...</div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success" data-uae-pay-btn>Перейти к оплате</button>
                            <span class="text-muted small" data-uae-status></span>
                        </div>
                        <div class="alert alert-danger d-none mt-3" data-uae-error></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
});

add_shortcode('uae_return', static function (): string {
    uae_enqueue_bridge_assets();

    ob_start();
    ?>
    <div class="container py-4" data-uae-widget="return">
        <div class="row justify-content-center">
            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h3 class="card-title mb-3">Результат оплаты</h3>
                        <div class="alert alert-light border" data-uae-return-info>Проверяем статус оплаты...</div>
                        <div class="alert alert-danger d-none" data-uae-error></div>
                        <a class="btn btn-outline-primary mt-2" href="<?php echo esc_url(home_url('/registration/')); ?>">Вернуться к регистрации</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php

    return (string) ob_get_clean();
});

