<?php

/**
 * @file
 * Contains \Drupal\Tests\image\Kernel\ImageStyleIntegrationTest.
 */

namespace Drupal\Tests\image\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests the integration of ImageStyle with the core.
 *
 * @group image
 */
class ImageStyleIntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['image', 'file', 'field', 'system', 'user', 'node'];

  /**
   * Tests the dependency between ImageStyle and entity display components.
   */
  public function testEntityDisplayDependency() {
    // Create two image styles.
    /** @var \Drupal\image\ImageStyleInterface $style */
    $style = ImageStyle::create(['name' => 'main_style']);
    $style->save();
    /** @var \Drupal\image\ImageStyleInterface $replacement */
    $replacement = ImageStyle::create(['name' => 'replacement_style']);
    $replacement->save();

    // Create a node-type, named 'note'.
    $node_type = NodeType::create(['type' => 'note']);
    $node_type->save();

    // Create an image field and attach it to the 'note' node-type.
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'sticker',
      'type' => 'image',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'field_name' => 'sticker',
      'bundle' => 'note',
    ])->save();

    // Create the default entity view display and set the 'sticker' field to use
    // the 'main_style' images style in formatter.
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'note',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('sticker', ['settings' => ['image_style' => 'main_style']]);
    $view_display->save();

    // Create the default entity form display and set the 'sticker' field to use
    // the 'main_style' images style in the widget.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'note',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('sticker', ['settings' => ['preview_image_style' => 'main_style']]);
    $form_display->save();

    // Check that the entity displays exists before dependency removal.
    $this->assertNotNull(EntityViewDisplay::load($view_display->id()));
    $this->assertNotNull(EntityFormDisplay::load($form_display->id()));

    // Delete the 'main_style' image style. Before that, emulate the UI process
    // of selecting a replacement style by setting the replacement image style
    // ID in the image style storage.
    /** @var \Drupal\image\ImageStyleStorageInterface $storage */
    $storage = $this->container->get('entity.manager')->getStorage($style->getEntityTypeId());
    $storage->setReplacementId('main_style', 'replacement_style');
    $style->delete();

    // Check that the entity displays exists after dependency removal.
    $this->assertNotNull($view_display = EntityViewDisplay::load($view_display->id()));
    $this->assertNotNull($form_display = EntityFormDisplay::load($form_display->id()));
    // Check that the 'sticker' formatter component exists in both displays.
    $this->assertNotNull($formatter = $view_display->getComponent('sticker'));
    $this->assertNotNull($widget = $form_display->getComponent('sticker'));
    // Check that both displays are using now 'replacement_style' for images.
    $this->assertSame('replacement_style', $formatter['settings']['image_style']);
    $this->assertSame('replacement_style', $widget['settings']['preview_image_style']);

    // Delete the 'replacement_style' without setting a replacement image style.
    $replacement->delete();

    // The entity view and form displays exists after dependency removal.
    $this->assertNotNull($view_display = EntityViewDisplay::load($view_display->id()));
    $this->assertNotNull($form_display = EntityFormDisplay::load($form_display->id()));
    // The 'sticker' formatter component should be hidden in view display.
    $this->assertNull($view_display->getComponent('sticker'));
    $this->assertTrue($view_display->get('hidden')['sticker']);
    // The 'sticker' widget component should be active in form displays, but the
    // image preview should be disabled.
    $this->assertNotNull($widget = $form_display->getComponent('sticker'));
    $this->assertSame('', $widget['settings']['preview_image_style']);
  }

}
