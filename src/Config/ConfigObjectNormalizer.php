<?php

namespace Droath\ProjectX\Config;

use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

/**
 * Define the configuration object normalizer
 */
class ConfigObjectNormalizer extends ObjectNormalizer
{
    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = array())
    {
        return array_filter(parent::normalize($object, $format, $context));
    }
}
