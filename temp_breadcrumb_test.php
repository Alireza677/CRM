<?php
require 'vendor/autoload.php';
require 'app/Services/BreadcrumbService.php';
$service = new App\Services\BreadcrumbService;
$ref = new ReflectionClass($service);
$method = $ref->getMethod('formatTitle');
$method->setAccessible(true);
echo $method->invoke($service, 'documents');
