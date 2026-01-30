<?php

declare(strict_types=1);

namespace App\Auth\OAuth;

use App\Entity\OAuthSetting;
use App\Entity\Repository\OAuthSettingRepository;
use App\Entity\Repository\UserOAuthAccountRepository;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Entity\UserOAuthAccount;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;
use LogicException;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Service for handling OAuth 2.0 authentication flows.
 */
class OAuthService
{
    public function __construct(
        private readonly OAuthSettingRepository $oauthSettingRepository,
        private readonly UserOAuthAccountRepository $userOAuthAccountRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    /**
     * Get authorization URL for OAuth provider.
     */
    public function getAuthorizationUrl(string $provider, string $redirectUri, string $state): ?string
    {
        $oauthSetting = $this->oauthSettingRepository->findByProvider($provider);
        if (!$oauthSetting?->enabled) {
            return null;
        }

        $providerConfig = $this->buildProviderConfig($oauthSetting);
        $abstractProvider = new GenericProvider($providerConfig);

        $options = [
            'scope' => $this->parseScopes($oauthSetting->scope ?? 'openid email profile'),
            'state' => $state,
        ];

        return $abstractProvider->getAuthorizationUrl($options);
    }

    /**
     * Handle OAuth callback and authenticate/create user.
     *
     * @throws IdentityProviderException
     */
    public function handleCallback(
        string $provider,
        string $code,
        string $redirectUri,
    ): ?User {
        $oauthSetting = $this->oauthSettingRepository->findByProvider($provider);
        if (!$oauthSetting?->enabled) {
            return null;
        }

        $providerConfig = $this->buildProviderConfig($oauthSetting);
        $abstractProvider = new GenericProvider($providerConfig);

        try {
            $accessToken = $abstractProvider->getAccessToken('authorization_code', [
                'code' => $code,
                'redirect_uri' => $redirectUri,
            ]);

            $resourceOwner = $abstractProvider->getResourceOwner($accessToken);
            $oauthData = $resourceOwner->toArray();

            return $this->authenticateOrCreateUser($provider, $oauthData, $accessToken);
        } catch (IdentityProviderException $e) {
            throw $e;
        }
    }

    /**
     * Refresh OAuth access token if needed.
     *
     * @throws IdentityProviderException
     */
    public function refreshAccessToken(UserOAuthAccount $oauthAccount): void
    {
        if ($oauthAccount->isTokenExpired() && $oauthAccount->refresh_token) {
            $oauthSetting = $this->oauthSettingRepository->findByProvider($oauthAccount->provider);
            if (!$oauthSetting) {
                return;
            }

            $providerConfig = $this->buildProviderConfig($oauthSetting);
            $abstractProvider = new GenericProvider($providerConfig);

            try {
                $newAccessToken = $abstractProvider->getAccessToken('refresh_token', [
                    'refresh_token' => $oauthAccount->refresh_token,
                ]);

                $oauthAccount->access_token = $newAccessToken->getToken();
                if ($newAccessToken->getRefreshToken()) {
                    $oauthAccount->refresh_token = $newAccessToken->getRefreshToken();
                }
                $oauthAccount->token_expires_at = $newAccessToken->getExpires();
                $oauthAccount->updated_at = new \DateTime('now');
            } catch (IdentityProviderException $e) {
                throw $e;
            }
        }
    }

    /**
     * Get available OAuth providers.
     */
    public function getAvailableProviders(): array
    {
        $enabled = $this->oauthSettingRepository->findAllEnabled();
        return array_map(fn(OAuthSetting $s) => $s->provider, $enabled);
    }

    /**
     * Check if OAuth is enabled globally.
     */
    public function isEnabled(): bool
    {
        return !empty($this->getAvailableProviders());
    }

    /**
     * Build provider configuration from OAuth settings.
     */
    private function buildProviderConfig(OAuthSetting $setting): array
    {
        return [
            'clientId' => $setting->client_id,
            'clientSecret' => $setting->client_secret,
            'urlAuthorize' => $setting->authorization_endpoint,
            'urlAccessToken' => $setting->token_endpoint,
            'urlResourceOwner' => $setting->userinfo_endpoint,
            'scopes' => $this->parseScopes($setting->scope ?? 'openid email profile'),
        ];
    }

    /**
     * Parse scope string into array.
     */
    private function parseScopes(string $scope): array
    {
        return array_filter(
            array_map('trim', explode(' ', $scope)),
            fn($s) => !empty($s)
        );
    }

    /**
     * Extract email from OAuth user data (supports common field names).
     */
    private function extractEmail(array $oauthData): ?string
    {
        foreach (['email', 'mail', 'user_email', 'primary_email'] as $field) {
            if (isset($oauthData[$field]) && is_string($oauthData[$field])) {
                return $oauthData[$field];
            }
        }

        return null;
    }

    /**
     * Extract user ID from OAuth data (supports common field names).
     */
    private function extractRemoteUserId(array $oauthData): string
    {
        foreach (['id', 'sub', 'user_id', 'uid'] as $field) {
            if (isset($oauthData[$field])) {
                return (string)$oauthData[$field];
            }
        }

        throw new LogicException('Unable to extract user ID from OAuth response');
    }

    /**
     * Extract name from OAuth data (supports common field names).
     */
    private function extractName(array $oauthData): ?string
    {
        foreach (['name', 'display_name', 'full_name', 'given_name'] as $field) {
            if (isset($oauthData[$field]) && is_string($oauthData[$field])) {
                return $oauthData[$field];
            }
        }

        return null;
    }

    /**
     * Extract avatar URL from OAuth data (supports common field names).
     */
    private function extractAvatarUrl(array $oauthData): ?string
    {
        foreach (['picture', 'avatar_url', 'avatar', 'profile_picture'] as $field) {
            if (isset($oauthData[$field]) && is_string($oauthData[$field])) {
                return $oauthData[$field];
            }
        }

        return null;
    }

    /**
     * Authenticate existing OAuth account or create new account/user.
     */
    private function authenticateOrCreateUser(
        string $provider,
        array $oauthData,
        AccessToken $accessToken,
    ): ?User {
        $remoteUserId = $this->extractRemoteUserId($oauthData);
        $email = $this->extractEmail($oauthData);

        // Try to find existing OAuth account
        $oauthAccount = $this->userOAuthAccountRepository->findByProviderAndRemoteId(
            $provider,
            $remoteUserId
        );

        if ($oauthAccount) {
            // Update OAuth account with latest data
            $oauthAccount->remote_email = $email;
            $oauthAccount->remote_name = $this->extractName($oauthData);
            $oauthAccount->remote_avatar_url = $this->extractAvatarUrl($oauthData);
            $oauthAccount->access_token = $accessToken->getToken();
            if ($accessToken->getRefreshToken()) {
                $oauthAccount->refresh_token = $accessToken->getRefreshToken();
            }
            $oauthAccount->token_expires_at = $accessToken->getExpires();
            $oauthAccount->updated_at = new \DateTime('now');

            return $oauthAccount->user;
        }

        // Try to find existing user by email
        $user = null;
        if ($email) {
            $user = $this->userRepository->findByEmail($email);
        }

        // Create new user if needed
        if (!$user) {
            if (!$email) {
                return null; // Cannot create account without email
            }

            $user = new User();
            $user->email = $email;
            $user->name = $this->extractName($oauthData) ?? $email;
        }

        // Create OAuth account
        $oauthAccount = new UserOAuthAccount();
        $oauthAccount->user = $user;
        $oauthAccount->provider = $provider;
        $oauthAccount->remote_user_id = $remoteUserId;
        $oauthAccount->remote_email = $email;
        $oauthAccount->remote_name = $this->extractName($oauthData);
        $oauthAccount->remote_avatar_url = $this->extractAvatarUrl($oauthData);
        $oauthAccount->access_token = $accessToken->getToken();
        if ($accessToken->getRefreshToken()) {
            $oauthAccount->refresh_token = $accessToken->getRefreshToken();
        }
        $oauthAccount->token_expires_at = $accessToken->getExpires();

        $user->oAuthAccounts->add($oauthAccount);

        return $user;
    }
}
