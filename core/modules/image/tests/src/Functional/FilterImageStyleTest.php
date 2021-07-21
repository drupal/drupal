<?php

declare(strict_types = 1);

namespace Drupal\Tests\image\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\file\Entity\File;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests FilterImageStyle conversion of inline images to utilize image styles.
 *
 * @coversDefaultClass \Drupal\image\Plugin\Filter\FilterImageStyle
 *
 * @group image
 */
class FilterImageStyleTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'file', 'editor', 'node', 'image'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A text format allowing images and with FilterImageStyle applied.
   *
   * @var \Drupal\Filter\FilterFormatInterface
   */
  protected $format;

  /**
   * Tasks common to all tests.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->format = FilterFormat::create([
      'format' => $this->randomMachineName(),
      'name' => $this->randomString(),
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<img src alt data-entity-type data-entity-uuid data-align data-caption data-image-style width height>',
          ],
        ],
        'filter_image_style' => ['status' => TRUE],
        'editor_file_reference' => ['status' => TRUE],
      ],
    ]);
    $this->format->save();

    $user = $this->drupalCreateUser(['access content', 'administer nodes']);
    $this->drupalLogin($user);

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
  }

  /**
   * Helper function to create a test node with configurable image html.
   *
   * @param string $image_html
   *   The image HTML markup.
   */
  protected function nodeHelper(string $image_html): void {
    $node = $this->createNode([
      'type' => 'page',
      'title' => $this->randomString(),
      'body' => [
        [
          'format' => $this->format->id(),
          'value' => $image_html,
        ],
      ],
    ]);
    $node->save();

    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that images not uploaded through media module are unmolested.
   */
  public function testImageNoStyle(): void {
    $file_url = Url::fromUri('base:core/themes/stark/screenshot.png')->toString();

    $image_html = '<img src="' . $file_url . '" width="220">';
    $this->nodeHelper($image_html);

    /** @var \Behat\Mink\Element\NodeElement $img_element */
    $image_element = $this->getSession()->getPage()->find('css', 'img');
    $this->assertNotEmpty($image_element);

    $this->assertFalse($image_element->hasAttribute('class'));
    $this->assertSame($file_url, $image_element->getAttribute('src'));
    $this->assertSame('220', $image_element->getAttribute('width'));
    $this->assertFalse($image_element->hasAttribute('height'));
  }

  /**
   * Tests image style modification of images.
   */
  public function testImageStyle(): void {
    $this->assertArrayHasKey('medium', $this->container->get('entity_type.manager')->getStorage('image_style')->loadMultiple());

    $file = File::create(['uri' => 'core/themes/stark/screenshot.png']);
    $file->save();

    $image_html = '<img src="' . $file->createFileUrl() . '" data-entity-type="file" data-entity-uuid="' . $file->uuid() . '" data-image-style="medium" width="220">';
    $this->nodeHelper($image_html);

    /** @var \Behat\Mink\Element\NodeElement $img_element */
    $image_element = $this->getSession()->getPage()->find('css', 'img[data-entity-uuid="' . $file->uuid() . '"]');
    $this->assertNotEmpty($image_element);

    $this->assertStringContainsString('medium', $image_element->getAttribute('src'));
    $this->assertSame('220', $image_element->getAttribute('width'));
    $this->assertSame('164', $image_element->getAttribute('height'));
  }

}
