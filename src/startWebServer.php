<?php

use function Amp\call;

use CatPaw\Utilities\Container;
use CatPaw\Web\WebServer;
use Psr\Log\LoggerInterface;

function startWebServer() {
    return call(function() {
        /** @var LoggerInterface */
        $logger = yield Container::create(LoggerInterface::class);
        $logger->info("Starting web server...");
        yield WebServer::start(
            interfaces: $_ENV["web-server"]['interfaces']                     ?? [],
            secureInterfaces: $_ENV["web-server"]['secureInterfaces']         ?? [],
            routesFromFileSystem: $_ENV["web-server"]['routesFromFileSystem'] ?? ['./resources/routes/'],
            webRoot: $_ENV["web-server"]['webRoot']                           ?? ['./resources/www/'],
            showStackTrace: $_ENV['showStackTrace']                           ?? false,
            showExceptions: $_ENV['showExceptions']                           ?? false,
            redirectToSecure: $_ENV['redirectToSecure']                       ?? false,
            pemCertificates: $_ENV['pemCertificates']                         ?? [],
            headers: $_ENV['headers']                                         ?? [],
        );
    });
}