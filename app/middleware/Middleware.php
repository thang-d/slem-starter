<?php
namespace App\Middleware;

class Middleware
{
    protected $c;

    public function __construct($container)
    {
        $this->c = $container;
    }
}