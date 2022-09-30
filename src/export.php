<?php
/**
 * @psalm-type ProjectName = "dev-tools" | "core" | "cli" | "environment" | "examples" | "mysql" | "mysql-dbms" | "optional" | "queue" | "raspberrypi" | "starter" | "store" | "web" | "cui" | "spa" | "web-starter" | "svelte-starter"
 */

use function Amp\call;
use function Amp\File\{createDirectoryRecursively, exists, isFile};
use Amp\Promise;
use function CatPaw\{copyDirectoryRecursively, copyFile, deleteDirectoryRecursively};

/**
 * @param  string      $root
 * @param  ProjectName $project
 * @param  array       $items
 * @return Promise
 */
function exportProjectItems(string $root, mixed $project, array $items):Promise {
    return call(function() use ($root, $project, $items) {
        foreach ($items as $item) {
            $source      = "$root/catpaw-dev-tools/$item";
            $destination = "$root/catpaw-$project/$item";
            if (!yield exists($source)) {
                echo "Skipping source file \"$source\" because it doesn't exist.".PHP_EOL;
                continue;
            }
            if (yield isFile($source)) {
                $ddirname = dirname($destination);
                if (!yield exists($ddirname)) {
                    yield createDirectoryRecursively($ddirname);
                }
                yield copyFile($source, $destination);
                continue;
            }
            if (yield exists($destination)) {
                yield deleteDirectoryRecursively($destination);
            }
            yield copyDirectoryRecursively($source, $destination);
        }
    });
}


/**
 * 
 * @return Promise<void>
 */
function export():Promise {
    return call(function() {
        /** @var array */
        $projects = $_ENV['projects'] ?? [];
        chdir(dirname(__FILE__));
        $root = realpath('../../');

        foreach ($projects as $name => $props) {
            if ($_ENV['name'] ?? '' === $name) {
                // skip self
                continue;
            }
            $exports = $props['exports'] ?? $_ENV['exports'] ?? [];
            exportProjectItems($root, $name, $exports);
        }
    });
}
