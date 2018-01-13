<?php

use Doctrine\ORM\DeprecatedClassesRegistry;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

require_once __DIR__ . '/DeprecatedClassesRegistry.php';
$registry = new DeprecatedClassesRegistry;
$registry->registerClasses([
    Doctrine\ORM\Mapping\ClassMetadataInfo::class => [ClassMetadata::class, ['2.7', '3.0']],
]);

spl_autoload_register(function ($class) use ($registry) : void {
    $registry->autoload($class);
});
