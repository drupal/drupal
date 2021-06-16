<?php

namespace Drupal\layout_builder_test\Plugin\SectionStorage;

use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\Plugin\SectionStorage\SectionStorageBase;
use Drupal\layout_builder\Section;
use Drupal\layout_builder\SectionComponent;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides a test section storage that is controlled by state.
 *
 * @SectionStorage(
 *   id = "layout_builder_test_state",
 * )
 */
class TestStateBasedSectionStorage extends SectionStorageBase {

  /**
   * {@inheritdoc}
   */
  public function getSections() {
    // Return a custom section.
    $section = new Section('layout_onecol');
    $section->appendComponent(new SectionComponent('fake-uuid', 'content', [
      'id' => 'system_powered_by_block',
      'label' => 'Test block title',
      'label_display' => 'visible',
    ]));
    return [$section];
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable(RefinableCacheableDependencyInterface $cacheability) {
    $cacheability->mergeCacheMaxAge(0);
    return \Drupal::state()->get('layout_builder_test_state', FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function access($operation, AccountInterface $account = NULL, $return_as_object = FALSE) {}

  /**
   * {@inheritdoc}
   */
  protected function getSectionList() {}

  /**
   * {@inheritdoc}
   */
  public function getStorageId() {}

  /**
   * {@inheritdoc}
   */
  public function getSectionListFromId($id) {}

  /**
   * {@inheritdoc}
   */
  public function buildRoutes(RouteCollection $collection) {}

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {}

  /**
   * {@inheritdoc}
   */
  public function getLayoutBuilderUrl($rel = 'view') {}

  /**
   * {@inheritdoc}
   */
  public function extractIdFromRoute($value, $definition, $name, array $defaults) {}

  /**
   * {@inheritdoc}
   */
  public function deriveContextsFromRoute($value, $definition, $name, array $defaults) {}

  /**
   * {@inheritdoc}
   */
  public function label() {}

  /**
   * {@inheritdoc}
   */
  public function save() {}

}
