<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\OAuthSetting;

/**
 * @extends Repository<OAuthSetting>
 */
final class OAuthSettingRepository extends Repository
{
    protected string $entityClass = OAuthSetting::class;

    public function findByProvider(string $provider): ?OAuthSetting
    {
        return $this->repository->find($provider);
    }

    public function findAllEnabled(): array
    {
        return $this->repository->findBy(['enabled' => true]);
    }

    public function getOrCreate(string $provider): OAuthSetting
    {
        $setting = $this->findByProvider($provider);
        if (!($setting instanceof OAuthSetting)) {
            $setting = new OAuthSetting($provider);
        }

        return $setting;
    }
}
