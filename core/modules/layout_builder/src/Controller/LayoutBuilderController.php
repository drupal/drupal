<?php

namespace Drupal\layout_builder\Controller;

use Drupal\Component\Assertion\Inspector;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\layout_builder\SectionStorageInterface;

/**
 * Defines a controller to provide the Layout Builder admin UI.
 *
 * @internal
 *   Controller classes are internal.
 */
class LayoutBuilderController {

  use StringTranslationTrait;

  /**
   * Provides a title callback.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   *
   * @return string
   *   The title for the layout page.
   */
  public function title(SectionStorageInterface $section_storage) {
    assert(Inspector::assertStringable($section_storage->label()), 'Section storage label is expected to be a string.');
    return $this->t('Edit layout for %label', ['%label' => $section_storage->label() ?? $section_storage->getStorageType() . ' ' . $section_storage->getStorageId()]);
  }

  /**
   * Renders the Layout UI.
   *
   * @param \Drupal\layout_builder\SectionStorageInterface $section_storage
   *   The section storage.
   *
   * @return array
   *   A render array.
   */
  public function layout(SectionStorageInterface $section_storage) {
    return [
      '#type' => 'layout_builder',
      '#section_storage' => $section_storage,
    ];
  }

}
