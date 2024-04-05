<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class LoginController extends AbstractController
{
    #[Route(path: "/api/login", name: "api_login", methods: ["POST"])]
    public function ApiLogin()
    {
        $user = $this->getUser();
        $userData = [
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
        ];

        /* return new JsonResponse(json_encode($userDate, JSON_THROW_ON_ERROR)); */
        return $this->json($userData);
    }
}
