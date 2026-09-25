<?php

/**
 * PHPUnit bootstrap.
 *
 * The module's runtime bootstrap.php registers the PSR-4 prefixes for the
 * vendored ksfraser/ksf-fa-common package, but it relies on
 * `require_once vendor/autoload.php` returning the Composer ClassLoader. Under
 * PHPUnit the autoloader is already loaded (phpunit's own binary requires it
 * first), so `require_once` returns `true` and the registration is skipped —
 * leaving AbstractAppShell/AbstractTabController/FAModuleMenu unresolvable.
 *
 * Here we locate the already-registered Composer loader and register the same
 * prefixes so the app shell and tab controllers can be unit tested.
 */

$ksfCrmLoader = require_once dirname(__DIR__) . '/vendor/autoload.php';
if (!$ksfCrmLoader instanceof \Composer\Autoload\ClassLoader) {
    foreach (spl_autoload_functions() as $fn) {
        if (is_array($fn) && isset($fn[0]) && $fn[0] instanceof \Composer\Autoload\ClassLoader) {
            $ksfCrmLoader = $fn[0];
            break;
        }
    }
}

if ($ksfCrmLoader instanceof \Composer\Autoload\ClassLoader) {
    $ksfFaCommon = dirname(__DIR__) . '/vendor/ksfraser/ksf-fa-common/src';
    if (is_dir($ksfFaCommon)) {
        $ksfCrmLoader->addPsr4('ksfraser\\FrontAccounting\\Common\\', $ksfFaCommon);
        $ksfCrmLoader->addPsr4('Ksfraser\\Frontaccounting\\HTML\\', $ksfFaCommon . '/HTML');
    }
}

require_once __DIR__ . '/stubs.php';
