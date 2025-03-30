<?php

namespace App\Entity;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: "compatibility")]
class Compatibility
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Component::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Component $component1;

    #[ORM\ManyToOne(targetEntity: Component::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private Component $component2;

    // Getters and Setters
}