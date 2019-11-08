<?php

namespace Drupal\Tests\media\Functional;

use Drupal\media\Entity\MediaType;

/**
 * Ensures that media UI works correctly without JavaScript.
 *
 * @group media
 */
class MediaTypeCreationTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'media_test_source',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the media type creation form with only the mandatory options.
   */
  public function testMediaTypeCreationForm() {
    $machine_name = mb_strtolower($this->randomMachineName());

    $this->drupalGet('/admin/structure/media/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('label')->setValue($this->randomString());
    $this->assertSession()->fieldExists('id')->setValue($machine_name);
    $this->assertSession()->selectExists('source')->selectOption('test');
    $this->assertSession()->buttonExists('Save')->press();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('Test config value', 'This is default value.');
    $this->assertSession()->buttonExists('Save')->press();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals('admin/structure/media');

    $this->assertInstanceOf(MediaType::class, MediaType::load($machine_name));
  }

}
