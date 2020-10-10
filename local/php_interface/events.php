<?php

use Local\Seo\Clearizer;
use Local\Seo\CMainHandlers;
use Local\Seo\KostylRedirector;
use Local\Seo\SchemaOrg;
use Local\Util\Handler404;

// Чтобы Битрикс не рубил 404-й запросы через Symfony Router
AddEventHandler(
    'main',
    'OnEpilog',
    [
        new Handler404(),
        'apiHandler',
    ]
);

// LastModified
AddEventHandler(
    'main',
    'OnEpilog',
    [CMainHandlers::class, 'checkIfModifiedSince']
);

if (empty($_SESSION['PHPUNIT_RUNNING'])) {
    $redirector = new KostylRedirector();
    // Редирект костыльный.
    AddEventHandler(
        'main',
        'OnProlog',
        [$redirector, 'treatment']
    );
}

// Удаление HTML комментариев по рекомендации граждан из SEO.
AddEventHandler(
    'main',
    'OnEndBufferContent',
    [Clearizer::class, 'clearHtmlComments']
);

// itemprop для description.
AddEventHandler(
    'main',
    'OnEndBufferContent',
    [SchemaOrg::class, 'descriptionItemprop']
);
