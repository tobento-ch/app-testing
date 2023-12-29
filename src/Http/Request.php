<?php

/**
 * TOBENTO
 *
 * @copyright   Tobias Strub, TOBENTO
 * @license     MIT License, see LICENSE file distributed with this source code.
 * @author      Tobias Strub
 * @link        https://www.tobento.ch
 */

declare(strict_types=1);

namespace Tobento\App\Testing\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Stream;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

final class Request
{
    private ServerRequestInterface $request;
    
    /**
     * Create a new Request instance.
     *
     * @param string $method
     * @param string $uri
     * @param array $serverParams
     */
    public function __construct(
        private string $method,
        private string $uri,
        private array $serverParams = [],
    ) {
        $this->request = (new Psr17Factory())->createServerRequest(
            method: $method,
            uri: $uri,
            serverParams: $serverParams,
        );
    }
    
    /**
     * Set the body.
     *
     * @param array|object $data
     * @return static $this
     */
    public function body(array|object $data): static
    {
        $this->request = $data instanceof StreamInterface
            ? $this->request->withBody($data)
            : $this->request->withParsedBody($data);

        return $this;
    }
    
    /**
     * Set the body and sets JSON specific headers.
     *
     * @param array|StreamInterface $data
     * @return static $this
     */
    public function json(array|StreamInterface $data): static
    {
        if (!$data instanceof StreamInterface) {
            $data = Stream::create(json_encode($data));
        }
        
        $this->body($data);
        
        $this->request = $this->request
            ->withHeader('Content-Length', (string)$data->getSize())
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json');
            
        return $this;
    }
    
    /**
     * Set the query parameters.
     *
     * @param array $query
     * @return static $this
     */
    public function query(array $query): static
    {
        if ($this->request->getUri()->getQuery() !== '') {
            $uriQuery = [];
            parse_str($this->request->getUri()->getQuery(), $uriQuery);
            $query = array_merge($uriQuery, $query);
        } else {
            $this->request = $this->request->withUri($this->request->getUri()->withQuery(http_build_query($query)));
        }
        
        $this->request = $this->request->withQueryParams($query);
        
        return $this;
    }
    
    /**
     * Set the headers.
     *
     * @param array $headers
     * @return static $this
     */
    public function headers(array $headers): static
    {
        foreach($headers as $name => $value) {
            $this->request = $this->request->withHeader($name, $value);
        }
        
        return $this;
    }
    
    /**
     * Set the cookie params.
     *
     * @param array $cookies
     * @return static $this
     */
    public function cookies(array $cookies): static
    {
        $this->request = $this->request->withCookieParams($cookies);
        return $this;
    }
    
    /**
     * Set the uploaded files.
     *
     * @param array $files
     * @return static $this
     */
    public function files(array $files): static
    {
        $this->request = $this->request->withUploadedFiles($files);
        return $this;
    }

    /**
     * Returns the request
     *
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}