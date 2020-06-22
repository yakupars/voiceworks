<?php


namespace App\Service;


use Symfony\Component\Serializer\Serializer;

class XmlSerializer extends Serializer
{
    private ?Serializer $serializer;

    /**
     * XmlSerializer constructor.
     * @param array $encoders
     * @param array $normalizers
     */
    public function __construct(array $normalizers, array $encoders)
    {
        $this->serializer = parent::__construct($normalizers, $encoders);
    }
}