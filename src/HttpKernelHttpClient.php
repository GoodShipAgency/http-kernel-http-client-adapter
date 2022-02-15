<?php

namespace Mashbo\Components\HttpKernelHttpClient;

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
    public function __construct(
        private HttpKernelInterface $kernel,
        private HttpFoundationFactoryInterface $httpFoundationFactory,
        private HttpMessageFactoryInterface $httpMessageFactory,
        private ServerRequestFactory $serverRequestFactory
    ) {
    }

    /**
     * Sends a PSR-7 request.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \Http\Client\Exception If an error happens during processing the request.
     * @throws \Exception             If processing the request is impossible (eg. bad configuration).
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $serverRequest = $this->serverRequestFactory->convertToServerRequest($request);

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
            throw new HttpException("Kernel returned non-successful status code {$foundationResponse->getStatusCode()}", $request, $psrResponse);
        }
        return $psrResponse;
    }

}