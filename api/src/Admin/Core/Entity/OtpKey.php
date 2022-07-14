<?php

namespace App\Admin\Core\Entity;

use App\Admin\Core\Enum\OtpType;
use App\Admin\Core\Repository\OtpKeyRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UlidGenerator;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: OtpKeyRepository::class)]
class OtpKey
{
    use OwnerTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: 'ulid', unique: true)]
    #[ORM\CustomIdGenerator(class: UlidGenerator::class)]
    private ?Ulid $id;

    #[ORM\Column(type: 'integer')]
    private int $key;

    #[ORM\Column(type: 'string', enumType: OtpType::class)]
    private OtpType $type;

    #[ORM\Column(type: 'boolean')]
    private bool $used = false;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $expiredAt;

    public function getId(): ?Ulid
    {
        return $this->id;
    }

    public function getKey(): int
    {
        return $this->key;
    }

    public function setKey(int $key): self
    {
        $this->key = $key;

        return $this;
    }

    public function getType(): OtpType
    {
        return $this->type;
    }

    public function setType(OtpType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): self
    {
        $this->used = $used;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiredAt->getTimestamp() > time();
    }

    public function getExpiredAt(): DateTimeImmutable
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(DateTimeImmutable $expiredAt): self
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }
}
