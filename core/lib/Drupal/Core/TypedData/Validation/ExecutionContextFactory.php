<?php

namespace Drupal\Core\TypedData\Validation;

use Drupal\Core\Validation\ExecutionContextFactory as NewExecutionContextFactory;
use Drupal\Core\Validation\TranslatorInterface;

/**
 * Defines an execution factory for the Typed Data validator.
 *
 * We do not use the factory provided by Symfony as it is marked internal.
 */
class ExecutionContextFactory extends NewExecutionContextFactory {

  /**
   * Constructs a new ExecutionContextFactory instance.
   *
   * @param \Drupal\Core\Validation\TranslatorInterface $translator
   *   The translator instance.
   * @param string $translationDomain
   *   (optional) The translation domain.
   */
  public function __construct(TranslatorInterface $translator, $translationDomain = NULL) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:10.3.0 and is removed from drupal:11.0.0. Instead, use \Drupal\Core\Validation\ExecutionContextFactory. See https://www.drupal.org/node/3396238', E_USER_DEPRECATED);
    parent::__construct($translator, $translationDomain);
  }

}
