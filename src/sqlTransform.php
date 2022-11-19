<?php
use function Amp\call;
use function Amp\File\deleteFile;
use function Amp\File\exists;
use function Amp\File\read;
use function Amp\File\write;

use Amp\Promise;
use CatPaw\Utilities\Container;
use Psr\Log\LoggerInterface;

const PATTERN_SQL_INJECT = '/\/\*[\s\*]*\s+@inject\s+(option|query|path|param)\s+"(\w+)"\s+into\s+(@\w+)\s+[\s\*]*\*\//i';
const PATTERN_PHP_ARGS   = '/\/\*[\s\*]*\s+@args\s+[\s\*]*\*\//i';
const PATTERN_PHP_QUERY  = '/\/\*[\s\*]*\s+@query\s+[\s\*]*\*\//i';
const PATTERN_PHP_INJECT = '/\/\*[\s\*]*\s+@inject\s+[\s\*]*\*\//i';

/**
 * 
 * @param  string        $generator
 * @param  array         $fileNames
 * @return Promise<void>
 */
function sqlTransform(string $generator, array $fileNames):Promise {
    return call(function() use ($generator, $fileNames) {
        /** @var LoggerInterface */
        $logger            = yield Container::create(LoggerInterface::class);
        $phpCode           = '';
        $realPathGenerator = realpath($generator);
        if (yield exists($generator)) {
            $phpCode = yield read($generator);
        }

        foreach ($fileNames as $fileName) {
            if (realpath($fileName) === $realPathGenerator) {
                $logger->info("Skipping \"$realPathGenerator\" because it's a generator file.");
                continue;
            }
            if (str_ends_with(strtolower($fileName), '.sql')) {
                $query   = yield read($fileName);
                $params  = [];
                $queries = [];
                $inject  = [];
                while (
                    preg_match(PATTERN_SQL_INJECT, $query, $groups)
                    && ($length = count($groups) >= 4)
                ) {
                    [$_,$type,$extern,$intern] = $groups;
                    $type                      = trim($type);
                    $extern                    = trim($extern);
                    $intern                    = trim($intern);
                    $inject[]                  = "\"$extern\" => \$$extern";
                    if ('param' === $type || 'path' === $type) {
                        $params[] = <<<PHP
                            \n\t#[\CatPaw\Web\Attributes\Param] string $$extern
                            PHP;
                        $query = preg_replace(
                            pattern: PATTERN_SQL_INJECT,
                            replacement: <<<SQL
                                set @$intern = :$extern;
                                SQL,
                            subject: $query,
                            limit: 1
                        );
                    } else if ('query' === $type || 'option' === $type) {
                        $queries[] = <<<PHP
                            \n\t#[\CatPaw\Web\Attributes\Query] string $$extern = ''
                            PHP;
                        $query = preg_replace(
                            pattern: PATTERN_SQL_INJECT,
                            replacement: <<<SQL
                                set $intern = :$extern;
                                SQL,
                            subject: $query,
                            limit: 1
                        );
                    }
                }

                $args = join(', ', [...$params,...$queries]);
                if ($args) {
                    $args .= ',';
                }
                $fileName = preg_replace('/.sql/i', '.php', $fileName);

                $query = preg_replace('/\s*use\s+\w+\s*;?/i', '', $query);
                $query = preg_replace('/\n/', "\\n", $query);
                

                $injectStringified = '['.join(',', $inject).']';

                $phpCode = preg_replace(PATTERN_PHP_ARGS, "$args\n", $phpCode);
                $phpCode = preg_replace(PATTERN_PHP_QUERY, $query, $phpCode);
                $phpCode = preg_replace(PATTERN_PHP_INJECT, $injectStringified, $phpCode);

                if (yield exists($fileName)) {
                    yield deleteFile($fileName);
                }
                yield write($fileName, $phpCode);
            }
        }
    });
}