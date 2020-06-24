<?php


namespace App\Dao;


use App\Contract\ResponseDaoInterface;
use App\Dao\Common\Header;
use App\Dao\Partial\PingResponseBody;

class PingResponse implements ResponseDaoInterface
{
    private Header $header;

    private PingResponseBody $body;

    public function __construct()
    {
        $this->header = new Header();
        $this->body = new PingResponseBody();
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
     * @return PingResponseBody
     */
    public function getBody(): PingResponseBody
    {
        return $this->body;
    }

    /**
     * @param PingResponseBody $body
     * @return self
     */
    public function setBody(PingResponseBody $body): self
    {
        $this->body = $body;
        return $this;
    }
}