<?php
require __DIR__ . '/../vendor/autoload.php';

honwei189\Flayer\Config::load();
honwei189\Flayer\Data::$_user = "abc";

$app    = new honwei189\Flayer\Core;
$crypto = $app->bind("honwei189\\Flayer\\Crypto");

echo $crypto->decrypt($crypto->encrypt("Test"));

echo "------------------<br>";

unset($app);
$app = app("Flayer"); // Or use this method instead of $app = new honwei189\Flayer\Core;
$app->bind("honwei189\\Flayer\\Crypto");
echo app("Crypto")->decrypt(app("Crypto")->encrypt("Test"));

echo "------------------<br>";

echo honwei189\Flayer\Crypto::d(honwei189\Flayer\Crypto::e("Test"));

echo "------------------<br>";

pre(honwei189\Flayer\Data::all());

