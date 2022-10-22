<?php

use function Amp\File\read;
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
    // #[Option("--build")] string $build = '',
    #[Option("--delete-all-tags")] bool $deleteAllTags,
) {
    // if (!$build || !trim($build)) {
    //     $build = <<<YAML
    //         name: App
    //         entry: ./src/main.php
    //         libraries: ./src/lib
    //         match: /^\.\/(src|vendor|resources|dist|bin)\/.*/
    //         YAML;
    // }

    // if (!$buildOptions = yaml_parse(yield read($build))) {
    //     die("Invalid yaml build file.");
    // }



    return match (true) {
        $sync   => yield sync(),
        $export => yield export(),
        // $build         => yield build($buildOptions),
        $deleteAllTags => yield deleteAllTags(),
        default        => 0
    };
}