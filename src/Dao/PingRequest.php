<?php


namespace App\Dao;


use App\Contract\RequestDaoInterface;
use App\Dao\Common\Header;
use App\Dao\Partial\PingRequestBody;

class PingRequest implements RequestDaoInterface
{
    private Header $header;
    private PingRequestBody $body;

    public function __construct()
    {
        $this->header = new Header();
        $this->body = new PingRequestBody();
    }

    /**
     * @return Header
     */
    public function getHeader(): Header
    {
        return $this->header;
    }

    /**
     * @param Header $header
     * @return self
     */
    public function setHeader(Header $header): self
    {
        $this->header = $header;
        return $this;
    }

    /**
     * @return PingRequestBody
     */
    public function getBody(): PingRequestBody
    {
        return $this->body;
    }

    /**
     * @param PingRequestBody $body
     * @return self
     */
    public function setBody(PingRequestBody $body): self
    {
        $this->body = $body;
        return $this;
    }
}