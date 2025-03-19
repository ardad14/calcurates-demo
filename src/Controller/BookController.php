<?php

namespace App\Controller;

use App\DTO\BookUpdateDTO;
use App\Entity\Author;
use App\Entity\Book;
use App\Message\BookMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api/book')]
class BookController extends AbstractController
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
        $books = $this->entityManager->getRepository(Book::class)->findAll();
        $json = $serializer->serialize($books, 'json', ['groups' => 'book:read']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(Book $book, SerializerInterface $serializer): JsonResponse
    {
        $json = $serializer->serialize($book, 'json', ['groups' => 'book:read']);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('', methods: ['POST'])]
    public function create(
        #[MapRequestPayload] Book $book,
        Request $request,
    ): Response
    {
        $data = json_decode($request->getContent(), true);
        // Add authors by id
        if (!empty($data['authors'])) {
            $authorRepo = $this->entityManager->getRepository(Author::class);
            foreach ($data['authors'] as $authorId) {
                $author = $authorRepo->find($authorId);
                if ($author) {
                    $book->addAuthor($author);
                }
            }
        }

        $this->bus->dispatch(new BookMessage($book, 'create'));
        return new Response(null, Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}', methods: ['PUT'])]
    public function update(Book $book, #[MapRequestPayload] BookUpdateDTO $newData): Response
    {
        $this->bus->dispatch(new BookMessage($newData, 'update', $book));
        return new Response(null, Response::HTTP_ACCEPTED);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete(Book $book): Response
    {
        $this->bus->dispatch(new BookMessage($book, 'delete'));
        return new Response(null, Response::HTTP_ACCEPTED);
    }
}
