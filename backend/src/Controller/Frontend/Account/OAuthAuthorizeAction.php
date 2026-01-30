<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Auth\OAuth\OAuthService;
use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Mezzio\Session\SessionCookiePersistenceInterface;
use Psr\Http\Message\ResponseInterface;

final class OAuthAuthorizeAction implements SingleActionInterface
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly OAuthService $oauthService,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $provider = $params['provider'] ?? '';

        if (!$provider || !$this->oauthService->isEnabled()) {
            return $response->withStatus(404);
        }

        // Generate state token
        $state = bin2hex(random_bytes(16));

        // Store state in session
        $session = $request->getAttribute(SessionCookiePersistenceInterface::SESSION_ATTRIBUTE);
        $session->set('oauth_state_' . $provider, $state);

        // Get authorization URL
        $redirectUri = $request->getUri()->withPath('/oauth/callback/' . $provider)->__toString();
        $authUrl = $this->oauthService->getAuthorizationUrl($provider, $redirectUri, $state);

        if (!$authUrl) {
            return $response->withStatus(403);
        }

        return $response->withRedirect($authUrl);
    }
}
