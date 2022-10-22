<?php

use function Amp\call;

use function Amp\File\deleteFile;
use function Amp\File\exists;
use Amp\Promise;

/**
 * 
 * @param  array   $config
 * @return Promise
 */
function build(array $config):Promise {
    return call(function() use ($config) {
        /**
         * @var string $name
         * @var string $entry
         * @var string $libraries
         * @var string $match
         */

        $name      = $config['name']      ?? '';
        $entry     = $config['entry']     ?? '';
        $libraries = $config['libraries'] ?? '';
        $match     = $config['match']     ?? '';

        $name      = str_replace(['"',"\n"], ['\\"',''], $name);
        $entry     = str_replace(['"',"\n"], ['\\"',''], $entry);
        $libraries = str_replace(['"',"\n"], ['\\"',''], $libraries);
    
        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if (!str_starts_with($entry, './')) {
            if (!$isWindows) {
                die("The entry file path must be relative to the project, received: $entry.".PHP_EOL);
            }
            if (!str_starts_with($entry, '.\\')) {
                die("The entry file path must be relative to the project, received: $entry.".PHP_EOL);
            }
        }
    
        foreach (!$libraries?[]:\preg_split('/,|;/', $libraries) as $libraries) {
            if (!str_starts_with($libraries, './')) {
                if (!$isWindows) {
                    die("All library directory paths must be relative to the project, received: $libraries.".PHP_EOL);
                }
                if (str_starts_with($libraries, '.\\')) {
                    continue;
                }
            }
        }

        $app = 'dist/app.phar';

        try {
            if (yield exists($app)) {
                yield deleteFile($app);
            }

            if (yield exists($app.'.gz')) {
                yield deleteFile($app.'.gz');
            }
        
            $phar = new Phar($app);

            $phar->startBuffering();

            $phar->buildFromDirectory('.', $match);

            $phar->setStub(
                "#!/usr/bin/env php \n".$phar->createDefaultStub($entry)
            );

            $phar->stopBuffering();

            $phar->compressFiles(Phar::GZ);

            # Make the file executable
            chmod($app, 0770);

            echo "$app successfully created".PHP_EOL;
        } catch (Exception $e) {
            die(((string)$e).PHP_EOL);
        }
    });
}