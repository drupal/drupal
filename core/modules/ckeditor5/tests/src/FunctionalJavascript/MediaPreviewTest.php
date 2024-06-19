<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\filter\Entity\FilterFormat;

/**
 * @coversDefaultClass \Drupal\ckeditor5\Plugin\CKEditor5Plugin\Media
 * @group ckeditor5
 * @group #slow
 * @internal
 */
class MediaPreviewTest extends MediaTestBase {

  /**
   * Tests that failed media embed preview requests inform the end user.
   */
  public function testErrorMessages(): void {
    // This test currently frequently causes the SQLite database to lock, so
    // skip the test on SQLite until the issue can be resolved.
    // @todo https://www.drupal.org/project/drupal/issues/3273626
    if (Database::getConnection()->driver() === 'sqlite') {
      $this->markTestSkipped('Test frequently causes a locked database on SQLite');
    }

    // Assert that a request to the `media.filter.preview` route that does not
    // result in a 200 response (due to server error or network error) is
    // handled in the JavaScript by displaying the expected error message.
    // @see core/modules/media/js/media_embed_ckeditor.theme.js
    // @see js/ckeditor5_plugins/drupalMedia/src/drupalmediaediting.js
    $this->container->get('state')->set('test_media_filter_controller_throw_error', TRUE);
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $assert_session->waitForElementVisible('css', '.ck-widget.drupal-media');
    $this->assertEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]', 1000));
    $assert_session->elementNotExists('css', '.ck-widget.drupal-media .media');
    $this->assertNotEmpty($assert_session->waitForText('An error occurred while trying to preview the media. Save your work and reload this page.'));
    // Now assert that the error doesn't appear when the override to force an
    // error is removed.
    $this->container->get('state')->set('test_media_filter_controller_throw_error', FALSE);
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));

    // There's a second kind of error message that comes from the back end
    // that happens when the media uuid can't be converted to a media preview.
    // In this case, the error will appear in a the themeable
    // media-embed-error.html template.  We have a hook altering the css
    // classes to test the twig template is working properly and picking up our
    // extra class.
    // @see \Drupal\media\Plugin\Filter\MediaEmbed::renderMissingMediaIndicator()
    // @see core/modules/media/templates/media-embed-error.html.twig
    // @see media_test_embed_preprocess_media_embed_error()
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace($this->media->uuid(), 'invalid_uuid', $original_value);
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.drupal-media .this-error-message-is-themeable'));

    // Test when using the starterkit_theme theme, an additional class is added
    // to the error, which is supported by
    // stable9/templates/content/media-embed-error.html.twig.
    $this->assertTrue($this->container->get('theme_installer')->install(['starterkit_theme']));
    $this->config('system.theme')
      ->set('default', 'starterkit_theme')
      ->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-widget.drupal-media .this-error-message-is-themeable'));

    // Test that restoring a valid UUID results in the media embed preview
    // displaying.
    $this->host->body->value = $original_value;
    $this->host->save();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));
    $assert_session->elementNotExists('css', '.ck-widget.drupal-media .this-error-message-is-themeable');
  }

  /**
   * The CKEditor Widget must load a preview generated using the default theme.
   */
  public function testPreviewUsesDefaultThemeAndIsClientCacheable(): void {
    // Make the node edit form use the admin theme, like on most Drupal sites.
    $this->config('node.settings')
      ->set('use_admin_theme', TRUE)
      ->save();

    // Allow the test user to view the admin theme.
    $this->adminUser
      ->addRole($this->drupalCreateRole(['view the administration theme']))
      ->save();

    // Configure a different default and admin theme, like on most Drupal sites.
    $this->config('system.theme')
      ->set('default', 'stable9')
      ->set('admin', 'starterkit_theme')
      ->save();

    // Assert that when looking at an embedded entity in the CKEditor Widget,
    // the preview is generated using the default theme, not the admin theme.
    // @see media_test_embed_entity_view_alter()
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));
    $element = $assert_session->elementExists('css', '[data-media-embed-test-active-theme]');
    $this->assertSame('stable9', $element->getAttribute('data-media-embed-test-active-theme'));
    // Assert that the first preview request transferred >500 B over the wire.
    // Then toggle source mode on and off. This causes the CKEditor widget to be
    // destroyed and then reconstructed. Assert that during this reconstruction,
    // a second request is sent. This second request should have transferred 0
    // bytes: the browser should have cached the response, thus resulting in a
    // much better user experience.
    $this->assertGreaterThan(500, $this->getLastPreviewRequestTransferSize());
    $this->pressEditorButton('Source');
    $this->assertNotEmpty($assert_session->waitForElement('css', '.ck-source-editing-area'));
    // CKEditor 5 is very smart: if no changes were made in the Source Editing
    // Area, it will not rerender the contents. In this test, we
    // want to verify that Media preview responses are cached on the client side
    // so it is essential that rerendering occurs. To achieve this, we append a
    // single space.
    $source_text_area = $this->getSession()->getPage()->find('css', '[name="body[0][value]"] + .ck-editor textarea');
    $source_text_area->setValue($source_text_area->getValue() . ' ');
    $this->pressEditorButton('Source');
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'img[src*="image-test.png"]'));
    $this->assertSame(0, $this->getLastPreviewRequestTransferSize());
  }

  /**
   * Tests preview route access.
   *
   * @param bool $media_embed_enabled
   *   Whether to test with media_embed filter enabled on the text format.
   * @param bool $can_use_format
   *   Whether the logged in user is allowed to use the text format.
   *
   * @dataProvider previewAccessProvider
   */
  public function testEmbedPreviewAccess($media_embed_enabled, $can_use_format): void {
    // Reconfigure the host entity's text format to suit our needs.
    /** @var \Drupal\filter\FilterFormatInterface $format */
    $format = FilterFormat::load($this->host->body->format);
    $format->set('filters', [
      'filter_align' => ['status' => TRUE],
      'filter_caption' => ['status' => TRUE],
      'media_embed' => ['status' => $media_embed_enabled],
    ]);
    $format->save();

    $permissions = [
      'bypass node access',
    ];
    if ($can_use_format) {
      $permissions[] = $format->getPermissionName();
    }
    $this->drupalLogin($this->drupalCreateUser($permissions));
    $this->drupalGet($this->host->toUrl('edit-form'));

    $assert_session = $this->assertSession();
    if ($can_use_format) {
      $this->waitForEditor();
      if ($media_embed_enabled) {
        // The preview rendering, which in this test will use Starterkit theme's
        // media.html.twig template, will fail without the CSRF token/header.
        // @see ::testEmbeddedMediaPreviewWithCsrfToken()
        $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'article.media'));
      }
      else {
        // If the filter isn't enabled, there won't be an error, but the
        // preview shouldn't be rendered.
        $assert_session->elementNotExists('css', 'article.media');
      }
    }
    else {
      $assert_session->pageTextContains('This field has been disabled because you do not have sufficient permissions to edit it.');
    }
  }

  /**
   * Data provider for ::testEmbedPreviewAccess.
   */
  public static function previewAccessProvider() {
    return [
      'media_embed filter enabled' => [
        TRUE,
        TRUE,
      ],
      'media_embed filter disabled' => [
        FALSE,
        TRUE,
      ],
      'media_embed filter enabled, user not allowed to use text format' => [
        TRUE,
        FALSE,
      ],
    ];
  }

  /**
   * Ensure media preview isn't clickable.
   */
  public function testMediaPointerEvent(): void {
    $entityViewDisplay = EntityViewDisplay::load('media.image.view_mode_1');
    $thumbnail = $entityViewDisplay->getComponent('thumbnail');
    $thumbnail['settings']['image_link'] = 'file';
    $entityViewDisplay->setComponent('thumbnail', $thumbnail);
    $entityViewDisplay->save();

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $url = $this->host->toUrl('edit-form');
    $this->drupalGet($url);
    $this->waitForEditor();
    $assert_session->waitForLink('default alt');
    $page->find('css', '.ck .drupal-media')->click();
    // Assert that the media preview is not clickable by comparing the URL.
    $this->assertEquals($url->toString(), $this->getUrl());
  }

}
