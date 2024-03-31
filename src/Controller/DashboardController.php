<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{

    public function __construct(
        protected UserRepository $_userRepository
    ) {}

    #[Route('/')]
    public function displayDashboard() : Response
    {
        $user = $this->_getUserDashboard();
        return $this->render('dashboard.html.twig', [
            "username" => $user->getUsername()
        ]);
    }

    protected function _getUserDashboard() : User
    {
        return $this->_userRepository->find(1); // todo later won't be forced
    }
}
