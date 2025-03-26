<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\link\LinkItemInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests link field widgets.
 *
 * @group link
 */
class LinkFieldWidgetTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'link',
  ];

  /**
   * Tests '#link_type' property exists on 'link_default' widget.
   *
   * Make sure the 'link_default' widget exposes a '#link_type' property on
   * its element. Modules can use it to understand if a text form element is
   * a link and also which LinkItemInterface::LINK_* is (EXTERNAL, GENERIC,
   * INTERNAL).
   */
  public function testLinkTypeOnLinkWidget(): void {
    $link_type = LinkItemInterface::LINK_EXTERNAL;
    $field_name = $this->randomMachineName();

    // Create a field with settings to validate.
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'link',
      'cardinality' => 1,
    ]);
    $fieldStorage->save();
    FieldConfig::create([
      'field_storage' => $fieldStorage,
      'label' => 'Read more about this entity',
      'bundle' => 'entity_test',
      'settings' => [
        'title' => DRUPAL_OPTIONAL,
        'link_type' => $link_type,
      ],
    ])->save();

    \Drupal::service('entity_display.repository')
      ->getFormDisplay('entity_test', 'entity_test')
      ->setComponent($field_name, ['type' => 'link_default'])
      ->save();

    $form = \Drupal::service('entity.form_builder')->getForm(EntityTest::create());
    $this->assertEquals($link_type, $form[$field_name]['widget'][0]['uri']['#link_type']);
  }

}
