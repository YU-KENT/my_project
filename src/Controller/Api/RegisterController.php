<?php

namespace App\Controller\Api;

use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(ValidatorInterface $validator, SerializerInterface $serializer, Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser()) {
            return new JsonResponse($serializer->serialize(['messages' => 'you must logout to get register page'], 'json'), Response::HTTP_UNAUTHORIZED, [], true);
        }

        $newUser = $serializer->deserialize($request->getContent(), User::class, 'json');

        $error = $validator->validate($newUser);

        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }


        $user = $this->getUser();
        // Hash the password for the new user
        $plainPassword = $newUser->getPassword();
        $hashedPassword = $userPasswordHasher->hashPassword($newUser, $plainPassword);
        $newUser->setPassword($hashedPassword);

        // Save the new user to the database
        $entityManager->persist($newUser);
        $entityManager->flush();

        // do anything else you need here, like send an email


        return new JsonResponse($serializer->serialize(['message' => 'your account has been created'], 'json'), Response::HTTP_OK, ['Content-Type' => 'application/json'], true);


    }
}
