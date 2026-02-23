<?php

// src/Service/FormErrorHandler.php
namespace App\Service;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FormErrorHandler
{
    public function getFormErrors(FormInterface $form): array
    {
        $errors = [];
        foreach ($form->getErrors(true) as $error) {

            $errors[] = [
            'field' => $error->getOrigin() ? $error->getOrigin()->getName() : 'form',
            'message' => $error->getMessage(),
            ];
        }

         return $errors;
    }

    public function createErrorResponse(FormInterface $form, int $status): JsonResponse
    {
        return new JsonResponse(['errors' => $this->getFormErrors($form)], $status);
    }
}
