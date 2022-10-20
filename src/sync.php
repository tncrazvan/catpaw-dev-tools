<?php
use function Amp\call;
use function Amp\File\{read, write};
use Amp\Promise;
use function CatPaw\execute;

/**
 * @return Promise<void>
 */
function sync():Promise {
    return call(function() {
        /** @var array */
        $projects = $_ENV['projects'] ?? [];
        /** @var string */
        $prefix = $_ENV['prefix'] ?? '';
        chdir(dirname(__FILE__));
        $root = realpath('../../');

        foreach ($projects as $name => $props) {
            $library       = $props['library'] ?? $name;
            $version       = preg_replace('/"/', '\\"', $props['version']);
            $versionPieces = explode('.', $version);
            $mversion      = join('.', [$versionPieces[0] ?? '0',$versionPieces[1] ?? '0']);
            $message       = preg_replace('/"/', '\\"', $props['message'] ?? "Version $version");
            echo "Tagging project $prefix-$name".PHP_EOL;
            $cwd = "$root/$prefix-$name";

            $composeFileName = "$cwd/composer.json";
            $composer        = json_decode(yield read($composeFileName), true);

            foreach ($composer['require'] as $rname => &$rversion) {
                // if (str_starts_with($rname, "$prefix/")) {
                //     $rversion = "^$mversion";
                // }
                if ($rname === $library) {
                    $rversion = "^$mversion";
                }
            }

            yield write($composeFileName, json_encode($composer, JSON_PRETTY_PRINT));

            yield write($composeFileName, str_replace('\/', '/', yield read($composeFileName)));

            call(function() use ($version, $message, $cwd) {
                echo yield execute("composer fix", $cwd);
                echo yield execute("rm composer.lock", $cwd);
                echo yield execute("git fetch", $cwd);
                echo yield execute("git pull", $cwd);
                echo yield execute("git add .", $cwd);
                echo yield execute("git commit -m\"$message\"", $cwd);
                echo yield execute("git push", $cwd);
                echo yield execute("git tag -a \"$version\" -m\"$message\"", $cwd);
                echo yield execute("git push --tags", $cwd);
            });
        }


        foreach ($projects as $name => $props) {
            $version = preg_replace('/"/', '\\"', $props['version']);
            $message = preg_replace('/"/', '\\"', $props['message'] ?? "Version $version");

            echo "Updating project $prefix-$name".PHP_EOL;

            $cwd = "$root/$prefix-$name";


            call(function() use ($cwd) {
                yield execute("composer update", $cwd);
            });
        }
    });
}
