<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account;

use App\Auth\OAuth\OAuthService;
use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Mezzio\Session\SessionCookiePersistenceInterface;
use Psr\Http\Message\ResponseInterface;

final class OAuthCallbackAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
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

        // Get session
        $session = $request->getAttribute(SessionCookiePersistenceInterface::SESSION_ATTRIBUTE);

        // Check state token
        $stateKey = 'oauth_state_' . $provider;
        $expectedState = $session->get($stateKey);
        $actualState = $request->getQueryParams()['state'] ?? '';

        if (!$expectedState || $expectedState !== $actualState) {
            $session->unset($stateKey);
            return $response->withRedirect($request->getRouter()->named('login'))
                ->withStatus(302);
        }

        $session->unset($stateKey);

        // Check for error
        if ($error = $request->getQueryParams()['error'] ?? null) {
            return $response->withRedirect($request->getRouter()->named('login'))
                ->withStatus(302);
        }

        // Get authorization code
        $code = $request->getQueryParams()['code'] ?? '';
        if (!$code) {
            return $response->withRedirect($request->getRouter()->named('login'))
                ->withStatus(302);
        }

        try {
            $redirectUri = $request->getUri()->withPath('/oauth/callback/' . $provider)->__toString();
            $user = $this->oauthService->handleCallback($provider, $code, $redirectUri);

            if (!$user) {
                return $response->withRedirect($request->getRouter()->named('login'))
                    ->withStatus(302);
            }

            // Persist user and OAuth account
            $this->em->persist($user);
            foreach ($user->oAuthAccounts as $account) {
                $this->em->persist($account);
            }
            $this->em->flush();

            // Log in the user
            $auth = $request->getAuth();
            $auth->setUserID($user->id);

            // Mark login as complete (for 2FA purposes)
            $session->set('is_login_complete', true);

            return $response->withRedirect($request->getRouter()->named('dashboard'))
                ->withStatus(302);
        } catch (IdentityProviderException $e) {
            return $response->withRedirect($request->getRouter()->named('login'))
                ->withStatus(302);
        }
    }
}
