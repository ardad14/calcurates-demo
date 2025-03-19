<?php

namespace App\MessageHandler;

use App\DTO\BookUpdateDTO;
use App\Entity\Author;
use App\Entity\Book;
use App\Message\BookMessage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class BookMessageHandler
{
    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function __invoke(BookMessage $message): void
    {
        $action = $message->getAction();
        $book = $message->getBook();
        $existingBook = $message->getExistingBook();

        switch ($action) {
            case 'create':
                $this->handleCreate($book);
                break;
            case 'update':
               $this->handleUpdate($book, $existingBook);
                break;
            case 'delete':
                $this->handleDelete($book);
                break;
        }

        $this->entityManager->flush();
    }

    private function handleCreate(Book $book): void
    {
        $authorRepo = $this->entityManager->getRepository(Author::class);
        $validAuthors = new ArrayCollection();

        foreach ($book->getAuthors() as $author) {
            try {
                $existingAuthor = $authorRepo->find($author->getId());
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                continue;
            }

            if ($existingAuthor) {
                $validAuthors->add($existingAuthor);
            }
        }

        $book->getAuthors()->clear();
        foreach ($validAuthors as $validAuthor) {
            $book->addAuthor($validAuthor);
        }

        $this->entityManager->persist($book);
    }

    private function handleUpdate(BookUpdateDTO $book, Book $existingBook): void
    {
        $existingBook->setName($book->name);
        $existingBook->setDescription($book->description);
        $existingBook->setPublishDate($book->publish_date);

        $authorRepo = $this->entityManager->getRepository(Author::class);
        $existingBook->getAuthors()->clear();

        foreach ($book->authors as $author) {
            $existingAuthor = $authorRepo->find($author);
            if ($existingAuthor) {
                $existingBook->addAuthor($existingAuthor);
            }
        }
    }

    private function handleDelete(Book $book): void
    {
        foreach ($book->getAuthors() as $author) {
            $book->removeAuthor($author);
        }

        $this->entityManager->remove($book);
    }
}
