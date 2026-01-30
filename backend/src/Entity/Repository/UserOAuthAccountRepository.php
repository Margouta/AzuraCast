<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\User;
use App\Entity\UserOAuthAccount;

/**
 * @extends Repository<UserOAuthAccount>
 */
final class UserOAuthAccountRepository extends Repository
{
    protected string $entityClass = UserOAuthAccount::class;

    public function findByProviderAndRemoteId(string $provider, string $remoteUserId): ?UserOAuthAccount
    {
        return $this->repository->findOneBy([
            'provider' => $provider,
            'remote_user_id' => $remoteUserId,
        ]);
    }

    public function findByUser(User $user): array
    {
        return $this->repository->findBy(['user' => $user]);
    }

    public function findByProviderAndEmail(string $provider, string $email): ?UserOAuthAccount
    {
        return $this->repository->findOneBy([
            'provider' => $provider,
            'remote_email' => $email,
        ]);
    }

    public function getOrCreate(string $provider, string $remoteUserId): UserOAuthAccount
    {
        $oauthAccount = $this->findByProviderAndRemoteId($provider, $remoteUserId);
        if (!($oauthAccount instanceof UserOAuthAccount)) {
            $oauthAccount = new UserOAuthAccount();
            $oauthAccount->provider = $provider;
            $oauthAccount->remote_user_id = $remoteUserId;
        }

        return $oauthAccount;
    }
}
