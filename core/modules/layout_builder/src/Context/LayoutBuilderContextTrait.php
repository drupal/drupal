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
   *
   * @deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use
   *   \Drupal\layout_builder\Context\LayoutBuilderContextTrait::getPopulatedContexts()
   *   instead.
   *
   * @see https://www.drupal.org/node/3195121
   */
  protected function getAvailableContexts(SectionStorageInterface $section_storage) {
    @trigger_error('\Drupal\layout_builder\Context\LayoutBuilderContextTrait::getAvailableContexts() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal\layout_builder\Context\LayoutBuilderContextTrait::getPopulatedContexts() instead. See https://www.drupal.org/node/3195121', E_USER_DEPRECATED);
    return self::getPopulatedContexts($section_storage);
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
