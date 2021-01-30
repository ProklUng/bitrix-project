<?php

return [
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    Local\Bundles\CustomArgumentResolverBundle\CustomArgumentResolverBundle::class => ['all' => true],
    Local\Bundles\ApiExceptionBundle\M6WebApiExceptionBundle::class => ['all' => true],
    // Local\Bundles\ApiDtoConvertorBundle\ApiDtoConvertorBundle::class => ['all' => true],
    Local\Bundles\GuzzleBundle\CsaGuzzleBundle::class => ['all' => true],
    Local\Bundles\SymfonyMiddlewareBundle\MiddlewareBundle::class => ['all' => true],
    Local\Bundles\TaskSchedulerBundle\RewieerTaskSchedulerBundle::class => ['all' => true],
    Symfony\Bundle\MakerBundle\MakerBundle::class => ['all' => true],
    Local\Bundles\BundleMakerBundle\BundleMakerBundle::class => ['all' => true],
    Local\Bundles\StaticPageMakerBundle\StaticPageMakerBundle::class => ['all' => true],
    Local\Bundles\ModelBundle\ModelBundle::class => ['all' => true],
];
