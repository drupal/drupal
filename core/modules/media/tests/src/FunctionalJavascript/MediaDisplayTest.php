<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media\Entity\Media;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Basic display tests for Media.
 *
 * @group media
 */
class MediaDisplayTest extends MediaJavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Install the optional configs from the standard profile.
    $extension_path = $this->container->get('extension.list.profile')->getPath('standard');
    $optional_install_path = $extension_path . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
    $storage = new FileStorage($optional_install_path);
    $this->container->get('config.installer')->installOptionalConfig($storage, '');
    // Reset all the static caches and list caches.
    $this->container->get('config.factory')->reset();

    // This test is going to test the display, so we need the standalone URL.
    \Drupal::configFactory()
      ->getEditable('media.settings')
      ->set('standalone_url', TRUE)
      ->save(TRUE);

    $this->container->get('router.builder')->rebuild();
  }

  /**
   * Tests basic media display.
   */
  public function testMediaDisplay() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $media_type = $this->createMediaType('test');

    // Create a media item.
    $media = Media::create([
      'bundle' => $media_type->id(),
      'name' => 'Fantastic!',
    ]);
    $media->save();

    $this->drupalGet('media/' . $media->id());
    // Verify the "name" field is really not present. The name should be in the
    // h1 with no additional markup in the h1.
    $assert_session->elementTextContains('css', 'h1', $media->getName());
    $assert_session->elementNotExists('css', 'h1 div');

    // Enable the field on the display and verify it becomes visible on the UI.
    $this->drupalGet("/admin/structure/media/manage/{$media_type->id()}/display");
    $assert_session->buttonExists('Show row weights')->press();
    $this->assertSession()->waitForElementVisible('css', '[name="fields[name][region]"]');
    $page->selectFieldOption('fields[name][region]', 'content');
    $assert_session->waitForElementVisible('css', '#edit-fields-name-settings-edit');
    $page->pressButton('Save');
    $this->drupalGet('media/' . $media->id());
    // Verify the name is present, and its text matches what is expected. Now
    // there should be markup in the h1.
    $assert_session->elementTextContains('xpath', '//h1/div/div[1]', 'Name');
    $assert_session->elementTextContains('xpath', '//h1/div/div[2]', $media->getName());

    // In the standard profile, there are some pre-cooked types. Make sure the
    // elements configured on their displays are the expected ones.
    $this->drupalGet('media/add/image');
    $image_media_name = 'example_1.jpeg';
    $page->attachFileToField('files[field_media_image_0]', $this->root . '/core/modules/media/tests/fixtures/' . $image_media_name);
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->fillField('field_media_image[0][alt]', 'Image Alt Text 1');
    $page->pressButton('Save');
    $image_media_id = $this->container
      ->get('entity_type.manager')
      ->getStorage('media')
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('mid', 'DESC')
      ->execute();
    $image_media_id = reset($image_media_id);

    // Go to the media entity view.
    $this->drupalGet('/media/' . $image_media_id);

    // Check if the default media name is generated as expected.
    $assert_session->elementTextContains('xpath', '//h1', $image_media_name);
    // Here we expect to see only the image, nothing else.
    // Assert only one element in the content region.
    $media_item = $assert_session->elementExists('xpath', '//div[@class="layout-content"]/div/div[2]');
    $assert_session->elementsCount('xpath', '/div', 1, $media_item);
    // Assert the image is present inside the media element.
    $media_image = $assert_session->elementExists('xpath', '//img', $media_item);
    // Assert that the image src uses the large image style, the label is
    // visually hidden, and there is no link to the image file.
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    $expected_image_src = $file_url_generator->generateString(\Drupal::token()->replace('public://styles/large/public/[date:custom:Y]-[date:custom:m]/example_1.jpeg'));
    $this->assertStringContainsString($expected_image_src, $media_image->getAttribute('src'));
    $field = $assert_session->elementExists('xpath', '/div[1]', $media_item);
    $assert_session->elementExists('xpath', '/div[@class="visually-hidden"]', $field);
    $assert_session->elementNotExists('xpath', '//a', $field);

    $test_filename = $this->randomMachineName() . '.txt';
    $test_filepath = 'public://' . $test_filename;
    file_put_contents($test_filepath, $this->randomMachineName());
    $this->drupalGet("media/add/document");
    $page->attachFileToField("files[field_media_document_0]", \Drupal::service('file_system')->realpath($test_filepath));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');

    // Go to the media entity view.
    $this->drupalGet($this->assertLinkToCreatedMedia());

    // Check if the default media name is generated as expected.
    $assert_session->elementTextContains('css', 'h1', $test_filename);
    // Here we expect to see only the linked filename.
    // Assert only one element in the content region.
    $media_item = $assert_session->elementExists('xpath', '//div[@class="layout-content"]/div/div[2]');
    $assert_session->elementsCount('xpath', '/div', 1, $media_item);
    // Assert the file link is present, and its text matches the filename.
    $link = $assert_session->elementExists('xpath', '//a', $media_item);
    $this->assertSame($test_filename, $link->getText());

    // Create a node type "page" to use as host entity.
    $node_type = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $node_type->save();

    // Reference the created media using an entity_reference field and make sure
    // the output is what we expect.
    $storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => 'field_related_media',
      'type' => 'entity_reference',
      'settings' => [
        'target_type' => 'media',
      ],
    ]);
    $storage->save();

    FieldConfig::create([
      'field_storage' => $storage,
      'entity_type' => 'node',
      'bundle' => $node_type->id(),
      'label' => 'Related media',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'image' => 'image',
          ],
        ],
      ],
    ])->save();

    \Drupal::service('entity_display.repository')->getViewDisplay('node', $node_type->id())
      ->setComponent('field_related_media', [
        'type' => 'entity_reference_entity_view',
        'label' => 'above',
        'settings' => [
          'view_mode' => 'full',
        ],
      ])->save();

    $node = Node::create([
      'title' => 'Host node',
      'type' => $node_type->id(),
      'field_related_media' => [
        'target_id' => $image_media_id,
      ],
    ]);
    $node->save();

    $this->drupalGet('/node/' . $node->id());
    // Media field (field_related_media) is there.
    $assert_session->pageTextContains('Related media');
    // Media name element is not there.
    $assert_session->pageTextNotContains($image_media_name);
    // Only one image is present.
    $assert_session->elementsCount('xpath', '//img', 1);
    // The image has the correct image style.
    $assert_session->elementAttributeContains('xpath', '//img', 'src', '/styles/large/');
  }

}
