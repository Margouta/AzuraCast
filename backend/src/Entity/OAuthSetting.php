<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes as OA;
use Stringable;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'oauth_setting'),
    ORM\UniqueConstraint(name: 'provider_idx', columns: ['provider']),
]
class OAuthSetting implements Stringable
{
    #[
        OA\Property(example: "google"),
        ORM\Column(length: 50, nullable: false),
        ORM\Id,
    ]
    public string $provider;

    #[
        OA\Property(example: true),
        ORM\Column(type: 'boolean', nullable: false),
    ]
    public bool $enabled = false;

    #[
        OA\Property(example: "client_id_xyz"),
        ORM\Column(length: 500, nullable: false),
    ]
    public string $client_id = '';

    #[
        OA\Property(example: "client_secret_xyz"),
        ORM\Column(type: 'text', nullable: false),
    ]
    public string $client_secret = '';

    #[
        OA\Property(example: "https://accounts.google.com/o/oauth2/auth"),
        ORM\Column(length: 500, nullable: true),
    ]
    public ?string $authorization_endpoint = null;

    #[
        OA\Property(example: "https://oauth2.googleapis.com/token"),
        ORM\Column(length: 500, nullable: true),
    ]
    public ?string $token_endpoint = null;

    #[
        OA\Property(example: "https://www.googleapis.com/oauth2/v2/userinfo"),
        ORM\Column(length: 500, nullable: true),
    ]
    public ?string $userinfo_endpoint = null;

    #[
        OA\Property(example: "openid email profile"),
        ORM\Column(length: 500, nullable: true),
    ]
    public ?string $scope = null;

    #[
        ORM\Column(type: 'datetime', nullable: false),
    ]
    public \DateTime $created_at;

    #[
        ORM\Column(type: 'datetime', nullable: false),
    ]
    public \DateTime $updated_at;

    public function __construct(string $provider = '')
    {
        $this->provider = $provider;
        $this->created_at = new \DateTime('now');
        $this->updated_at = new \DateTime('now');
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->provider, $this->enabled ? 'enabled' : 'disabled');
    }
}
