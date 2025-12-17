<?php

namespace App\Controller\Api;



use App\Entity\Address;
use App\Repository\AddressRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
class AddressController extends AbstractController
{
    #[Route('/user/addresses', name: 'api_user_addresses', methods: ['GET'])]
    public function getUserAddresses(AddressRepository $addressRepo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $addresses = $addressRepo->findBy(['user' => $user]);

        $addressesData = [];
        foreach ($addresses as $address) {
            $addressesData[] = [
                'id' => $address->getId(),
                'firstname' => $address->getFirstname(),
                'lastname' => $address->getLastname(),
                'address' => $address->getAddress(),
                'postal' => $address->getPostal(),
                'city' => $address->getCity(),
                'country' => $address->getCountry(),
                'phone' => $address->getPhone(),
            ];
        }

        return new JsonResponse($addressesData);
    }

    #[Route('/user/addresses', name: 'api_user_addresses_create', methods: ['POST'])]
    public function createAddress(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        // Validate required fields
        $required = ['firstname', 'lastname', 'address', 'postal', 'city', 'country', 'phone'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return new JsonResponse(['error' => "Missing field: $field"], Response::HTTP_BAD_REQUEST);
            }
        }

        $address = new Address();
        $address->setUser($user);
        $address->setFirstname($data['firstname']);
        $address->setLastname($data['lastname']);
        $address->setAddress($data['address']);
        $address->setPostal($data['postal']);
        $address->setCity($data['city']);
        $address->setCountry($data['country']);
        $address->setPhone($data['phone']);

        $em->persist($address);
        $em->flush();

        return new JsonResponse([
            'id' => $address->getId(),
            'firstname' => $address->getFirstname(),
            'lastname' => $address->getLastname(),
            'address' => $address->getAddress(),
            'postal' => $address->getPostal(),
            'city' => $address->getCity(),
            'country' => $address->getCountry(),
            'phone' => $address->getPhone(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/user/addresses/{id}', name: 'api_user_addresses_delete', methods: ['DELETE'])]
    public function deleteAddress(int $id, AddressRepository $addressRepo, EntityManagerInterface $em): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $address = $addressRepo->find($id);

        if (!$address || $address->getUser() !== $user) {
            return new JsonResponse(['error' => 'Address not found'], Response::HTTP_NOT_FOUND);
        }

        $em->remove($address);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}