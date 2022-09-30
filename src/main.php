<?php

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
    #[Option("--delete-all-tags")] bool $deleteAllTags,
) {
    return match (true) {
        $sync          => yield sync(),
        $export        => yield export(),
        $deleteAllTags => yield deleteAllTags(),
        default        => 0
    };
}