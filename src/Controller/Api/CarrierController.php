<?php

namespace App\Controller\Api;

use App\Repository\CarrierRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class CarrierController extends AbstractController
{
    #[Route('/carriers', name: 'api_carriers', methods: ['GET'])]
    public function getCarriers(CarrierRepository $carrierRepo): JsonResponse
    {
        $carriers = $carrierRepo->findAll();

        $carriersData = [];
        foreach ($carriers as $carrier) {
            $carriersData[] = [
                'id' => $carrier->getId(),
                'name' => $carrier->getName(),
                'description' => $carrier->getDescription(),
                'price' => $carrier->getPrice(),
            ];
        }

            return new JsonResponse($carriersData);
    }
}