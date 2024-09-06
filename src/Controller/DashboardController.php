<?php

namespace App\Controller;

use App\Repository\EvaluationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\DBAL\Connection;

class DashboardController extends AbstractController
{
    #[Route('/api/dashboard', name: 'app_dashboard')]
    public function index(Connection $connection,EvaluationRepository $repo): JsonResponse
    {
        $sql  ="select * from evaluation where validate = 1";
        $sqlCount  ="select count(*) from evaluation";
        $con = $repo->count();
        $val = $repo->findBy(['validate'=>1]);
        $validated = $connection->fetchAllAssociative($sql);
        $count = $connection->fetchOne($sqlCount);
        return new JsonResponse([$validated,$count,$con,$val]);
    }
}
