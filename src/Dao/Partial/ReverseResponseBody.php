<?php


namespace App\Dao\Partial;


use App\Dao\Common\Error;

class ReverseResponseBody
{
    private string $string = '';
    private string $reverse = '';
    private ?Error $error = null;

    /**
     * @return string
     */
    public function getString(): string
    {
        return $this->string;
    }

    /**
     * @param string $string
     * @return self
     */
    public function setString(string $string): self
    {
        $this->string = $string;
        return $this;
    }

    /**
     * @return string
     */
    public function getReverse(): string
    {
        return $this->reverse;
    }

    /**
     * @param string $reverse
     * @return self
     */
    public function setReverse(string $reverse): self
    {
        $this->reverse = $reverse;
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