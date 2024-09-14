<?php

namespace App\Controller;

use App\Repository\AlignmentRepository;
use App\Repository\EvaluationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class RapportController extends AbstractController
{
    #[Route('/api/admin/rapport/evaluated', name: 'app_rapport_evaluated')]
    public function evaluated(Connection $connection, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        // Récupérer les paramètres limit et lastId depuis le corps de la requête
        // Met à jour pour utiliser lastId au lieu de offset
        $limit = $data->limit;  // Nombre d'enregistrements à récupérer
        $lastId = $data->lastId ?? 0;  // Utiliser 0 si lastId n'est pas défini
        $sqlEvaluated = "SELECT evaluation.id as ID, source_author, source_title, source_content, target_author, target_title, target_content
                        FROM evaluation
                        JOIN alignment ON alignment.id = evaluation.alignment_id
                        JOIN user on user.id = evaluation.user_id
                        WHERE evaluation.id > :lastId
                        ORDER BY evaluation.id ASC
                        lIMIT :limit";

        // Préparer la requête avec Doctrine DBAL
        $stmt = $connection->prepare($sqlEvaluated);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('lastId', $lastId, \PDO::PARAM_INT);

        // Exécuter la requête et récupérer les résultats
        $result = $stmt->executeQuery();

        $evaluated = $result->fetchAllAssociative();
        return new JsonResponse($evaluated);
    }

    #[Route('/api/admin/rapport/validated', name: 'app_rapport_validated')]
    public function validated(Connection $connection, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent());
        // Récupérer les paramètres limit et lastId depuis le corps de la requête
        // Met à jour pour utiliser lastId au lieu de offset
        $limit = $data->limit;  // Nombre d'enregistrements à récupérer
        $lastId = $data->lastId ?? 0;  // Utiliser 0 si lastId n'est pas défini
        $sqlValidated = "SELECT evaluation.id as ID, source_author, source_title, source_content, target_author, target_title, target_content  
                        FROM evaluation
                        JOIN alignment ON alignment.id = evaluation.alignment_id
                        JOIN user on user.id = evaluation.user_id
                        WHERE validate = 1 AND evaluation.id > :lastId
                        ORDER BY evaluation.id ASC
                        lIMIT :limit";

        // Préparer la requête avec Doctrine DBAL
        $stmt = $connection->prepare($sqlValidated);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('lastId', $lastId, \PDO::PARAM_INT);

        // Exécuter la requête et récupérer les résultats
        $result = $stmt->executeQuery();

        $validated = $result->fetchAllAssociative();
        return new JsonResponse($validated);
    }

    #[Route('/api/admin/rapport/alignment', name: 'app_rapport_alignment', methods: ['POST'])]
    public function alignment(Connection $connection, Request $request): JsonResponse
    {

        $data = json_decode($request->getContent());

        // Récupérer les paramètres limit et lastId depuis le corps de la requête
        // Met à jour pour utiliser lastId au lieu de offset
        $limit = $data->limit;  // Nombre d'enregistrements à récupérer
        $lastId = $data->lastId ?? 0;  // Utiliser 0 si lastId n'est pas défini

        // Requête SQL avec pagination basée sur l'ID
        $sqlAlignment = "SELECT id , source_author, source_title, source_content, target_author, target_title, target_content
                     FROM alignment
                     WHERE id > :lastId
                     ORDER BY id ASC
                     LIMIT :limit";

        // Préparer la requête avec Doctrine DBAL
        $stmt = $connection->prepare($sqlAlignment);
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue('lastId', $lastId, \PDO::PARAM_INT);

        // Exécuter la requête et récupérer les résultats
        $result = $stmt->executeQuery();
        $alignment = $result->fetchAllAssociative();  // Récupérer tous les résultats sous forme de tableau associatif

        // Retourner les résultats sous forme de JSON
        return new JsonResponse($alignment);
    }

    #[Route('/api/admin/rapport/count', name: 'app_rapport_count')]
    public function count(EvaluationRepository $repo, AlignmentRepository $repoAlign): JsonResponse
    {
        $countEvaluated = $repo->count();
        $countValidated = $repo->count(['validate' => 1]);
        $countAlignment = $repoAlign->count();
        $sourceAuthor = $repoAlign->countBySourceAuthor();
        $targetAuthor = $repoAlign->countByTargetAuthor();
        return new JsonResponse(
            [
                'evaluated' => $countEvaluated,
                'validated' => $countValidated,
                'alignment' => $countAlignment,
                'sourceAuthor' => count($sourceAuthor),
                'targetAuthor' => count($targetAuthor)
            ]
        );
    }

    #[Route('/api/admin/rapport/author_source', name: 'app_rapport_author_source')]
    public function author_source(Connection $connection): JsonResponse
    {

        // Requête SQL pour récupérer les auteurs distincts
        $sqlAuthor = "SELECT DISTINCT source_author as Auteur
                  FROM alignment
                  ORDER BY source_author ASC";

        $source_author = $connection->fetchAllAssociative($sqlAuthor);  // Récupérer tous les résultats sous forme de tableau associatif

        // Retourner les résultats sous forme de JSON
        return new JsonResponse($source_author);
    }

    #[Route('/api/admin/rapport/author_target', name: 'app_rapport_author_target')]
    public function author_target(Connection $connection): JsonResponse
    {
        // Requête SQL pour récupérer les auteurs distincts
        $sqlAuthor = "SELECT DISTINCT target_author as Auteur
                  FROM alignment
                  ORDER BY target_author ASC";

        $target_author = $connection->fetchAllAssociative($sqlAuthor);  // Récupérer tous les résultats sous forme de tableau associatif

        // Retourner les résultats sous forme de JSON
        return new JsonResponse($target_author);
    }
}
