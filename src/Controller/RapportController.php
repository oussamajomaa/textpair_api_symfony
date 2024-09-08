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
    public function evaluated(Connection $connection): JsonResponse
    {
        $sqlEvaluated = "SELECT evaluation.id as ID, evaluation.*, alignment.*, user.*  FROM evaluation
                        JOIN alignment ON alignment.id = evaluation.alignment_id
                        JOIN user on user.id = evaluation.user_id";



        $evaluated = $connection->fetchAllAssociative($sqlEvaluated);
        return new JsonResponse($evaluated);
    }

    #[Route('/api/admin/rapport/validated', name: 'app_rapport_validated')]
    public function validated(Connection $connection): JsonResponse
    {
        $sqlValidated = "SELECT evaluation.id as ID, evaluation.*, alignment.*, user.*  FROM evaluation
                        JOIN alignment ON alignment.id = evaluation.alignment_id
                        JOIN user on user.id = evaluation.user_id
                        WHERE validate = 1";


        $validated = $connection->fetchAllAssociative($sqlValidated);
        return new JsonResponse($validated);
    }

    #[Route('/api/admin/rapport/alignment', name: 'app_rapport_alignment', methods: ['POST'])]
    public function alignment(Connection $connection, Request $request): JsonResponse
    {
        // $sqlAlignment = "SELECT *  FROM alignment";

        // $alignment = $connection->fetchAllAssociative($sqlAlignment);
        // return new JsonResponse($alignment);

        // $data = json_decode($request->getContent());

        // $limit = $data->limit;  // Utiliser la syntaxe objet
        // $offset = $data->offset;  // Utiliser la syntaxe objet

        // $sqlAlignment = "SELECT * FROM alignment LIMIT :? OFFSET :?";

        // $alignment = $connection->fetchAllAssociative($sqlAlignment, [$limit, $offset]);
        // return new JsonResponse($alignment);
        // Décoder le corps JSON de la requête
         // Décoder le corps JSON de la requête
    $data = json_decode($request->getContent());

    // Récupérer les paramètres limit et lastId depuis le corps de la requête
    // Met à jour pour utiliser lastId au lieu de offset
    $limit = $data->limit;  // Nombre d'enregistrements à récupérer
    $lastId = $data->lastId ?? 0;  // Utiliser 0 si lastId n'est pas défini

    // Requête SQL avec pagination basée sur l'ID
    $sqlAlignment = "SELECT id as ID, source_author, source_title, source_content, target_author, target_title, target_content
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
        return new JsonResponse(
            [
                'evaluated' => $countEvaluated,
                'validated' => $countValidated,
                'alignment' => $countAlignment
            ]
        );
    }
}
