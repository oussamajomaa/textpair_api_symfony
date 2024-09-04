<?php

namespace App\Entity;
use App\Repository\EvaluationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EvaluationRepository::class)]

class Evaluation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $user_id = null;

    #[ORM\Column]
    private ?int $alignment_id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $evaluate = null;

    #[ORM\Column(nullable: true)]
    private ?bool $validate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): static
    {
        $this->user_id = $user_id;

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
    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getEvaluate(): ?string
    {
        return $this->evaluate;
    }

    public function setEvaluate(?string $evaluate): static
    {
        $this->evaluate = $evaluate;

        return $this;
    }

    public function isValidate(): ?bool
    {
        return $this->validate;
    }

    public function setValidate(?bool $validate): static
    {
        $this->validate = $validate;

        return $this;
    }
}
