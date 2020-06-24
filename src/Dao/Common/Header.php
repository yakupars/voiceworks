<?php


namespace App\Dao\Common;


class Header
{
    public string $type = '';
    private string $sender = '';
    private string $recipient = '';
    private string $reference = '';
    private string $timestamp;

    public function __construct()
    {
        $this->timestamp = gmdate(\DateTimeInterface::ATOM, time());
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return static
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string
     */
    public function getSender(): string
    {
        return $this->sender;
    }

    /**
     * @param string $sender
     * @return self
     */
    public function setSender(string $sender): self
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * @return string
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    /**
     * @param string $recipient
     * @return self
     */
    public function setRecipient(string $recipient): self
    {
        $this->recipient = $recipient;
        return $this;
    }

    /**
     * @return string
     */
    public function getReference(): string
    {
        return $this->reference;
    }

    /**
     * @param string $reference
     * @return self
     */
    public function setReference(string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    /**
     * @return string
     */
    public function getTimestamp(): string
    {
        return $this->timestamp;
    }

    /**
     * @param string $timestamp
     * @return self
     */
    public function setTimestamp(string $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }
}