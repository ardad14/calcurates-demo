<?php

namespace App\Message;

use App\DTO\BookUpdateDTO;
use App\Entity\Book;

class BookMessage
{
    private Book|BookUpdateDTO $book;
    private string $action;
    private ?Book $existingBook;

    public function __construct(Book|BookUpdateDTO $book, string $action, ?Book $existingBook = null)
    {
        $this->book = $book;
        $this->action = $action;
        $this->existingBook = $existingBook;
    }

    public function getBook(): Book|BookUpdateDTO
    {
        return $this->book;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getExistingBook(): ?Book
    {
        return $this->existingBook;
    }
}
