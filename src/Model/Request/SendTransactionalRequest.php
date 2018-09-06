<?php

namespace Ruwler\Model\Request;


/**
 * Class SendTransactionalRequest
 * @package Ruwler\Model\Request
 */
class SendTransactionalRequest
{
    /**
     * @var string
     */
    private $email;

    /**
     * @var int
     */
    private $campaign;

    /**
     * @var int
     */
    private $campaignMessage;

    /**
     * @var array
     */
    private $message;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @return string
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return SendTransactionalRequest
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return int
     */
    public function getCampaign(): ?int
    {
        return $this->campaign;
    }

    /**
     * @param int $campaign
     * @return SendTransactionalRequest
     */
    public function setCampaign(int $campaign): self
    {
        $this->campaign = $campaign;

        return $this;
    }

    /**
     * @return int
     */
    public function getCampaignMessage(): ?int
    {
        return $this->campaignMessage;
    }

    /**
     * @param int $campaignMessage
     * @return SendTransactionalRequest
     */
    public function setCampaignMessage(int $campaignMessage): self
    {
        $this->campaignMessage = $campaignMessage;

        return $this;
    }

    /**
     * @return array
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return SendTransactionalRequest
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @return array
     */
    public function getMessage(): ?array
    {
        return $this->message;
    }

    /**
     * @param array $message
     * @return SendTransactionalRequest
     */
    public function setMessage(array $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getBody(): array
    {
        $body = [
            'email' => $this->getEmail(),
            'campaign' => '/campaigns/'. $this->getCampaign(),
            'data' => $this->getData(),
        ];

        if ($this->getCampaignMessage()) {
            $body['campaignMessage'] = '/campaign_messages/'. $this->getCampaignMessage();
        }

        if ($this->getMessage()) {
            $body['message'] = $this->getMessage();
        }

        return $body;
    }
}