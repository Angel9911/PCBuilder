<?php

declare(strict_types=1);

namespace App\Commands;

use App\Service\ComponentService;
use App\utils\SlugifyClass;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:slugify_component_name', description: 'Hello PhpStorm')]
class SlugifyComponentNameCommand extends Command
{
    private ComponentService $componentService;

    public function __construct(ComponentService $componentService)
    {
        parent::__construct();
        $this->componentService = $componentService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $components = $this->componentService->getAllComponents();

        foreach ($components as $componentType) {

            foreach ($componentType as $component) {

                $slugifyComponent = SlugifyClass::slugify($component);

                $this->componentService->updateComponentName($component, $slugifyComponent);
            }
        }

        return Command::SUCCESS;
    }
}
