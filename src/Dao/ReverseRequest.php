<?php


namespace App\Dao;


use App\Contract\RequestDaoInterface;
use App\Dao\Common\Header;
use App\Dao\Partial\ReverseRequestBody;

class ReverseRequest implements RequestDaoInterface
{
    private Header $header;
    private ReverseRequestBody $body;

    public function __construct()
    {
        $this->header = new Header();
        $this->body = new ReverseRequestBody();
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
     * @return ReverseRequestBody
     */
    public function getBody(): ReverseRequestBody
    {
        return $this->body;
    }

    /**
     * @param ReverseRequestBody $body
     * @return self
     */
    public function setBody(ReverseRequestBody $body): self
    {
        $this->body = $body;
        return $this;
    }
}