<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'pc_configuration_components')]
class PCConfigComponent
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: CompletedConfiguration::class/*, inversedBy: 'components'*/)]
    #[ORM\JoinColumn(name: 'configuration_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private ?CompletedConfiguration $configuration;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Component::class/*, inversedBy: 'components'*/)]
    #[ORM\JoinColumn(name: 'component_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Component $components;

    public function __construct(CompletedConfiguration $configuration = null, Component $component= null)
    {
        $this->configuration = $configuration;
        $this->components = $component;
    }

    // Getters and setters...

    public function getConfiguration(): CompletedConfiguration
    {
        return $this->configuration;
    }

    public function setConfiguration(?CompletedConfiguration $configuration = null): self
    {
        $this->configuration = $configuration;
        return $this;
    }

    public function getComponent(): Component
    {
        return $this->components;
    }

    public function setComponent(Component $component): self
    {
        $this->components = $component;
        return $this;
    }
}