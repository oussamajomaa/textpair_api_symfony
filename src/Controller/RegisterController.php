<?php
// src/Controller/RegisterController.php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class RegisterController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator
    ): JsonResponse {
        // Récupérer le token depuis les cookies
        $token = $request->cookies->get('token');

        // Log the token to see if it's being received (optional)
        error_log('Received token: ' . ($token ?: 'No token received'));

        // Vérifier si le token est fourni
        if (!$token) {
            return new JsonResponse(['message' => 'Token not provided'], 401);
        }

        // Vérification simple du token
        try {
            $secretKey = $_ENV['JWT_SECRET']; // Remplacez par votre clé secrète utilisée pour signer le token
            $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
            error_log('Token successfully decoded: ' . print_r($decodedToken, true));
        } catch (\Exception $e) {
            error_log('Error decoding token: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Invalid token'], 401);
        }

        // Si le token est valide, continuer avec l'enregistrement
        $data = json_decode($request->getContent(), true);
        // $username = $data['username'] ?? '';
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'Annotateur';  // Rôle par défaut

        // Validation des données
        if (empty($email) || empty($password)) {
            return new JsonResponse(['message' => 'Email and password are required'], 400);
        }

        // Vérification si l'utilisateur existe déjà
        if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
            return new JsonResponse(['message' => 'Adresse mail existe déjà'], 409);
        }

        // Création du nouvel utilisateur
        $user = new User();
        // $user->setUsername($username);
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));
        $user->setRole($role);

        // Validation de l'entité User
        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return new JsonResponse(['errors' => $errorMessages], 400);
        }

        // Sauvegarde de l'utilisateur dans la base de données
        $em->persist($user);
        $em->flush();

        return new JsonResponse(['message' => 'User registered successfully'], 201);
    }
}
