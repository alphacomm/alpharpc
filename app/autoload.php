<?php
// Autoloading
call_user_func(function() {
    $autoload_files = array(
        // Main project AlphaRPC clone.
        __DIR__.'/../vendor/autoload.php',

        // Required in composer.json (within vendor folder).
        __DIR__.'/../../../../vendor/autoload.php',
    );

    foreach ($autoload_files as $autoload_file) {
        if (file_exists($autoload_file)) {
            require_once $autoload_file;

            return;
        }
    }

    throw new \RuntimeException('Unable to locate autoload.php');
});
