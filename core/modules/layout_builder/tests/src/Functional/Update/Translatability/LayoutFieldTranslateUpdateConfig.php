<?php

namespace Drupal\Tests\layout_builder\Functional\Update\Translatability;

/**
 * A test case that updates 1 bundle's field but not both.
 *
 * @group layout_builder
 * @group legacy
 */
class LayoutFieldTranslateUpdateConfig extends MakeLayoutUntranslatableUpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $layoutBuilderTestCases = [
    'article' => [
      'has_translation' => TRUE,
      'has_layout' => TRUE,
      'nid' => 1,
      'vid' => 2,
    ],
    'page' => [
      'has_translation' => FALSE,
      'has_layout' => FALSE,
      'nid' => 4,
      'vid' => 5,
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected $expectedBundleUpdates = [
    'article' => FALSE,
    'page' => TRUE,
  ];

  /**
   * {@inheritdoc}
   */
  protected $expectedFieldStorageUpdate = FALSE;

}
