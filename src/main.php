<?php
use function Amp\File\exists;
use function Amp\File\write;
use Amp\Promise;

use CatPaw\Attributes\Option;

use CatPaw\Environment\Attributes\Environment;

/**
 * 
 * @param bool $sync
 * @param bool $export
 * @param bool $deleteAllTags
 * @return Generator<
 *  int, 
 *  Promise<void>, 
 *  mixed, 
 *  mixed
 * >
 */
#[Environment('product.yml', 'product.yaml', 'resources/product.yml')]
function main(
    #[Option("--sync")] bool $sync,
    #[Option("--export")] bool $export,
    #[Option("--build-config")] bool $buildConfig,
    #[Option("--build")] false|string $build,
    #[Option("--delete-all-tags")] bool $deleteAllTags,
) {
    if ($buildConfig) {
        echo 'Trying to generate build.yml file...'.PHP_EOL;
        if (!yield exists('build.yml')) {
            yield write('build.yml', <<<YAML
                name: app
                entry: ./src/main.php
                libraries: ./src/lib
                match: /^\.\/(src|vendor|resources|dist|bin)\/.*/
                YAML);
        } else {
            echo 'A build.yml file already exists - will not overwrite.'.PHP_EOL;
        }
    }

    if (false !== $build) {
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