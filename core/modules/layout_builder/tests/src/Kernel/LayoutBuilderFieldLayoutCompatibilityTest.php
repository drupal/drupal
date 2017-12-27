<?php

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\layout_builder\Section;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Ensures that Layout Builder and Field Layout are compatible with each other.
 *
 * @group layout_builder
 */
class LayoutBuilderFieldLayoutCompatibilityTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'layout_discovery',
    'field_layout',
  ];

  /**
   * The entity view display.
   *
   * @var \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface
   */
  protected $display;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('entity_test_base_field_display');
    $this->installConfig(['filter']);

    \Drupal::service('theme_handler')->install(['classy']);
    $this->config('system.theme')->set('default', 'classy')->save();

    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'entity_test_base_field_display',
      'field_name' => 'test_field_display_configurable',
      'type' => 'boolean',
    ]);
    $field_storage->save();
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'entity_test_base_field_display',
      'label' => 'FieldConfig with configurable display',
    ])->save();

    $this->display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test_base_field_display',
      'bundle' => 'entity_test_base_field_display',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $this->display
      ->setComponent('test_field_display_configurable', ['region' => 'content'])
      ->setLayoutId('layout_twocol')
      ->save();
  }

  /**
   * Tests the compatibility of Layout Builder and Field Layout.
   */
  public function testCompatibility() {
    // Create an entity with fields that are configurable and non-configurable.
    $entity_storage = $this->container->get('entity_type.manager')->getStorage('entity_test_base_field_display');
    // @todo Remove langcode workarounds after resolving
    //   https://www.drupal.org/node/2915034.
    $entity = $entity_storage->createWithSampleValues('entity_test_base_field_display', [
      'langcode' => 'en',
      'langcode_default' => TRUE,
    ]);
    $entity->save();

    // Ensure that the configurable field is shown in the correct region and
    // that the non-configurable field is shown outside the layout.
    $original_markup = $this->renderEntity($entity);
    $this->assertNotEmpty($this->cssSelect('.layout__region--first .field--name-test-display-configurable'));
    $this->assertNotEmpty($this->cssSelect('.layout__region--first .field--name-test-field-display-configurable'));
    $this->assertNotEmpty($this->cssSelect('.field--name-test-display-non-configurable'));
    $this->assertEmpty($this->cssSelect('.layout__region .field--name-test-display-non-configurable'));

    // Install the Layout Builder, configure it for this entity display, and
    // reload the entity.
    $this->installModule('layout_builder');
    $this->display = $this->reloadEntity($this->display);
    $this->display->setThirdPartySetting('layout_builder', 'allow_custom', TRUE)->save();
    $entity = $this->reloadEntity($entity);

    // Without using Layout Builder for an override, the result has not changed.
    $new_markup = $this->renderEntity($entity);
    $this->assertSame($original_markup, $new_markup);

    // Add a layout override.
    /** @var \Drupal\layout_builder\SectionStorageInterface $field_list */
    $field_list = $entity->layout_builder__layout;
    $field_list->appendSection(new Section('layout_onecol'));
    $entity->save();

    // The rendered entity has now changed. The non-configurable field is shown
    // outside the layout, the configurable field is not shown at all, and the
    // layout itself is rendered (but empty).
    $new_markup = $this->renderEntity($entity);
    $this->assertNotSame($original_markup, $new_markup);
    $this->assertEmpty($this->cssSelect('.field--name-test-display-configurable'));
    $this->assertEmpty($this->cssSelect('.field--name-test-field-display-configurable'));
    $this->assertNotEmpty($this->cssSelect('.field--name-test-display-non-configurable'));
    $this->assertNotEmpty($this->cssSelect('.layout--onecol'));

    // Removing the layout restores the original rendering of the entity.
    $field_list->removeItem(0);
    $entity->save();
    $new_markup = $this->renderEntity($entity);
    $this->assertSame($original_markup, $new_markup);
  }

  /**
   * Renders the provided entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to render.
   * @param string $view_mode
   *   (optional) The view mode that should be used to render the entity.
   * @param string $langcode
   *   (optional) For which language the entity should be rendered, defaults to
   *   the current content language.
   *
   * @return string
   *   The rendered string output (typically HTML).
   */
  protected function renderEntity(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    $view_builder = $this->container->get('entity_type.manager')->getViewBuilder($entity->getEntityTypeId());
    $build = $view_builder->view($entity, $view_mode, $langcode);
    return $this->render($build);
  }

}
