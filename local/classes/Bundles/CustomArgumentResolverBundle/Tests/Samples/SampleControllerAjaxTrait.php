<?php
/** @noinspection PhpUnusedParameterInspection */

namespace Local\Bundles\CustomArgumentResolverBundle\Tests\Samples;

use Local\Bundles\CustomArgumentResolverBundle\Event\Traits\ValidatorTraits\SecurityAjaxCallTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SampleControllerAjaxTrait
 * @package Local\Bundles\CustomArgumentResolverBundle\Tests\Samples
 * @codeCoverageIgnore
 *
 * @since 10.09.2020
 */
class SampleControllerAjaxTrait extends AbstractController
{
    use SecurityAjaxCallTrait;

    public function action(Request $request)
    {
        return new Response('OK');
    }
}
