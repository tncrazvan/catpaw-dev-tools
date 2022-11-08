<?php

use Amp\Http\Server\Response;
use Amp\Http\Status;

return function(
	#[\CatPaw\Web\Attributes\Query] string $username = '',
) {
    static $connection = new \PDO(
        'mysql:host=localhost;dbname=Applicazioni',
        'root',
        'root'
    );
    
    $statement = $connection->prepare("\ndeclare @username varchar(255)\n\n\nset @username = :username;\n\n\nselect * from Log;\n");
    if ($statement->execute(["username" => $username])) {
        return new Response(Status::OK, $statement->fetchAll());
    }
    return new Response(Status::INTERNAL_SERVER_ERROR);
};