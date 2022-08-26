<?php

namespace Drupal\Tests\editor\Functional;

use Drupal\Core\Url;
use Drupal\editor\Entity\Editor;
use Drupal\Tests\BrowserTestBase;

/**
 * Test access to the editor dialog forms.
 *
 * @group editor
 */
class EditorDialogAccessTest extends BrowserTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['editor', 'filter', 'editor_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests access to the editor image dialog.
   */
  public function testEditorImageDialogAccess() {
    $url = Url::fromRoute('editor.image_dialog', ['editor' => 'plain_text']);
    $session = $this->assertSession();

    // With no text editor, expect a 404.
    $this->drupalGet($url);
    $session->statusCodeEquals(404);

    // With a text editor but without image upload settings, expect a 200, but
    // there should not be an input[type=file].
    $editor = Editor::create([
      'editor' => 'unicorn',
      'format' => 'plain_text',
      'image_upload' => [
        'status' => FALSE,
        'scheme' => 'public',
        'directory' => 'inline-images',
        'max_size' => '',
        'max_dimensions' => [
          'width' => 0,
          'height' => 0,
        ],
      ],
    ]);
    $editor->save();
    $this->resetAll();
    $this->drupalGet($url);
    $this->assertNotEmpty($this->cssSelect('input[type=text][name="attributes[src]"]'), 'Image uploads disabled: input[type=text][name="attributes[src]"] is present.');
    $this->assertEmpty($this->cssSelect('input[type=file]'), 'Image uploads disabled: input[type=file] is absent.');
    $session->statusCodeEquals(200);

    // With image upload settings, expect a 200, and now there should be an
    // input[type=file].
    $editor->setImageUploadSettings(['status' => TRUE] + $editor->getImageUploadSettings())
      ->save();
    $this->resetAll();
    $this->drupalGet($url);
    $this->assertEmpty($this->cssSelect('input[type=text][name="attributes[src]"]'), 'Image uploads enabled: input[type=text][name="attributes[src]"] is absent.');
    $this->assertNotEmpty($this->cssSelect('input[type=file]'), 'Image uploads enabled: input[type=file] is present.');
    $session->statusCodeEquals(200);
  }

}
