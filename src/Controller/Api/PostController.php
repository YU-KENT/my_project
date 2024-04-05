<?php

namespace App\Controller\Api;

use App\Entity\Post;
use App\Repository\PostRepository;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Filesystem\Filesystem;
use Knp\Component\Pager\PaginatorInterface;

// Your code here


class PostController extends AbstractController
{
    #[Route(path: '/api/posts', name: 'api_post_index', methods: ['GET'])]
    public function index(PostRepository $postRepository, SerializerInterface $serializer, PaginatorInterface $paginator, Request $request): JsonResponse
    {
        $posts = $postRepository->findBy([], []);
        $postsPager = $paginator->paginate(
            $posts,
            $request->query->getInt('page', 1),
            5
        );
        // arranger data
        $data = [];
        foreach ($postsPager->getItems() as $key => $value) {
            $dataItem = [
                'posts' => $value,
            ];
            $data[] = $dataItem;
        }

        $getData = [
            'data' => $data,
            'current_page_number' => $postsPager->getCurrentPageNumber(),
            'num_items_per_page' => $postsPager->getItemNumberPerPage(),
            'total-count' => $postsPager->getTotalItemCount()
        ];

        $context = SerializationContext::create()->setGroups(['getPost']);

        $jsonPosts = $serializer->serialize($getData, 'json', $context);

        return new JsonResponse($jsonPosts, Response::HTTP_OK, [], true);

        //  get all posts
        /* $posts = $postRepository->findAll();

        $jsonCategories = $serializer->serialize($posts, 'json');

        return new JsonResponse($jsonCategories, Response::HTTP_OK, [], true); */

        /*  
        //filter all posts by different user
        $user = $this->getUser();
         if ($user) {
             $posts = $postRepository->findBy(['user' => $user]);
             $jsonPosts = $serializer->serialize($posts, 'json');
             return new JsonResponse($jsonPosts, Response::HTTP_OK, [], true);
         } else {
             return new JsonResponse($serializer->serialize(['message' => 'you must login in'], 'json'), Response::HTTP_UNAUTHORIZED, [], true);
         } */








    }
    #[Route(path: '/api/category/{id}/post/new', name: 'api_post_new', methods: ['POST'])]
    public function postNew($id, CategoryRepository $categoryRepository, SerializerInterface $serializer, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse($serializer->serialize(['messages' => 'you must login in to delete'], 'json'), Response::HTTP_UNAUTHORIZED, [], true);
        }
        $postRequest = $request->request->all();

        $category = $categoryRepository->find($id);
        $file = $request->files->get('imageName');
        $post = new Post();

        if (!empty($file)) {
            $fileExt = ["png", "PNG", "jpg", "JPG", "JPEG", "jpeg"];
            if (in_array($file->guessExtension(), $fileExt, true)) {
                $imageName = md5(uniqid("", true)) . '.' . $file->guessExtension();
                $file->move($this->getParameter('app.image.dir'), $imageName);
                $post->setImageName($imageName);

            }
        }

        $post->setTitle($postRequest['title']);
        $post->setContent($postRequest['content']);
        $post->setCategory($category);
        $user = $this->getUser();
        $post->setUser($user);
        $entityManager->persist($post);
        $entityManager->flush();
        return new JsonResponse($serializer->serialize(['messages' => 'your post have been saved'], 'json'), Response::HTTP_CREATED, [], true);

    }
    #[Route(path: '/api/post/{id}/delete', name: 'api_post_delete', methods: ['DELETE'])]
    public function deletePost(SerializerInterface $serializer, Post $post, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        if (!$this->getUser()) {
            return new JsonResponse($serializer->serialize(['messages' => 'you must login in to delete'], 'json'), Response::HTTP_UNAUTHORIZED, [], true);
        }

        if ($this->getUser() === $post->getUser()) {
            $imageName = $post->getImageName();
            if (!empty($imageName)) {
                $imagePath = $this->getParameter('app.image.dir') . '/' . $imageName;
                $filesystem = new Filesystem();
                if ($filesystem->exists($imagePath)) {
                    $filesystem->remove($imagePath);
                }
            }
            $entityManager->remove($post);
            $entityManager->flush();

            return new JsonResponse(null, Response::HTTP_NO_CONTENT);
        }

        return new JsonResponse($serializer->serialize(['message' => "you don't have permission to delete this post"], 'json'), Response::HTTP_UNAUTHORIZED, [], true);
    }


}