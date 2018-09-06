<?php

namespace Ruwler;

use Ruwler\Exception\ConfigurationException;
use Ruwler\Exception\ConnectionException;
use Ruwler\Exception\MissingArgumentException;
use Ruwler\Exception\RuwlerException;
use Psr\Log\LoggerInterface;
use Ruwler\Model\Request\SendTransactionalRequest;

/**
 * Class RuwlerSdk
 * @package Ruwler
 */
class RuwlerSdk
{
    private $settings = [
        'scheme' => 'https',
        'host' => 'api.ruwler.io',
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
     * @var
     */
    private $ch; // Curl handler

    /**
     * Ruwler constructor.
     *
     * @param array $options
     *
     * @throws ConfigurationException
     * @throws MissingArgumentException
     */
    public function __construct(array $options)
    {
        $this->checkCompatibility();

        if (!isset($options['api_key'])) {
            throw new MissingArgumentException('You must provide an api key');
        }

        $this->apiKey = $options['api_key'];

        foreach ($options as $key => $value) {
            // only set if valid setting/option
            if (isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }
    }

    /**
     * @param array $options
     *
     * @return array
     *
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws MissingArgumentException
     * @throws RuwlerException
     */
    public function createProject(array $options)
    {
        if (is_null($options['name'])) {
            throw new MissingArgumentException('You must provide the name of the campaign');
        }

        $body = [
            'name' => $options['name'],
        ];

        $ch = $this->createCurl('POST', '/projects', $body);
        $response = $this->execCurl($ch);

        return $response;
    }

    /**
     * @param SendTransactionalRequest $request
     * @throws ConfigurationException
     * @throws ConnectionException
     * @throws RuwlerException
     */
    public function sendTransactionalMail(SendTransactionalRequest $request)
    {
        $ch = $this->createCurl('POST', '/send_transactional', $request->getBody());
        $this->execCurl($ch);
    }

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

    /**
     * @param LoggerInterface $logger
     * @return RuwlerSdk
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param $msg
     * @param string $level
     * @return RuwlerSdk
     */
    protected function log($msg, $level = 'info'): self
    {
        if (false === is_null($this->logger)) {
            $this->logger->log($level, 'Ruwler API: '.$msg);
        }

        return $this;
    }

    /**
     * @param $request_method
     * @param $path
     * @param null  $body
     * @param array $query_params
     *
     * @return resource
     *
     * @throws ConfigurationException
     */
    protected function createCurl($request_method, $path, $body = null, $query_params = [])
    {
        $full_url = sprintf('%s://%s%s', $this->settings['scheme'], $this->settings['host'], $path);
        $query_string = http_build_query($query_params);
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
            'Content-Type: application/json',
            sprintf('Authorization: %s', $this->apiKey),
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->settings['timeout']);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request_method);

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

    /**
     * @param $ch
     *
     * @return array
     *
     * @throws ConnectionException
     * @throws RuwlerException
     */
    protected function execCurl($ch)
    {
        $response = [];

        $response['body'] = json_decode(curl_exec($ch), true);

        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $response['status'] = $status;

        // inform the user of a connection failure
        if (0 == $status || false === $response['body']) {
            throw new ConnectionException(curl_error($ch));
        }

        // or an error response from Chatkit
        if ($status >= 400) {
            $this->log('ERROR: execCurl error: '.print_r($response, true));
            throw (new RuwlerException($response['body']['hydra:description'], $status))->setBody($response['body']);
        }

        $this->log('INFO: execCurl response: '.print_r($response, true));

        return $response;
    }
}
