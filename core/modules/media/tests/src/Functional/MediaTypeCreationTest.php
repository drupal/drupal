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
  protected static $modules = [
    'media_test_source',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the media type creation form with only the mandatory options.
   *
   * @dataProvider providerMediaTypeCreationForm
   */
  public function testMediaTypeCreationForm($button_label, $address, $machine_name) {
    $this->drupalGet('/admin/structure/media/add');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('label')->setValue($this->randomString());
    $this->assertSession()->fieldExists('id')->setValue($machine_name);
    $this->assertSession()->selectExists('source')->selectOption('test');
    $this->assertSession()->buttonExists($button_label)->press();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals('Test config value', 'This is default value.');
    $this->assertSession()->buttonExists($button_label)->press();
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->addressEquals($address);

    $this->assertInstanceOf(MediaType::class, MediaType::load($machine_name));
  }

  /**
   * Data provider for testMediaTypeCreationForm().
   */
  public function providerMediaTypeCreationForm() {
    $machine_name = $this->randomMachineName();
    return [
      [
        'Save',
        'admin/structure/media',
        $machine_name,
      ],
      [
        'Save and manage fields',
        'admin/structure/media/manage/' . $machine_name . '/fields',
        $machine_name,
      ],
    ];
  }

}
