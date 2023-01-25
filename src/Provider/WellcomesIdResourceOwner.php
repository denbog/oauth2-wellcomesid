<?php 

namespace Denbog\Oauth2Wellcomesid\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class WellcomesIdResourceOwner implements ResourceOwnerInterface
{
    protected $response;

    public function __construct(array $response = [])
    {
        $this->response = $response['user'];
    }

    public function getId()
    {
        return $this->response['id'] ?: null;
    }

    public function toArray()
    {
        return $this->response;
    }
}