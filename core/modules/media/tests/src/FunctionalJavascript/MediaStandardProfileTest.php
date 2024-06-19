<?php

declare(strict_types=1);

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\media_test_oembed\Controller\ResourceController;
use Drupal\node\Entity\Node;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

// cspell:ignore Drupalin Hustlin Schipulcon

/**
 * Basic tests for Media configuration in the standard profile.
 *
 * @group media
 */
class MediaStandardProfileTest extends MediaJavascriptTestBase {

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'standard';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['media_test_oembed'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->lockHttpClientToFixtures();
    $this->hijackProviderEndpoints();
  }

  /**
   * Tests all media sources in one method.
   *
   * This prevents installing the standard profile for every test case and
   * increases the performance of this test.
   */
  public function testMediaSources(): void {
    // This test currently frequently causes the SQLite database to lock, so
    // skip the test on SQLite until the issue can be resolved.
    // @todo https://www.drupal.org/project/drupal/issues/3273626
    if (Database::getConnection()->driver() === 'sqlite') {
      $this->markTestSkipped('Test frequently causes a locked database on SQLite');
    }

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
      'bundle' => 'article',
      'label' => 'Related media',
      'settings' => [
        'handler_settings' => [
          'target_bundles' => [
            'audio' => 'audio',
            'document' => 'document',
            'image' => 'image',
            'remote_video' => 'remote_video',
            'video' => 'video',
          ],
        ],
      ],
    ])->save();

    $display = EntityViewDisplay::load('node.article.default');
    $display->setComponent('field_related_media', [
      'type' => 'entity_reference_entity_view',
      'settings' => [
        'view_mode' => 'full',
      ],
    ])->save();

    $this->audioTest();
    $this->documentTest();
    $this->imageTest();
    $this->remoteVideoTest();
    $this->videoTest();
  }

  /**
   * Tests the standard profile configuration for media type 'audio'.
   */
  protected function audioTest() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $source_field_id = 'field_media_audio_file';

    // Create 2 test files.
    $test_filename = $this->randomMachineName() . '.mp3';
    $test_filepath = 'public://' . $test_filename;
    $test_filename_updated = $this->randomMachineName() . '.mp3';
    $test_filepath_updated = 'public://' . $test_filename_updated;
    file_put_contents($test_filepath, str_repeat('t', 10));
    file_put_contents($test_filepath_updated, str_repeat('u', 10));

    // Check if the name field is properly hidden on the media form.
    $this->drupalGet('media/add/audio');
    $assert_session->fieldNotExists('name');

    // Check if the source field is available.
    $assert_session->fieldExists("files[{$source_field_id}_0]");

    // Create a media item.
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::service('file_system')->realpath($test_filepath));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');
    $audio_media_id = $this->container
      ->get('entity_type.manager')
      ->getStorage('media')
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('mid', 'DESC')
      ->execute();
    $audio_media_id = reset($audio_media_id);

    // Reference the created media using an entity_reference field and make sure
    // the output is what we expect.
    $node = Node::create([
      'title' => 'Host node',
      'type' => 'article',
      'field_related_media' => [
        'target_id' => $audio_media_id,
      ],
    ]);
    $node->save();

    $this->drupalGet('/node/' . $node->id());

    // Check if the default media name is generated as expected.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($audio_media_id);
    $this->assertSame($test_filename, $media->label());

    // Here we expect to see only the linked filename. Assert only one element
    // in the content region.
    $assert_session->elementsCount('css', 'div.media--type-audio > *', 1);

    // Assert the audio file is present inside the media element and that its
    // src attribute matches the audio file.
    $audio_element = $assert_session->elementExists('css', 'div.media--type-audio .field--name-field-media-audio-file audio > source');
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    $expected_audio_src = $file_url_generator->generateString(\Drupal::token()->replace('public://[date:custom:Y]-[date:custom:m]/' . $test_filename));
    $this->assertSame($expected_audio_src, $audio_element->getAttribute('src'));

    // Assert the media name is updated through the field mapping when changing
    // the source field.
    $this->drupalGet('media/' . $audio_media_id . '/edit');
    $page->pressButton('Remove');
    $result = $assert_session->waitForField("files[{$source_field_id}_0]");
    $this->assertNotEmpty($result);
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::service('file_system')->realpath($test_filepath_updated));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');

    $this->drupalGet('/node/' . $node->id());

    // Check if the default media name is updated as expected.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($audio_media_id);
    $this->assertSame($test_filename_updated, $media->label());

    // Again we expect to see only the linked filename. Assert only one element
    // in the content region.
    $assert_session->elementsCount('css', 'div.media--type-audio > *', 1);

    // Assert the audio file is present inside the media element and that its
    // src attribute matches the updated audio file.
    $audio_element = $assert_session->elementExists('css', 'div.media--type-audio .field--name-field-media-audio-file audio > source');
    $expected_audio_src = $file_url_generator->generateString(\Drupal::token()->replace('public://[date:custom:Y]-[date:custom:m]/' . $test_filename_updated));
    $this->assertSame($expected_audio_src, $audio_element->getAttribute('src'));
  }

  /**
   * Tests the standard profile configuration for media type 'image'.
   */
  protected function imageTest() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $source_field_id = 'field_media_image';

    // Check if the name field is properly hidden on the media form.
    $this->drupalGet('media/add/image');
    $assert_session->fieldNotExists('name');

    // Check if the source field is available.
    $assert_session->fieldExists("files[{$source_field_id}_0]");

    // Create a media item.
    $image_media_name = 'example_1.jpeg';
    $page->attachFileToField("files[{$source_field_id}_0]", $this->root . '/core/modules/media/tests/fixtures/' . $image_media_name);
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->fillField("{$source_field_id}[0][alt]", 'Image Alt Text 1');
    $page->pressButton('Save');
    $image_media_id = $this->container
      ->get('entity_type.manager')
      ->getStorage('media')
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('mid', 'DESC')
      ->execute();
    $image_media_id = reset($image_media_id);

    // Reference the created media using an entity_reference field and make sure
    // the output is what we expect.
    $node = Node::create([
      'title' => 'Host node',
      'type' => 'article',
      'field_related_media' => [
        'target_id' => $image_media_id,
      ],
    ]);
    $node->save();

    $this->drupalGet('/node/' . $node->id());

    // Check if the default media name is generated as expected.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($image_media_id);
    $this->assertSame($image_media_name, $media->label());

    // Here we expect to see only the image, nothing else. Assert only one
    // element in the content region.
    $assert_session->elementsCount('css', 'div.media--type-image > *', 1);

    // Assert the image element is present inside the media element and that its
    // src attribute uses the large image style, the label is visually hidden,
    // and there is no link to the image file.
    $image_element = $assert_session->elementExists('css', 'div.media--type-image img');
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    $expected_image_src = $file_url_generator->generateString(\Drupal::token()->replace('public://styles/large/public/[date:custom:Y]-[date:custom:m]/' . $image_media_name));

    $this->assertStringContainsString($expected_image_src, $image_element->getAttribute('src'));
    $assert_session->elementExists('css', '.field--name-field-media-image .field__label.visually-hidden');
    $assert_session->elementNotExists('css', '.field--name-field-media-image a');

    // Assert the media name is updated through the field mapping when changing
    // the source field.
    $this->drupalGet('media/' . $image_media_id . '/edit');
    $page->pressButton('Remove');
    $result = $assert_session->waitForField("files[{$source_field_id}_0]");
    $this->assertNotEmpty($result);
    $image_media_name_updated = 'example_2.jpeg';
    $page->attachFileToField("files[{$source_field_id}_0]", $this->root . '/core/modules/media/tests/fixtures/' . $image_media_name_updated);
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->fillField("{$source_field_id}[0][alt]", 'Image Alt Text 2');
    $page->pressButton('Save');

    $this->drupalGet('/node/' . $node->id());

    // Check if the default media name is updated as expected.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($image_media_id);
    $this->assertSame($image_media_name_updated, $media->label());

    // Again we expect to see only the image, nothing else. Assert only one
    // element in the content region.
    $assert_session->elementsCount('css', 'div.media--type-image > *', 1);

    // Assert the image element is present inside the media element and that its
    // src attribute uses the large image style, the label is visually hidden,
    // and there is no link to the image file.
    $image_element = $assert_session->elementExists('css', 'div.media--type-image img');
    $expected_image_src = $file_url_generator->generateString(\Drupal::token()->replace('public://styles/large/public/[date:custom:Y]-[date:custom:m]/' . $image_media_name_updated));
    $this->assertStringContainsString($expected_image_src, $image_element->getAttribute('src'));
    $assert_session->elementExists('css', '.field--name-field-media-image .field__label.visually-hidden');
    $assert_session->elementNotExists('css', '.field--name-field-media-image a');
  }

  /**
   * Tests the standard profile configuration for media type 'document'.
   */
  protected function documentTest() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $source_field_id = 'field_media_document';

    // Create 2 test files.
    $test_filename = $this->randomMachineName() . '.txt';
    $test_filepath = 'public://' . $test_filename;
    $test_filename_updated = $this->randomMachineName() . '.txt';
    $test_filepath_updated = 'public://' . $test_filename_updated;
    file_put_contents($test_filepath, $this->randomMachineName());
    file_put_contents($test_filepath_updated, $this->randomMachineName());

    // Check if the name field is properly hidden on the media form.
    $this->drupalGet('media/add/document');
    $assert_session->fieldNotExists('name');

    // Check if the source field is available.
    $assert_session->fieldExists("files[{$source_field_id}_0]");

    // Create a media item.
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::service('file_system')->realpath($test_filepath));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');
    $file_media_id = $this->container
      ->get('entity_type.manager')
      ->getStorage('media')
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('mid', 'DESC')
      ->execute();
    $file_media_id = reset($file_media_id);

    // Reference the created media using an entity_reference field and make sure
    // the output is what we expect.
    $node = Node::create([
      'title' => 'Host node',
      'type' => 'article',
      'field_related_media' => [
        'target_id' => $file_media_id,
      ],
    ]);
    $node->save();

    $this->drupalGet('/node/' . $node->id());

    // Check if the default media name is generated as expected.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($file_media_id);
    $this->assertSame($test_filename, $media->label());

    // Here we expect to see only the linked filename. Assert only one element
    // in the content region.
    $assert_session->elementsCount('css', 'div.media--type-document > *', 1);

    // Assert the file link is present in the media element and its text matches
    // the filename.
    $link_element = $assert_session->elementExists('css', 'div.media--type-document .field--name-field-media-document a');
    $this->assertSame($test_filename, $link_element->getText());

    // Assert the media name is updated through the field mapping when changing
    // the source field.
    $this->drupalGet('media/' . $file_media_id . '/edit');
    $page->pressButton('Remove');
    $result = $assert_session->waitForField("files[{$source_field_id}_0]");
    $this->assertNotEmpty($result);
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::service('file_system')->realpath($test_filepath_updated));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');

    $this->drupalGet('/node/' . $node->id());

    // Check if the default media name is updated as expected.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($file_media_id);
    $this->assertSame($test_filename_updated, $media->label());

    // Again we expect to see only the linked filename. Assert only one element
    // in the content region.
    $assert_session->elementsCount('css', 'div.media--type-document > *', 1);

    // Assert the file link is present in the media element and its text matches
    // the updated filename.
    $link_element = $assert_session->elementExists('css', 'div.media--type-document .field--name-field-media-document a');
    $this->assertSame($test_filename_updated, $link_element->getText());
  }

  /**
   * Tests the standard profile configuration for media type 'remote_video'.
   */
  protected function remoteVideoTest() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $source_field_id = 'field_media_oembed_video';

    // Set video fixtures.
    $video_title = 'Drupal Rap Video - Schipulcon09';
    $video_url = 'https://vimeo.com/7073899';
    ResourceController::setResourceUrl($video_url, $this->getFixturesDirectory() . '/video_vimeo.json');
    $video_title_updated = "Everyday I'm Drupalin' Drupal Rap (Rick Ross - Hustlin)";
    $video_url_updated = 'https://www.youtube.com/watch?v=PWjcqE3QKBg';
    ResourceController::setResourceUrl($video_url_updated, $this->getFixturesDirectory() . '/video_youtube.json');

    // Check if the name field is properly hidden on the media form.
    $this->drupalGet('media/add/remote_video');
    $assert_session->fieldNotExists('name');

    // Check if the source field is available.
    $assert_session->fieldExists("{$source_field_id}[0][value]");

    // Create a media item.
    $page->fillField("{$source_field_id}[0][value]", $video_url);
    $page->pressButton('Save');
    $remote_video_media_id = $this->container
      ->get('entity_type.manager')
      ->getStorage('media')
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('mid', 'DESC')
      ->execute();
    $remote_video_media_id = reset($remote_video_media_id);

    // Reference the created media using an entity_reference field and make sure
    // the output is what we expect.
    $node = Node::create([
      'title' => 'Host node',
      'type' => 'article',
      'field_related_media' => [
        'target_id' => $remote_video_media_id,
      ],
    ]);
    $node->save();

    $this->drupalGet('/node/' . $node->id());

    // Check if the default media name is generated as expected.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($remote_video_media_id);
    $this->assertSame($video_title, $media->label());

    // Here we expect to see only the video iframe. Assert only one element in
    // the content region.
    $assert_session->elementsCount('css', 'div.media--type-remote-video > *', 1);

    // Assert the iframe is present in the media element and its src attribute
    // matches the URL and query parameters.
    $iframe_url = $assert_session->elementExists('css', 'div.media--type-remote-video .field--name-field-media-oembed-video iframe')->getAttribute('src');
    $iframe_url = parse_url($iframe_url);
    $this->assertStringEndsWith('/media/oembed', $iframe_url['path']);
    $this->assertNotEmpty($iframe_url['query']);
    $query = [];
    parse_str($iframe_url['query'], $query);
    $this->assertSame($video_url, $query['url']);
    $this->assertNotEmpty($query['hash']);

    // Assert the media name is updated through the field mapping when changing
    // the source field.
    $this->drupalGet('media/' . $remote_video_media_id . '/edit');
    $page->fillField("{$source_field_id}[0][value]", $video_url_updated);
    $page->pressButton('Save');

    $this->drupalGet('/node/' . $node->id());

    // Check if the default media name is updated as expected.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($remote_video_media_id);
    $this->assertSame($video_title_updated, $media->label());

    // Again we expect to see only the video iframe. Assert only one element in
    // the content region.
    $assert_session->elementsCount('css', 'div.media--type-remote-video > *', 1);

    // Assert the iframe is present in the media element and its src attribute
    // matches the updated URL and query parameters.
    $iframe_url = $assert_session->elementExists('css', 'div.media--type-remote-video .field--name-field-media-oembed-video iframe')->getAttribute('src');
    $iframe_url = parse_url($iframe_url);
    $this->assertStringEndsWith('/media/oembed', $iframe_url['path']);
    $this->assertNotEmpty($iframe_url['query']);
    $query = [];
    parse_str($iframe_url['query'], $query);
    $this->assertSame($video_url_updated, $query['url']);
    $this->assertNotEmpty($query['hash']);
  }

  /**
   * Tests the standard profile configuration for media type 'video'.
   */
  protected function videoTest() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $source_field_id = 'field_media_video_file';

    // Create 2 test files.
    $test_filename = $this->randomMachineName() . '.mp4';
    $test_filepath = 'public://' . $test_filename;
    $test_filename_updated = $this->randomMachineName() . '.mp4';
    $test_filepath_updated = 'public://' . $test_filename_updated;
    file_put_contents($test_filepath, str_repeat('t', 10));
    file_put_contents($test_filepath_updated, str_repeat('u', 10));

    // Check if the name field is properly hidden on the media form.
    $this->drupalGet('media/add/video');
    $assert_session->fieldNotExists('name');

    // Check if the source field is available.
    $assert_session->fieldExists("files[{$source_field_id}_0]");

    // Create a media item.
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::service('file_system')->realpath($test_filepath));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');
    $video_media_id = $this->container
      ->get('entity_type.manager')
      ->getStorage('media')
      ->getQuery()
      ->accessCheck(FALSE)
      ->sort('mid', 'DESC')
      ->execute();
    $video_media_id = reset($video_media_id);

    // Reference the created media using an entity_reference field and make sure
    // the output is what we expect.
    $node = Node::create([
      'title' => 'Host node',
      'type' => 'article',
      'field_related_media' => [
        'target_id' => $video_media_id,
      ],
    ]);
    $node->save();

    $this->drupalGet('/node/' . $node->id());

    // Check if the default media name is generated as expected.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($video_media_id);
    $this->assertSame($test_filename, $media->label());

    // Here we expect to see only the linked filename. Assert only one element
    // in the content region.
    $assert_session->elementsCount('css', 'div.media--type-video > *', 1);

    // Assert the video element is present inside the media element and that its
    // src attribute matches the video file.
    $video_element = $assert_session->elementExists('css', 'div.media--type-video .field--name-field-media-video-file video > source');
    /** @var \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator */
    $file_url_generator = \Drupal::service('file_url_generator');
    $expected_video_src = $file_url_generator->generateString(\Drupal::token()->replace('public://[date:custom:Y]-[date:custom:m]/' . $test_filename));
    $this->assertSame($expected_video_src, $video_element->getAttribute('src'));

    // Assert the media name is updated through the field mapping when changing
    // the source field.
    $this->drupalGet('media/' . $video_media_id . '/edit');
    $page->pressButton('Remove');
    $result = $assert_session->waitForField("files[{$source_field_id}_0]");
    $this->assertNotEmpty($result);
    $page->attachFileToField("files[{$source_field_id}_0]", \Drupal::service('file_system')->realpath($test_filepath_updated));
    $result = $assert_session->waitForButton('Remove');
    $this->assertNotEmpty($result);
    $page->pressButton('Save');

    $this->drupalGet('/node/' . $node->id());

    // Check if the default media name is updated as expected.
    $media = \Drupal::entityTypeManager()->getStorage('media')->loadUnchanged($video_media_id);
    $this->assertSame($test_filename_updated, $media->label());

    // Again we expect to see only the linked filename. Assert only one element
    // in the content region.
    $assert_session->elementsCount('css', 'div.media--type-video > *', 1);

    // Assert the video element is present inside the media element and that its
    // src attribute matches the updated video file.
    $video_element = $assert_session->elementExists('css', 'div.media--type-video .field--name-field-media-video-file video > source');
    $expected_video_src = $file_url_generator->generateString(\Drupal::token()->replace('public://[date:custom:Y]-[date:custom:m]/' . $test_filename_updated));
    $this->assertSame($expected_video_src, $video_element->getAttribute('src'));
  }

}
