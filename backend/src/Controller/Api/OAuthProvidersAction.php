<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Auth\OAuth\OAuthService;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/auth/oauth/providers',
        operationId: 'getOAuthProviders',
        summary: 'Get available OAuth providers.',
        tags: ['Authentication'],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class OAuthProvidersAction implements SingleActionInterface
{
    public function __construct(
        private readonly OAuthService $oauthService,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $providers = $this->oauthService->getAvailableProviders();

        return $response->withJson([
            'oauth_enabled' => !empty($providers),
            'providers' => $providers,
        ]);
    }
}
