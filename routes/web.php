<?php

use App\Controllers\HomeController;
use App\Controllers\GroupController;

$app->get('/', HomeController::class . ':index')->setName('home.index');

$app->group('/group', GroupController::class .':groupRoute');