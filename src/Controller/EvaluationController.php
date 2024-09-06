<?php

namespace App\Controller;

use App\Entity\Evaluation;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Doctrine\DBAL\Connection;

class EvaluationController extends AbstractController
{
    #[Route('/api/evaluate', name: 'api_evaluate', methods: ['POST'])]
    public function evaluate(Request $request, EntityManagerInterface $em): JsonResponse
    {

        // Récupérer le token depuis les cookies
        $token = $request->cookies->get('token');
        if (!$token) {
            error_log('Token not provided');
            return new JsonResponse(['message' => 'Token not provided'], 401);
        }

        // Décoder le token JWT
        try {
            $secretKey = $_ENV['JWT_SECRET'];
            if (!$secretKey) {
                throw new \Exception('JWT Secret Key not set');
            }
            $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
            error_log('Token successfully decoded');
        } catch (\Exception $e) {
            error_log('Error decoding token: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Invalid token'], 401);
        }

        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);
        error_log('Request data: ' . print_r($data, true));

        $userId = $data['user_id'] ?? null;
        $alignmentId = $data['alignement_id'] ?? null;
        $comment = $data['comment'] ?? null;
        $evaluate = $data['evaluate'] ?? null;

        if (!$userId || !$alignmentId) {
            error_log('Missing required parameters');
            return new JsonResponse(['message' => 'Missing required parameters'], 400);
        }

        try {
            // Vérifier si une évaluation existe déjà pour cet utilisateur et cet alignement
            $existingEvaluation = $em->getRepository(Evaluation::class)->findOneBy([
                'user_id' => $userId,
                'alignment_id' => $alignmentId
            ]);

            if ($existingEvaluation) {
                error_log('Updating existing evaluation');
                // Mettre à jour l'évaluation existante
                $existingEvaluation->setEvaluate($evaluate);
                $existingEvaluation->setComment($comment);
                $em->persist($existingEvaluation);
                $em->flush();
                return new JsonResponse(['message' => 'Evaluation updated'], 200);
            } else {
                error_log('Creating new evaluation');
                // Créer une nouvelle évaluation
                $evaluation = new Evaluation();
                $evaluation->setUserId($userId);
                $evaluation->setAlignmentId($alignmentId);
                $evaluation->setComment($comment);
                $evaluation->setEvaluate($evaluate);
                $em->persist($evaluation);
                $em->flush();
                return new JsonResponse(['message' => 'Evaluation created'], 200);
            }
        } catch (\Exception $e) {
            error_log('Error processing evaluation: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Internal Server Error'], 500);
        }
    }

    #[Route('/api/evaluation', name: 'api_evaluation')]
    public function evaluation(Request $request, Connection $connection): JsonResponse
    {
        // Accéder au cookie
        $token = $request->cookies->get('token');
        error_log('Received token: ' . $token);
        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
        }

        $content = $request->getContent();
        error_log('Received content: ' . $content);

        // Décoder le JSON en tableau PHP
        $data = json_decode($content, true);
        $lastId = isset($data['lastId']) ? (int)$data['lastId'] : 0;

        // Décoder le token pour obtenir l'utilisateur connecté
        try {
            $secretKey = $_ENV['JWT_SECRET'];
            error_log('JWT Secret: ' . $secretKey);
            $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
            error_log('Decoded Token: ' . print_r($decodedToken, true));

            $userId = $decodedToken->sub ?? null; // Assurez-vous que le token contient un champ 'sub'
            if (!$userId) {
                return new JsonResponse(['error' => 'Invalid user ID'], 401);
            }

            $sql = "SELECT alignment.id as ID, evaluation.id as evaluation_id, alignment.*, evaluation.* 
                FROM alignment 
                JOIN evaluation ON evaluation.alignment_id = alignment.id
                WHERE evaluation.id > ? AND evaluation.user_id = ? order by evaluation.id LIMIT 10 ";

            $sqlCount = "SELECT COUNT(*) as total  
                     FROM alignment 
                     JOIN evaluation ON evaluation.alignment_id = alignment.id
                     WHERE evaluation.user_id = ?";

            try {
                error_log('Executing SQL: ' . $sql);
                $results = $connection->fetchAllAssociative($sql, [$lastId, $userId]);
                $countTotal = $connection->fetchOne($sqlCount, [$userId]);
                $count = (int) $countTotal;

                if (empty($results)) {
                    return new JsonResponse(['message' => 'No data found'], 200);
                }

                // Après avoir récupéré les résultats dans la variable $results
                $evaluationIds = array_column($results, 'evaluation_id'); // Récupère tous les evaluation_id
                $maxEvaluationId = !empty($evaluationIds) ? max($evaluationIds) : null; // Récupère le maximum
                return new JsonResponse(
                    [
                        'results' => $results,
                        'count' => $count,
                        'lastId' => $maxEvaluationId
                    ]
                );
            } catch (\Exception $e) {
                error_log('SQL Error: ' . $e->getMessage());
                return new JsonResponse(['error' => $e->getMessage()], 500);
            }
        } catch (\Exception $e) {
            error_log('Token Error: ' . $e->getMessage());
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }
    }


    #[Route('/api/evaluation/{id}', name: 'api_evaluation_delete')]
    public function evaluation_delete(Evaluation $evaluation, Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Accéder au cookie
        $token = $request->cookies->get('token');
        error_log('Received token: ' . $token);
        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
        }

        // Décoder le token pour obtenir l'utilisateur connecté
        try {
            $secretKey = $_ENV['JWT_SECRET'];
            $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
            $userId = $decodedToken->sub; // Assurez-vous que le token contient un champ 'sub' avec l'ID utilisateur

            if (!$userId) {
                return new JsonResponse(['error' => 'Invalid user ID'], 401);
            }
            // Supprimer l'évaluation
            $em->remove($evaluation);
            $em->flush(); // Assurez-vous de bien exécuter flush pour appliquer les modifications
            return new JsonResponse(['message' => 'Evaluation deleted successfully'], 200);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }
    }

    #[Route('/api/comment', name: 'app_comment')]
    public function comment(EntityManagerInterface $em, Request $request): JsonResponse
    {
        // Récupérer le token depuis les cookies
        $token = $request->cookies->get('token');
        if (!$token) {
            error_log('Token not provided');
            return new JsonResponse(['message' => 'Token not provided'], 401);
        }

        // Décoder le token JWT
        try {
            $secretKey = $_ENV['JWT_SECRET'];
            if (!$secretKey) {
                throw new \Exception('JWT Secret Key not set');
            }
            $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
            error_log('Token successfully decoded');
        } catch (\Exception $e) {
            error_log('Error decoding token: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Invalid token'], 401);
        }

        // Récupérer les données de la requête
        $data = json_decode($request->getContent(), true);
        error_log('Received data: ' . print_r($data, true)); // Log the incoming data

        $userId = $data['user_id'] ?? null;
        $alignmentId = $data['alignment_id'] ?? null;
        $comment = $data['comment'] ?? null;

        if (!$userId || !$alignmentId) {
            error_log('Missing required parameters: user_id or alignment_id');
            return new JsonResponse(['message' => 'Missing required parameters'], 400);
        }

        try {
            // Vérifier si une évaluation existe déjà pour cet utilisateur et cet alignement
            $existingEvaluation = $em->getRepository(Evaluation::class)->findOneBy([
                'user_id' => $userId,
                'alignment_id' => $alignmentId
            ]);
    
            if ($existingEvaluation) {
                // Vérifier si l'évaluation a déjà été faite avant de permettre d'ajouter un commentaire
                if (empty($existingEvaluation->getEvaluate())) {
                    // Si le champ 'evaluate' est vide, cela signifie que l'évaluation n'a pas encore été faite
                    return new JsonResponse(['message' => 'Evaluation must be done before adding a comment'], 400);
                }
    
                // Mettre à jour l'évaluation existante avec le commentaire
                $existingEvaluation->setComment($comment);
                $em->persist($existingEvaluation);
                $em->flush();
    
                return new JsonResponse(['message' => 'Comment added to existing evaluation'], 200);
            } else {
                // Si l'évaluation n'existe pas, renvoyer une erreur car il faut une évaluation avant d'ajouter un commentaire
                return new JsonResponse(['message' => 'Evaluation not found. Please evaluate before adding a comment'], 400);
            }
        } catch (\Exception $e) {
            error_log('Error processing evaluation: ' . $e->getMessage());
            return new JsonResponse(['message' => 'Internal Server Error'], 500);
        }
    }
}
