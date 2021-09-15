<?php
require __DIR__ . '/../vendor/autoload.php';

honwei189\Flayer\Config::load();
honwei189\Flayer\Data::$_user = "abc";

$app = new honwei189\Flayer\Core;
$crypto = $app->bind("honwei189\\crypto");

echo $crypto->decrypt($crypto->encrypt("Test"));

echo "------------------<br>";

echo honwei189\Flayer\Crypto::d(honwei189\Flayer\Crypto::e("Test"));

echo "------------------<br>";

pre(honwei189\Flayer\Data::all());
