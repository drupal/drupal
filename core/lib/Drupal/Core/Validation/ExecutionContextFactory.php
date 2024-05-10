<?php

namespace Drupal\Core\Validation;

use Symfony\Component\Validator\Context\ExecutionContextFactoryInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Defines an execution factory for the Symfony validator.
 *
 * We do not use the factory provided by Symfony as it is marked internal.
 */
class ExecutionContextFactory implements ExecutionContextFactoryInterface {

  /**
   * Constructs a new ExecutionContextFactory instance.
   *
   * @param \Drupal\Core\Validation\TranslatorInterface $translator
   *   The translator instance.
   * @param string|null $translationDomain
   *   (optional) The translation domain.
   */
  public function __construct(
    protected TranslatorInterface $translator,
    protected ?string $translationDomain = NULL,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public function createContext(ValidatorInterface $validator, mixed $root): ExecutionContextInterface {
    return new ExecutionContext(
      $validator,
      $root,
      $this->translator,
      $this->translationDomain
    );
  }

}
