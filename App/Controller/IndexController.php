<?php
namespace App\Controller;

use Swoolefy\Core\Application;
use Swoolefy\Core\Controller\BController;

class IndexController extends BController {
    public function index() {
        Application::getApp()->swooleResponse->write('<h1>Hello, Welcome to Swoolefy Framework! <h1>');
    }
}