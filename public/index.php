<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

session_start();

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория, в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);
$router = $app->getRouteCollector()->getRouteParser();
$users = json_decode(file_get_contents("/home/igor/hexlet-slim-example/users.json"), true);

$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    return $response->write("Course id: {$id}");
});

$app->get('/users/new', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');
    $name = $user['name'];
    $email = $user['email'];
    $user['id'] = random_int(10000, 1000000);

    $params = ['user' => $user];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

$app->get('/users/{id}', function ($request, $response, $args) use ($users) {

    $id = $args['id'];

    $user = array_filter($users, fn($item) => (int) $item['id'] === (int) $id);

    if (!$user) {
        return $response->write('Page not found')
            ->withStatus(404);
    }

    $params = ['id' => $args['id'], 'user' => $user["{$id}"]];

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);

})->setName('user');

$app->get('/users', function ($request, $response) use ($users) {
    $input = $request->getQueryParam('input');
    $filteredUsers = array_filter($users, fn($user) => str_contains($user['name'], $input));
    $params = ['users' => $filteredUsers, 'input' => $input];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->post('/users', function ($request, $response) use ($router, $users) {
    $user = $request->getParsedBodyParam('user');

    if ($user['name'] = '' || $user['email']) {
        $errors = 'Все поля должны быть заполнены';
    }

    if (!$errors) {
        $params = ['user' => $user, 'errors' => $errors];
        $response = $response->withStatus(422);
        return $this->get('renderer')->render($response, 'users/new.phtml', $params);
    }

    $id = array_key_last($users) + 1;
    $user['id'] = $id;
    $users["{$id}"] = $user;

    file_put_contents("/home/igor/hexlet-slim-example/users.json",json_encode($users));

    $this->get('flash')->addMessage('success', 'User successfully created');

    $url = $router->urlFor('users');
    $messages = $this->get('flash')->getMessages();
    $params = ['users' => $users, 'flash' => $messages];

    return $this->get('renderer')->render($response, "users/index.phtml", $params);
});


$app->run();
