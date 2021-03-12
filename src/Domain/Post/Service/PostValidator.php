<?php

namespace App\Domain\Post\Service;

use App\Factory\ValidationFactory;
use Cake\Validation\Validator;
use Selective\Validation\Exception\ValidationException;

/**
 * Service.
 */
final class PostValidator
{
    private ValidationFactory $validationFactory;

    /**
     * The constructor.
     *
     * @param ValidationFactory $validationFactory The validation
     */
    public function __construct(ValidationFactory $validationFactory)
    {
        $this->validationFactory = $validationFactory;
    }

    /**
     * Validate new post.
     *
     * @param array<mixed> $data The data
     *
     * @throws ValidationException
     *
     * @return void
     */
    public function validatePost(array $data): void
    {
        $validator = $this->createValidator();

        $validationResult = $this->validationFactory->createValidationResult(
            $validator->validate($data)
        );

        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
    }

    /**
     * Create validator.
     *
     * @return Validator The validator
     */
    private function createValidator(): Validator
    {
        $validator = $this->validationFactory->createValidator();

        return $validator
            ->notEmptyString('title', 'Input required')
            ->notEmptyString('body', 'Input required')
            ->minLength('title', 6, 'Too short')
            ->minLength('body', 6, 'Too short');
    }
}
