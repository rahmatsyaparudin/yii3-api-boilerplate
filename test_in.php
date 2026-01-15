<?php
require_once 'vendor/autoload.php';

echo 'Class In found: ' . (class_exists('Yiisoft\Validator\Rule\In') ? 'YES' : 'NO') . PHP_EOL;
echo 'In namespace: ' . (new ReflectionClass('Yiisoft\Rule\In')->getName()) . PHP_EOL;
