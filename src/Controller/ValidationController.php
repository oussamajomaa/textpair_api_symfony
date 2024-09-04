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
        $sql = "SELECT evaluation.id as ID, alignment.*, evaluation.* from evaluation
                JOIN alignment on alignment.id = evaluation.alignment_id";
        $sqlCount = "SELECT count(*) from evaluation
        JOIN alignment on alignment.id = evaluation.alignment_id";
        try {
            $results = $connection->fetchAllAssociative($sql);
            $count = $connection->fetchOne($sqlCount);
            return new JsonResponse(['count'=>$count,'results'=>$results]);
        } catch (\Exception $e) {
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

        
        try {
            $evaluation->setValidate($validate);
            $evaluation->setValidateurId($validateur_id);
            $em->persist($evaluation);
            $em->flush();
            return new JsonResponse(['message'=>'La validation a été mis à jour']);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
