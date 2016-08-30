<?php

namespace Mashbo\Components\SymfonyHttpClient;

use Http\Client\Exception\HttpException;
use Http\Client\HttpClient;
use Mashbo\Components\Psr7ServerRequestFactory\ServerRequestFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;

class HttpKernelHttpClient implements HttpClient
{
    /**
     * @var HttpKernelInterface
     */
    private $kernel;
    /**
     * @var HttpFoundationFactoryInterface
     */
    private $httpFoundationFactory;
    /**
     * @var HttpMessageFactoryInterface
     */
    private $httpMessageFactory;
    /**
     * @var ServerRequestFactory
     */
    private $serverRequestFactory;

    public function __construct(
        HttpKernelInterface $kernel,
        HttpFoundationFactoryInterface $httpFoundationFactory,
        HttpMessageFactoryInterface $httpMessageFactory,
        ServerRequestFactory $serverRequestFactory
    ) {
        $this->kernel = $kernel;
        $this->httpFoundationFactory = $httpFoundationFactory;
        $this->httpMessageFactory = $httpMessageFactory;
        $this->serverRequestFactory = $serverRequestFactory;
    }

    /**
     * Sends a PSR-7 request.
     *
     * @param RequestInterface $psrRequest
     *
     * @return ResponseInterface
     *
     * @throws \Http\Client\Exception If an error happens during processing the request.
     * @throws \Exception             If processing the request is impossible (eg. bad configuration).
     */
    public function sendRequest(RequestInterface $psrRequest)
    {
        $serverRequest = $this->serverRequestFactory->convertToServerRequest($psrRequest);

        $foundationRequest = $this->httpFoundationFactory->createRequest(
            $serverRequest
        );
        $foundationResponse = $this->kernel->handle($foundationRequest);

        if ($this->kernel instanceof Kernel) {
            $this->kernel->terminate($foundationRequest, $foundationResponse);
            $this->kernel->shutdown();
        }

        $psrResponse = $this->httpMessageFactory->createResponse($foundationResponse);

        if (!$foundationResponse->isSuccessful()) {
            echo $psrResponse->getBody();
            throw new HttpException("Kernel returned non-successful status code {$foundationResponse->getStatusCode()}", $psrRequest, $psrResponse);
        }
        return $psrResponse;
    }

}