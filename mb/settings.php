<?php

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    header('HTTP/1.0 403 Forbidden');
    exit('Access denied');
}

$AUTHOR_NAME = 'evbkv';
$AUTHOR_PASS = '0000';
$lang = ['en', 'en_EN'];
$blogTitle = 'Blog';
$blogDescription = '';
$indexPosts = 5;
$homePage = ['Home', 'https://github.com/evbkv'];
$lastPosts = 5;
