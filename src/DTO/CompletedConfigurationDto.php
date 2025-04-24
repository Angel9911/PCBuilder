<?php

namespace App\DTO;

class CompletedConfigurationDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 1)]
    protected int $cpu;
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 1)]
    protected int $description;
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 1)]
    protected int $username;
}