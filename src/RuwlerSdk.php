<?php

namespace Ruwler;

use Ruwler\Exception\ConfigurationException;
use Ruwler\Exception\ConnectionException;
use Psr\Log\LoggerInterface;
use Ruwler\Exception\InvalidAuthModeException;
use Ruwler\Exception\InvalidFormatException;
use Ruwler\Exception\UnauthorizedException;
use Ruwler\Model\ApiResponse;
use Symfony\Component\HttpFoundation\Response;

class RuwlerSdk
{
    public const FORMAT_JSON_LD     = 'jsonld';
    public const FORMAT_JSON        = 'json';
    public const FORMAT_HTML        = 'html';

    public const AUTH_MODE_APIKEY   = 'apikey';
    public const AUTH_MODE_TOKEN    = 'token';

    public const MAX_ITEMS_PER_PAGE = 30;

    private $settings = [
        'host' => 'https://api.ruwler.io',
        'format' => self::FORMAT_JSON_LD,
        'timeout' => 30,
        'debug' => false,
        'auth_mode' => self::AUTH_MODE_APIKEY,
        'curl_options' => [],
    ];

    /**
     * @var string
     */
    private $apiKey;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     *  Curl handler
     */
    private $ch;

    public function __construct($apiKey = null, array $options = [], LoggerInterface $logger = null)
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
     *                  Security RESOURCE
     ************************************************************************/

    public function login(string $email, string $password): ApiResponse
    {
        return $this->send('POST', '/login_check', ['email' => $email, 'password' => $password]);
    }

    /************************************************************************
     *                  Tokens RESOURCE
     ************************************************************************/

    public function getTokens(array $filters = []): ApiResponse
    {
        return $this->send('GET', '/tokens', null, $filters);
    }

    public function createToken(array $content): ApiResponse
    {
        return $this->send('POST', '/tokens', $content);
    }

    public function getToken($tokenId): ApiResponse
    {
        return $this->send('GET', '/tokens/'. $tokenId);
    }

    public function updateToken($tokenId, array $content = []): ApiResponse
    {
        return $this->send('PUT', '/tokens/'. $tokenId, $content);
    }

    public function deleteToken($tokenId): ApiResponse
    {
        return $this->send('DELETE', '/tokens/'. $tokenId);
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

    public function getProjectHistory($projectId): ApiResponse
    {
        return $this->send('GET', '/projects/'. $projectId .'/history');
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
     *                  Campaigns RESOURCE
     ************************************************************************/

    public function getCampaigns(array $filters = []): ApiResponse
    {
        return $this->send('GET', '/campaigns', null, $filters);
    }

    public function createCampaign(array $content): ApiResponse
    {
        return $this->send('POST', '/campaigns', $content);
    }

    public function getCampaign($campaignId): ApiResponse
    {
        return $this->send('GET', '/campaigns/'. $campaignId);
    }

    public function getCampaignStatistics($campaignId): ApiResponse
    {
        return $this->send('GET', '/campaigns/'. $campaignId .'/statistics');
    }

    public function updateCampaign($campaignId, array $content = []): ApiResponse
    {
        return $this->send('PUT', '/campaigns/'. $campaignId, $content);
    }

    public function deleteCampaign($campaignId): ApiResponse
    {
        return $this->send('DELETE', '/campaigns/'. $campaignId);
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
     *                  CampaignChannels RESOURCE
     ************************************************************************/

    public function getCampaignChannels(array $filters = []): ApiResponse
    {
        return $this->send('GET', '/campaign_channels', null, $filters);
    }

    public function createCampaignChannel(array $content): ApiResponse
    {
        return $this->send('POST', '/campaign_channels', $content);
    }

    public function getCampaignChannel($campaignChannelId): ApiResponse
    {
        return $this->send('GET', '/campaign_channels/'. $campaignChannelId);
    }

    public function updateCampaignChannel($campaignChannelId, array $content = []): ApiResponse
    {
        return $this->send('PUT', '/campaign_channels/'. $campaignChannelId, $content);
    }

    public function deleteCampaignChannel($campaignChannelId): ApiResponse
    {
        return $this->send('DELETE', '/campaign_channels/'. $campaignChannelId);
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
     *                  Templates RESOURCE
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

    public function getTemplateStatistics($templateId): ApiResponse
    {
        return $this->send('GET', '/templates/'. $templateId .'/statistics');
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
     *                  Messages RESOURCE
     ************************************************************************/

    public function getMessages(array $filters = []): ApiResponse
    {
        return $this->send('GET', '/messages', null, $filters);
    }

    public function getMessage($messageId): ApiResponse
    {
        return $this->send('GET', '/messages/'. $messageId);
    }


    /************************************************************************
     *                  Contacts RESOURCE
     ************************************************************************/

    public function getContacts(array $filters = []): ApiResponse
    {
        return $this->send('GET', '/contacts', null, $filters);
    }

    public function getContact($contactId): ApiResponse
    {
        return $this->send('GET', '/contacts/'. $contactId);
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

        return $this->execCurl($ch);
    }

    protected function createCurl($method, $path, $body = null, $queryParams = [])
    {
        $full_url = sprintf('%s%s', $this->settings['host'], $path);
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
            sprintf('Authorization: %s', $this->getAuthHeader()),
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

        if ($apiResponse->getCode() === Response::HTTP_UNAUTHORIZED) {
            throw new UnauthorizedException();
        }

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

    public function setApiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    private function getAuthHeader(): string
    {
        switch ($this->settings['auth_mode']) {
            case self::AUTH_MODE_APIKEY:
                return $this->apiKey;
                break;
            case self::AUTH_MODE_TOKEN:
                return 'Bearer '. $this->apiKey;
                break;
        }

        throw new InvalidAuthModeException($this->settings['auth_mode']);
    }
}
