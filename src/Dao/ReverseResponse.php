<?php


namespace App\Dao;


use App\Contract\ResponseDaoInterface;
use App\Dao\Common\Header;
use App\Dao\Partial\ReverseResponseBody;

class ReverseResponse implements ResponseDaoInterface
{
    private Header $header;

    private ReverseResponseBody $body;

    public function __construct()
    {
        $this->header = new Header();
        $this->body = new ReverseResponseBody();
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
     * @return ReverseResponseBody
     */
    public function getBody(): ReverseResponseBody
    {
        return $this->body;
    }

    /**
     * @param ReverseResponseBody $body
     * @return self
     */
    public function setBody(ReverseResponseBody $body): self
    {
        $this->body = $body;
        return $this;
    }
}