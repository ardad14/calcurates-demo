<?php

namespace App\Controller;

use App\DTO\AuthorUpdateDTO;
use App\Entity\Author;
use App\Entity\Book;
use App\Message\AuthorMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/author')]
class AuthorController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private MessageBusInterface $bus;

    public function __construct(EntityManagerInterface $entityManager, MessageBusInterface $bus)
    {
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }

    #[Route('', methods: ['GET'])]
    public function index(SerializerInterface $serializer): JsonResponse
    {
        $authors = $this->entityManager->getRepository(Author::class)->findAll();
        $json = $serializer->serialize($authors, 'json', ['groups' => 'author:read']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Author $author, SerializerInterface $serializer): JsonResponse
    {
        $json = $serializer->serialize($author, 'json', ['groups' => 'author:read']);
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] Author $author,
        Request $request
    ): Response
    {
        $data = json_decode($request->getContent(), true);
        // Add books by id
        if (!empty($data['books'])) {
            $bookRepo = $this->entityManager->getRepository(Book::class);
            foreach ($data['books'] as $bookId) {
                $book = $bookRepo->find($bookId);
                if ($book) {
                    $author->addBook($book);
                }
            }
        }

        $this->bus->dispatch(new AuthorMessage($author, 'create'));
        return new Response(null, Response::HTTP_ACCEPTED);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/{id}', methods: ['PUT'])]
    public function update(Author $author, #[MapRequestPayload] AuthorUpdateDTO $newData): Response
    {
        $this->bus->dispatch(new AuthorMessage($newData, 'update', $author));
        return new Response(null, Response::HTTP_ACCEPTED);
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Author $author): Response
    {
        $this->bus->dispatch(new AuthorMessage($author, 'delete'));
        return new Response(null, Response::HTTP_ACCEPTED);
    }
}
