<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
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
        $sql = "SELECT * FROM user";
        $users = $connection->fetchAllAssociative($sql);
        return new JsonResponse($users);
    }

    #[Route('/api/user/delete/{id}', name:'app_user_delete')]
    public function user_delete(User $user, Request $request, EntityManagerInterface $em)
    {
        // Vérifier la présence du token dans les cookies
        $token = $request->cookies->get('token');
        
        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
        }
    
        // Si le token est présent, continuer l'opération
        // Supprimer l'utilisateur
        $em->remove($user);
        $em->flush();
    
        return new JsonResponse(['message' => 'User deleted successfully'], 200);
    }
}
