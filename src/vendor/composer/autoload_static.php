<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitccbeb81e6f5d35d385a2970070aabd75
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'Slim\\Middleware\\' => 16,
        ),
        'P' => 
        array (
            'Psr\\Log\\' => 8,
        ),
        'M' => 
        array (
            'Monolog\\' => 8,
        ),
        'F' => 
        array (
            'Firebase\\JWT\\' => 13,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Slim\\Middleware\\' => 
        array (
            0 => __DIR__ . '/..' . '/tuupola/slim-jwt-auth/src',
        ),
        'Psr\\Log\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/log/Psr/Log',
        ),
        'Monolog\\' => 
        array (
            0 => __DIR__ . '/..' . '/monolog/monolog/src/Monolog',
        ),
        'Firebase\\JWT\\' => 
        array (
            0 => __DIR__ . '/..' . '/firebase/php-jwt/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'S' => 
        array (
            'Slim' => 
            array (
                0 => __DIR__ . '/..' . '/slim/slim',
            ),
        ),
    );

    public static $classMap = array (
        'EasyPeasyICS' => __DIR__ . '/..' . '/phpmailer/phpmailer/extras/EasyPeasyICS.php',
        'PHPMailer' => __DIR__ . '/..' . '/phpmailer/phpmailer/class.phpmailer.php',
        'PHPMailerOAuth' => __DIR__ . '/..' . '/phpmailer/phpmailer/class.phpmaileroauth.php',
        'PHPMailerOAuthGoogle' => __DIR__ . '/..' . '/phpmailer/phpmailer/class.phpmaileroauthgoogle.php',
        'POP3' => __DIR__ . '/..' . '/phpmailer/phpmailer/class.pop3.php',
        'SMTP' => __DIR__ . '/..' . '/phpmailer/phpmailer/class.smtp.php',
        'ntlm_sasl_client_class' => __DIR__ . '/..' . '/phpmailer/phpmailer/extras/ntlm_sasl_client.php',
        'phpmailerException' => __DIR__ . '/..' . '/phpmailer/phpmailer/class.phpmailer.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitccbeb81e6f5d35d385a2970070aabd75::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitccbeb81e6f5d35d385a2970070aabd75::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitccbeb81e6f5d35d385a2970070aabd75::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitccbeb81e6f5d35d385a2970070aabd75::$classMap;

        }, null, ClassLoader::class);
    }
}
