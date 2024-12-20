<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitf2cc7140db136ebed4e4186ec17cd22f
{
    public static $prefixLengthsPsr4 = array (
        'P' => 
        array (
            'Prince\\MyApp\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Prince\\MyApp\\' => 
        array (
            0 => __DIR__ . '/../..' . '/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitf2cc7140db136ebed4e4186ec17cd22f::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitf2cc7140db136ebed4e4186ec17cd22f::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitf2cc7140db136ebed4e4186ec17cd22f::$classMap;

        }, null, ClassLoader::class);
    }
}