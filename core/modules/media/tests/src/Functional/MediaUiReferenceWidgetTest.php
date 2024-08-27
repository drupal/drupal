<?php

declare(strict_types=1);

namespace Drupal\Tests\media\Functional;

use Behat\Mink\Element\NodeElement;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;

/**
 * Ensures that media UI works correctly.
 *
 * @group media
 * @group #slow
 */
class MediaUiReferenceWidgetTest extends MediaFunctionalTestBase {

  use FieldUiTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'media_test_source',
    'media',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Data provider for testMediaReferenceWidget().
   *
   * @return array[]
   *   Test data. See testMediaReferenceWidget() for the child array structure.
   */
  public static function providerTestMediaReferenceWidget() {
    return [
      // Single-value fields with a single media type and the default widget:
      // - The user can create and list the media.
      'single_value:single_type:create_list' => [1, [TRUE], TRUE],
      // - The user can list but not create the media.
      'single_value:single_type:list' => [1, [FALSE], TRUE],
      // - The user can create but not list the media.
      'single_value:single_type:create' => [1, [TRUE], FALSE],
      // - The user can neither create nor list the media.
      'single_value:single_type' => [1, [FALSE], FALSE],

      // Single-value fields with the tags-style widget:
      // - The user can create and list the media.
      'single_value:single_type:create_list:tags' => [1, [TRUE], TRUE, 'entity_reference_autocomplete_tags'],
      // - The user can list but not create the media.
      'single_value:single_type:list:tags' => [1, [FALSE], TRUE, 'entity_reference_autocomplete_tags'],
      // - The user can create but not list the media.
      'single_value:single_type:create:tags' => [1, [TRUE], FALSE, 'entity_reference_autocomplete_tags'],
      // - The user can neither create nor list the media.
      'single_value:single_type:tags' => [1, [FALSE], FALSE, 'entity_reference_autocomplete_tags'],

      // Single-value fields with two media types:
      // - The user can create both types.
      'single_value:two_type:create2_list' => [1, [TRUE, TRUE], TRUE],
      // - The user can create only one type.
      'single_value:two_type:create1_list' => [1, [TRUE, FALSE], TRUE],
      // - The user cannot create either type.
      'single_value:two_type:list' => [1, [FALSE, FALSE], TRUE],

      // Multiple-value field with a cardinality of 3, with media the user can
      // create and list.
      'multi_value:single_type:create_list' => [3, [TRUE], TRUE],
      // The same, with the tags field.
      'multi-value:single_type:create_list:tags' => [3, [TRUE], TRUE, 'entity_reference_autocomplete_tags'],

      // Unlimited value field.
      'unlimited_value:single_type:create_list' => [FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED, [TRUE], TRUE],
      // Unlimited value field with the tags widget.
      'unlimited_value:single_type:create_list:tags' => [FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED, [TRUE], TRUE, 'entity_reference_autocomplete_tags'],
    ];
  }

  /**
   * Tests the default autocomplete widgets for media reference fields.
   *
   * @param int $cardinality
   *   The field cardinality.
   * @param bool[] $media_type_create_access
   *   An array of booleans indicating whether to grant the test user create
   *   access for each media type. A media type is created automatically for
   *   each; for example, an array [TRUE, FALSE] would create two media types,
   *   one that allows the user to create media and a second that does not.
   * @param bool $list_access
   *   Whether to grant the test user access to list media.
   * @param string $widget_id
   *   The widget ID to test.
   *
   * @see media_field_widget_entity_reference_autocomplete_form_alter()
   * @see media_field_widget_multiple_entity_reference_autocomplete_form_alter()
   *
   * @dataProvider providerTestMediaReferenceWidget
   */
  public function testMediaReferenceWidget($cardinality, array $media_type_create_access, $list_access, $widget_id = 'entity_reference_autocomplete'): void {
    $assert_session = $this->assertSession();

    // Create two content types.
    $non_media_content_type = $this->createContentType();
    $content_type = $this->createContentType();

    // Create some media types.
    $media_types = [];
    $permissions = [];
    $create_media_types = [];
    foreach ($media_type_create_access as $id => $access) {
      if ($access) {
        $create_media_types[] = "media_type_$id";
        $permissions[] = "create media_type_$id media";
      }
      $this->createMediaType('test', [
        'id' => "media_type_$id",
        'label' => "media_type_$id",
      ]);
      $media_types["media_type_$id"] = "media_type_$id";
    }

    // Create a user that can create content of the type, with other
    // permissions as given by the data provider.
    $permissions[] = "create {$content_type->id()} content";
    if ($list_access) {
      $permissions[] = "access media overview";
    }
    $test_user = $this->drupalCreateUser($permissions);

    // Create a non-media entity reference.
    $non_media_storage = FieldStorageConfig::create([
      'field_name' => 'field_not_a_media_field',
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      'settings' => [
        'target_type' => 'node',
      ],
    ]);
    $non_media_storage->save();
    $non_media_field = FieldConfig::create([
      'label' => 'No media here!',
      'field_storage' => $non_media_storage,
      'entity_type' => 'node',
      'bundle' => $non_media_content_type->id(),
      'settings' => [
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [
            $non_media_content_type->id() => $non_media_content_type->id(),
          ],
        ],
      ],
    ]);
    $non_media_field->save();
    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.' . $non_media_content_type->id() . '.default')
      ->setComponent('field_not_a_media_field', [
        'type' => $widget_id,
      ])
      ->save();

