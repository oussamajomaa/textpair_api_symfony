<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

    #[Route('/api/user/delete/{id}', name:'app_user_delete')]
    public function user_delete(User $user, Request $request, EntityManagerInterface $em, Connection $connection)
    {
        // Vérifier la présence du token dans les cookies
        $token = $request->cookies->get('token');
        
        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
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
}
