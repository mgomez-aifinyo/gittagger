<?php

try {
    // Create a new Phar object
    $phar = new Phar('gittagger.phar', 0, 'gittagger.phar');

    // Add files to the archive
    $phar->buildFromDirectory(__DIR__ . '/');


    // Set the default stub file
    $phar->setDefaultStub('App/run.php', 'App/run.php');

    // Save the archive
    $phar->stopBuffering();
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

echo "Phar archive created successfully.\n";
