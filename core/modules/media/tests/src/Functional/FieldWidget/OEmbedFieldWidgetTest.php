<?php

namespace Drupal\Tests\media\Functional\FieldWidget;

use Drupal\field\Entity\FieldConfig;
use Drupal\Tests\media\Functional\MediaFunctionalTestBase;

/**
 * @covers \Drupal\media\Plugin\Field\FieldWidget\OEmbedWidget
 *
 * @group media
 */
class OEmbedFieldWidgetTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the oEmbed field widget shows the configured help text.
   */
  public function testFieldWidgetHelpText() {
    $account = $this->drupalCreateUser(['create media']);
    $this->drupalLogin($account);

    $media_type = $this->createMediaType('oembed:video');
    $source_field = $media_type->getSource()
      ->getSourceFieldDefinition($media_type)
      ->getName();

    /** @var \Drupal\field\Entity\FieldConfig $field */
    $field = FieldConfig::loadByName('media', $media_type->id(), $source_field);
    $field->setDescription('This is help text for oEmbed field.')
      ->save();

    $this->drupalGet('media/add/' . $media_type->id());
    $assert_session = $this->assertSession();
    $assert_session->pageTextContains('This is help text for oEmbed field.');
    $assert_session->pageTextContains('You can link to media from the following services: YouTube, Vimeo');
  }

}
