<?php

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;

require __DIR__ . '/../vendor/autoload.php';

// Настройка сессий
session_start();

// Instantiate app
$app = AppFactory::create();

// структура пользователей
$users = [
    'alice' => [
        'password' => password_hash('pass123', PASSWORD_DEFAULT),
        'email' => 'alice@example.com',
        'balance' => 1500.50
    ],
    'bob' => [
        'password' => password_hash('secret123', PASSWORD_DEFAULT),
        'email' => 'bob@example.com',
        'balance' => 2750.75
    ]
];

$authMw = function (Request $request, RequestHandler $handler) {
    // проверка авторизации пользователя
    if (empty($_SESSION['user'])) {
        $response = new Response();
        return $response
            ->withHeader('Location', '/')
            ->withStatus(302);
    }

    return $handler->handle($request);
};

$app->get('/', function (Request $request, Response $response) {
    $response->getBody()->write(
        '<form action="/login" method="post">
            <input type="text" name="name" placeholder="User Name"><br>
            <input type="password" name="pass" placeholder="Password"><br>
            <button type="submit">Login</button>
        </form>'
    );
    return $response;
});

$app->post('/login', function (Request $request, Response $response) use ($users) {
    $params = $request->getParsedBody();
    $name = $params['name'] ?? null;
    $pass = $params['pass'] ?? null;

    // Проверяем существование пользователя и верифицируем пароль
    if (!isset($users[$name]) || !password_verify($pass, $users[$name]['password'])) {
        $response->getBody()->write('Invalid credentials');
        return $response->withStatus(401);
    }

    // сохранение аутентифицированного пользователя
    $_SESSION['user'] = $name;

    // Регенерация session ID для безопасности
    session_regenerate_id(true);

    return $response
        ->withHeader('Location', '/profile')
        ->withStatus(302);
});

$app->post('/logout', function (Request $request, Response $response) {
    // Очистка сессии
    $_SESSION = [];
    session_destroy();

    return $response
        ->withHeader('Location', '/')
        ->withStatus(302);
});

$app->get('/profile', function (Request $request, Response $response) use ($users) {
    $username = $_SESSION['user'];
    $userData = $users[$username];

    $response->getBody()->write(
        "<h2>Профиль пользователя {$username}</h2>
        <p><strong>Email:</strong> {$userData['email']}</p>
        <p><strong>Баланс:</strong> {$userData['balance']} руб.</p>
        <br>
        <form action=\"/logout\" method=\"post\">
            <button type=\"submit\">Logout</button>
        </form>"
    );
    return $response;
})->add($authMw);

// Add Error Handling Middleware
$app->addErrorMiddleware(true, false, false);

// Run application
$app->run();
