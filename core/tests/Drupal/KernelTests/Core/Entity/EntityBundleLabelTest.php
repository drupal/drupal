<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests entity bundle label
 *
 * @coversDefaultClass \Drupal\Core\Entity\EntityTypeBundleInfo
 * @group Entity
 */
class EntityBundleLabelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test'];

  /**
   * @covers ::getAllBundleInfo
   * @see entity_test_entity_bundle_info_alter()
   * @group legacy
   */
  public function testLabelAltering(): void {
    $bundle_entity = EntityTestBundle::create([
      'id' => 'bundle_with_alterable_label',
      'label' => 'Alterable label',
    ]);
    $bundle_entity->save();
    $this->expectDeprecation('Using hook_entity_bundle_info_alter() to alter the label of bundles stored as config entities is deprecated in drupal:9.2.0 and is not permitted in drupal:10.0.0. Label altered bundles: bundle_with_alterable_label (entity_test_with_bundle). Use different methods to alter the label for bundles stored as config entities. See https://www.drupal.org/node/3186694');
    $bundle_info = \Drupal::service('entity_type.bundle.info')->getBundleInfo('entity_test_with_bundle')['bundle_with_alterable_label'];
    $this->assertNotSame($bundle_info['label'], $bundle_entity->label());
    $this->assertSame('Alterable label', $bundle_entity->label());
    $this->assertSame('Altered', $bundle_info['label']);
  }

}
