<?php

namespace Denbog\Oauth2Wellcomesid\Provider;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Token\AccessToken;
use Denbog\Oauth2Wellcomesid\Provider\Exception\WellcomesIdException;
use Denbog\Oauth2Wellcomesid\Provider\Exception\WellcomesIdLockedException;
use Psr\Http\Message\ResponseInterface;

class WellcomesId extends AbstractProvider
{
    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'id';
    const METHOD_PATCH = 'PATCH';

    protected $host = 'https://id.wellcomes.ru';


    public function getBaseAuthorizationUrl(): string
    {
        return $this->host.'/users/sign_in';
    }

    public function getBaseAccessTokenUrl(array $params): string
    {
        return $this->host.'/oauth/token';
    }

    public function getResourceOwnerDetailsUrl(AccessToken $token): string
    {
        return $this->host.'/api/v1/me';
    }

    public function getResourceOwnerUrl(): string
    {
        return $this->host.'/users';
    }

    protected function getDefaultScopes(): array
    {
        return [];
    }

    protected function getAuthorizationHeaders($token = null)
    {
        return [
            'Authorization' => 'Bearer ' . $token,
            'Accept' => '*/*'
        ];
    }

    protected function checkResponse(ResponseInterface $response, $data): void
    {
		if ($response->getStatusCode() == 423) {
			throw new WellcomesIdLockedException(
                $data['errorMessage'] ?? $response->getReasonPhrase(),
                $response->getStatusCode()
            );
		}
        if ($response->getStatusCode() >= 400) {
            throw new WellcomesIdException(
                $data['errorMessage'] ?? $response->getReasonPhrase(),
                $response->getStatusCode(),
                $data['errors'] ?? []
            );
        }
    }

    protected function createResourceOwner(
        array $response, 
        AccessToken $token
    ) {
        return new WellcomesIdResourceOwner($response);
    }

    public function updateResourceOwner(
        array $updateOwnerData, 
        AccessToken $token
    ) {
        $request = $this->getAuthenticatedRequest(
            self::METHOD_PATCH, 
            $this->getResourceOwnerUrl(), 
            $token,
            [
                'headers' => [
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'user' => $updateOwnerData
                ])
            ]
        );

        $this->getParsedResponse($request);
    }

    public function registerResourceOwner(
        array $newOwnerData
    ) {
        $newOwnerData = array_merge($newOwnerData, [
            'application_uid' => $this->clientId,
            'confirmation_callback_url' => $this->redirectUri
        ]);

        $request = $this->getRequest(
            self::METHOD_POST, 
            $this->getResourceOwnerUrl(), 
            [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'user' => $newOwnerData
                ])
            ]
        );

        $response = $this->getParsedResponse($request);

        if (!is_array($response)) {
            throw new \UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }

        return $response;
    }
}