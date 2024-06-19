<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\Functional;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\File\FileExists;

/**
 * Test image upload access.
 *
 * @group ckeditor5
 * @internal
 */
class ImageUploadAccessTest extends ImageUploadTest {

  /**
   * Test access to the CKEditor 5 image upload controller.
   */
  public function testCkeditor5ImageUploadRoute(): void {
    $this->createBasicFormat();
    $url = $this->getUploadUrl();
    $test_image = file_get_contents(current($this->getTestFiles('image'))->uri);

    // With no text editor, expect a 404.
    $response = $this->uploadRequest($url, $test_image, 'test.jpg');
    $this->assertSame(404, $response->getStatusCode());

    $editor = $this->createEditorWithUpload(['status' => FALSE]);

    // Ensure that images cannot be uploaded when image upload is disabled.
    $response = $this->uploadRequest($url, $test_image, 'test.jpg');
    $this->assertSame(403, $response->getStatusCode());

    $editor->setImageUploadSettings([
      'status' => TRUE,
      'scheme' => 'public',
      'directory' => 'inline-images',
      'max_size' => NULL,
      'max_dimensions' => [
        'width' => NULL,
        'height' => NULL,
      ],
    ])->save();
    $response = $this->uploadRequest($url, $test_image, 'test.jpg');
    $this->assertSame(201, $response->getStatusCode());

    // Ensure lock failures are reported correctly.
    $d = 'public://inline-images/test.jpg';
    $f = $this->container->get('file_system')->getDestinationFilename($d, FileExists::Rename);
    $this->container->get('lock')
      ->acquire('file:ckeditor5:' . Crypt::hashBase64($f));
    $response = $this->uploadRequest($url, $test_image, 'test.jpg');
    $this->assertSame(503, $response->getStatusCode());
    $this->assertStringContainsString('File &quot;public://inline-images/test_0.jpg&quot; is already locked for writing.', (string) $response->getBody());

    // Ensure that users without permissions to the text format cannot upload
    // images.
    $this->drupalLogout();
    $response = $this->uploadRequest($url, $test_image, 'test.jpg');
    $this->assertSame(403, $response->getStatusCode());
  }

}
