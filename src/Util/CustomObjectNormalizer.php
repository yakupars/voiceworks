<?php


namespace App\Util;


use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class CustomObjectNormalizer extends ObjectNormalizer
{
    public function normalize($object, string $format = null, array $context = [])
    {
        $normalizedWithNulls = parent::normalize($object, $format, $context);

        return array_filter($normalizedWithNulls, function ($property) {
            return $property !== null;
        });
    }
}