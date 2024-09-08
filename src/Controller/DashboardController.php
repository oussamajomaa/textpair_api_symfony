<?php

namespace App\Controller;

use App\Repository\AlignmentRepository;
use App\Repository\EvaluationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Request;

class DashboardController extends AbstractController
{
    #[Route('/api/dashboard', name: 'app_dashboard')]
    public function index(
        Connection $connection,
        EvaluationRepository $evaluationRepo,
        AlignmentRepository $alignmentRepo,
    ): JsonResponse {
        // Get the counts directly from the repository using more efficient count queries
        $evaluated = $evaluationRepo->count([]); // Count all evaluations
        $validatedCount = $evaluationRepo->count(['validate' => 1]); // Count validated evaluations
        $correctCount = $evaluationRepo->count(['evaluate' => 'Correct']); // Count correct evaluations
        $incorrectCount = $evaluationRepo->count(['evaluate' => 'Incorrect']); // Count incorrect evaluations
        $passurCount = $evaluationRepo->count(['evaluate' => 'Pas sûr']); // Count "Pas sûr" evaluations
        $alignment = $alignmentRepo->count([]); // Count all alignments

        $sqlEvaluatedByUser = "SELECT u.username As name, COUNT(*) AS value
                                FROM evaluation e
                                JOIN user u ON e.user_id = u.id
                                WHERE e.evaluate IS NOT NULL
                                GROUP BY e.user_id";
        $evaluatedByUser = $connection->fetchAllAssociative($sqlEvaluatedByUser);

        $sqlAnnotateur = "SELECT * from user WHERE role = 'Annotateur'";
        $sqlValidateur = "SELECT * from user WHERE role = 'Validateur'";



        $annotateur = $connection->fetchAllAssociative($sqlAnnotateur);
        $validateur = $connection->fetchAllAssociative($sqlValidateur);


        // Return the counts as JSON response
        return new JsonResponse([
            'evaluated' => $evaluated,
            'validated' => $validatedCount,
            'correct' => $correctCount,
            'incorrect' => $incorrectCount,
            'notSure' => $passurCount,
            'alignment' => $alignment,
            'evaluatedByUser' => $evaluatedByUser,
            'annotateur' => $annotateur,
            'validateur' => $validateur,

        ]);
    }

    #[Route('api/dashboard/{userId}', name: 'app_dashboard_annotateur')]
    public function annotateur(int $userId, Connection $connection): JsonResponse
    {
        $sqlByUser = "
            SELECT 
            u.username,
                SUM(CASE WHEN e.evaluate = 'Correct' THEN 1 ELSE 0 END) AS correct_count,
                SUM(CASE WHEN e.evaluate = 'Incorrect' THEN 1 ELSE 0 END) AS incorrect_count,
                SUM(CASE WHEN e.evaluate = 'Pas sûr' THEN 1 ELSE 0 END) AS pas_sur_count
            FROM evaluation e
            JOIN user u ON e.user_id = u.id
            WHERE e.user_id = ?
            GROUP BY u.username";
        
        // Récupération des détails de l'annotateur
        $annotateurDetail = $connection->fetchAllAssociative($sqlByUser, [$userId]);
        if (!empty($annotateurDetail)) {
            return new JsonResponse(['annotateurDetail' => $annotateurDetail]);
        } else {
            return new JsonResponse(['annotateurDetail' => []]);
        }
        
    }
}
