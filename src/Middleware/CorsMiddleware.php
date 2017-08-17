<?php
declare(strict_types=1);

namespace Pac\CorsMiddleware\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Neomerx\Cors\Analyzer;
use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\AnalysisStrategyInterface;
use Neomerx\Cors\Strategies\Settings;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Zend\Diactoros\Response\JsonResponse;

class CorsMiddleware implements MiddlewareInterface
{
    private $config;
    /** @var LoggerInterface */
    private $logger;

    public function __construct(array $config, LoggerInterface $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $cors = $this->analyze($request);

        if ($this->logger) {
            switch ($cors->getRequestType()) {
                case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
                    $requestType = 'Out of CORS specification';

                    break;
                case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                    $requestType = 'Pre-flight';

                    break;
                case AnalysisResultInterface::TYPE_ACTUAL_REQUEST:
                    $requestType = 'Actual request';

                    break;
                case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
                    $requestType = 'origin is not allowed ';

                    break;
                case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
                    $requestType = 'method is not supported';

                    break;
                case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                    $requestType = 'headers are not supported';

                    break;
                case AnalysisResultInterface::ERR_NO_HOST_HEADER:
                    $requestType = 'No Host header in request';

                    break;
                default:
                    $requestType = 'Unknown';

                    break;
            }

            $this->logger->info("CORS Info");
            $this->logger->info("=========");
            $this->logger->info("Request Type: $requestType");
            $this->logger->info("=========");
        }

        switch ($cors->getRequestType()) {
            case AnalysisResultInterface::ERR_NO_HOST_HEADER:
            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                // todo: fit in with PAC error handing with proper error messages and headers
                return new JsonResponse([], 403);

            case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                $response = new JsonResponse([]);
                foreach ($cors->getResponseHeaders() as $header => $value) {
                    $response = $response->withHeader($header, $value);
                }

                return $response;

            case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
                return $delegate->process($request);

            default:
                $response = $delegate->process($request);
                foreach ($cors->getResponseHeaders() as $header => $value) {
                    $response = $response->withHeader($header, $value);
                }

                return $response;
        }
    }

    private function analyze(ServerRequestInterface $request): AnalysisResultInterface
    {
        $analyzer = Analyzer::instance($this->getCorsSettings($request));
        if ($this->logger) {
            $analyzer->setLogger($this->logger);
        }

        return $analyzer->analyze($request);
    }

    private function getCorsSettings(ServerRequestInterface $request): AnalysisStrategyInterface
    {
        $config = $this->prepareConfig($this->config['default']);

        $settings = (new Settings())
            ->setPreFlightCacheMaxAge($config['max_age'])
            ->setRequestAllowedHeaders($config['allow_headers'])
            ->setRequestAllowedMethods($config['allow_methods'])
            ->setRequestAllowedOrigins($config['allow_origin'])
            ->setRequestCredentialsSupported($config['allow_credentials'])
            ->setResponseExposedHeaders($config['expose_headers']);

        return $settings;
    }

    private function prepareConfig(array $configs = []): array
    {
        $defaultConfigs = [
            'allow_credentials' => false,
            'allow_origin' => [],
            'allow_headers' => [],
            'allow_methods' => [],
            'expose_headers' => [],
            'max_age' => 0,
        ];
        array_merge($defaultConfigs, $configs);

        $allowedOrigins = [];
        foreach ($configs['allow_origin'] as $origin) {
            $allowedOrigins[$origin] = true;
        }
        $configs['allow_origin'] = $allowedOrigins;

        $allowedMethods = [];
        foreach ($configs['allow_methods'] as $method) {
            $allowedMethods[$method] = true;
        }
        $configs['allow_methods'] = $allowedMethods;

        $allowedHeaders = [];
        foreach ($configs['allow_headers'] as $header) {
            $allowedHeaders[$header] = true;
        }
        $configs['allow_headers'] = $allowedHeaders;

        return $configs;
    }
}
