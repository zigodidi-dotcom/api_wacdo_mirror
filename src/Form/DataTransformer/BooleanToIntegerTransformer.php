<?php
// src/Form/DataTransformer/BooleanToIntegerTransformer.php
namespace App\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class BooleanToIntegerTransformer implements DataTransformerInterface
{
    public function transform($value): mixed
    {
        // De l'entité (int) vers le formulaire (bool)
        return $value === 1;
    }

    public function reverseTransform($value): mixed
    {
        // Du formulaire (bool) vers l'entité (int)
        return $value ? 1 : 0;
    }
}
