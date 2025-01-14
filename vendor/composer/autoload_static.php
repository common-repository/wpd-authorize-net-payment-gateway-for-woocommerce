<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit05b6151f1d657f0626edd856ee9ec6ac
{
    public static $prefixesPsr0 = array (
        'C' => 
        array (
            'Curl' => 
            array (
                0 => __DIR__ . '/..' . '/curl/curl/src',
            ),
        ),
    );

    public static $classMap = array (
        'JohnConde\\Authnet\\AuthnetApiFactory' => __DIR__ . '/..' . '/stymiee/authnetjson/src/authnet/AuthnetApiFactory.php',
        'JohnConde\\Authnet\\AuthnetCannotSetParamsException' => __DIR__ . '/..' . '/stymiee/authnetjson/src/exceptions/AuthnetCannotSetParamsException.php',
        'JohnConde\\Authnet\\AuthnetCurlException' => __DIR__ . '/..' . '/stymiee/authnetjson/src/exceptions/AuthnetCurlException.php',
        'JohnConde\\Authnet\\AuthnetException' => __DIR__ . '/..' . '/stymiee/authnetjson/src/exceptions/AuthnetException.php',
        'JohnConde\\Authnet\\AuthnetInvalidAmountException' => __DIR__ . '/..' . '/stymiee/authnetjson/src/exceptions/AuthnetInvalidAmountException.php',
        'JohnConde\\Authnet\\AuthnetInvalidCredentialsException' => __DIR__ . '/..' . '/stymiee/authnetjson/src/exceptions/AuthnetInvalidCredentialsException.php',
        'JohnConde\\Authnet\\AuthnetInvalidJsonException' => __DIR__ . '/..' . '/stymiee/authnetjson/src/exceptions/AuthnetInvalidJsonException.php',
        'JohnConde\\Authnet\\AuthnetInvalidParameterException' => __DIR__ . '/..' . '/stymiee/authnetjson/src/exceptions/AuthnetInvalidParameterException.php',
        'JohnConde\\Authnet\\AuthnetInvalidServerException' => __DIR__ . '/..' . '/stymiee/authnetjson/src/exceptions/AuthnetInvalidServerException.php',
        'JohnConde\\Authnet\\AuthnetJsonRequest' => __DIR__ . '/..' . '/stymiee/authnetjson/src/authnet/AuthnetJsonRequest.php',
        'JohnConde\\Authnet\\AuthnetJsonResponse' => __DIR__ . '/..' . '/stymiee/authnetjson/src/authnet/AuthnetJsonResponse.php',
        'JohnConde\\Authnet\\AuthnetSim' => __DIR__ . '/..' . '/stymiee/authnetjson/src/authnet/AuthnetSim.php',
        'JohnConde\\Authnet\\AuthnetTransactionResponseCallException' => __DIR__ . '/..' . '/stymiee/authnetjson/src/exceptions/AuthnetTransactionResponseCallException.php',
        'JohnConde\\Authnet\\AuthnetWebhooksRequest' => __DIR__ . '/..' . '/stymiee/authnetjson/src/authnet/AuthnetWebhooksRequest.php',
        'JohnConde\\Authnet\\AuthnetWebhooksResponse' => __DIR__ . '/..' . '/stymiee/authnetjson/src/authnet/AuthnetWebhooksResponse.php',
        'JohnConde\\Authnet\\TransactionResponse' => __DIR__ . '/..' . '/stymiee/authnetjson/src/authnet/TransactionResponse.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit05b6151f1d657f0626edd856ee9ec6ac::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit05b6151f1d657f0626edd856ee9ec6ac::$classMap;

        }, null, ClassLoader::class);
    }
}
