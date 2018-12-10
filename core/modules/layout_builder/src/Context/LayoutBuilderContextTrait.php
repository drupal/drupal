<?php

namespace Drupal\layout_builder\Context;

use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Provides a wrapper around getting contexts from a section storage object.
 */
trait LayoutBuilderContextTrait {

  /**
   * The context repository.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Gets the context repository service.
   *
   * @return \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   *   The context repository service.
   */
  protected function contextRepository() {
    if (!$this->contextRepository) {
      $this->contextRepository = \Drupal::service('context.repository');
    }
    return $this->contextRepository;
  }

  /**
   * Provides all available contexts, both global and section_storage-specific.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   The array of context objects.
   */
  protected function getAvailableContexts(SectionStorageInterface $section_storage) {
    // Get all globally available contexts that have a defined value.
    $contexts = array_filter($this->contextRepository()->getAvailableContexts(), function (ContextInterface $context) {
      return $context->hasContextValue();
    });

    // Add in the per-section_storage contexts.
    $contexts += $section_storage->getContextsDuringPreview();
    return $contexts;
  }

}
