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
            'cd:ef:hi:kl:m:rs:t:u',
            [
                'crawl:',
                'delay:',
                'external',
                'follow:',
                'help',
                'index:',
                'keep',
                'level:',
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