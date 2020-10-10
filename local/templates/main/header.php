<?php

use Bitrix\Main\Application;
use Bitrix\Main\Web\Uri;
use Local\Constants;
use Local\Guta\MenuCacher;
use Local\Seo\Canonical;

?>
<!doctype html>
<html lang="<?= LANGUAGE_ID ?>">
<head itemscope itemtype="http://schema.org/WPHeader">
    <title itemprop="headline"><?php $APPLICATION->ShowTitle() ?></title>

    <?php $APPLICATION->ShowHead();

    CJSCore::Init('jquery2');
    CJSCore::Init(['fx']);
    Canonical::show(); ?>

    <meta id="viewport" name="viewport" content="width=device-width,initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet"
          href="//fonts.googleapis.com/css2?family=Lora&family=Montserrat:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= container()->get('guta.assets')->getEntry('main.css') ?>">

</head>
<body class="page page_<?= LANGUAGE_ID ?> page_<?php $APPLICATION->ShowProperty('page_type', 'secondary') ?>">
<div style="display: none;"><?php echo container()->get('icons.svg.load') ?></div>


<!-- wrapper -->
<div class="wrapper">

    <!--подключение twig-->
    <?php
    $twig = container()->get('twig.instance');
    ?>
    <!--/подключение twig-->
