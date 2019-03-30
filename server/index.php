<?php
/**
 * Golk
 * @version 0.1.0
 */

require_once __DIR__ . '/vendor/autoload.php';

use Medoo\Medoo;

$config = [
    'settings' => [
        'disableNewApiKey' => true,
        'host' => 'http://localhost'
    ]
];

$app = new Slim\App($config);

$container = $app->getContainer();

$container['database'] = function () {
    return new Medoo([
        'database_type' => 'sqlite',
        'database_file' => '/run/db/db.db',
        'logging' => true
    ]);
};

// CORS
$app->options('/{routes:.+}', function ($request, $response, $args) {
    return $response;
});

$app->add(function ($req, $res, $next) {
    $response = $next($req, $res);
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
});

/**
 * GET getApiKey
 * Summary: Create and get a new API key
 * Notes: 
 * Output-Formats: [application/json]
 */
$app->GET('/v1/apiKey', function($request, $response, $args) {
    if ($this->get('settings')['disableNewApiKey'] === true) {
        return $response->withStatus(403);
    }
    $this->database->insert('api_key', [
        'api_key' => bin2hex(random_bytes(16))
    ]);
    $lastId = $this->database->id();
    $record = $this->database->get('api_key', ['id', 'api_key'], ['id' => $lastId]);
    return $response->withJSON([
        'id' => $record['id'],
        'apiKey' => $record['api_key']
    ]);
});

/**
 * DELETE deleteLink
 * Summary: Deletes a link
 * Notes: 
 * Output-Formats: [application/json]
 */
$app->DELETE('/v1/link', function($request, $response, $args) {
    $body = $request->getParsedBody();

    $destination = $this->database->get(
        'link',
        ['[>]api_key' =>['api_key_id' => 'id']],
        ['api_key_id', 'code', 'destination'],
        ['api_key.api_key' => $body['apiKey'],
         'link.code' => $body['code'],
         'link.destination' => $body['destination']]
    );

    if ($destination === NULL) {
        return $response->withStatus(404);
    }

    $data = $this->database->delete(
        'link',
        [
            'AND' => [
                'code' => $destination['code'],
                'destination' => $destination['destination'],
                'api_key_id' => $destination['api_key_id']
            ]
        ]
    );

    if ($data->rowCount() === 0) {
        return $response->withStatus(404);
    }

    return $response->withJSON([
        'code' => $destination['code'],
        'destination' => $destination['destination'],
        'apiKey' => $body['apiKey'],
    ]);
});

/**
 * GET getFind
 * Summary: Find where a short code is pointing to
 * Notes: 

 */
$app->GET('/v1/find', function($request, $response, $args) {
    $queryParams = $request->getQueryParams();
    $apiKey = $queryParams['apiKey'];
    $code = $queryParams['code'];

    $destination = $this->database->get(
        'link',
        ['[>]api_key' =>['api_key_id' => 'id']],
        ['api_key', 'code', 'destination'],
        ['api_key.api_key' => $apiKey,
         'link.code' => $code]
    );

    if ($destination === NULL) {
        return $response->withStatus(404);
    }

    return $response->withJSON(array_merge(
        $destination, [
            'url' => $this->get('settings')['host'].'/v1/?apiKey='.$apiKey.'&code='.$code
        ]
    ));
});


/**
 * GET getTo
 * Summary: Redirect user given a short code
 * Notes: 

 */
$app->GET('/v1/', function($request, $response, $args) {
    $queryParams = $request->getQueryParams();
    $apiKey = $queryParams['apiKey'];
    $code = $queryParams['code'];

    $destination = $this->database->get(
        'link',
        ['[>]api_key' =>['api_key_id' => 'id']],
        'destination',
        ['api_key.api_key' => $apiKey,
         'link.code' => $code]
    );

    if ($destination === NULL) {
        return $response->withStatus(404);
    }

    return $response->withRedirect($destination, 302);
});


/**
 * POST postLink
 * Summary: Creates a URL with a short code
 * Notes: 
 * Output-Formats: [application/json]
 */
$app->POST('/v1/link', function($request, $response, $args) {
    $body = $request->getParsedBody();
    $user = $this->database->get('api_key', ['id', 'api_key'], ['api_key' => $body['apiKey']]);
    if ($user === NULL) {
        return $response->withStatus(404);
    }

    $record = $this->database->get(
        'link',
        ['api_key_id', 'code', 'destination'],
        ['api_key_id' => $user['id'],
         'code' => $body['code'],
         'destination' => $body['destination']]
    );
    if ($record !== NULL) {
        return $response->withStatus(409);
    }

    $this->database->insert('link', [
        'api_key_id' => $user['id'],
        'code' => $body['code'],
        'destination' => $body['destination']
    ]);

    $lastId= $this->database->id();
    $record = $this->database->get(
        'link',
        ['api_key_id', 'code', 'destination'],
        ['api_key_id' => $user['id'],
         'code' => $body['code'],
         'destination' => $body['destination']]
    );

    return $response->withJSON([
        'code' => $record['code'],
        'destination' => $record['destination'],
        'apiKey' => $user['api_key'],
        'url' => $this->get('settings')['host'].'/v1/?apiKey='.$user['api_key'].'&code='.$record['code']
    ]);
});

$app->run();
