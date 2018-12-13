<?php
namespace App\Controllers;

use Psr\Container\ContainerInterface;

abstract class Controller
{
    /**
     * The container instance.
     *
     * @var \Psr\Container\ContainerInterface
     */
    protected $c;

    /**
     * Set up controllers to have access to the container.
     *
     * @param \Psr\Container\ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->c = $container;
    }
}
