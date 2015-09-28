<?php
namespace Mouf\Database\MagicQuery\Twig;

use Doctrine\DBAL\Connection;

/**
 * Class in charge of creating the Twig environment
 */
class SqlTwigEnvironmentFactory
{
    public static function getTwigEnvironment(Connection $connection = null) {
        $stringLoader = new StringLoader();

        $options = array(
            // The cache directory is in the temporary directory and reproduces the path to the directory (to avoid cache conflict between apps).
            'cache' => self::getCacheDirectory(),
            'strict_variables' => true
        );

        $twig = new \Twig_Environment($stringLoader, $options);

        if ($connection !== null) {

            $twig->getExtension('core')->setEscaper('sql', function(\Twig_Environment $env, $string, $charset) use ($connection) {
                var_dump($string);
                // ARGH! Escapers seems to be not called if $string is empty!
                if ($string === null) {
                    return "null";
                } else {
                    return $connection->quote($string);
                }
            });

            // SQL identifier (table or column names....)
            $twig->getExtension('core')->setEscaper('sqli', function(\Twig_Environment $env, $string, $charset) use ($connection) {
                return $connection->quoteIdentifier($string);
            });

        } else {
            $twig->getExtension('core')->setEscaper('sql', function(\Twig_Environment $env, $string, $charset) use ($connection) {
                if ($string === null) {
                    return "null";
                } else {
                    return "'".addslashes($string)."'";
                }
            });

            $twig->getExtension('core')->setEscaper('sqli', function(\Twig_Environment $env, $string, $charset) use ($connection) {
                // Note: we don't know how to escape backticks in a column name. In order to avoid injection,
                // we remove any backticks.
                return "`".str_replace('`', '', $string)."`";
            });
        }

        // Default autoescape mode: sql
        $twig->addExtension(new \Twig_Extension_Escaper('sql'));

        return $twig;
    }

    private static function getCacheDirectory() {
        // If we are running on a Unix environment, let's prepend the cache with the user id of the PHP process.
        // This way, we can avoid rights conflicts.
        if (function_exists('posix_geteuid')) {
            $posixGetuid = '_'.posix_geteuid();
        } else {
            $posixGetuid = '';
        }
        $cacheDirectory = rtrim(sys_get_temp_dir(), '/\\').'/magicquerysqltwigtemplate'.$posixGetuid.str_replace(":", "", __DIR__);

        return $cacheDirectory;
    }
}
