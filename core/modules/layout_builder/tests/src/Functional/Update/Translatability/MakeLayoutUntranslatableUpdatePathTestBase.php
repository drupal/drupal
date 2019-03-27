<?php

namespace Drupal\Tests\layout_builder\Functional\Update\Translatability;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\layout_builder\Plugin\SectionStorage\OverridesSectionStorage;

/**
 * Base class for upgrade path for translatable layouts.
 *
 * Each class that extends this class will test 1 case for including 2 content
 * types.
 *
 * This method of testing is used instead of a data provider method because test
 * fixtures do not have access to the provide data. This allows varying fixture
 * data per test case.
 *
 * @see layout_builder_post_update_make_layout_untranslatable()
 */
abstract class MakeLayoutUntranslatableUpdatePathTestBase extends UpdatePathTestBase {

  /**
   * Layout builder test cases.
   *
   * Keys are bundle names. Values are test cases including keys:
   *   - has_translation
   *   - has_layout
   *   - vid
   *   - nid
   *
   * @var array
   */
  protected $layoutBuilderTestCases;

  /**
   * Expectations of field updates by bundles.
   *
   * @var array
   */
  protected $expectedBundleUpdates;

  /**
   * Whether the field storage should be updated.
   *
   * @var bool
   */
  protected $expectedFieldStorageUpdate;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../../system/tests/fixtures/update/drupal-8.filled.standard.php.gz',
      __DIR__ . '/../../../../fixtures/update/layout-builder.php',
      __DIR__ . '/../../../../fixtures/update/layout-builder-field-schema.php',
      __DIR__ . '/../../../../fixtures/update/layout-builder-translation.php',
    ];
  }

  /**
   * Tests the upgrade path for translatable layouts.
   *
   * @see layout_builder_post_update_make_layout_untranslatable()
   */
  public function testDisableTranslationOnLayouts() {
    $this->runUpdates();
    foreach ($this->expectedBundleUpdates as $bundle => $field_update_expected) {
      $this->assertEquals(
        $field_update_expected,
        !FieldConfig::loadByName('node', $bundle, OverridesSectionStorage::FIELD_NAME)->isTranslatable(),
        $field_update_expected ? "Field on $bundle set to be non-translatable." : "Field on $bundle not set to non-translatable."
      );
    }

    $this->assertEquals(
      $this->expectedFieldStorageUpdate,
      !FieldStorageConfig::loadByName('node', OverridesSectionStorage::FIELD_NAME)->isTranslatable()
    );
  }

}
