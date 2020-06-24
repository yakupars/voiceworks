<?php


namespace App\Dao\Partial;


class ReverseRequestBody
{
    private string $string = '';

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
}