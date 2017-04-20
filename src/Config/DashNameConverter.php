<?php

namespace Droath\ProjectX\Config;

use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

/**
 * Define a dash name converter for the object normalizer.
 */
class DashNameConverter implements NameConverterInterface
{
   /**
    * {@inheritdoc}
    */
    public function normalize($propertyName)
    {
        return strtr($propertyName, '_', '-');
    }

   /**
    * {@inheritdoc}
    */
    public function denormalize($propertyName)
    {
        return strtr($propertyName, '-', '_');
    }
}
