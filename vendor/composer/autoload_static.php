<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit6135b296261c1c1347ac3eb6c9ab67b1
{
    public static $prefixesPsr0 = array (
        'x' => 
        array (
            'xrstf\\Composer52' => 
            array (
                0 => __DIR__ . '/..' . '/xrstf/composer-php52/lib',
            ),
        ),
        't' => 
        array (
            'tad_DI52_' => 
            array (
                0 => __DIR__ . '/..' . '/lucatume/di52/src',
            ),
        ),
        'g' => 
        array (
            'gattiny_' => 
            array (
                0 => __DIR__ . '/../..' . '/src',
            ),
        ),
    );

    public static $classMap = array (
        'gattiny_AdminScripts' => __DIR__ . '/../..' . '/src/gattiny/AdminScripts.php',
        'gattiny_GifEditor' => __DIR__ . '/../..' . '/src/gattiny/GifEditor.php',
        'gattiny_ImageEditors' => __DIR__ . '/../..' . '/src/gattiny/ImageEditors.php',
        'gattiny_ImageSize' => __DIR__ . '/../..' . '/src/gattiny/ImageSize.php',
        'gattiny_ImageSizes' => __DIR__ . '/../..' . '/src/gattiny/ImageSizes.php',
        'gattiny_MediaScripts' => __DIR__ . '/../..' . '/src/gattiny/MediaScripts.php',
        'gattiny_PluginsScreen' => __DIR__ . '/../..' . '/src/gattiny/PluginsScreen.php',
        'gattiny_Settings' => __DIR__ . '/../..' . '/src/gattiny/Settings.php',
        'gattiny_SettingsPage' => __DIR__ . '/../..' . '/src/gattiny/SettingsPage.php',
        'gattiny_System' => __DIR__ . '/../..' . '/src/gattiny/System.php',
        'gattiny_Utils_Templates' => __DIR__ . '/../..' . '/src/gattiny/Utils/Templates.php',
        'tad_DI52_Container' => __DIR__ . '/..' . '/lucatume/di52/src/tad/DI52/Container.php',
        'tad_DI52_ContainerInterface' => __DIR__ . '/..' . '/lucatume/di52/src/tad/DI52/ContainerInterface.php',
        'tad_DI52_ProtectedValue' => __DIR__ . '/..' . '/lucatume/di52/src/tad/DI52/ProtectedValue.php',
        'tad_DI52_ServiceProvider' => __DIR__ . '/..' . '/lucatume/di52/src/tad/DI52/ServiceProvider.php',
        'tad_DI52_ServiceProviderInterface' => __DIR__ . '/..' . '/lucatume/di52/src/tad/DI52/ServiceProviderInterface.php',
        'xrstf\\Composer52\\AutoloadGenerator' => __DIR__ . '/..' . '/xrstf/composer-php52/lib/xrstf/Composer52/AutoloadGenerator.php',
        'xrstf\\Composer52\\Generator' => __DIR__ . '/..' . '/xrstf/composer-php52/lib/xrstf/Composer52/Generator.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit6135b296261c1c1347ac3eb6c9ab67b1::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit6135b296261c1c1347ac3eb6c9ab67b1::$classMap;

        }, null, ClassLoader::class);
    }
}
