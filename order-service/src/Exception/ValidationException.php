<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

final class ValidationException extends \RuntimeException
{
    public function __construct(
        private readonly ConstraintViolationListInterface $violations,
    ) {
        parent::__construct('Validation failed.');
    }

    /**
     * @return array<string, list<string>>
     */
    public function getErrors(): array
    {
        $errors = [];

        /** @var ConstraintViolationInterface $violation */
        foreach ($this->violations as $violation) {
            $propertyPath = $violation->getPropertyPath() ?: 'request';
            $errors[$propertyPath][] = $violation->getMessage();
        }

        return $errors;
    }
}
