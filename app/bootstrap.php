<?php
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

require __DIR__.'/autoload.php';

// Dependecy Injection
return call_user_func(function() {
    $container = new ContainerBuilder();
    $loader = new YamlFileLoader($container, new FileLocator(array(
        getcwd(),
        __DIR__.'/../../../..',
        __DIR__.'/config',
    )));
    $loader->load('alpharpc_resources.yml');

    return $container;
});
