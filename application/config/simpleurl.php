<?php
$config = array(
    'base_url' => 'https://jcodeigniter.jteamstudio.com',
    'db_connection' => new PDO('mysql:dbname=EXPERIMENT;host=127.0.0.1;port=3307', 'root', 'Jt2020te`s~<'),
    'chars'   => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789',
    'salt'    => 'testsalt',
    'padding' => 3,
);