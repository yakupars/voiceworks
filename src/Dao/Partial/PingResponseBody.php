<?php


namespace App\Dao\Partial;


use App\Dao\Common\Error;

class PingResponseBody
{
    private ?string $echo = null;
    private ?Error $error = null;

    /**
     * @return string|null
     */
    public function getEcho(): ?string
    {
        return $this->echo;
    }

    /**
     * @param string|null $echo
     * @return self
     */
    public function setEcho(?string $echo): self
    {
        $this->echo = $echo;
        return $this;
    }

    /**
     * @return Error|null
     */
    public function getError(): ?Error
    {
        return $this->error;
    }

    /**
     * @param Error|null $error
     * @return self
     */
    public function setError(?Error $error): self
    {
        $this->error = $error;
        return $this;
    }
}