<?php

namespace Drupal\Tests\media\FunctionalJavascript;

use Drupal\media\Entity\Media;
use Drupal\media_test_oembed\Controller\ResourceController;
use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * Tests the oembed:video media source.
 *
 * @group media
 */
class MediaSourceOEmbedVideoTest extends MediaSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['media_test_oembed'];

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->lockHttpClientToFixtures();
  }

  /**
   * Tests the oembed media source.
   */
  public function testMediaOEmbedVideoSource() {
    $media_type_id = 'test_media_oembed_type';
    $provided_fields = [
      'type',
      'title',
      'author_name',
      'author_url',
      'provider_name',
      'provider_url',
      'cache_age',
      'thumbnail_url',
      'thumbnail_local_uri',
      'thumbnail_local',
      'thumbnail_width',
      'thumbnail_height',
      'url',
      'width',
      'height',
      'html',
    ];

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->doTestCreateMediaType($media_type_id, 'oembed:video', $provided_fields);

    // Create custom fields for the media type to store metadata attributes.
    $fields = [
      'field_string_width' => 'string',
      'field_string_height' => 'string',
      'field_string_author_name' => 'string',
    ];
    $this->createMediaTypeFields($fields, $media_type_id);

    // Hide the name field widget to test default name generation.
    $this->hideMediaTypeFieldWidget('name', $media_type_id);

    $this->drupalGet("admin/structure/media/manage/$media_type_id");
    // Only accept Vimeo videos.
    $page->checkField("source_configuration[allowed_providers][Vimeo]");
    $assert_session->selectExists('field_map[width]')->setValue('field_string_width');
    $assert_session->selectExists('field_map[height]')->setValue('field_string_height');
    $assert_session->selectExists('field_map[author_name]')->setValue('field_string_author_name');
    $assert_session->buttonExists('Save')->press();

    $this->hijackProviderEndpoints();
    $video_url = 'https://vimeo.com/7073899';
    ResourceController::setResourceUrl($video_url, $this->getFixturesDirectory() . '/video_vimeo.json');

    // Create a media item.
    $this->drupalGet("media/add/$media_type_id");
    $assert_session->fieldExists('Remote video URL')->setValue($video_url);
    $assert_session->buttonExists('Save')->press();

    $assert_session->addressEquals('media/1');
    $thumbnail = Media::load(1)->uuid() . '.png';

    // The thumbnail should have been downloaded.
    $this->assertFileExists("public://oembed_thumbnails/$thumbnail");

    // Make sure the video is displayed in an iframe.
    $assert_session->elementAttributeContains('css', 'iframe', 'src', '/media/oembed?url=' . str_replace('://', '%3A//', $video_url));

    // Make sure the thumbnail is displayed from uploaded image.
    $assert_session->elementAttributeContains('css', '.image-style-thumbnail', 'src', "/oembed_thumbnails/$thumbnail");

    // Load the media and check that all fields are properly populated.
    $media = Media::load(1);
    $this->assertSame('Drupal Rap Video - Schipulcon09', $media->getName());
    $this->assertSame('480', $media->field_string_width->value);
    $this->assertSame('360', $media->field_string_height->value);

    // Try to create a media asset from a disallowed provider.
    $this->drupalGet("media/add/$media_type_id");
    $assert_session->fieldExists('Remote video URL')->setValue('http://www.collegehumor.com/video/40003213/grant-and-katie-are-starting-their-own-company');
    $page->pressButton('Save');

    $assert_session->pageTextContains('The CollegeHumor provider is not allowed.');
  }

  /**
   * Test that a security warning appears if iFrame domain is not set.
   */
  public function testOEmbedSecurityWarning() {
    $media_type_id = 'test_media_oembed_type';
    $source_id = 'oembed:video';

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', $media_type_id);
    $this->getSession()
      ->wait(5000, "jQuery('.machine-name-value').text() === '{$media_type_id}'");

    // Make sure the source is available.
    $assert_session->fieldExists('Media source');
    $assert_session->optionExists('Media source', $source_id);
    $page->selectFieldOption('Media source', $source_id);
    $result = $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]');
    $this->assertNotEmpty($result);

    $assert_session->pageTextContains('It is potentially insecure to display oEmbed content in a frame');

    $this->config('media.settings')->set('iframe_domain', 'http://example.com')->save();

    $this->drupalGet('admin/structure/media/add');
    $page->fillField('label', $media_type_id);
    $this->getSession()
      ->wait(5000, "jQuery('.machine-name-value').text() === '{$media_type_id}'");

    // Make sure the source is available.
    $assert_session->fieldExists('Media source');
    $assert_session->optionExists('Media source', $source_id);
    $page->selectFieldOption('Media source', $source_id);
    $result = $assert_session->waitForElementVisible('css', 'fieldset[data-drupal-selector="edit-source-configuration"]');
    $this->assertNotEmpty($result);

    $assert_session->pageTextNotContains('It is potentially insecure to display oEmbed content in a frame');
  }

}
