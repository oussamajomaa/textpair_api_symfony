<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminAlignmentController extends AbstractController
{
    #[Route('/api/admin/search', name: 'app_admin_alignment')]
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
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid token'], 401);
        }

        $content = $request->getContent();

        // Décoder le JSON en tableau PHP
        $data = json_decode($content, true);

        // Gérer les paramètres %LIKE% et valeurs par défaut
        $sourceContent = '%' . ($data['source_content'] ?? '') . '%';
        $sourceAuthor = '%' . ($data['source_author'] ?? '') . '%';
        $sourceTitle = '%' . ($data['source_title'] ?? '') . '%';
        $sourceYear = '%' . ($data['source_year'] ?? '') . '%';
        $targetContent = '%' . ($data['target_content'] ?? '') . '%';
        $targetAuthor = '%' . ($data['target_author'] ?? '') . '%';
        $targetTitle = '%' . ($data['target_title'] ?? '') . '%';
        $targetYear = '%' . ($data['target_year'] ?? '') . '%';

        // Utiliser le lastId pour la pagination, ou 0 par défaut
        $lastId = (int) ($data['lastId'] ?? 0);

        // Utilisation de start et end pour un filtre supplémentaire
        $start = isset($data['start']) ? (int) $data['start'] : 0;
        $end = isset($data['end']) ? (int) $data['end'] : 0;



        // Construire la requête
        if ($start !== $end) {
            $sql = "SELECT alignment.id as ID, alignment.*
            FROM alignment 
            WHERE alignment.id > ? AND
            source_content LIKE ? AND
            source_author LIKE ? AND 
            source_title LIKE ? AND
            source_year LIKE ? AND
            target_content LIKE ? AND
            target_author LIKE ? AND 
            target_title LIKE ? AND
            target_year LIKE ? AND 
            alignment.id BETWEEN ? AND ? 
            ORDER BY alignment.id ASC
            LIMIT 50";

            $countSql = "SELECT COUNT(*) as total_count
            FROM alignment 
            WHERE 
            source_content LIKE ? AND
            source_author LIKE ? AND 
            source_title LIKE ? AND
            source_year LIKE ? AND
            target_content LIKE ? AND
            target_author LIKE ? AND 
            target_title LIKE ? AND
            target_year LIKE ? AND 
            alignment.id BETWEEN ? AND ?";

            $values = [
                $lastId,
                $sourceContent,
                $sourceAuthor,
                $sourceTitle,
                $sourceYear,
                $targetContent,
                $targetAuthor,
                $targetTitle,
                $targetYear,
                $start,
                $end
            ];
        } else {
            $sql = "SELECT alignment.id as ID, alignment.*
            FROM alignment 
            WHERE alignment.id > ? AND
            source_content LIKE ? AND
            source_author LIKE ? AND 
            source_title LIKE ? AND
            source_year LIKE ? AND
            target_content LIKE ? AND
            target_author LIKE ? AND 
            target_title LIKE ? AND
            target_year LIKE ?
            ORDER BY alignment.id ASC
            LIMIT 50";

            $countSql = "SELECT COUNT(*) as total_count
            FROM alignment 
            WHERE 
            source_content LIKE ? AND
            source_author LIKE ? AND 
            source_title LIKE ? AND
            source_year LIKE ? AND
            target_content LIKE ? AND
            target_author LIKE ? AND 
            target_title LIKE ? AND
            target_year LIKE ?";

            $values = [
                $lastId,
                $sourceContent,
                $sourceAuthor,
                $sourceTitle,
                $sourceYear,
                $targetContent,
                $targetAuthor,
                $targetTitle,
                $targetYear
            ];
        }

        // Log pour vérifier lastId
        error_log('last_id reçu dans la requête: ' . $lastId);

        try {
            // Exécution de la requête pour récupérer le nombre total d'enregistrements
            $totalCountResult = $connection->fetchOne($countSql, array_slice($values, 1), array_fill(0, count($values) - 1, \PDO::PARAM_STR));
            $totalCount = (int) $totalCountResult;

            // Exécution de la requête pour les résultats paginés
            $results = $connection->fetchAllAssociative(
                $sql,
                $values,
                array_merge([\PDO::PARAM_INT], array_fill(0, count($values) - 2, \PDO::PARAM_STR))
            );

            // Log des résultats obtenus
            if (!empty($results)) {
                error_log('Query returned IDs: ' . implode(', ', array_column($results, 'ID')));
            } else {
                error_log('Query returned no results');
            }

            return new JsonResponse([
                'total_count' => $totalCount,
                'results' => $results,
                'lastId' => $lastId,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
