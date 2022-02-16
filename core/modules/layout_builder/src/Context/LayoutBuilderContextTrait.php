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
   * Returns all populated contexts, both global and section-storage-specific.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   The array of context objects.
   */
  protected function getPopulatedContexts(SectionStorageInterface $section_storage): array {
    // Get all known globally available contexts IDs.
    $available_context_ids = array_keys($this->contextRepository()->getAvailableContexts());
    // Filter to those that are populated.
    $contexts = array_filter($this->contextRepository()->getRuntimeContexts($available_context_ids), function (ContextInterface $context) {
      return $context->hasContextValue();
    });

    // Add in the per-section_storage contexts.
    $contexts += $section_storage->getContextsDuringPreview();
    return $contexts;
  }

}
