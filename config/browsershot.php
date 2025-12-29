<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Chrome Binary Path
    |--------------------------------------------------------------------------
    |
    | The path to the Chrome binary that Browsershot will use.
    | On Windows, Puppeteer installs Chrome in the user's cache folder.
    |
    */
    'chrome_path' => env('BROWSERSHOT_CHROME_PATH', null),

    /*
    |--------------------------------------------------------------------------
    | Node Modules Path
    |--------------------------------------------------------------------------
    |
    | The path to the node_modules directory where Puppeteer is installed.
    |
    */
    'node_modules_path' => base_path('node_modules'),
];
