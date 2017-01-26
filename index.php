<?php
require 'vendor/autoload.php';
require 'config/database.php';

$app = new \Slim\Slim();

$app->db = function() {
	global $capsule;
	return $capsule;
};

include 'routes.php';

$app->run();