    // Create a media field through the user interface to ensure that the
    // help text handling does not break the default value entry on the field
    // settings form.
    // Using submitForm() to avoid dealing with JavaScript on the previous
    // page in the field creation.
    $field_edit = [];
    foreach ($media_types as $type) {
      $field_edit["settings[handler_settings][target_bundles][$type]"] = TRUE;
    }
    $this->fieldUIAddNewField("admin/structure/types/manage/{$content_type->id()}", 'media_reference', "Media (cardinality $cardinality)", 'field_ui:entity_reference:media', [], $field_edit);
    \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.' . $content_type->id() . '.default')
      ->setComponent('field_media_reference', [
        'type' => $widget_id,
      ])
      ->save();

    // Some of the expected texts.
    $create_help = 'Create your media on the media add page (opens a new window), then add it by name to the field below.';
    $list_text = 'See the media list (opens a new window) to help locate media.';
    $use_help = 'Type part of the media name.';
    $create_header = "Create new media";
    $use_header = "Use existing media";

    // First check that none of the help texts are on the non-media content.
    $this->drupalGet("/node/add/{$non_media_content_type->id()}");
    $this->assertNoHelpTexts([
      $create_header,
      $create_help,
      $use_header,
      $use_help,
      $list_text,
      'Allowed media types:',
    ]);

    // Now, check that the widget displays the expected help text under the
    // given conditions for the test user.
    $this->drupalLogin($test_user);
    $this->drupalGet("/node/add/{$content_type->id()}");

    // Specific expected help texts for the media field.
    $create_header = "Create new media";
    $use_header = "Use existing media";
    $type_list = 'Allowed media types: ' . implode(", ", array_keys($media_types));

    $fieldset_selector = '#edit-field-media-reference-wrapper fieldset';
    $fieldset = $assert_session->elementExists('css', $fieldset_selector);

    $this->assertSame("Media (cardinality $cardinality)", $assert_session->elementExists('css', 'legend', $fieldset)->getText());

    // Assert text that should be displayed regardless of other access.
    $this->assertHelpTexts([$use_header, $use_help, $type_list], $fieldset_selector);

    // The entire section for creating new media should only be displayed if
    // the user can create at least one media of the type.
    if ($create_media_types) {
      if (count($create_media_types) === 1) {
        $url = Url::fromRoute('entity.media.add_form')->setRouteParameter('media_type', $create_media_types[0]);
      }
      else {
        $url = Url::fromRoute('entity.media.add_page');
      }
      $this->assertHelpTexts([$create_header, $create_help], $fieldset_selector);
      $this->assertHelpLink(
        $fieldset,
        'media add page',
        [
          'target' => '_blank',
          'href' => $url->toString(),
        ]
      );
    }
    else {
      $this->assertNoHelpTexts([$create_header, $create_help]);
      $this->assertNoHelpLink($fieldset, 'media add page');
    }

    if ($list_access) {
      $this->assertHelpTexts([$list_text], $fieldset_selector);
      $this->assertHelpLink(
        $fieldset,
        'media list',
        [
          'target' => '_blank',
          'href' => Url::fromRoute('entity.media.collection')->toString(),
        ]
      );
    }
    else {
      $this->assertNoHelpTexts([$list_text]);
      $this->assertNoHelpLink($fieldset, 'media list');
    }
  }

  /**
   * Asserts that the given texts are present exactly once.
   *
   * @param string[] $texts
   *   A list of the help texts to check.
   * @param string $selector
   *   (optional) The selector to search.
   *
   * @internal
   */
  protected function assertHelpTexts(array $texts, string $selector = ''): void {
    $assert_session = $this->assertSession();
    foreach ($texts as $text) {
      // We only want to escape single quotes, so use str_replace() rather than
      // addslashes().
      $text = str_replace("'", "\'", $text);
      if ($selector) {
        $assert_session->elementsCount('css', $selector . ":contains('$text')", 1);
      }
      else {
        $assert_session->pageTextContains($text);
      }
    }
  }

  /**
   * Asserts that none of the given texts are present.
   *
   * @param string[] $texts
   *   A list of the help texts to check.
   *
   * @internal
   */
  protected function assertNoHelpTexts(array $texts): void {
    $assert_session = $this->assertSession();
    foreach ($texts as $text) {
      $assert_session->pageTextNotContains($text);
    }
  }

  /**
   * Asserts whether a given link is present.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to search.
   * @param string $text
   *   The link text.
   * @param string[] $attributes
   *   An associative array of any expected attributes, keyed by the
   *   attribute name.
   *
   * @internal
   */
  protected function assertHelpLink(NodeElement $element, string $text, array $attributes = []): void {
    // Find all the links inside the element.
    $link = $element->findLink($text);

    $this->assertNotEmpty($link);
    foreach ($attributes as $attribute => $value) {
      $this->assertSame($link->getAttribute($attribute), $value);
    }
  }

  /**
   * Asserts that a given link is not present.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element to search.
   * @param string $text
   *   The link text.
   *
   * @internal
   */
  protected function assertNoHelpLink(NodeElement $element, string $text): void {
    $assert_session = $this->assertSession();
    // Assert that the link and its text are not present anywhere on the page.
    $assert_session->elementNotExists('named', ['link', $text], $element);
    $assert_session->pageTextNotContains($text);
  }

}
