<?php

use Symfony\Component\ClassLoader\UniversalClassLoader;
use Doctrine\Common\Annotations\AnnotationRegistry;

define('ROOTDIR', __DIR__.'/../../../..');
define('LIBDIR', __DIR__);

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony'          => array(ROOTDIR.'/vendor/symfony/src', ROOTDIR.'/vendor/bundles'),
 //   'Sensio'           => ROOTDIR.'/vendor/bundles',
    'RedpillLinpro'    => ROOTDIR.'/vendor/bundles',
    'Doctrine\\Common' => ROOTDIR.'/vendor/doctrine-common/lib',
    'Doctrine\\DBAL'   => ROOTDIR.'/vendor/doctrine-dbal/lib',
    'Doctrine'         => ROOTDIR.'/vendor/doctrine/lib',
    'al13_debug'       => ROOTDIR.'/vendor',
));
// intl
if (!function_exists('intl_get_error_code')) {
    require_once ROOTDIR.'/vendor/symfony/src/Symfony/Component/Locale/Resources/stubs/functions.php';

    $loader->registerPrefixFallbacks(array(ROOTDIR.'/vendor/symfony/src/Symfony/Component/Locale/Resources/stubs'));
}

$loader->register();

AnnotationRegistry::registerLoader(function($class) use ($loader) {
    $loader->loadClass($class);
    return class_exists($class, false);
});
AnnotationRegistry::registerFile(ROOTDIR.'/vendor/doctrine/lib/Doctrine/ORM/Mapping/Driver/DoctrineAnnotations.php');

require_once ROOTDIR.'/vendor/Vgid/Client.php';

require_once ROOTDIR.'/vendor/al13_debug/config/bootstrap.php';

dbc('Doctrine\Common\Annotations\AnnotationReader');
dbc('ReflectionClass');
dbp('_entitymanager');
dbp('_gamineservice');
