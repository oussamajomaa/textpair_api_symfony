<?php

namespace App\Entity;

use App\Repository\AlignmentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AlignmentRepository::class)]

class Alignment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1000)]
    private ?string $target_title = null;

    #[ORM\Column(length: 1000, nullable: true)]
    private ?string $source_title = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $target_content = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $source_content = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $target_before = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $source_before = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $target_after = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $source_after = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $target_author = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source_author = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $target_year = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source_year = null;

    #[ORM\Column]
    private ?int $source_id = null;

    #[ORM\Column]
    private ?int $target_id = null;

    #[ORM\Column]
    private ?int $alignment_id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTargetTitle(): ?string
    {
        return $this->target_title;
    }

    public function setTargetTitle(string $target_title): static
    {
        $this->target_title = $target_title;

        return $this;
    }

    public function getSourceTitle(): ?string
    {
        return $this->source_title;
    }

    public function setSourceTitle(?string $source_title): static
    {
        $this->source_title = $source_title;

        return $this;
    }

    public function getTargetContent(): ?string
    {
        return $this->target_content;
    }

    public function setTargetContent(string $target_content): static
    {
        $this->target_content = $target_content;

        return $this;
    }

    public function getSourceContent(): ?string
    {
        return $this->source_content;
    }

    public function setSourceContent(string $source_content): static
    {
        $this->source_content = $source_content;

        return $this;
    }

    public function getTargetBefore(): ?string
    {
        return $this->target_before;
    }

    public function setTargetBefore(string $target_before): static
    {
        $this->target_before = $target_before;

        return $this;
    }

    public function getSourceBefore(): ?string
    {
        return $this->source_before;
    }

    public function setSourceBefore(string $source_before): static
    {
        $this->source_before = $source_before;

        return $this;
    }

    public function getTargetAfter(): ?string
    {
        return $this->target_after;
    }

    public function setTargetAfter(string $target_after): static
    {
        $this->target_after = $target_after;

        return $this;
    }

    public function getSourceAfter(): ?string
    {
        return $this->source_after;
    }

    public function setSourceAfter(string $source_after): static
    {
        $this->source_after = $source_after;

        return $this;
    }

    public function getTargetAuthor(): ?string
    {
        return $this->target_author;
    }

    public function setTargetAuthor(?string $target_author): static
    {
        $this->target_author = $target_author;

        return $this;
    }

    public function getSourceAuthor(): ?string
    {
        return $this->source_author;
    }

    public function setSourceAuthor(?string $source_author): static
    {
        $this->source_author = $source_author;

        return $this;
    }

    public function getTargetYear(): ?string
    {
        return $this->target_year;
    }

    public function setTargetYear(?string $target_year): static
    {
        $this->target_year = $target_year;

        return $this;
    }

    public function getSourceYear(): ?string
    {
        return $this->source_year;
    }

    public function setSourceYear(?string $source_year): static
    {
        $this->source_year = $source_year;

        return $this;
    }

    public function getSourceId(): ?int
    {
        return $this->source_id;
    }

    public function setSourceId(int $source_id): static
    {
        $this->source_id = $source_id;

        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->target_id;
    }

    public function setTargetId(int $target_id): static
    {
        $this->target_id = $target_id;

        return $this;
    }

    public function getAlignmentId(): ?int
    {
        return $this->alignment_id;
    }

    public function setAlignmentId(int $alignment_id): static
    {
        $this->alignment_id = $alignment_id;

        return $this;
    }
}
