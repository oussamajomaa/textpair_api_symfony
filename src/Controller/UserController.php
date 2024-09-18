<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/api/user', name: 'app_user')]
    public function index(Connection $connection, Request $request): Response
    {
        // Vérifier la présence du token dans les cookies
        $token = $request->cookies->get('token');

        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
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
        $sql = "SELECT * FROM user order by role";
        $users = $connection->fetchAllAssociative($sql);
        return new JsonResponse($users);
    }

    #[Route('/api/user/{id}', name: 'app_user_one')]
    public function oneUser(int $id, Connection $connection, Request $request): Response
    {
        // Vérifier la présence du token dans les cookies
        $token = $request->cookies->get('token');
    
        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
        }
    
        // Vérification du token JWT
        try {
            $secretKey = $_ENV['JWT_SECRET'];
            $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
            error_log('Token successfully decoded: ' . print_r($decodedToken, true));
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid token'], 401);
        }
    
        // Requête SQL avec paramètre nommé
        $sql = "SELECT * FROM user WHERE id = :id";
        
        // Associer explicitement le paramètre `:id` à `$id`
        $user = $connection->fetchAssociative($sql, ['id' => $id]);
    
        // Si aucun utilisateur n'est trouvé, retourner une erreur 404
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }
    
        return new JsonResponse($user);
    }

    #[Route('/api/user/update/{id}', name: 'app_user_update', methods: ['PUT'])]
    public function update(int $id, UserRepository $repo, Request $request, EntityManagerInterface $em): Response
    {
        // Vérifier la présence du token dans les cookies
        $token = $request->cookies->get('token');
    
        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
        }
    
        // Vérification du token JWT
        try {
            $secretKey = $_ENV['JWT_SECRET'];
            $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
            error_log('Token successfully decoded: ' . print_r($decodedToken, true));
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid token'], 401);
        }

        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);

        $username = $data['usernameUpdate'] ?? null;
        // $email = $data['emailUpdate'] ?? null;
        $role = $data['roleUpdate'] ?? null;
        $user = $repo->findOneBy(['id'=>$id]);

        // Si aucun utilisateur n'est trouvé, retourner une erreur 404
        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // if ($em->getRepository(User::class)->findOneBy(['email' => $email])) {
        //     return new JsonResponse(['message' => 'Adresse mail existe déjà'], 409);
        // }

        $user->setUsername($username);
        // $user->setEmail($email);
        $user->setRole($role);

        $em->persist($user);
        $em->flush();
    
        return new JsonResponse($user);
    }
    

    #[Route('/api/user/delete/{id}', name: 'app_user_delete')]
    public function user_delete(User $user, Request $request, EntityManagerInterface $em, Connection $connection)
    {
        // Vérifier la présence du token dans les cookies
        $token = $request->cookies->get('token');

        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
        }

        // Vérification du token JWT
        try {
            $secretKey = $_ENV['JWT_SECRET'];
            $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
            error_log('Token successfully decoded: ' . print_r($decodedToken, true));
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid token'], 401);
        }

        // ID de l'utilisateur
        $userId = $user->getId();
        // Supprimer les évaluations associées à cet utilisateur en utilisant SQL brut
        $sqlDeleteEvaluations = 'DELETE FROM evaluation WHERE user_id = :userId';
        $sqlUpdateValidations = 'UPDATE evaluation 
                                SET validate = 0, validateur_id = NULL 
                                WHERE validateur_id = :userId';
        $connection->executeStatement($sqlDeleteEvaluations, ['userId' => $userId]);
        $connection->executeStatement($sqlUpdateValidations, ['userId' => $userId]);

        // Supprimer l'utilisateur
        $em->remove($user);
        $em->flush();

        return new JsonResponse(['message' => 'User deleted successfully'], 200);
    }

    #[Route('/api/user/change_pw/{userId}', name: 'app_user_change_pw', methods: ['POST'])]
    public function change_pw(int $userId, UserRepository $userRepository, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, Connection $connection)
    {
        // Vérifier la présence du token dans les cookies
        $token = $request->cookies->get('token');

        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
        }

        // Vérification du token JWT
        try {
            $secretKey = $_ENV['JWT_SECRET']; // Clé secrète pour le JWT
            $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Invalid token'], 401);
        }
        // Récupération de l'utilisateur par son ID
        $user = $userRepository->find($userId);

        if (!$user) {
            return new JsonResponse(['error' => 'User not found'], 404);
        }

        // Récupération des données de la requête (ancien et nouveau mot de passe)
        $data = json_decode($request->getContent(), true);
        $oldPassword = $data['oldPW'] ?? '';
        $newPassword = $data['newPW'] ?? '';

        // Vérification de l'ancien mot de passe
        if (!$passwordHasher->isPasswordValid($user, $oldPassword)) {
            return new JsonResponse(['error' => 'Ancien mot de passe incorrect'], 400);
        }

        // Valider le nouveau mot de passe (longueur, etc.)
        // if (strlen($newPassword) < 8) {
        //     return new JsonResponse(['error' => 'Le nouveau mot de passe doit contenir au moins 8 caractères.'], 400);
        // }

        // Encoder et mettre à jour le nouveau mot de passe
        $encodedPassword = $passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($encodedPassword);

        // Sauvegarder l'utilisateur
        $em->persist($user);
        $em->flush();

        return new JsonResponse(['message' => 'Mot de passe changé avec succès'], 200);
    }
}
