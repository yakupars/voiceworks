<?php


namespace App\Dao;


use App\Contract\ResponseDaoInterface;
use App\Dao\Common\Error;
use App\Dao\Common\Header;
use stdClass;

class NackResponse implements ResponseDaoInterface
{
    private Header $header;

    private stdClass $body;

    public function __construct()
    {
        $this->header = new Header();
        $this->body = new stdClass();
        $this->body->error = new Error();
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
     * @return stdClass
     */
    public function getBody(): stdClass
    {
        return $this->body;
    }

    /**
     * @param stdClass $body
     * @return self
     */
    public function setBody(stdClass $body): self
    {
        $this->body = $body;
        return $this;
    }
}