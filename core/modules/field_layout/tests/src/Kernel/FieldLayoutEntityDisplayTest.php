<?php

namespace Drupal\Tests\field_layout\Kernel;

use Drupal\field_layout\Entity\FieldLayoutEntityViewDisplay;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\field_layout\Entity\FieldLayoutEntityDisplayTrait
 * @group field_layout
 */
class FieldLayoutEntityDisplayTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['layout_discovery', 'field_layout', 'entity_test', 'field_layout_test', 'system'];

  /**
   * @covers ::preSave
   * @covers ::calculateDependencies
   */
  public function testPreSave() {
    // Create an entity display with one hidden and one visible field.
    $entity_display = FieldLayoutEntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
      'content' => [
        'foo' => ['type' => 'visible'],
        'name' => ['type' => 'hidden', 'region' => 'content'],
      ],
      'hidden' => [
        'bar' => TRUE,
      ],
    ]);

    $expected = [
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
      'third_party_settings' => [
        'field_layout' => [
          'id' => 'layout_onecol',
          'settings' => [
            'label' => '',
          ],
        ],
      ],
      'id' => 'entity_test.entity_test.default',
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'content' => [
        'foo' => [
          'type' => 'visible',
        ],
      ],
      'hidden' => [
        'bar' => TRUE,
      ],
    ];
    $this->assertEntityValues($expected, $entity_display->toArray());

    // Save the display.
    // the 'content' property and the visible field has the default region set.
    $entity_display->save();

    // The dependencies have been updated.
    $expected['dependencies']['module'] = [
      'entity_test',
      'field_layout',
      'layout_discovery',
    ];
    // A third party setting is added by the entity_test module.
    $expected['third_party_settings']['entity_test'] = ['foo' => 'bar'];
    // The visible field is assigned the default region.
    $expected['content']['foo']['region'] = 'content';

    $this->assertEntityValues($expected, $entity_display->toArray());

    // Assign a new layout that has default settings and complex dependencies,
    // but do not save yet.
    $entity_display->setLayoutId('test_layout_main_and_footer');

    // The default settings were added.
    $expected['third_party_settings']['field_layout'] = [
      'id' => 'test_layout_main_and_footer',
      'settings' => [
        'setting_1' => 'Default',
      ],
    ];
    // The field was moved to the default region.
    $expected['content']['foo'] = [
      'type' => 'visible',
      'region' => 'main',
      'weight' => -4,
      'settings' => [],
      'third_party_settings' => [],
    ];
    $this->assertEntityValues($expected, $entity_display->toArray());

    // After saving, the dependencies have been updated.
    $entity_display->save();
    $expected['dependencies']['module'] = [
      'dependency_from_annotation',
      'dependency_from_calculateDependencies',
      'entity_test',
      'field_layout',
      'field_layout_test',
    ];
    $this->assertEntityValues($expected, $entity_display->toArray());

    // Assign a layout with provided settings.
    $entity_display->setLayoutId('test_layout_main_and_footer', ['setting_1' => 'foobar']);
    $entity_display->save();

    // The setting overrides the default value.
    $expected['third_party_settings']['field_layout']['settings']['setting_1'] = 'foobar';
    $this->assertEntityValues($expected, $entity_display->toArray());

    // Move a field to the non-default region.
    $component = $entity_display->getComponent('foo');
    $component['region'] = 'footer';
    $entity_display->setComponent('foo', $component);
    $entity_display->save();

    // The field region is saved.
    $expected['content']['foo']['region'] = 'footer';
    $this->assertEntityValues($expected, $entity_display->toArray());

    // Assign a different layout that shares the same non-default region.
    $entity_display->setLayoutId('test_layout_content_and_footer');
    $entity_display->save();

    // The dependencies have been updated.
    $expected['dependencies']['module'] = [
      'entity_test',
      'field_layout',
      'field_layout_test',
    ];
    // The layout has been updated.
    $expected['third_party_settings']['field_layout'] = [
      'id' => 'test_layout_content_and_footer',
      'settings' => [
        'label' => '',
      ],
    ];
    // The field remains in its current region instead of moving to the default.
    $this->assertEntityValues($expected, $entity_display->toArray());

    $this->container->get('module_installer')->uninstall(['field_layout']);

    $entity_storage = $this->container->get('entity_type.manager')->getStorage('entity_view_display');
    $entity_display = $entity_storage->load('entity_test.entity_test.default');

    // The dependencies have been updated.
    $expected['dependencies']['module'] = [
      'entity_test',
    ];
    // All field_layout settings were removed.
    unset($expected['third_party_settings']['field_layout']);
    // The field has returned to the default content region.
    $expected['content']['foo']['region'] = 'content';
    $this->assertEntityValues($expected, $entity_display->toArray());
  }

  /**
   * Asserts than an entity has the correct values.
   *
   * @param mixed $expected
   * @param array $values
   * @param string $message
   */
  public static function assertEntityValues($expected, array $values, $message = '') {

    static::assertArrayHasKey('uuid', $values);
    unset($values['uuid']);

    static::assertEquals($expected, $values, $message);
  }

}
