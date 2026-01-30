<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Api\Status;
use App\Entity\OAuthSetting;
use App\Entity\Repository\OAuthSettingRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractApiCrudController<OAuthSetting> */
#[
    OA\Get(
        path: '/admin/oauth-settings',
        operationId: 'listOAuthSettings',
        summary: 'List OAuth provider settings.',
        tags: [OpenApi::TAG_ADMIN_SETTINGS],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/admin/oauth-settings/{provider}',
        operationId: 'getOAuthSetting',
        summary: 'Get settings for a specific OAuth provider.',
        tags: [OpenApi::TAG_ADMIN_SETTINGS],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                description: 'OAuth provider name',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
            new OpenApi\Response\NotFound(),
        ]
    ),
    OA\Post(
        path: '/admin/oauth-settings',
        operationId: 'createOAuthSetting',
        summary: 'Create or update an OAuth provider setting.',
        tags: [OpenApi::TAG_ADMIN_SETTINGS],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: OAuthSetting::class)
        ),
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/admin/oauth-settings/{provider}',
        operationId: 'deleteOAuthSetting',
        summary: 'Delete an OAuth provider setting.',
        tags: [OpenApi::TAG_ADMIN_SETTINGS],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                description: 'OAuth provider name',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string'),
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
            new OpenApi\Response\NotFound(),
        ]
    )
]
final class OAuthSettingsController extends AbstractApiCrudController
{
    use EntityManagerAwareTrait;

    protected string $entityClass = OAuthSetting::class;

    public function __construct(
        private readonly OAuthSettingRepository $oauthSettingRepository,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $settings = $this->oauthSettingRepository->repository->findAll();
        return $response->withJson($this->toArray($settings));
    }

    public function getAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $provider = $params['provider'] ?? '';

        $setting = $this->oauthSettingRepository->findByProvider($provider);
        if (!$setting) {
            return $response->withStatus(404)->withJson(new Status(false, 'OAuth provider setting not found.'));
        }

        return $response->withJson($this->toArray($setting));
    }

    public function createAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $data = $request->getParsedBody();

        if (empty($data['provider'])) {
            return $response->withStatus(400)->withJson(new Status(false, 'Provider is required.'));
        }

        $provider = (string)$data['provider'];
        $setting = $this->oauthSettingRepository->getOrCreate($provider);

        $setting = $this->fromArray($data, $setting);

        $errors = $this->validator->validate($setting);
        if (count($errors) > 0) {
            return $response->withStatus(422)->withJson(
                new Status(false, 'Validation failed', array: (array)$errors)
            );
        }

        $this->em->persist($setting);
        $this->em->flush();

        return $response->withJson(new Status(true, 'OAuth setting updated.'));
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $provider = $params['provider'] ?? '';

        $setting = $this->oauthSettingRepository->findByProvider($provider);
        if (!$setting) {
            return $response->withStatus(404)->withJson(new Status(false, 'OAuth provider setting not found.'));
        }

        $this->em->remove($setting);
        $this->em->flush();

        return $response->withJson(new Status(true, 'OAuth setting deleted.'));
    }
}
