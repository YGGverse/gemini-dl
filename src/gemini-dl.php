#!/usr/bin/env php

<?php

// Load dependencies
require_once __DIR__ .
             DIRECTORY_SEPARATOR . '..'.
             DIRECTORY_SEPARATOR . 'vendor' .
             DIRECTORY_SEPARATOR . 'autoload.php';

use \Yggverse\GeminiDL\Controller\Cli;

try
{
    // Start application
    $cli = new Cli(
        getopt(
            'acd:f:hi:k:m:rs:t:u',
            [
                'absolute:',
                'crawl:',
                'delay:',
                'follow:',
                'help',
                'index:',
                'keep',
                'match:',
                'raw',
                'source:',
                'target:',
                'unique'
            ]
        )
    );

    $cli->start();
}

// Something went wrong
catch (\Exception $data)
{
    Cli::exception(
        $data->getMessage(),
        file_get_contents(
            __DIR__ .
            DIRECTORY_SEPARATOR . '..' .
            DIRECTORY_SEPARATOR . 'help.gmi'
        )
    );
}