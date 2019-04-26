<?php

namespace Drupal\Tests\path\Functional;

use Drupal\media\Entity\MediaType;

/**
 * Tests the path media form UI.
 *
 * @group path
 */
class PathMediaFormTest extends PathTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['media', 'media_test_source'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create test user and log in.
    $web_user = $this->drupalCreateUser(['create media', 'create url aliases']);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the media form UI.
   */
  public function testMediaForm() {
    $assert_session = $this->assertSession();

    // Create media type.
    $media_type_id = 'foo';
    $media_type = MediaType::create([
      'id' => $media_type_id,
      'label' => $media_type_id,
      'source' => 'test',
      'source_configuration' => [],
      'field_map' => [],
      'new_revision' => FALSE,
    ]);
    $media_type->save();

    $this->drupalGet('media/add/' . $media_type_id);

    // Make sure we have a vertical tab fieldset and 'Path' field.
    $assert_session->elementContains('css', '.form-type-vertical-tabs #edit-path-0 summary', 'URL alias');
    $assert_session->fieldExists('path[0][alias]');

    // Disable the 'Path' field for this content type.
    \Drupal::service('entity_display.repository')->getFormDisplay('media', $media_type_id, 'default')
      ->removeComponent('path')
      ->save();

    $this->drupalGet('media/add/' . $media_type_id);

    // See if the whole fieldset is gone now.
    $assert_session->elementNotExists('css', '.form-type-vertical-tabs #edit-path-0');
    $assert_session->fieldNotExists('path[0][alias]');
  }

}
