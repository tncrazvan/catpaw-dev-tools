<?php
use function Amp\call;
use Amp\Promise;
use function CatPaw\execute;

/**
 * 
 * @param  string        $executeEverywhere
 * @return Promise<void>
 */
function executeEverywhere(string $command):Promise {
    return call(function() use ($command) {
        /** @var array */
        $projects = $_ENV['projects'] ?? [];
        /** @var string */
        $master = $_ENV['master'] ?? '';

        $cwd = "$master";
        echo "Executing \"$command\" in $master (master)".PHP_EOL;

        echo yield execute($command, $cwd);

        foreach ($projects as $projectName => $_) {
            $cwd = "$projectName";
            echo "Executing \"$command\" in $projectName".PHP_EOL;
            echo yield execute($command, $cwd);
        }
    });
}
