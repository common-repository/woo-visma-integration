<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInita3f44f0d6cf347d3f333fd9d8245e828
{
    public static $prefixesPsr0 = array (
        'M' => 
        array (
            'Mustache' => 
            array (
                0 => __DIR__ . '/..' . '/mustache/mustache/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInita3f44f0d6cf347d3f333fd9d8245e828::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
