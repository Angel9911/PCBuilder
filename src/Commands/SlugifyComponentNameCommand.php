<?php

declare(strict_types=1);

namespace App\Commands;

use App\Service\ComponentService;
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

                $slugifyComponent = $this->slugify($component);

                $this->componentService->updateComponentName($component, $slugifyComponent);
            }
        }

        return Command::SUCCESS;
    }
    function slugify(string $text): string
    {
        // Replace special characters with ASCII equivalents
        $text = iconv('UTF-8', 'ASCII//TRANSLIT', $text);

        // Replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // Remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // Trim and lowercase
        $text = strtolower(trim($text, '-'));

        return $text;
    }
}
