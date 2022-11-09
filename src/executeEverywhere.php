<?php
use function Amp\call;
use Amp\Promise;
use function CatPaw\execute;

/**
 * 
 * @param  string        $command
 * @return Promise<void>
 */
function executeEverywhere(string $command):Promise {
    return call(function() use ($command) {
        /** @var array */
        $projects = $_ENV['projects'] ?? [];
        /** @var string */
        $master = $_ENV['master'] ?? '';

        $cwd = "$master";

        $output = yield execute($command, $cwd);
        echo "Executing \"$command\" in $master (master)".PHP_EOL;
        echo $output.PHP_EOL;

        foreach ($projects as $projectName => $_) {
            $cwd    = "$projectName";
            $output = yield execute($command, $cwd);
            echo "Executing \"$command\" in $projectName".PHP_EOL;
            echo $output.PHP_EOL;
        }
    });
}
