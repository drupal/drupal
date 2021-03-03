<?php

namespace Drupal\Tests\image\FunctionalJavascript;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\Node;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the image field widget support multiple upload correctly.
 *
 * @group image
 */
class ImageFieldWidgetMultipleTest extends WebDriverTestBase {

  use ImageFieldCreationTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field_ui', 'image'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests image widget element support multiple upload correctly.
   */
  public function testWidgetElementMultipleUploads(): void {
    $image_factory = \Drupal::service('image.factory');
    $file_system = \Drupal::service('file_system');
    $web_driver = $this->getSession()->getDriver();

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $field_name = 'images';
    $storage_settings = ['cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED];
    $field_settings = ['alt_field_required' => 0];
    $this->createImageField($field_name, 'article', $storage_settings, $field_settings);
    $this->drupalLogin($this->drupalCreateUser(['access content', 'create article content']));
    $this->drupalGet('node/add/article');
    $this->xpath('//input[@name="title[0][value]"]')[0]->setValue('Test');

    $images = $this->getTestFiles('image');
    $images = array_slice($images, 0, 5);

    $paths = [];
    foreach ($images as $image) {
      $paths[] = $file_system->realpath($image->uri);
    }

    $remote_paths = [];
    foreach ($paths as $path) {
      $remote_paths[] = $web_driver->uploadFileAndGetRemoteFilePath($path);
    }

    $multiple_field = $this->xpath('//input[@multiple]')[0];
    $multiple_field->setValue(implode("\n", $remote_paths));
    $this->assertSession()->waitForElementVisible('css', '[data-drupal-selector="edit-images-4-preview"]');
    $this->getSession()->getPage()->findButton('Save')->click();

    $node = Node::load(1);
    foreach ($paths as $delta => $path) {
      $node_image = $node->{$field_name}[$delta];
      $original_image = $image_factory->get($path);
      $this->assertEquals($original_image->getWidth(), $node_image->width, "Correct width of image #$delta");
      $this->assertEquals($original_image->getHeight(), $node_image->height, "Correct height of image #$delta");
    }
  }

}
