<?php

namespace Drupal\Tests\media\Functional;

use Drupal\Tests\media\Traits\OEmbedTestTrait;

/**
 * @coversDefaultClass \Drupal\media\Plugin\Validation\Constraint\OEmbedResourceConstraintValidator
 *
 * @group media
 */
class OEmbedValidationTest extends MediaFunctionalTestBase {

  use OEmbedTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'field_ui',
    'link',
    'media_test_oembed',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test validation for url with provider_name that is not in providers.json.
   */
  public function testValidateUnlistedProvider() {

    $media_type = $this->createMediaType('oembed:video');
    $source_field_name = $media_type->getSource()->getSourceFieldDefinition($media_type)->getName();

    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();
    $this->drupalGet('/media/add/' . $media_type->id());

    $this->lockHttpClientToFixtures();
    $page->fillField($source_field_name . '[0][value]', 'video_unlisted.html');
    $page->pressButton('Save');
    $assert_session->pageTextContains('The given URL does not match any known oEmbed providers.');
  }

}
