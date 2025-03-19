<?php

namespace App\DTO;

class AuthorUpdateDTO
{
    /**
     * @Assert\NotBlank
     */
    public ?string $firstname;

    public ?string $lastname;

    public ?\DateTimeInterface $dob;

    /**
     * @var int[]
     */
    public array $books = [];

    public function __construct()
    {
        $this->books = [];
    }
}
