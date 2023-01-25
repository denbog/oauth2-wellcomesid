<?php

namespace Denbog\Oauth2Wellcomesid\Provider;

use GuzzleHttp\Client;
use SplFileObject;
use UnexpectedValueException;

class DirectUpload
{
    const HOST = 'https://id.wellcomes.ru';

    protected $client;

    public function __construct() {
        $this->client = new Client([
            'base_uri' => static::HOST,
            'timeout'  => 2.0,
        ]);
    }

    public function upload(
        SplFileObject $file,
		string $fileName = ''
    ): string {   
        $params = [
            'blob' => [
                'filename' => $fileName ?: $file->getFilename(),
                'content_type' => mime_content_type($file->getRealPath()),
                'byte_size' => $file->getSize(),
                'checksum' => base64_encode(md5_file($file->getRealPath(), true))
            ]
        ];

        $content = $this->makeRequest(
            'POST', 
            '/storage/direct_uploads',
            [
                'json' => $params
            ]
        );

        $parsed = $this->parseJson($content);

        $this->makeRequest(
            'PUT', 
            $parsed['direct_upload']['url'],
            [
                'headers' => $parsed['direct_upload']['headers'],
                'body' => \GuzzleHttp\Psr7\Utils::tryFopen($file->getRealPath(), 'r')
            ]
        );

        return $parsed['signed_id'];
    }

    protected function makeRequest(
        string $method,
        string $url,
        array $params
    ): string {
        $response = $this->client->request(
            $method, 
            $url,
            $params
        );

        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new \Exception(sprintf(
                'Direct uploads: %s, status %d',
                $method,
                $response->getStatusCode()
            ));
        }

        return $response->getBody()->getContents();
    }

    protected function parseJson($content)
    {
        $content = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new UnexpectedValueException(sprintf(
                "Failed to parse JSON response: %s",
                json_last_error_msg()
            ));
        }

        return $content;
    }
}