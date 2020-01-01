<?php
require __DIR__ . '/../vendor/autoload.php';

honwei189\config::load();
honwei189\data::$_user = "abc";

$app = new honwei189\flayer;
$crypto = $app->bind("honwei189\\crypto");

echo $crypto->decrypt($crypto->encrypt("Test"));

echo "------------------<br>";

echo crypto::d(crypto::e("Test"));

echo "------------------<br>";

pre(honwei189\data::all());
