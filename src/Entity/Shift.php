<?php

namespace App\Entity;

use App\Repository\ShiftRepository;
use App\Validator\NoShiftOverlap;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShiftRepository::class)]
#[NoShiftOverlap]
class Shift
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Agent::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Agent $agent = null;

    #[ORM\ManyToOne(targetEntity: Site::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    #[ORM\Column(type: 'date')]
    private ?\DateTimeInterface $shiftDate = null;

    #[ORM\Column(type: 'time')]
    private ?\DateTimeInterface $startTime = null;

    #[ORM\Column(type: 'time')]
    private ?\DateTimeInterface $endTime = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null; // JOUR, NUIT

    #[ORM\Column(length: 50)]
    private ?string $status = null; // PREVU, EFFECTUE, ABSENT

    // Getters & setters …

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getShiftDate(): ?\DateTimeInterface
    {
        return $this->shiftDate;
    }

    public function setShiftDate(\DateTimeInterface $shiftDate): self
    {
        $this->shiftDate = $shiftDate;

        return $this;
    }

    public function getStartTime(): ?\DateTimeInterface
    {
        return $this->startTime;
    }

    public function setStartTime(\DateTimeInterface $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?\DateTimeInterface
    {
        return $this->endTime;
    }

    public function setEndTime(\DateTimeInterface $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getAgent(): ?Agent
    {
        return $this->agent;
    }

    public function setAgent(?Agent $agent): self
    {
        $this->agent = $agent;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): self
    {
        $this->site = $site;

        return $this;
    }
}
