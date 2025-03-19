<?php

namespace App\Message;

use App\DTO\AuthorUpdateDTO;
use App\Entity\Author;

class AuthorMessage
{
    private Author|AuthorUpdateDTO $author;
    private string $action;
    private ?Author $existingAuthor;

    public function __construct(Author|AuthorUpdateDTO $author, string $action, ?Author $existingAuthor = null)
    {
        $this->author = $author;
        $this->action = $action;
        $this->existingAuthor = $existingAuthor;
    }

    public function getAuthor(): Author|AuthorUpdateDTO
    {
        return $this->author;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getExistingAuthor(): ?Author
    {
        return $this->existingAuthor;
    }
}