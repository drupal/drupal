<?php

declare(strict_types = 1);

namespace Drupal\Tests\image\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests the JavaScript functionality of the drupalimagestyle CKEditor plugin.
 *
 * @group image
 */
class AddImageTest extends WebDriverTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'file', 'node', 'image', 'ckeditor'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'filtered_html',
      'name' => $this->randomString(),
      'filters' => [
        'filter_image_style' => ['status' => TRUE],
      ],
    ])->save();

    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
    ])->save();

    $user = $this->drupalCreateUser([
      'access content',
      'administer nodes',
      'create page content',
      'use text format filtered_html',
    ]);
    $this->drupalLogin($user);
  }

  /**
   * Tests if an image can be placed inline with the data-image-style attribute.
   */
  public function testDataImageStyleElement(): void {
    $image_url = Url::fromUri('base:core/themes/bartik/screenshot.png')->toString();

    $this->drupalGet('node/add/page');
    $this->assertSession()->pageTextContains('Create Basic page');

    $page = $this->getSession()->getPage();
    // Wait for the ckeditor toolbar elements to appear (loading is done).
    $image_button_selector = 'a.cke_button__drupalimage';
    $this->assertJsCondition("jQuery('$image_button_selector').length > 0");

    $image_button = $page->find('css', $image_button_selector);
    $this->assertNotEmpty($image_button);
    $image_button->click();

    $url_input = $this->assertSession()->waitForField('attributes[src]');
    $this->assertNotEmpty($url_input);
    $url_input->setValue($image_url);

    $alt_input = $page->findField('attributes[alt]');
    $this->assertNotEmpty($alt_input);
    $alt_input->setValue('asd');

    $image_style_input_name = 'attributes[data-image-style]';
    $this->assertNotEmpty($page->findField($image_style_input_name));
    $page->selectFieldOption($image_style_input_name, 'thumbnail');

    // To prevent 403s on save, we re-set our request (cookie) state.
    $this->prepareRequest();

    // @todo: Switch to using NodeElement::click() on the button or
    // NodeElement::submit() on the form when #2831506 is fixed.
    // @see https://www.drupal.org/node/2831506
    $script = "jQuery('input[id^=\"edit-actions-save-modal\"]').click()";
    $this->getSession()->executeScript($script);
    $this->assertSession()->assertWaitOnAjaxRequest();

    $source_button = $page->find('css', 'a.cke_button__source');
    $this->assertNotEmpty($source_button);
    $source_button->click();

    $this->assertStringContainsString('data-image-style="thumbnail"', $page->find('css', 'textarea.cke_source')->getValue());
  }

}
