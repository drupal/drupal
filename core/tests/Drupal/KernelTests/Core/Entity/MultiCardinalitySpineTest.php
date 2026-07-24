<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestMulRev;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Checks loading several multilingual multiple cardinality fields at once.
 */
#[Group('Entity')]
#[RunTestsInSeparateProcesses]
class MultiCardinalitySpineTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * Creates three unlimited fields on entity_test_mulrev, two translatable.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_mulrev');
    ConfigurableLanguage::createFromLangcode('de')->save();

    foreach (['a' => TRUE, 'b' => TRUE, 'c' => FALSE] as $name => $translatable) {
      FieldStorageConfig::create([
        'field_name' => "field_$name",
        'entity_type' => 'entity_test_mulrev',
        'type' => 'integer',
        'cardinality' => -1,
      ])->save();
      FieldConfig::create([
        'field_name' => "field_$name",
        'entity_type' => 'entity_test_mulrev',
        'bundle' => 'entity_test_mulrev',
        'translatable' => $translatable,
      ])->save();
    }
  }

  /**
   * Loads several multiple cardinality fields together.
   */
  public function testSpineLoad(): void {
    $entity = EntityTestMulRev::create([
      'langcode' => 'en',
      // Different counts per field would previously multiply into a cartesian
      // product of rows.
      'field_a' => [10, 11, 12, 13],
      'field_b' => [20],
      'field_c' => [30, 31, 32],
    ]);
    $entity->addTranslation('de', [
      'field_a' => [110, 111],
      'field_b' => [120, 121, 122],
    ]);
    $entity->save();
    $id = $entity->id();

    // Introduce a sparse delta: delete the middle value of field_a directly in
    // the dedicated table, leaving deltas 0, 2, 3. The highest cardinality
    // field would not contain delta 1 as a spine.
    \Drupal::database()->delete('entity_test_mulrev__field_a')
      ->condition('entity_id', $id)
      ->condition('langcode', 'en')
      ->condition('delta', 1)
      ->execute();

    $this->container->get('entity_type.manager')
      ->getStorage('entity_test_mulrev')
      ->resetCache();

    $loaded = EntityTestMulRev::load($id);
    $this->assertSame(['10', '12', '13'], array_column($loaded->get('field_a')->getValue(), 'value'));
    $this->assertSame(['20'], array_column($loaded->get('field_b')->getValue(), 'value'));
    $this->assertSame(['30', '31', '32'], array_column($loaded->get('field_c')->getValue(), 'value'));

    $de = $loaded->getTranslation('de');
    $this->assertSame(['110', '111'], array_column($de->get('field_a')->getValue(), 'value'));
    $this->assertSame(['120', '121', '122'], array_column($de->get('field_b')->getValue(), 'value'));
    // Non-translatable field shows the default translation values.
    $this->assertSame(['30', '31', '32'], array_column($de->get('field_c')->getValue(), 'value'));
  }

}
