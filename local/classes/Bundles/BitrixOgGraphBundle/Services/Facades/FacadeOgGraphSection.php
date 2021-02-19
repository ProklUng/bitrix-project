<?php

namespace Local\Bundles\BitrixOgGraphBundle\Services\Facades;

use Local\Bundles\BitrixOgGraphBundle\Services\InjectGraph;
use Local\Bundles\BitrixOgGraphBundle\Services\OgDTO;
use Local\Bundles\BitrixOgGraphBundle\Services\SectionsProcessor;

/**
 * Class FacadeOgGraphSection
 * @package Local\Bundles\BitrixOgGraphBundle\Services\Facades
 *
 * @since 19.02.2021
 */
class FacadeOgGraphSection
{
    /**
     * @var SectionsProcessor $sectionProcessor
     */
    private $sectionProcessor;

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
     * @param SectionsProcessor $sectionProcessor
     * @param InjectGraph       $injectGraph
     * @param OgDTO             $ogDTO            DTO.
     */
    public function __construct(
        SectionsProcessor $sectionProcessor,
        InjectGraph $injectGraph,
        OgDTO $ogDTO
    ) {
        $this->sectionProcessor = $sectionProcessor;
        $this->injector = $injectGraph;
        $this->ogDTO = $ogDTO;
    }

    /**
     * @param integer $iblockId  ID инфоблока.
     * @param integer $idSection ID подраздела.
     *
     * @return void
     */
    public function make(int $iblockId, int $idSection): void
    {
        $data = $this->sectionProcessor->setIblockId($iblockId)
                                      ->setIdSection($idSection)
                                      ->go();

        $this->ogDTO->update($data);

        $this->injector->inject($this->ogDTO);
    }
}
