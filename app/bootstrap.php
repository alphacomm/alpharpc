<?php
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

require __DIR__.'/autoload.php';

// Dependency Injection
return call_user_func(function() {
    $container = new ContainerBuilder();

    $paths = array(
        getcwd(),
        __DIR__.'/../../../..',
        __DIR__.'/config',
    );

    $loader = new YamlFileLoader($container, new FileLocator($paths));
    $loader->load('alpharpc_resources.yml');

    return $container;
});