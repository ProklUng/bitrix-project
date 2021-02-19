<?php

namespace Local\Bundles\BitrixOgGraphBundle\Services\Facades;

use Local\Bundles\BitrixOgGraphBundle\Services\DetailPageProcessor;
use Local\Bundles\BitrixOgGraphBundle\Services\InjectGraph;
use Local\Bundles\BitrixOgGraphBundle\Services\OgDTO;

/**
 * Class FacadeOgGraphDetailPage
 * @package Local\Bundles\BitrixOgGraphBundle\Services\Facades
 *
 * @since 19.02.2021
 */
class FacadeOgGraphDetailPage
{
    /**
     * @var DetailPageProcessor $detailProcessor
     */
    private $detailProcessor;

    /**
     * @var OgDTO $ogDTO DTO.
     */
    private $ogDTO;

    /**
     * @var InjectGraph $injector
     */
    private $injector;

    /**
     * FacadeOgGraphDetailPage constructor.
     *
     * @param DetailPageProcessor $detailProcessor
     * @param InjectGraph         $injectGraph
     * @param OgDTO               $ogDTO            DTO.
     */
    public function __construct(
        DetailPageProcessor $detailProcessor,
        InjectGraph $injectGraph,
        OgDTO $ogDTO
    ) {
        $this->detailProcessor = $detailProcessor;
        $this->injector = $injectGraph;
        $this->ogDTO = $ogDTO;
    }

    /**
     * @param integer $iblockId  ID инфоблока.
     * @param integer $idElement ID элемента.
     *
     * @return void
     */
    public function make(int $iblockId, int $idElement): void
    {
        $data = $this->detailProcessor->setIblockId($iblockId)
                                      ->setIdElement($idElement)
                                      ->go();

        $this->ogDTO->update($data);

        $this->injector->inject($this->ogDTO);
    }
}
