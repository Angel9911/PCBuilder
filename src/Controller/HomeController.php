<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home' , methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('pages/home_page.html.twig');
    }
    #[Route('/forum', name: 'forum' , methods: ['GET'])]
    public function forum(): Response
    {
        return $this->render('pages/forum_page.html.twig');
    }
}