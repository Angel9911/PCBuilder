<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/user')]
    public function user(): Response
    {
        return $this->render('user/index.html.twig');
    }

    #[Route('/user/login', name: 'user.login', methods: ['POST'])]
    public function login(): Response
    {

    }

    #[Route('/user/register', name: 'user.register', methods: ['POST'])]
    public function register(): Response
    {

    }
}
