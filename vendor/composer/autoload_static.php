<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit4adb87ec95b7d8f0be87e97526a59249
{
    public static $prefixLengthsPsr4 = array (
        'M' => 
        array (
            'MessageBird\\' => 12,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'MessageBird\\' => 
        array (
            0 => __DIR__ . '/..' . '/messagebird/php-rest-api/src/MessageBird',
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit4adb87ec95b7d8f0be87e97526a59249::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit4adb87ec95b7d8f0be87e97526a59249::$prefixDirsPsr4;

        }, null, ClassLoader::class);
    }
}
