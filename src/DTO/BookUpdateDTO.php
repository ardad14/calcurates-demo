<?php

namespace App\DTO;

class BookUpdateDTO
{
    /**
     * @Assert\NotBlank
     */
    public ?string $name;

    public ?string $description;

    public ?\DateTimeInterface $publish_date;

    /**
     * @var int[]
     */
    public array $authors = [];

    public function __construct()
    {
        $this->authors = [];
    }
}
