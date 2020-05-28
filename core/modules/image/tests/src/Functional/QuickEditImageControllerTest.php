<?php

namespace Drupal\Tests\image\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests the endpoints used by the "image" in-place editor.
 *
 * @group image
 */
class QuickEditImageControllerTest extends BrowserTestBase {

  use ImageFieldCreationTrait;
  use TestFileCreationTrait {
    getTestFiles as drupalGetTestFiles;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'image', 'quickedit'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The machine name of our image field.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * A user with permissions to edit articles and use Quick Edit.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $contentAuthorUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create the Article node type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Log in as a content author who can use Quick Edit and edit Articles.
    $this->contentAuthorUser = $this->drupalCreateUser([
      'access contextual links',
      'access in-place editing',
      'access content',
      'create article content',
      'edit any article content',
      'delete any article content',
    ]);
    $this->drupalLogin($this->contentAuthorUser);

    // Create a field with basic resolution validators.
    $this->fieldName = strtolower($this->randomMachineName());
    $field_settings = [
      'max_resolution' => '100x',
      'min_resolution' => '50x',
    ];
    $this->createImageField($this->fieldName, 'article', [], $field_settings);
  }

  /**
   * Tests that routes restrict access for un-privileged users.
   */
  public function testAccess() {
    // Create an anonymous user.
    $user = $this->createUser();
    $this->drupalLogin($user);

    // Create a test Node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => t('Test Node'),
    ]);
    $this->drupalGet('quickedit/image/info/node/' . $node->id() . '/' . $this->fieldName . '/' . $node->language()->getId() . '/default');
    $this->assertSession()->statusCodeEquals(403);

    /** @var \Symfony\Component\BrowserKit\Client $client */
    $client = $this->getSession()->getDriver()->getClient();
    $client->request('POST', '/quickedit/image/upload/node/' . $node->id() . '/' . $this->fieldName . '/' . $node->language()->getId() . '/default');
    $this->assertEquals('403', $client->getResponse()->getStatusCode());
  }

  /**
   * Tests that the field info route returns expected data.
   */
  public function testFieldInfo() {
    // Create a test Node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => t('Test Node'),
    ]);
    $json = $this->drupalGet('quickedit/image/info/node/' . $node->id() . '/' . $this->fieldName . '/' . $node->language()->getId() . '/default', ['query' => ['_format' => 'json']]);
    $info = Json::decode($json);
    // Assert that the default settings for our field are respected by our JSON
    // endpoint.
    $this->assertTrue($info['alt_field']);
    $this->assertFalse($info['title_field']);
  }

  /**
   * Tests that uploading a valid image works.
   */
  public function testValidImageUpload() {
    // Create a test Node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => t('Test Node'),
    ]);

    // We want a test image that is a valid size.
    $valid_image = FALSE;
    $image_factory = $this->container->get('image.factory');
    foreach ($this->drupalGetTestFiles('image') as $image) {
      $image_file = $image_factory->get($image->uri);
      if ($image_file->getWidth() > 50 && $image_file->getWidth() < 100) {
        $valid_image = $image;
        break;
      }
    }
    $this->assertNotFalse($valid_image);

    $this->drupalLogin($this->contentAuthorUser);
    $this->uploadImage($valid_image, $node->id(), $this->fieldName, $node->language()->getId());
    $this->assertStringContainsString('"fid":"1"', $this->getSession()->getPage()->getContent(), 'Valid upload completed successfully.');
  }

  /**
   * Tests that uploading a invalid image does not work.
   */
  public function testInvalidUpload() {
    // Create a test Node.
    $node = $this->drupalCreateNode([
      'type' => 'article',
      'title' => t('Test Node'),
    ]);

    // We want a test image that will fail validation.
    $invalid_image = FALSE;
    /** @var \Drupal\Core\Image\ImageFactory $image_factory */
    $image_factory = $this->container->get('image.factory');
    foreach ($this->drupalGetTestFiles('image') as $image) {
      /** @var \Drupal\Core\Image\ImageInterface $image_file */
      $image_file = $image_factory->get($image->uri);
      if ($image_file->getWidth() < 50 || $image_file->getWidth() > 100) {
        $invalid_image = $image;
        break;
      }
    }
    $this->assertNotFalse($invalid_image);

    $this->drupalLogin($this->contentAuthorUser);
    $this->uploadImage($invalid_image, $node->id(), $this->fieldName, $node->language()->getId());
    $this->assertStringContainsString('"main_error":"The image failed validation."', $this->getSession()->getPage()->getContent(), 'Invalid upload returned errors.');
  }

  /**
   * Uploads an image using the image module's Quick Edit route.
   *
   * @param object $image
   *   The image to upload.
   * @param int $nid
   *   The target node ID.
   * @param string $field_name
   *   The target field machine name.
   * @param string $langcode
   *   The langcode to use when setting the field's value.
   */
  public function uploadImage($image, $nid, $field_name, $langcode) {
    $filepath = $this->container->get('file_system')->realpath($image->uri);
    $path = 'quickedit/image/upload/node/' . $nid . '/' . $field_name . '/' . $langcode . '/default';

    $this->prepareRequest();
    $client = $this->getSession()->getDriver()->getClient();
    $client->request('POST', $this->buildUrl($path, []), [], ['files[image]' => $filepath]);
  }

}
