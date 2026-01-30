<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth\OAuth;

use App\Auth\OAuth\OAuthService;
use App\Entity\OAuthSetting;
use App\Entity\Repository\OAuthSettingRepository;
use App\Entity\Repository\UserOAuthAccountRepository;
use App\Entity\Repository\UserRepository;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OAuthService
 */
class OAuthServiceTest extends TestCase
{
    private OAuthService $service;
    private OAuthSettingRepository $oauthSettingRepository;
    private UserOAuthAccountRepository $userOAuthAccountRepository;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        // Create mock repositories
        $this->oauthSettingRepository = $this->createMock(OAuthSettingRepository::class);
        $this->userOAuthAccountRepository = $this->createMock(UserOAuthAccountRepository::class);
        $this->userRepository = $this->createMock(UserRepository::class);

        // Create service instance
        $this->service = new OAuthService(
            $this->oauthSettingRepository,
            $this->userOAuthAccountRepository,
            $this->userRepository
        );
    }

    public function testGetAvailableProvidersReturnsEmptyArrayWhenNoneEnabled(): void
    {
        $this->oauthSettingRepository
            ->method('findAllEnabled')
            ->willReturn([]);

        $providers = $this->service->getAvailableProviders();

        $this->assertEmpty($providers);
    }

    public function testGetAvailableProvidersReturnsEnabledProviders(): void
    {
        $googleSetting = new OAuthSetting('google');
        $googleSetting->enabled = true;

        $githubSetting = new OAuthSetting('github');
        $githubSetting->enabled = true;

        $this->oauthSettingRepository
            ->method('findAllEnabled')
            ->willReturn([$googleSetting, $githubSetting]);

        $providers = $this->service->getAvailableProviders();

        $this->assertCount(2, $providers);
        $this->assertContains('google', $providers);
        $this->assertContains('github', $providers);
    }

    public function testIsEnabledReturnsFalseWhenNoProvidersAvailable(): void
    {
        $this->oauthSettingRepository
            ->method('findAllEnabled')
            ->willReturn([]);

        $this->assertFalse($this->service->isEnabled());
    }

    public function testIsEnabledReturnsTrueWhenProvidersAvailable(): void
    {
        $setting = new OAuthSetting('google');
        $setting->enabled = true;

        $this->oauthSettingRepository
            ->method('findAllEnabled')
            ->willReturn([$setting]);

        $this->assertTrue($this->service->isEnabled());
    }

    public function testGetAuthorizationUrlReturnsNullForDisabledProvider(): void
    {
        $this->oauthSettingRepository
            ->method('findByProvider')
            ->with('google')
            ->willReturn(null);

        $url = $this->service->getAuthorizationUrl('google', 'http://localhost/callback', 'state123');

        $this->assertNull($url);
    }

    public function testGetAuthorizationUrlReturnsNullForDisabledSetting(): void
    {
        $setting = new OAuthSetting('google');
        $setting->enabled = false;

        $this->oauthSettingRepository
            ->method('findByProvider')
            ->with('google')
            ->willReturn($setting);

        $url = $this->service->getAuthorizationUrl('google', 'http://localhost/callback', 'state123');

        $this->assertNull($url);
    }
}
