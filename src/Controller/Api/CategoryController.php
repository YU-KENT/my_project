<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CategoryController extends AbstractController
{
    #[Route(path: '/api/category', name: 'api_category_index', methods: ['GET'])]
    public function index(CategoryRepository $categoryRepository, SerializerInterface $serializer): JsonResponse
    {

        $categories = $categoryRepository->findAll();

        $jsonCategories = $serializer->serialize($categories, 'json');

        return new JsonResponse($jsonCategories, Response::HTTP_OK, [], true);



    }
    #[Route(path: '/api/category/new', name: 'api_category_add', methods: ['POST'])]
    public function addCategory(ValidatorInterface $validator, SerializerInterface $serializer, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $category = $serializer->deserialize($request->getContent(), Category::class, 'json');
        $error = $validator->validate($category);

        if ($error->count() > 0) {
            return new JsonResponse($serializer->serialize($error, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        $entityManager->persist($category);
        $entityManager->flush();
        $jsonCategory = $serializer->serialize($category, 'json');
        return new JsonResponse($jsonCategory, Response::HTTP_CREATED, [], true);
    }


    #[Route(path: '/api/category/{id}/update', name: 'api_category_update', methods: ['POST'])]
    public function updateCategory($id,Category $category, CategoryRepository $categoryRepository, ValidatorInterface $validator, SerializerInterface $serializer, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $newCategory = $serializer->deserialize($request->getContent(), Category::class, 'json');
        $categoryItem = $categoryRepository->find($id);
        $categoryItem ->setName($newCategory->getName());
        $entityManager ->flush();
        return new JsonResponse($serializer->serialize(['messages' => 'update successfully'] , 'json'), Response::HTTP_OK,[],true);
    }

    #[Route(path: '/api/category/{id}/delete', name: 'api_category_delete', methods: ['DELETE'])]
    public function deleteCategory(SerializerInterface $serializer, Category $category, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse($serializer->serialize(['messages' => 'you must login in to delete'], 'json'), Response::HTTP_UNAUTHORIZED, [], true);
        }
        $entityManager->remove($category);

        $entityManager->flush();


        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}