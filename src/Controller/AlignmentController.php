<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AlignmentController extends AbstractController
{
    #[Route('/api/search', name: 'search', methods: ['POST'])]
    public function search(Request $request, Connection $connection): JsonResponse
    {
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
            $role = $decodedToken->role;
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $content = $request->getContent();

        // Décoder le JSON en tableau PHP
        $data = json_decode($content, true);

        $sourceContent = '%' . $data['source_content'] . '%';
        $sourceAuthor = '%' . $data['source_author'] . '%';
        $sourceTitle = '%' . $data['source_title'] . '%';
        $sourceYear = '%' . $data['source_year'] . '%';
        $targetContent = '%' . $data['target_content'] . '%';
        $targetAuthor = '%' . $data['target_author'] . '%';
        $targetTitle = '%' . $data['target_title'] . '%';
        $targetYear = '%' . $data['target_year'] . '%';
        $userId = $decodedToken->sub;


        // $pageSize = (int) $data['pageSize'];  // nombre de résultats par page
        // Récupération du dernier ID de la page précédente (curseur)
        $lastId = (int) $data['lastId'];

        $start = isset($data['start']) ? (int) $data['start'] : 0;
        $end = isset($data['end']) ? (int) $data['end'] : PHP_INT_MAX;

        if ($role !== 'Administrateur') {

        }
        if ($start !== $end) {
            $sql = "SELECT alignment.id as ID, alignment.*, evaluation.* 
            FROM alignment 
            LEFT JOIN evaluation ON evaluation.alignment_id = alignment.id 
            WHERE alignment.id > ? AND
            source_content LIKE ? AND
            source_author LIKE ? AND 
            source_title LIKE ? AND
            source_year LIKE ? AND
            target_content LIKE ? AND
            target_author LIKE ? AND 
            target_title LIKE ? AND
            target_year LIKE ? AND 
            alignment.id BETWEEN ? AND ? AND
            (evaluation.user_id = ? OR evaluation.user_id IS NULL)
            AND alignment.id NOT IN (
                SELECT alignment_id 
                FROM evaluation 
                WHERE user_id != ?
            )
            ORDER BY alignment.id ASC
            LIMIT 50";

            // Requête SQL pour récupérer le nombre total d'enregistrements
            $countSql = "SELECT COUNT(*) as total_count
            FROM alignment 
            LEFT JOIN evaluation ON evaluation.alignment_id = alignment.id 
            WHERE 
            source_content LIKE ? AND
            source_author LIKE ? AND 
            source_title LIKE ? AND
            source_year LIKE ? AND
            target_content LIKE ? AND
            target_author LIKE ? AND 
            target_title LIKE ? AND
            target_year LIKE ? AND 
            alignment.id BETWEEN ? AND ? AND
            (evaluation.user_id = ? OR evaluation.user_id IS NULL)
            AND alignment.id NOT IN (
                SELECT alignment_id 
                FROM evaluation 
                WHERE user_id != ?
            )";

            $values = [
                $sourceContent,
                $sourceAuthor,
                $sourceTitle,
                $sourceYear,
                $targetContent,
                $targetAuthor,
                $targetTitle,
                $targetYear,
                $start, $end,
                $userId,
                $userId
            ];
            // $values = array_merge([$lastId], $values, [$start, $end, $pageSize]);
        } else {
            $sql = "SELECT alignment.id as ID, alignment.*, evaluation.* 
            FROM alignment 
            LEFT JOIN evaluation ON evaluation.alignment_id = alignment.id 
            WHERE alignment.id > ? AND
            source_content LIKE ? AND
            source_author LIKE ? AND 
            source_title LIKE ? AND
            source_year LIKE ? AND
            target_content LIKE ? AND
            target_author LIKE ? AND 
            target_title LIKE ? AND
            target_year LIKE ? AND 
            (evaluation.user_id = ? OR evaluation.user_id IS NULL)
            AND alignment.id NOT IN (
                SELECT alignment_id 
                FROM evaluation 
                WHERE user_id != ?
            )
            ORDER BY alignment.id ASC
            LIMIT 50";

            // Requête SQL pour récupérer le nombre total d'enregistrements
            $countSql = "SELECT COUNT(*) as total_count
            FROM alignment 
            LEFT JOIN evaluation ON evaluation.alignment_id = alignment.id 
            WHERE 
            source_content LIKE ? AND
            source_author LIKE ? AND 
            source_title LIKE ? AND
            source_year LIKE ? AND
            target_content LIKE ? AND
            target_author LIKE ? AND 
            target_title LIKE ? AND
            target_year LIKE ? AND 
            (evaluation.user_id = ? OR evaluation.user_id IS NULL)
            AND alignment.id NOT IN (
                SELECT alignment_id 
                FROM evaluation 
                WHERE user_id != ?
            )";
            $values = [
                $sourceContent,
                $sourceAuthor,
                $sourceTitle,
                $sourceYear,
                $targetContent,
                $targetAuthor,
                $targetTitle,
                $targetYear,
                $userId,
                $userId
            ];
            // $values = array_merge([$lastId], $values, [$pageSize]);
        }

        error_log('last_id reçu dans la requête: ' . $lastId);


        try {
            // Exécution de la requête pour récupérer le nombre total d'enregistrements
            $totalCountResult = $connection->fetchOne($countSql, $values, array_fill(0, count($values), \PDO::PARAM_STR));
            $totalCount = (int) $totalCountResult;

            // Exécution de la requête pour les résultats paginés
            $values = array_merge([$lastId], $values);
            $results = $connection->fetchAllAssociative(
                $sql,
                $values,
                array_merge([\PDO::PARAM_INT], array_fill(0, count($values) - 2, \PDO::PARAM_STR), [\PDO::PARAM_INT])
            );

            if (!empty($results)) {
                error_log('Query returned IDs: ' . implode(', ', array_column($results, 'ID')));
            } else {
                error_log('Query returned no results');
            }
            return new JsonResponse([
                'total_count' => $totalCount,
                'results' => $results,
                'lastId' => $lastId,
                'role' => $role
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

   
}
