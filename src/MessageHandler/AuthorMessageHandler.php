<?php

namespace App\MessageHandler;

use App\DTO\AuthorUpdateDTO;
use App\Entity\Author;
use App\Entity\Book;
use App\Message\AuthorMessage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AuthorMessageHandler
{
    private EntityManagerInterface $entityManager;

    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function __invoke(AuthorMessage $message): void
    {
        $action = $message->getAction();
        $author = $message->getAuthor();
        $existingAuthor = $message->getExistingAuthor();

        switch ($action) {
            case 'create':
                $this->handleCreate($author);
                break;
            case 'update':
                $this->handleUpdate($author, $existingAuthor);
                break;
            case 'delete':
                $this->handleDelete($author);
                break;
        }

        $this->entityManager->flush();
    }

    private function handleCreate(Author $author): void
    {
        $bookRepo = $this->entityManager->getRepository(Book::class);
        $validBooks = new ArrayCollection();

        foreach ($author->getBooks() as $book) {
            try {
                $existingBook = $bookRepo->find($book->getId());
            } catch (\Exception $exception) {
                $this->logger->error($exception->getMessage());
                continue;
            }

            if ($existingBook) {
                $validBooks->add($existingBook);
            }
        }

        $author->getBooks()->clear();
        foreach ($validBooks as $validBook) {
            $author->addBook($validBook);
        }

        $this->entityManager->persist($author);
    }

    private function handleUpdate(AuthorUpdateDTO $author, Author $existingAuthor): void
    {
        $existingAuthor->setFirstname($author->firstname);
        $existingAuthor->setLastname($author->lastname);
        $existingAuthor->setDob($author->dob);

        $bookRepo = $this->entityManager->getRepository(Book::class);
        $existingAuthor->getBooks()->clear();

        foreach ($author->books as $book) {
            $existingBook = $bookRepo->find($book);
            if ($existingBook) {
                $existingAuthor->addBook($existingBook);

            }
        }
    }

    private function handleDelete(Author $author): void
    {
        foreach ($author->getBooks() as $book) {
            $author->removeBook($book);
        }

        $this->entityManager->remove($author);
    }
}
