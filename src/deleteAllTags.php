<?php
use function Amp\call;
use Amp\Promise;
use function CatPaw\execute;

/**
 * @return Promise<void>
 */
function deleteAllTags():Promise {
    return call(function() {
        /** @var array */
        $projects = $_ENV['projects'] ?? [];
        /** @var string */
        $master = $_ENV['master'] ?? '';

        $root = realpath('../');

        $cwd = "$root/$master";
        echo "Deleting tags of project $master".PHP_EOL;

        #Delete local tags.
        echo yield execute("git tag -l | xargs git tag -d", $cwd);
        #Fetch remote tags.
        echo yield execute("git fetch", $cwd);
        #Delete remote tags.
        echo yield execute("git tag -l | xargs git push --delete origin", $cwd);
        #Delete local tags.
        echo yield execute("git tag -l | xargs git tag -d", $cwd);

        foreach ($projects as $projectName => $_) {
            echo "Tagging project $projectName".PHP_EOL;
            $cwd = "$root/$projectName";
            
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
