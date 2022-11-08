<?php
use function Amp\call;
use function Amp\File\exists;
use function Amp\File\read;
use function Amp\File\write;
use Amp\Promise;

use function Amp\Promise\all;

use function CatPaw\execute;

/**
 * @return Promise<void>
 */
function sync():Promise {
    return call(function() {
        if (yield exists("./.product.cache")) {
            $cache = yaml_parse(yield read("./.product.cache"));
        } else {
            $cache = [];
        }

        /** @var array */
        $projects  = $_ENV['projects'] ?? [];
        $root      = realpath('.');
        $libraries = [];
        $versions  = [];
        $promises  = [];
        
        foreach ($projects as $projectName => $projectProperties) {
            $library            = $projectProperties['library'] ?? $projectName;
            $versionString      = preg_replace('/"/', '\\"', $projectProperties['version']);
            $versionPieces      = explode('.', $versionString);
            $version            = join('.', [$versionPieces[0] ?? '0',$versionPieces[1] ?? '0']);
            $message            = preg_replace('/"/', '\\"', $projectProperties['message'] ?? "Version $versionString");
            $libraries[]        = $library;
            $versions[$library] = $version;
        }

        foreach ($projects as $projectName => $projectProperties) {
            echo "Tagging project $projectName".PHP_EOL;

            $library       = $projectProperties['library'] ?? $projectName;
            $versionString = preg_replace('/"/', '\\"', $projectProperties['version']);

            if (($cache["projects"][$projectName]["version"] ?? '') === $versionString) {
                continue;
            }

            $versionPieces = explode('.', $versionString);
            $version       = join('.', [$versionPieces[0] ?? '0',$versionPieces[1] ?? '0']);
            $message       = preg_replace('/"/', '\\"', $projectProperties['message'] ?? "Version $versionString");

            $cwd             = "$root/$projectName";
            $composeFileName = "$cwd/composer.json";
            $composer        = json_decode(yield read($composeFileName));

            if (isset($composer->require)) {
                foreach ($composer->require as $composerLibrary => &$composerVersion) {
                    if (in_array($composerLibrary, $libraries)) {
                        $composerVersion = '^'.$versions[$composerLibrary];
                    }
                }
    
                yield write($composeFileName, trim(json_encode($composer, JSON_PRETTY_PRINT)));
    
                yield write($composeFileName, trim(str_replace('\/', '/', yield read($composeFileName))));
            }

            $cache["projects"][$projectName]["version"] = $versionString;

            /**
             * @psalm-suppress MissingClosureReturnType
             */
            $promises[] = function() use ($cwd, $message, $versionString) {
                echo yield execute("composer fix", $cwd);
                echo yield execute("rm composer.lock", $cwd);
                echo yield execute("git fetch", $cwd);
                echo yield execute("git pull", $cwd);
                echo yield execute("git add .", $cwd);
                echo yield execute("git commit -m\"$message\"", $cwd);
                echo yield execute("git push", $cwd);
                echo yield execute("git tag -a \"$versionString\" -m\"$message\"", $cwd);
                echo yield execute("git push --tags", $cwd);
            };
        }

        $joins = [];

        foreach ($promises as $promise) {
            $joins[] = call($promise);
        }

        yield all($joins);

        foreach ($projects as $projectName => $projectProperties) {
            $versionString = preg_replace('/"/', '\\"', $projectProperties['version']);
            $message       = preg_replace('/"/', '\\"', $projectProperties['message'] ?? "Version $versionString");

            echo "Updating project $projectName".PHP_EOL;

            $cwd = "$root/$projectName";


            call(function() use ($cwd) {
                yield execute("composer update", $cwd);
            });
        }
        
        $cacheStringified = yaml_emit($cache, YAML_UTF8_ENCODING);
        yield write(".product.cache", $cacheStringified);
    });
}