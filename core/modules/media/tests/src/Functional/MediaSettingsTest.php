<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Functional;

use Drupal\Core\Url;

/**
 * Testing the media settings.
 *
 * @group media
 */
class MediaSettingsTest extends MediaFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalLogin($this->createUser([
      'administer site configuration',
      'administer media',
    ]));
  }

  /**
   * Tests that media warning appears if oEmbed media types exists.
   */
  public function testStatusPage(): void {
    $assert_session = $this->assertSession();

    $this->drupalGet('admin/reports/status');
    $assert_session->pageTextNotContains('It is potentially insecure to display oEmbed content in a frame');

    $this->createMediaType('oembed:video');

    $this->drupalGet('admin/reports/status');
    $assert_session->pageTextContains('It is potentially insecure to display oEmbed content in a frame');
  }

  /**
   * Tests that the media settings form stores a `null` iFrame domain.
   */
  public function testSettingsForm(): void {
    $assert_session = $this->assertSession();

    $this->assertNull($this->config('media.settings')->get('iframe_domain'));
    $this->drupalGet(Url::fromRoute('media.settings'));
    $assert_session->fieldExists('iframe_domain');

    // Explicitly submitting an empty string does not result in the
    // `iframe_domain` property getting set to the empty string: it is converted
    // to `null` to comply with the config schema.
    // @see \Drupal\media\Form\MediaSettingsForm::submitForm()
    $this->submitForm([
      'iframe_domain' => '',
    ], 'Save configuration');
    $assert_session->statusMessageContains('The configuration options have been saved.', 'status');
    $this->assertNull($this->config('media.settings')->get('iframe_domain'));
  }

}
