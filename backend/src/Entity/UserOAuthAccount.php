<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;
use Symfony\Component\Serializer\Attribute as Serializer;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'user_oauth_account'),
    ORM\UniqueConstraint(name: 'provider_remote_id_idx', columns: ['provider', 'remote_user_id']),
    ORM\Index(columns: ['user_id']),
]
class UserOAuthAccount implements Stringable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[
        OA\Property(example: 1),
        ORM\Column(nullable: false),
        ORM\ManyToOne(targetEntity: User::class, inversedBy: 'oAuthAccounts'),
        ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE'),
        Serializer\Groups(['api'])
    ]
    public User $user;

    #[
        OA\Property(example: "google"),
        ORM\Column(length: 50, nullable: false),
    ]
    public string $provider;

    #[
        OA\Property(example: "118123456789101112131415"),
        ORM\Column(length: 255, nullable: false),
    ]
    public string $remote_user_id;

    #[
        OA\Property(example: "user@example.com"),
        ORM\Column(length: 255, nullable: true),
    ]
    public ?string $remote_email = null;

    #[
        OA\Property(example: "John Doe"),
        ORM\Column(length: 255, nullable: true),
    ]
    public ?string $remote_name = null;

    #[
        OA\Property(example: "https://example.com/avatar.jpg"),
        ORM\Column(type: 'text', nullable: true),
    ]
    public ?string $remote_avatar_url = null;

    #[
        OA\Property(example: "access_token_xyz"),
        ORM\Column(type: 'text', nullable: false),
    ]
    public string $access_token;

    #[
        OA\Property(example: "refresh_token_xyz"),
        ORM\Column(type: 'text', nullable: true),
    ]
    public ?string $refresh_token = null;

    #[
        OA\Property(example: 1643587200),
        ORM\Column(type: 'integer', nullable: true),
    ]
    public ?int $token_expires_at = null;

    #[
        ORM\Column(type: 'datetime', nullable: false),
    ]
    public DateTime $created_at;

    #[
        ORM\Column(type: 'datetime', nullable: false),
    ]
    public DateTime $updated_at;

    public function __construct()
    {
        $this->created_at = new DateTime('now');
        $this->updated_at = new DateTime('now');
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->provider, $this->remote_email ?? $this->remote_user_id);
    }

    public function isTokenExpired(): bool
    {
        if (null === $this->token_expires_at) {
            return false;
        }

        return time() > $this->token_expires_at;
    }
}
