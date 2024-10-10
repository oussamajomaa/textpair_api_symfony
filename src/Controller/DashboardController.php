<?php

namespace App\Controller;

use App\Repository\AlignmentRepository;
use App\Repository\EvaluationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\DBAL\Connection;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;

class DashboardController extends AbstractController
{
    #[Route('/api/dashboard/{order?}', name: 'app_dashboard')]
    public function index(
        Connection $connection,
        EvaluationRepository $evaluationRepo,
        AlignmentRepository $alignmentRepo,
        Request $request,
        $order,
    ): JsonResponse {

        // Accéder au cookie
        $token = $request->cookies->get('token');

        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
        }

        // Décoder le token pour obtenir l'utilisateur connecté
        try {
            $secretKey = $_ENV['JWT_SECRET'];
            $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
            $userId = $decodedToken->sub; // Assurez-vous que le token contient un champ 'sub' avec l'ID utilisateur
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $allowedOrders = ['name', 'value'];
        if (!in_array($order, $allowedOrders)) {
            $order = 'value';  // Default to source_year if invalid order is provided
        }
        $sqlGroupBySourceYear = "SELECT count(*) as value, source_year as name FROM alignment WEHRE GROUP BY source_year ORDER BY CAST($order AS UNSIGNED)";
        $sourceYear = $connection->fetchAllAssociative($sqlGroupBySourceYear);

        $sqlGroupByTargetYear = "SELECT count(*) as value, target_year as name FROM alignment WEHRE GROUP BY target_year ORDER BY CAST($order AS UNSIGNED)";
        $targetYear = $connection->fetchAllAssociative($sqlGroupByTargetYear);

        $sqlGroupBySourceAuthor = "SELECT count(*) as value, source_author as name FROM alignment WEHRE GROUP BY source_author ORDER BY CAST($order AS UNSIGNED)";
        $sourceAuthor = $connection->fetchAllAssociative($sqlGroupBySourceAuthor);

        $sqlGroupByTargetAuthor = "SELECT count(*) as value, target_author as name FROM alignment WEHRE GROUP BY target_author ORDER BY CAST($order AS UNSIGNED)";
        $targetAuthor = $connection->fetchAllAssociative($sqlGroupByTargetAuthor);

        $sqlEvaluatedByUser = "SELECT u.email As name, COUNT(*) AS value
                                FROM evaluation e
                                JOIN user u ON e.user_id = u.id
                                WHERE e.evaluate IS NOT NULL
                                GROUP BY e.user_id";

        $evaluatedByUser = $connection->fetchAllAssociative($sqlEvaluatedByUser);

        $sqlEvaluatedByvalidateur = "SELECT u.email As name, COUNT(*) AS value
                                FROM evaluation e
                                JOIN user u ON e.validateur_id = u.id
                                WHERE e.evaluate IS NOT NULL
                                GROUP BY e.validateur_id";
        $evaluatedByValidateur = $connection->fetchAllAssociative($sqlEvaluatedByvalidateur);


        // Get the counts directly from the repository using more efficient count queries
        $evaluated = $evaluationRepo->count([]); // Count all evaluations
        $validatedCount = $evaluationRepo->count(['validate' => 1]); // Count validated evaluations
        $ouiCount = $evaluationRepo->count(['evaluate' => 'Oui']); // Count correct evaluations
        $nonCount = $evaluationRepo->count(['evaluate' => 'Non']); // Count incorrect evaluations
        $douteuxCount = $evaluationRepo->count(['evaluate' => 'Douteux']); // Count "Douteux" evaluations
        $alignment = $alignmentRepo->count([]); // Count all alignments

        $sqlAnnotateur = "SELECT * from user WHERE role = 'Annotateur'";
        $sqlValidateur = "SELECT * from user WHERE role = 'Validateur'";



        $annotateur = $connection->fetchAllAssociative($sqlAnnotateur);
        $validateur = $connection->fetchAllAssociative($sqlValidateur);


        // Return the counts as JSON response
        return new JsonResponse([
            'evaluated'         => $evaluated,
            'validated'         => $validatedCount,
            'oui'           => $ouiCount,
            'non'         => $nonCount,
            'douteux'           => $douteuxCount,
            'alignment'         => $alignment,
            'evaluatedByUser'   => $evaluatedByUser,
            'evaluatedByValidateur' => $evaluatedByValidateur,
            'annotateur'        => $annotateur,
            'validateur'        => $validateur,
            'sourceYear'        => $sourceYear,
            'targetYear'        => $targetYear,
            'sourceAuthor'      => $sourceAuthor,
            'targetAuthor'      => $targetAuthor


        ]);
    }

    #[Route('api/dashboard/annotateur/{userId}', name: 'app_dashboard_annotateur')]
    public function annotateur(int $userId, Connection $connection, Request $request): JsonResponse
    {
        // Accéder au cookie
        $token = $request->cookies->get('token');

        if (!$token) {
            return new JsonResponse(['error' => 'Token not found'], 401);
        }

        // Vérifier si le token est valide sans nécessairement extraire des données utilisateur
        try {
            $secretKey = $_ENV['JWT_SECRET'];
            $decodedToken = JWT::decode($token, new Key($secretKey, 'HS256'));
            // Si le token est valide, vous n'avez rien à faire de plus ici
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $sqlByUser = "
            SELECT 
            u.email,
                SUM(CASE WHEN e.evaluate = 'Oui' THEN 1 ELSE 0 END) AS correct_count,
                SUM(CASE WHEN e.evaluate = 'Non' THEN 1 ELSE 0 END) AS incorrect_count,
                SUM(CASE WHEN e.evaluate = 'Douteux' THEN 1 ELSE 0 END) AS pas_sur_count
            FROM evaluation e
            JOIN user u ON e.user_id = u.id
            WHERE e.user_id = ?
            GROUP BY u.email";

        // Récupération des détails de l'annotateur
        $annotateurDetail = $connection->fetchAllAssociative($sqlByUser, [$userId]);
        if (!empty($annotateurDetail)) {
            return new JsonResponse(['annotateurDetail' => $annotateurDetail]);
        } else {
            return new JsonResponse(['annotateurDetail' => []]);
        }
    }
}
// tail -f var/log/dev.log