<?php
// src/Controller/AuthController.php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Firebase\JWT\JWT;

class AuthController extends AbstractController
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        // Récupération de l'utilisateur par email
        $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

        // Si l'utilisateur n'existe pas ou que le mot de passe est incorrect
        if (!$user || !$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['error' => 'identifiant ou mot de passe incorrect'], 401);
        }

        // Génération d'un JWT sécurisé
        $payload = [
            'sub' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
            'iat' => time(), // issued at
            // 'exp' => time() + (2 * 60 * 60) // expiration (2 heures)
        ];

        $jwt = JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');

        $response = new JsonResponse([
            'token' => $jwt,
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'role' => $user->getRole(),
        ]);

        // Ajouter le token au cookie
        $response->headers->setCookie(
            new \Symfony\Component\HttpFoundation\Cookie(
                'token',    // nom du cookie
                $jwt,       // valeur du cookie
                0,          // expiration (2 heures)
                '/',        // chemin
                null,       // domaine
                false,      // secure (mettre à true en production si HTTPS)
                true,       // httpOnly
                false,      // raw
                'Strict'    // same-site
            )
        );

        return $response;
    }

    #[Route('/api/logout', name: 'logout', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $response = new JsonResponse('ok');
        $response->headers->clearCookie('token');
        return $response;
    }

}
