<?php
/**
 * @psalm-type ProjectName = "dev-tools" | "core" | "cli" | "environment" | "examples" | "mysql" | "mysql-dbms" | "optional" | "queue" | "raspberrypi" | "starter" | "store" | "web" | "cui" | "spa" | "web-starter" | "svelte-starter"
 */

use function Amp\call;
use Amp\Promise;
use function CatPaw\execute;

/**
 * 
 * @return Promise<void>
 */
function deleteAllTags():Promise {
    return call(function() {
        /** @var array<ProjectName> */
        $projects = $_ENV['projects'] ?? [];

        chdir(dirname(__FILE__));
        $root = realpath('../../');

        $cwd = "$root/catpaw-dev-tools";
        echo "Deleting tags of project catpaw-dev-tools".PHP_EOL;

        #Delete local tags.
        echo yield execute("git tag -l | xargs git tag -d", $cwd);
        #Fetch remote tags.
        echo yield execute("git fetch", $cwd);
        #Delete remote tags.
        echo yield execute("git tag -l | xargs git push --delete origin", $cwd);
        #Delete local tags.
        echo yield execute("git tag -l | xargs git tag -d", $cwd);

        foreach ($projects as $project => $_) {
            echo "Tagging project catpaw-$project".PHP_EOL;
            $cwd = "$root/catpaw-$project";
            
            // work in parallel on each project to speed things up
            call(function() use ($cwd) {
                #Delete local tags.
                echo yield execute("git tag -l | xargs git tag -d", $cwd);
                #Fetch remote tags.
                echo yield execute("git fetch", $cwd);
                #Delete remote tags.
                echo yield execute("git tag -l | xargs git push --delete origin", $cwd);
                #Delete local tags.
                echo yield execute("git tag -l | xargs git tag -d", $cwd);
            });
        }
    });
}
