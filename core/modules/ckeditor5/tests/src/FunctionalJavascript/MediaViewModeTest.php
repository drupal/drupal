<?php

declare(strict_types=1);

namespace Drupal\Tests\ckeditor5\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityViewMode;
use Drupal\filter\Entity\FilterFormat;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// cspell:ignore alternatif drupalelementstyle hurlant layercake tatou texte
// cspell:ignore zartan
/**
 * Tests Drupal\ckeditor5\Plugin\CKEditor5Plugin\Media.
 *
 * @internal
 */
#[CoversClass(\Drupal\ckeditor5\Plugin\CKEditor5Plugin\Media::class)]
#[Group('ckeditor5')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class MediaViewModeTest extends MediaTestBase {

  /**
   * Tests view mode integration.
   *
   * Tests that view mode is reflected onto the CKEditor 5 Widget wrapper, that
   * the media style toolbar allows changing the view mode and that the changes
   * are reflected on the widget and downcast drupal-media tag.
   */
  #[DataProvider('providerTestViewMode')]
  public function testViewMode(bool $with_alignment): void {
    EntityViewMode::create([
      'id' => 'media.view_mode_3',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 3',
    ])->save();
    EntityViewMode::create([
      'id' => 'media.view_mode_4',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'enabled' => TRUE,
      'label' => 'View Mode 4',
    ])->save();
    // Enable view mode 1, 2, 4 for Image.
    EntityViewDisplay::create([
      'id' => 'media.image.view_mode_4',
      'targetEntityType' => 'media',
      'status' => TRUE,
      'bundle' => 'image',
      'mode' => 'view_mode_4',
    ])->save();

    $filter_format = FilterFormat::load('test_format');
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          'view_mode_1' => 'view_mode_1',
          '22222' => '22222',
          'view_mode_3' => 'view_mode_3',
        ],
      ],
    ])->save();

    if (!$with_alignment) {
      $filter_format->filters('filter_align')->setConfiguration(array_merge($filter_format->filters('filter_align')->getConfiguration(), ['status' => FALSE]));
    }

    // Test that view mode dependencies are returned from the MediaEmbed
    // filter's ::getDependencies() method.
    $expected_config_dependencies = [
      'core.entity_view_mode.media.view_mode_1',
      'core.entity_view_mode.media.22222',
      'core.entity_view_mode.media.view_mode_3',
    ];

    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertEqualsCanonicalizing($expected_config_dependencies, $dependencies['config']);
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();
    $this->drupalGet($this->host->toUrl('edit-form'));
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Check that there is no data-view-mode set after embedding media.
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-view-mode'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('View Mode 1')->click();

    // Set view mode.
    $this->getBalloonButton('View Mode 2 has Numeric ID')->click();
    $editor_dom = $this->getEditorDataAsDom();
    // Check that “data-view-mode” exists inside source editing.
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertEquals('22222', $drupal_media_element->getAttribute('data-view-mode'));

    // Check that toolbar matches current view mode.
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    $this->assertEquals('View Mode 2 has Numeric ID', $dropdown_button->getText());
    // Enter source mode.
    $this->pressEditorButton('Source');
    // Leave source mode to force CKEditor 5 to upcast again to check data
    // persistence.
    $this->pressEditorButton('Source');
    $this->click('.ck-widget.drupal-media');
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    // Check that view mode 2 persisted.
    $this->assertEquals('View Mode 2 has Numeric ID', $dropdown_button->getText());

    // Check that selecting a caption that is the child of a drupal-media will
    // inherit the drupalElementStyle of its parent element.
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('Toggle caption off')->click();
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    // Select the caption by toggling it on.
    $this->getBalloonButton('Toggle caption on')->click();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));
    // Ensure that the media contextual toolbar is visible after toggling
    // caption on.
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    $this->assertEquals('View Mode 2 has Numeric ID', $dropdown_button->getText());

    // Remove the current view mode by setting it to Default.
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('View Mode 2 has Numeric ID')->click();
    // Unset view mode.
    $this->getBalloonButton('View Mode 1')->click();
    $this->waitForEditor();
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    // Test that setting the view mode back to the default removes the
    // `data-view-mode` attribute.
    $this->assertFalse($drupal_media_element->hasAttribute('data-view-mode'));
    $this->assertNotEmpty($assert_session->waitForElement('css', 'article.media--view-mode-view-mode-1'));

    // Check that the toolbar status matches "no view mode".
    $dropdown_button = $page->find('css', 'button.ck-dropdown__button > span.ck-button__label');
    $this->assertEquals('View Mode 1', $dropdown_button->getText());

    // Test that setting the allowed_view_modes option to only one option hides
    // the field (it requires more than one option).
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          'view_mode_1' => 'view_mode_1',
        ],
      ],
    ])->save();

    // Test that the dependencies change when the allowed_view_modes change.
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertSame(['core.entity_view_mode.media.view_mode_1'], $dependencies['config']);
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $this->click('.ck-widget.drupal-media');
    // Check that view mode dropdown is gone because there is only one option.
    $this->assertEmpty($assert_session->waitForElementVisible('css', '.ck.ck-dropdown', 1000));
    $editor_dom = $this->getEditorDataAsDom();
    $drupal_media_element = $editor_dom->getElementsByTagName('drupal-media')
      ->item(0);
    $this->assertFalse($drupal_media_element->hasAttribute('data-view-mode'));
    $this->assertNotEmpty($assert_session->waitForElement('css', 'article.media--view-mode-view-mode-1'));

    // Test that setting allowed_view_modes back to two items restores the
    // field.
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          'view_mode_1' => 'view_mode_1',
          '22222' => '22222',
        ],
      ],
    ])->save();

    // Test that the dependencies change when the allowed_view_modes change.
    $expected_config_dependencies = [
      'core.entity_view_mode.media.view_mode_1',
      'core.entity_view_mode.media.22222',
    ];
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertEqualsCanonicalizing($expected_config_dependencies, $dependencies['config']);
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->waitForEditor();

    // Test that changing the view mode with an empty editable caption
    // preserves the empty editable caption when the preview reloads.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));
    $original_value = $this->host->body->value;
    $this->host->body->value = str_replace('data-caption="baz"', '', $original_value);
    $this->host->save();
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElement('css', 'article.media--view-mode-view-mode-1'));

    $this->assertEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');
    $this->getBalloonButton('View Mode 1')->click();
    $this->getBalloonButton('View Mode 2 has Numeric ID')->click();
    $assert_session->waitForElement('css', 'article.media--view-mode-_2222');
    $this->assertEmpty($assert_session->waitForElementVisible('css', '.drupal-media figcaption'));

    // Test that a media with no view modes configured will be
    // set to the default view mode.
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [],
      ],
    ])->save();
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertSame(['core.entity_view_mode.media.view_mode_1'], $dependencies['config']);
    $this->host->body->value = '<drupal-media data-caption="armadillo" data-entity-type="media" data-entity-uuid="' . $this->mediaFile->uuid() . '"></drupal-media>';
    $this->host->save();
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->waitForEditor();
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', 'article.media--view-mode-view-mode-1'));

    // Test that having a default_view_mode that is not an allowed_view_mode
    // will still be added to the editor.
    $filter_format->setFilterConfig('media_embed', [
      'status' => TRUE,
      'settings' => [
        'default_view_mode' => 'view_mode_1',
        'allowed_media_types' => [],
        'allowed_view_modes' => [
          '22222' => '22222',
          'view_mode_4' => 'view_mode_4',
        ],
      ],
    ])->save();

    // Test that the dependencies change when the allowed_view_modes change.
    $expected_config_dependencies = [
      'core.entity_view_mode.media.22222',
      'core.entity_view_mode.media.view_mode_1',
      'core.entity_view_mode.media.view_mode_4',
    ];
    $dependencies = $filter_format->getDependencies();
    $this->assertArrayHasKey('config', $dependencies);
    $this->assertEqualsCanonicalizing($expected_config_dependencies, $dependencies['config']);
    $this->host->body->value = '<drupal-media data-entity-type="media" data-entity-uuid="' . $this->media->uuid() . '" data-caption="baz"></drupal-media>';
    $this->host->save();
    // Reload page to get new configuration.
    $this->getSession()->reload();
    $this->waitForEditor();
    // Wait for the media preview to load.
    $this->assertNotEmpty($assert_session->waitForElementVisible('css', '.ck-widget.drupal-media img'));
    $this->click('.ck-widget.drupal-media');
    $this->assertVisibleBalloon('[aria-label="Drupal Media toolbar"]');

    $this->assertNotEmpty($dropdown = $this->getBalloonButton('View Mode 1'));
    $dropdown->click();

    // Check that all three view modes exist including the default view mode
    // that was not originally included in the allowed_view_modes.
    $this->assertNotEmpty($this->getBalloonButton('View Mode 1'));
    $this->assertNotEmpty($this->getBalloonButton('View Mode 2 has Numeric ID'));
    $this->assertNotEmpty($this->getBalloonButton('View Mode 4'));
  }

  /**
   * For testing view modes in different scenarios.
   */
  public static function providerTestViewMode(): array {
    return [
      'with alignment' => [TRUE],
      'without alignment' => [FALSE],
    ];
  }

}
