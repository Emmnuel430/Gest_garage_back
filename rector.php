<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/routes',
    ])
    // Ignore les fichiers ou règles qui pourraient casser votre logique spécifique
    ->withSkip([
        __DIR__ . '/app/Http/Middleware/VerifyCsrfToken.php',
    ])
    // Configure Rector pour cibler PHP 8.2 minimum
    ->withPhpSets(php82: true);
