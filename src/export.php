#!/usr/bin/env php
<?php
/**
 * @psalm-type ProjectName = "dev-tools" | "core" | "cli" | "environment" | "examples" | "mysql" | "mysql-dbms" | "optional" | "queue" | "raspberrypi" | "starter" | "store" | "web" | "cui" | "spa" | "web-starter" | "svelte-starter"
 */

use function Amp\call;
use function Amp\File\{createDirectoryRecursively, exists, isFile};
use Amp\Promise;
use CatPaw\Environment\Attributes\EnvironmentFile;
use function CatPaw\{copyDirectoryRecursively, copyFile, deleteDirectoryRecursively};

/**
 * @param  string      $root
 * @param  ProjectName $project
 * @param  array       $items
 * @return Promise
 */
function export(string $root, mixed $project, array $items):Promise {
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
 * @param  array<ProjectName,array{version:string,message:string}> $projects
 * @throws Error
 * @return void
 */
#[EnvironmentFile('options.yml')]
function main() {
    /** @var array */
    $projects = $_ENV['projects'] ?? [];
    chdir(dirname(__FILE__));
    $root = realpath('../../');

    foreach ($projects as $project => $options) {
        if ("dev-tools" === $project) {
            // skip self
            continue;
        }
        export($root, $project, match ($project) {
            "svelte-starter" => ['bin','.github','.php-cs-fixer.php','psalm.xml','build.yml'],
            default          => ['bin','.vscode','.github','.php-cs-fixer.php','psalm.xml','build.yml'],
        });
    }
}
