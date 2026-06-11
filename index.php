<?php

/**
 * Timeweb entry point when document root = project root (public_html).
 * Standard local/Docker setup uses public/index.php instead.
 */

use App\Kernel;

require_once __DIR__.'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
