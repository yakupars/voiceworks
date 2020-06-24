<?php


namespace App\Dao\Partial;


class PingRequestBody
{
    private ?string $echo = null;

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
}