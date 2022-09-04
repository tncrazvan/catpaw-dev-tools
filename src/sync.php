#!/usr/bin/env php
<?php
/**
 * @psalm-type ProjectName = "dev-tools" | "core" | "cli" | "environment" | "examples" | "mysql" | "mysql-dbms" | "optional" | "queue" | "raspberrypi" | "starter" | "store" | "web" | "cui" | "spa" | "web-starter" | "svelte-starter"
 */

use function Amp\call;
use function Amp\File\read;

use function Amp\File\write;
use CatPaw\Environment\Attributes\EnvironmentFile;
use function CatPaw\execute;


/**
 * @param  array<ProjectName,array{version:string,message:string}> $projects
 * @param  string                                                  $version
 * @param  string                                                  $message
 * @throws Error
 * @return Generator
 */
#[EnvironmentFile('options.yml')]
function main() {
    /** @var array */
    $projects = $_ENV['projects'] ?? [];
    chdir(dirname(__FILE__));
    $root = realpath('../../');

    foreach ($projects as $project => $options) {
        $version       = preg_replace('/"/', '\\"', $options['version']);
        $versionPieces = explode('.', $version);
        $mversion      = join('.', [$versionPieces[0] ?? '0',$versionPieces[1] ?? '0']);
        $message       = preg_replace('/"/', '\\"', $options['message'] ?? "Version $version");
        echo "Tagging project catpaw-$project".PHP_EOL;
        $cwd = "$root/catpaw-$project";

        $composeFileName = "$cwd/composer.json";
        $composer        = json_decode(yield read($composeFileName), true);

        foreach ($composer['require'] as $rname => &$rversion) {
            if (str_starts_with($rname, "catpaw/")) {
                $rversion = $mversion;
            }
        }

        yield write($composeFileName, json_encode($composer, JSON_PRETTY_PRINT));

        yield write($composeFileName, str_replace('\/', '/', yield read($composeFileName)));

        call(function() use ($version, $message, $cwd) {
            echo yield execute("rm composer.lock", $cwd);
            echo yield execute("git add .", $cwd);
            echo yield execute("git commit -m\"$message\"", $cwd);
            echo yield execute("git push", $cwd);
            echo yield execute("git tag -a \"$version\" -m\"$message\"", $cwd);
            echo yield execute("git push --tags", $cwd);
        });
    }


    foreach ($projects as $project => $options) {
        $version = preg_replace('/"/', '\\"', $options['version']);
        $message = preg_replace('/"/', '\\"', $options['message'] ?? "Version $version");

        echo "Updating project catpaw-$project".PHP_EOL;

        $cwd = "$root/catpaw-$project";


        call(function() use ($cwd) {
            yield execute("composer update", $cwd);
        });
    }
}
