<?php

namespace Drupal\layout_builder_test\ContextProvider;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;

/**
 * Defines a class for a fake context provider.
 */
class IHaveRuntimeContexts implements ContextProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    return [
      'runtime_contexts' => new Context(new ContextDefinition('string', 'Do you have runtime contexts?'), 'for sure you can'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return [
      'runtime_contexts' => new Context(new ContextDefinition('string', 'Do you have runtime contexts?')),
    ];
  }

}
