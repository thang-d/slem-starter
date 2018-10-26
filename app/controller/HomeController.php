<?php
namespace App\Controller;

use App\Interfaces\IController;
use Psr\Http\Message\{
    ServerRequestInterface as Request,
    ResponseInterface as Response
};

class HomeController extends Controller implements IController
{
    public function home(Request $request, Response $response)
    {
        return $response->getBody()->write('Hello');
    }
}
