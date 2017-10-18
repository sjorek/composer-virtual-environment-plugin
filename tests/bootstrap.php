<?php

/*
 * This file is part of Composer Virtual Environment Plugin.
 *
 * (c) Stephan Jorek <stephan.jorek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require __DIR__ . '/../vendor/autoload.php';

function start_webserver($docroot = WEBSERVER_DOCROOT)
{
    static $pid;

    $delay = (int) WEBSERVER_DELAY_IN_MILLISECONDS * 1000;

    $kill = function ($pid) use ($delay) {
        echo sprintf('%s - Killing process with ID %d', date('r'), $pid) . PHP_EOL;
        exec('kill ' . $pid . ' >/dev/null 2>&1');

        if (0 < $delay) {
            // let's wait a couple of milliseconds for the server to come down
            usleep($delay);
        }
    };

    if (isset($pid)) {
        $kill($pid);
        unset($pid);
    }

    // Command that starts the built-in web server
    $command = sprintf(
        'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
        WEBSERVER_HOST,
        WEBSERVER_PORT,
        escapeshellarg($docroot)
    );

    // Execute the command and store the process ID
    $output = array();
    exec($command, $output);
    $pid = (int) $output[0];

    echo sprintf(
        '%s - Web server started on %s:%d with PID %d',
        date('r'),
        WEBSERVER_HOST,
        WEBSERVER_PORT,
        $pid
    ) . PHP_EOL;

    $shutdown = function () use ($kill, $pid) {
        $kill($pid);
    };

    // Kill the web server when the process ends
    register_shutdown_function($shutdown);

    if (0 < $delay) {
        // let's wait a couple of milliseconds for the server to come down
        usleep($delay);
    }

    return $shutdown;
}

start_webserver();
