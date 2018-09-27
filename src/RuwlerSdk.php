<?php

namespace Ruwler;

use Ruwler\Exception\ConfigurationException;
use Ruwler\Exception\ConnectionException;
use Psr\Log\LoggerInterface;
use Ruwler\Exception\InvalidFormatException;
use Ruwler\Model\ApiResponse;

/**
 * Class RuwlerSdk
 * @package Ruwler
 */
class RuwlerSdk
{
    public const FORMAT_JSON_LD     = 'jsonld';
    public const FORMAT_JSON        = 'json';
    public const FORMAT_HTML        = 'html';

    private $settings = [
        'scheme' => 'https',
        'host' => 'api.ruwler.io',
        'format' => self::FORMAT_JSON_LD,
        'port' => 80,
        'timeout' => 30,
        'debug' => false,
        'curl_options' => [],
    ];

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var LoggerInterface
     */
    private $logger = null;

    /**
     *  Curl handler
     */
    private $ch;

    public function __construct($apiKey, array $options = [], LoggerInterface $logger = null)
    {
        $this->checkCompatibility();

        $this->apiKey = $apiKey;
        $this->logger = $logger;

        foreach ($options as $key => $value) {
            // only set if valid setting/option
            if (isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }
    }

    /************************************************************************
     *                  Projects RESOURCE
     ************************************************************************/

    public function getProjects(array $filters = []): ApiResponse
    {
        return $this->send('GET', '/projects', null, $filters);
    }

    public function createProject(array $content): ApiResponse
    {
        return $this->send('POST', '/projects', $content);
    }

    public function getProject($projectId): ApiResponse
    {
        return $this->send('GET', '/projects/'. $projectId);
    }

    public function updateProject($projectId, array $content = []): ApiResponse
    {
        return $this->send('PUT', '/projects/'. $projectId, $content);
    }

    public function deleteProject($projectId): ApiResponse
    {
        return $this->send('DELETE', '/projects/'. $projectId);
    }

    /************************************************************************
     *                  Channels RESOURCE
     ************************************************************************/

    public function getChannels(array $filters = []): ApiResponse
    {
        return $this->send('GET', '/channels', null, $filters);
    }

    public function createChannel(array $content): ApiResponse
    {
        return $this->send('POST', '/channels', $content);
    }

    public function getChannel($channelId): ApiResponse
    {
        return $this->send('GET', '/channels/'. $channelId);
    }

    public function updateChannel($channelId, array $content = []): ApiResponse
    {
        return $this->send('PUT', '/channels/'. $channelId, $content);
    }

    public function deleteChannel($channelId): ApiResponse
    {
        return $this->send('DELETE', '/channels/'. $channelId);
    }


    /************************************************************************
     *                  Providers RESOURCE
     ************************************************************************/

    public function getProviders(array $filters = []): ApiResponse
    {
        return $this->send('GET', '/providers', null, $filters);
    }

    public function getProvider($providerId): ApiResponse
    {
        return $this->send('GET', '/providers/'. $providerId);
    }


    /************************************************************************
     *                  Transactional RESOURCE
     ************************************************************************/

    public function sendTransactional(array $content): ApiResponse
    {
        return $this->send('POST', '/transactional', $content);
    }


    /************************************************************************
     *                  Templstes RESOURCE
     ************************************************************************/

    public function getTemplates(array $filters = []): ApiResponse
    {
        return $this->send('GET', '/templates', null, $filters);
    }

    public function createTemplate(array $content): ApiResponse
    {
        return $this->send('POST', '/templates', $content);
    }

    public function getTemplate($templateId): ApiResponse
    {
        return $this->send('GET', '/templates/'. $templateId);
    }

    public function updateTemplate($templateId, array $content = []): ApiResponse
    {
        return $this->send('PUT', '/templates/'. $templateId, $content);
    }

    public function deleteTemplate($templateId): ApiResponse
    {
        return $this->send('DELETE', '/templates/'. $templateId);
    }


    /************************************************************************
     *                  TOOLS
     ************************************************************************/

    /**
     * @throws ConfigurationException
     */
    protected function checkCompatibility()
    {
        if (!extension_loaded('curl')) {
            throw new ConfigurationException('The Ruwler library requires the PHP cURL module. Please ensure it is installed');
        }

        if (!extension_loaded('json')) {
            throw new ConfigurationException('The Ruwler library requires the PHP JSON module. Please ensure it is installed');
        }
    }

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    protected function log($msg, $level = 'info'): self
    {
        if (false === is_null($this->logger)) {
            $this->logger->log($level, 'Ruwler Sdk: '.$msg);
        }

        return $this;
    }

    public function send($method, $path, $body = null, $queryParams = []): ApiResponse
    {
        // Build and send CURL
        $ch = $this->createCurl($method, $path, $body, $queryParams);
        $response = $this->execCurl($ch);

        return $response;
    }

    protected function createCurl($method, $path, $body = null, $queryParams = [])
    {
        $full_url = sprintf('%s://%s%s', $this->settings['scheme'], $this->settings['host'], $path);
        $query_string = http_build_query($queryParams);
        $final_url = $full_url.'?'.$query_string;

        $this->log('createCurl( '.$final_url.' )');

        // Create or reuse existing curl handle
        if (null === $this->ch) {
            $this->ch = curl_init();
        }

        if (false === $this->ch) {
            throw new ConfigurationException('Could not initialise cURL!');
        }

        $ch = $this->ch;

        // curl handle is not reusable unless reset
        if (function_exists('curl_reset')) {
            curl_reset($ch);
        }

        // Set cURL opts and execute request
        curl_setopt($ch, CURLOPT_URL, $final_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            sprintf('Content-Type: %s', $this->getContentType()),
            sprintf('Accept: %s', $this->getContentType()),
            sprintf('Authorization: %s', $this->apiKey),
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->settings['timeout']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (!is_null($body)) {
            $json_encoded_body = json_encode($body);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json_encoded_body);
        }

        // Set custom curl options
        if (!empty($this->settings['curl_options'])) {
            foreach ($this->settings['curl_options'] as $option => $value) {
                curl_setopt($ch, $option, $value);
            }
        }

        return $ch;
    }

    protected function execCurl($ch): ApiResponse
    {
        $content = json_decode(curl_exec($ch), true);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // inform the user of a connection failure
        if (0 == $code || false === $content) {
            throw new ConnectionException(curl_error($ch));
        }

        $apiResponse = new ApiResponse($code, $content);

        $this->log('INFO: execCurl code: '.print_r($code, true));
        $this->log('INFO: execCurl body: '.print_r($content, true));

        return $apiResponse;
    }

    /**
     * @return string
     * @throws InvalidFormatException
     */
    protected function getContentType()
    {
        switch($this->settings['format']) {
            case self::FORMAT_JSON_LD:
                return 'application/ld+json';
                break;
            case self::FORMAT_JSON:
                return 'application/json';
            break;
            case self::FORMAT_HTML:
                return 'text/html';
            break;
            default:
                throw new InvalidFormatException();
        }
    }
}
