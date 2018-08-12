<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Interfaces\IController;
use Psr\Http\Message\{
    ServerRequestInterface as Request,
    ResponseInterface as Response
};

class GroupController extends Controller implements IController
{
    public function groupRoute($app)
    {
        $app->get('/group1', self::class .':group1');
        $app->get('/group2', self::class .':group2');
    }

    public function group1(Request $request, Response $response)
    {
        return $response->getBody()->write('Hello group 1');
    }

    public function group2(Request $request, Response $response)
    {
        return $response->getBody()->write('Hello group 2');
    }
}
