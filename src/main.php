<?php
use function Amp\File\exists;
use function Amp\File\isDirectory;
use function Amp\File\isFile;
use function Amp\File\write;
use CatPaw\Attributes\Option;
use CatPaw\Environment\Attributes\Environment;
use Monolog\Logger;

/**
 * 
 * @param  bool         $sync
 * @param  bool         $export
 * @param  bool         $buildConfig
 * @param  false|string $build
 * @param  bool         $deleteAllTags
 * @param  string       $executeEverywhere
 * @param  string       $executeEverywhereParallel
 * @param  string       $transform
 * @param  string       $generator
 * @throws Error
 * @return Generator
 */
#[Environment('product.yml', 'product.yaml', 'resources/product.yml', 'resources/product.yaml')]
function main(
    Logger $logger,
    #[Option("--sync")] bool $sync,
    #[Option("--export")] bool $export,
    #[Option("--build-config")] bool $buildConfig,
    #[Option("--build")] false|string $build,
    #[Option("--delete-all-tags")] bool $deleteAllTags,
    #[Option("--execute-everywhere")] string $executeEverywhere,
    #[Option("--execute-everywhere-parallel")] string $executeEverywhereParallel,
    #[Option("--sql-transform")] string $transform,
    #[Option("--sql-transform-generator")] string $generator = './generator.php',
) {
    if ($executeEverywhere) {
        yield executeEverywhere($executeEverywhere);
    }

    if ($executeEverywhereParallel) {
        yield executeEverywhereParallel($executeEverywhereParallel);
    }

    if ($transform) {
        $fileNames = [];
        foreach (explode(',', $transform) as $fileName) {
            if (yield isDirectory($fileName)) {
                /** @var array */
                $fileNames = [
                    ...$fileNames,
                    ...(yield \CatPaw\listFilesRecursively($fileName)),
                ];
            } else if (yield isFile($fileName)) {
                $fileNames = [
                    ...$fileNames,
                    $fileName,
                ];
            } else {
                $logger->warning("Could not find file $fileName.");
            }
        }
        yield SQLTransform($generator, $fileNames);
    }

    if ($buildConfig) {
        echo 'Trying to generate build.yml file...';
        if (!yield exists('build.yml')) {
            yield write('build.yml', <<<YAML
                name: app
                entry: ./src/main.php
                libraries: ./src/lib
                match: /^\.\/(\.build-cache|src|vendor|resources|bin)\/.*/
                YAML);
            
            echo 'done!'.PHP_EOL;
        } else {
            echo 'A build.yml file already exists - will not overwrite.'.PHP_EOL;
        }
    }

    if (false !== $build) {
        if (ini_get('phar.readonly')) {
            die('Cannot build using readonly phar, please disable the phar.readonly flag by running the builder with "php -dphar.readonly=0"'.PHP_EOL);
        }
        yield build($build?$build:'build.yml,build.yaml');
    }

    if ($export) {
        yield export();
    }

    if ($deleteAllTags) {
        yield deleteAllTags();
    }

    if ($sync) {
        yield sync();
    }
}