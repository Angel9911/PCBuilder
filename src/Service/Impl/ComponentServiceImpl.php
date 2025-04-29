<?php

namespace App\Service\Impl;

use App\Entity\Component;
use App\Repository\ComponentRepository;
use App\Service\ComponentService;

class ComponentServiceImpl implements ComponentService
{
    private ComponentRepository $componentRepository;

    /**
     * @param ComponentRepository $componentRepository
     */
    public function __construct(ComponentRepository $componentRepository)
    {
        $this->componentRepository = $componentRepository;
    }


    public function getAllComponents(): array
    {
        return $this->componentRepository->findAllComponents();
    }

    public function getComponentByName(string $name): Component
    {
        // TODO: Implement getComponentByName() method.
    }

    public function getComponentsByType(string $type): array
    {
        return $this->componentRepository->findComponentsByType($type);
    }

    public function getCompatibleComponents(array $filterParams): array
    {
        try{

            //$compatibleParts = $this->componentRepository->findCompatibleComponents($filterParams);

            $compatibleParts = $this->componentRepository->getCompatibleParts($filterParams);

            return $compatibleParts;

        }catch (\Exception $exception){
            return [];
        }
    }
}