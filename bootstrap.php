<?php

declare(strict_types=1);

/**
 * ksf_FA_CRM module bootstrap.
 *
 * Loads the Composer autoloader, then registers the PSR-4 prefixes for the
 * vendored ksfraser/ksf-fa-common package. The package is required in
 * composer.json but its generated autoload map predates the dependency
 * (composer update is blocked locally by unrelated private packages), so the
 * prefixes are registered here at runtime. This is idempotent and survives a
 * future `composer update`, which will add the same prefixes natively.
 *
 * @package ksf_FA_CRM
 * @since 1.0.0
 */

$ksfCrmLoader = require_once __DIR__ . '/vendor/autoload.php';

if ($ksfCrmLoader instanceof \Composer\Autoload\ClassLoader) {
    $ksfFaCommon = __DIR__ . '/vendor/ksfraser/ksf-fa-common/src';
    if (is_dir($ksfFaCommon)) {
        $ksfCrmLoader->addPsr4('ksfraser\\FrontAccounting\\Common\\', $ksfFaCommon);
        $ksfCrmLoader->addPsr4('Ksfraser\\Frontaccounting\\HTML\\', $ksfFaCommon . '/HTML');
    }
}
