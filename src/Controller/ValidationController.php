<?php

namespace App\Controller;

use App\Entity\Evaluation;
use App\Repository\EvaluationRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ValidationController extends AbstractController
{
    #[Route('/api/validation', name: 'app_validation')]
public function index(Request $request, Connection $connection): JsonResponse
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
    $userId = isset($data['userId']) ? (int)$data['userId'] : 0;

    // Corrected SQL query with WHERE clause
    $sql = "SELECT evaluation.id as ID, alignment.*, evaluation.* 
            FROM evaluation
            JOIN alignment ON alignment.id = evaluation.alignment_id
            WHERE evaluation.id > ? AND (evaluation.validate = 0 OR evaluation.validateur_id = ? OR evaluation.validate IS NULL ) 
            ORDER BY evaluation.id 
            LIMIT 10";

    $sqlCount = "SELECT count(*) 
                 FROM evaluation
                 WHERE evaluation.validate = 0 OR evaluation.validateur_id = ? OR evaluation.validate IS NULL ";
    
    try {
        $results = $connection->fetchAllAssociative($sql, [$lastId,$userId]);
        
        if (empty($results)) {
            return new JsonResponse(['message' => 'No data found'], 200);
        }

        // Fix the column name 'ID' instead of 'evaluation_id'
        $evaluationIds = array_column($results, 'ID'); // Récupère tous les evaluation_id
        $maxEvaluationId = !empty($evaluationIds) ? max($evaluationIds) : null; // Récupère le maximum

        // Fetch count
        $count = $connection->fetchOne($sqlCount, [$userId]);

        // Return the max evaluation id as lastId instead of the input lastId
        return new JsonResponse(['count' => $count, 'results' => $results, 'lastId' => $maxEvaluationId]);

    } catch (\Exception $e) {
        // Log the error for debugging
        error_log('SQL Error: ' . $e->getMessage());
        return new JsonResponse(['error' => $e->getMessage()], 500);
    }
}


    #[Route('/api/validation/{id}', name: 'app_validation_validate')]
    public function validate(Evaluation $evaluation,Request $request, EntityManagerInterface $em): JsonResponse
    {
        // Accéder au cookie
        $token = $request->cookies->get('token');
        error_log('Received token: ' . $token);
        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
        }
        $content = $request->getContent();
        $data = json_decode($content,true);
        $validateur_id= $data['validateur_id'];
        $validate= $data['validate'];
        error_log('validateur_id: ' . $validateur_id);
        error_log('validate: ' . $validate);
        
        if (!$evaluation) {
            return new JsonResponse(['error' => 'Evaluation not found'], 404);
        }
        try {

            $evaluation->setValidate($validate);
            if ($validate === 0 || $validate === false) {
                $evaluation->setValidateurId(null);  // Remettre validateur_id à null
            } else {
                $evaluation->setValidateurId($validateur_id);
            }

            // $evaluation->setValidate($validate);
            // $evaluation->setValidateurId($validateur_id);
            $em->persist($evaluation);
            $em->flush();
            return new JsonResponse(['message'=>'La validation a été mis à jour']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
