<?php

namespace Drupal\Tests\link\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\link\LinkItemInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests link field widgets and formatters.
 *
 * @group link
 */
class LinkFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['entity_test', 'link', 'node'];

  /**
   * A field to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The instance used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
      'link to any page',
    ]));
  }

  /**
   * Tests link field URL validation.
   */
  public function testURLValidation() {
    $field_name = Unicode::strtolower($this->randomMachineName());
    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'link',
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'settings' => [
        'title' => DRUPAL_DISABLED,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ]);
    $this->field->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, [
        'type' => 'link_default',
        'settings' => [
          'placeholder_url' => 'http://example.com',
        ],
      ])
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, [
        'type' => 'link',
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][uri]", '', 'Link URL field is displayed');
    $this->assertRaw('placeholder="http://example.com"');

    // Create a path alias.
    \Drupal::service('path.alias_storage')->save('/admin', '/a/path/alias');

    // Create a node to test the link widget.
    $node = $this->drupalCreateNode();

    $restricted_node = $this->drupalCreateNode(['status' => NodeInterface::NOT_PUBLISHED]);

    // Define some valid URLs (keys are the entered values, values are the
    // strings displayed to the user).
    $valid_external_entries = [
      'http://www.example.com/' => 'http://www.example.com/',
      // Strings within parenthesis without leading space char.
      'http://www.example.com/strings_(string_within_parenthesis)' => 'http://www.example.com/strings_(string_within_parenthesis)',
      // Numbers within parenthesis without leading space char.
      'http://www.example.com/numbers_(9999)' => 'http://www.example.com/numbers_(9999)',
    ];
    $valid_internal_entries = [
      '/entity_test/add' => '/entity_test/add',
      '/a/path/alias' => '/a/path/alias',

      // Front page, with query string and fragment.
      '/' => '&lt;front&gt;',
      '/?example=llama' => '&lt;front&gt;?example=llama',
      '/#example' => '&lt;front&gt;#example',

      // @todo '<front>' is valid input for BC reasons, may be removed by
      //   https://www.drupal.org/node/2421941
      '<front>' => '&lt;front&gt;',
      '<front>#example' => '&lt;front&gt;#example',
      '<front>?example=llama' => '&lt;front&gt;?example=llama',

      // Query string and fragment.
      '?example=llama' => '?example=llama',
      '#example' => '#example',

      // Entity reference autocomplete value.
      $node->label() . ' (1)' => $node->label() . ' (1)',
      // Entity URI displayed as ER autocomplete value when displayed in a form.
      'entity:node/1' => $node->label() . ' (1)',
      // URI for an entity that exists, but is not accessible by the user.
      'entity:node/' . $restricted_node->id() => '- Restricted access - (' . $restricted_node->id() . ')',
      // URI for an entity that doesn't exist, but with a valid ID.
      'entity:user/999999' => 'entity:user/999999',
    ];

    // Define some invalid URLs.
    $validation_error_1 = "The path '@link_path' is invalid.";
    $validation_error_2 = 'Manually entered paths should start with /, ? or #.';
    $validation_error_3 = "The path '@link_path' is inaccessible.";
    $invalid_external_entries = [
      // Invalid protocol
      'invalid://not-a-valid-protocol' => $validation_error_1,
      // Missing host name
      'http://' => $validation_error_1,
    ];
    $invalid_internal_entries = [
      'no-leading-slash' => $validation_error_2,
      'entity:non_existing_entity_type/yar' => $validation_error_1,
      // URI for an entity that doesn't exist, with an invalid ID.
      'entity:user/invalid-parameter' => $validation_error_1,
    ];

    // Test external and internal URLs for 'link_type' = LinkItemInterface::LINK_GENERIC.
    $this->assertValidEntries($field_name, $valid_external_entries + $valid_internal_entries);
    $this->assertInvalidEntries($field_name, $invalid_external_entries + $invalid_internal_entries);

    // Test external URLs for 'link_type' = LinkItemInterface::LINK_EXTERNAL.
    $this->field->setSetting('link_type', LinkItemInterface::LINK_EXTERNAL);
    $this->field->save();
    $this->assertValidEntries($field_name, $valid_external_entries);
    $this->assertInvalidEntries($field_name, $valid_internal_entries + $invalid_external_entries);

    // Test external URLs for 'link_type' = LinkItemInterface::LINK_INTERNAL.
    $this->field->setSetting('link_type', LinkItemInterface::LINK_INTERNAL);
    $this->field->save();
    $this->assertValidEntries($field_name, $valid_internal_entries);
    $this->assertInvalidEntries($field_name, $valid_external_entries + $invalid_internal_entries);

    // Ensure that users with 'link to any page', don't apply access checking.
    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
    ]));
    $this->assertValidEntries($field_name, ['/entity_test/add' => '/entity_test/add']);
    $this->assertInValidEntries($field_name, ['/admin' => $validation_error_3]);
  }

  /**
   * Asserts that valid URLs can be submitted.
   *
   * @param string $field_name
   *   The field name.
   * @param array $valid_entries
   *   An array of valid URL entries.
   */
  protected function assertValidEntries($field_name, array $valid_entries) {
    foreach ($valid_entries as $uri => $string) {
      $edit = [
        "{$field_name}[0][uri]" => $uri,
      ];
      $this->drupalPostForm('entity_test/add', $edit, t('Save'));
      preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
      $id = $match[1];
      $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));
      $this->assertRaw($string);
    }
  }

  /**
   * Asserts that invalid URLs cannot be submitted.
   *
   * @param string $field_name
   *   The field name.
   * @param array $invalid_entries
   *   An array of invalid URL entries.
   */
  protected function assertInvalidEntries($field_name, array $invalid_entries) {
    foreach ($invalid_entries as $invalid_value => $error_message) {
      $edit = [
        "{$field_name}[0][uri]" => $invalid_value,
      ];
      $this->drupalPostForm('entity_test/add', $edit, t('Save'));
      $this->assertText(t($error_message, ['@link_path' => $invalid_value]));
    }
  }

  /**
   * Tests the link title settings of a link field.
   */
  public function testLinkTitle() {
    $field_name = Unicode::strtolower($this->randomMachineName());
    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'link',
    ]);
    $this->fieldStorage->save();
    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'label' => 'Read more about this entity',
      'settings' => [
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ]);
    $this->field->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, [
        'type' => 'link_default',
        'settings' => [
          'placeholder_url' => 'http://example.com',
          'placeholder_title' => 'Enter the text for this link',
        ],
      ])
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, [
        'type' => 'link',
        'label' => 'hidden',
      ])
      ->save();

    // Verify that the link text field works according to the field setting.
    foreach ([DRUPAL_DISABLED, DRUPAL_REQUIRED, DRUPAL_OPTIONAL] as $title_setting) {
      // Update the link title field setting.
      $this->field->setSetting('title', $title_setting);
      $this->field->save();

      // Display creation form.
      $this->drupalGet('entity_test/add');
      // Assert label is shown.
      $this->assertText('Read more about this entity');
      $this->assertFieldByName("{$field_name}[0][uri]", '', 'URL field found.');
      $this->assertRaw('placeholder="http://example.com"');

      if ($title_setting === DRUPAL_DISABLED) {
        $this->assertNoFieldByName("{$field_name}[0][title]", '', 'Link text field not found.');
        $this->assertNoRaw('placeholder="Enter the text for this link"');
      }
      else {
        $this->assertRaw('placeholder="Enter the text for this link"');

        $this->assertFieldByName("{$field_name}[0][title]", '', 'Link text field found.');
        if ($title_setting === DRUPAL_REQUIRED) {
          // Verify that the link text is required, if the URL is non-empty.
          $edit = [
            "{$field_name}[0][uri]" => 'http://www.example.com',
          ];
          $this->drupalPostForm(NULL, $edit, t('Save'));
          $this->assertText(t('@name field is required.', ['@name' => t('Link text')]));

          // Verify that the link text is not required, if the URL is empty.
          $edit = [
            "{$field_name}[0][uri]" => '',
          ];
          $this->drupalPostForm(NULL, $edit, t('Save'));
          $this->assertNoText(t('@name field is required.', ['@name' => t('Link text')]));

          // Verify that a URL and link text meets requirements.
          $this->drupalGet('entity_test/add');
          $edit = [
            "{$field_name}[0][uri]" => 'http://www.example.com',
            "{$field_name}[0][title]" => 'Example',
          ];
          $this->drupalPostForm(NULL, $edit, t('Save'));
          $this->assertNoText(t('@name field is required.', ['@name' => t('Link text')]));
        }
      }
    }

    // Verify that a link without link text is rendered using the URL as text.
    $value = 'http://www.example.com/';
    $edit = [
      "{$field_name}[0][uri]" => $value,
      "{$field_name}[0][title]" => '',
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));

    $output = $this->renderTestEntity($id);
    $expected_link = (string) \Drupal::l($value, Url::fromUri($value));
    $this->assertContains($expected_link, $output);

    // Verify that a link with text is rendered using the link text.
    $title = $this->randomMachineName();
    $edit = [
      "{$field_name}[0][title]" => $title,
    ];
    $this->drupalPostForm("entity_test/manage/$id/edit", $edit, t('Save'));
    $this->assertText(t('entity_test @id has been updated.', ['@id' => $id]));

    $output = $this->renderTestEntity($id);
    $expected_link = (string) \Drupal::l($title, Url::fromUri($value));
    $this->assertContains($expected_link, $output);
  }

  /**
   * Tests the default 'link' formatter.
   */
  public function testLinkFormatter() {
    $field_name = Unicode::strtolower($this->randomMachineName());
    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'link',
      'cardinality' => 3,
    ]);
    $this->fieldStorage->save();
    FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'label' => 'Read more about this entity',
      'bundle' => 'entity_test',
      'settings' => [
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ])->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, [
        'type' => 'link_default',
      ])
      ->save();
    $display_options = [
      'type' => 'link',
      'label' => 'hidden',
    ];
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, $display_options)
      ->save();

    // Create an entity with three link field values:
    // - The first field item uses a URL only.
    // - The second field item uses a URL and link text.
    // - The third field item uses a fragment-only URL with text.
    // For consistency in assertion code below, the URL is assigned to the title
    // variable for the first field.
    $this->drupalGet('entity_test/add');
    $url1 = 'http://www.example.com/content/articles/archive?author=John&year=2012#com';
    $url2 = 'http://www.example.org/content/articles/archive?author=John&year=2012#org';
    $url3 = '#net';
    $title1 = $url1;
    // Intentionally contains an ampersand that needs sanitization on output.
    $title2 = 'A very long & strange example title that could break the nice layout of the site';
    $title3 = 'Fragment only';
    $edit = [
      "{$field_name}[0][uri]" => $url1,
      // Note that $title1 is not submitted.
      "{$field_name}[0][title]" => '',
      "{$field_name}[1][uri]" => $url2,
      "{$field_name}[1][title]" => $title2,
      "{$field_name}[2][uri]" => $url3,
      "{$field_name}[2][title]" => $title3,
    ];
    // Assert label is shown.
    $this->assertText('Read more about this entity');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));

    // Verify that the link is output according to the formatter settings.
    // Not using generatePermutations(), since that leads to 32 cases, which
    // would not test actual link field formatter functionality but rather
    // the link generator and options/attributes. Only 'url_plain' has a
    // dependency on 'url_only', so we have a total of ~10 cases.
    $options = [
      'trim_length' => [NULL, 6],
      'rel' => [NULL, 'nofollow'],
      'target' => [NULL, '_blank'],
      'url_only' => [
        ['url_only' => FALSE],
        ['url_only' => FALSE, 'url_plain' => TRUE],
        ['url_only' => TRUE],
        ['url_only' => TRUE, 'url_plain' => TRUE],
      ],
    ];
    foreach ($options as $setting => $values) {
      foreach ($values as $new_value) {
        // Update the field formatter settings.
        if (!is_array($new_value)) {
          $display_options['settings'] = [$setting => $new_value];
        }
        else {
          $display_options['settings'] = $new_value;
        }
        entity_get_display('entity_test', 'entity_test', 'full')
          ->setComponent($field_name, $display_options)
          ->save();

        $output = $this->renderTestEntity($id);
        switch ($setting) {
          case 'trim_length':
            $url = $url1;
            $title = isset($new_value) ? Unicode::truncate($title1, $new_value, FALSE, TRUE) : $title1;
            $this->assertContains('<a href="' . Html::escape($url) . '">' . Html::escape($title) . '</a>', $output);

            $url = $url2;
            $title = isset($new_value) ? Unicode::truncate($title2, $new_value, FALSE, TRUE) : $title2;
            $this->assertContains('<a href="' . Html::escape($url) . '">' . Html::escape($title) . '</a>', $output);

            $url = $url3;
            $title = isset($new_value) ? Unicode::truncate($title3, $new_value, FALSE, TRUE) : $title3;
            $this->assertContains('<a href="' . Html::escape($url) . '">' . Html::escape($title) . '</a>', $output);
            break;

          case 'rel':
            $rel = isset($new_value) ? ' rel="' . $new_value . '"' : '';
            $this->assertContains('<a href="' . Html::escape($url1) . '"' . $rel . '>' . Html::escape($title1) . '</a>', $output);
            $this->assertContains('<a href="' . Html::escape($url2) . '"' . $rel . '>' . Html::escape($title2) . '</a>', $output);
            $this->assertContains('<a href="' . Html::escape($url3) . '"' . $rel . '>' . Html::escape($title3) . '</a>', $output);
            break;

          case 'target':
            $target = isset($new_value) ? ' target="' . $new_value . '"' : '';
            $this->assertContains('<a href="' . Html::escape($url1) . '"' . $target . '>' . Html::escape($title1) . '</a>', $output);
            $this->assertContains('<a href="' . Html::escape($url2) . '"' . $target . '>' . Html::escape($title2) . '</a>', $output);
            $this->assertContains('<a href="' . Html::escape($url3) . '"' . $target . '>' . Html::escape($title3) . '</a>', $output);
            break;

          case 'url_only':
            // In this case, $new_value is an array.
            if (!$new_value['url_only']) {
              $this->assertContains('<a href="' . Html::escape($url1) . '">' . Html::escape($title1) . '</a>', $output);
              $this->assertContains('<a href="' . Html::escape($url2) . '">' . Html::escape($title2) . '</a>', $output);
              $this->assertContains('<a href="' . Html::escape($url3) . '">' . Html::escape($title3) . '</a>', $output);
            }
            else {
              if (empty($new_value['url_plain'])) {
                $this->assertContains('<a href="' . Html::escape($url1) . '">' . Html::escape($url1) . '</a>', $output);
                $this->assertContains('<a href="' . Html::escape($url2) . '">' . Html::escape($url2) . '</a>', $output);
                $this->assertContains('<a href="' . Html::escape($url3) . '">' . Html::escape($url3) . '</a>', $output);
              }
              else {
                $this->assertNotContains('<a href="' . Html::escape($url1) . '">' . Html::escape($url1) . '</a>', $output);
                $this->assertNotContains('<a href="' . Html::escape($url2) . '">' . Html::escape($url2) . '</a>', $output);
                $this->assertNotContains('<a href="' . Html::escape($url3) . '">' . Html::escape($url3) . '</a>', $output);
                $this->assertContains(Html::escape($url1), $output);
                $this->assertContains(Html::escape($url2), $output);
                $this->assertContains(Html::escape($url3), $output);
              }
            }
            break;
        }
      }
    }
  }

  /**
   * Tests the 'link_separate' formatter.
   *
   * This test is mostly the same as testLinkFormatter(), but they cannot be
   * merged, since they involve different configuration and output.
   */
  public function testLinkSeparateFormatter() {
    $field_name = Unicode::strtolower($this->randomMachineName());
    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'link',
      'cardinality' => 3,
    ]);
    $this->fieldStorage->save();
    FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'settings' => [
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ],
    ])->save();
    $display_options = [
      'type' => 'link_separate',
      'label' => 'hidden',
    ];
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, [
        'type' => 'link_default',
      ])
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, $display_options)
      ->save();

    // Create an entity with three link field values:
    // - The first field item uses a URL only.
    // - The second field item uses a URL and link text.
    // - The third field item uses a fragment-only URL with text.
    // For consistency in assertion code below, the URL is assigned to the title
    // variable for the first field.
    $this->drupalGet('entity_test/add');
    $url1 = 'http://www.example.com/content/articles/archive?author=John&year=2012#com';
    $url2 = 'http://www.example.org/content/articles/archive?author=John&year=2012#org';
    $url3 = '#net';
    // Intentionally contains an ampersand that needs sanitization on output.
    $title2 = 'A very long & strange example title that could break the nice layout of the site';
    $title3 = 'Fragment only';
    $edit = [
      "{$field_name}[0][uri]" => $url1,
      "{$field_name}[1][uri]" => $url2,
      "{$field_name}[1][title]" => $title2,
      "{$field_name}[2][uri]" => $url3,
      "{$field_name}[2][title]" => $title3,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->getUrl(), $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', ['@id' => $id]));

    // Verify that the link is output according to the formatter settings.
    $options = [
      'trim_length' => [NULL, 6],
      'rel' => [NULL, 'nofollow'],
      'target' => [NULL, '_blank'],
    ];
    foreach ($options as $setting => $values) {
      foreach ($values as $new_value) {
        // Update the field formatter settings.
        $display_options['settings'] = [$setting => $new_value];
        entity_get_display('entity_test', 'entity_test', 'full')
          ->setComponent($field_name, $display_options)
          ->save();

        $output = $this->renderTestEntity($id);
        switch ($setting) {
          case 'trim_length':
            $url = $url1;
            $url_title = isset($new_value) ? Unicode::truncate($url, $new_value, FALSE, TRUE) : $url;
            $expected = '<div class="link-item">';
            $expected .= '<div class="link-url"><a href="' . Html::escape($url) . '">' . Html::escape($url_title) . '</a></div>';
            $expected .= '</div>';
            $this->assertContains($expected, $output);

            $url = $url2;
            $url_title = isset($new_value) ? Unicode::truncate($url, $new_value, FALSE, TRUE) : $url;
            $title = isset($new_value) ? Unicode::truncate($title2, $new_value, FALSE, TRUE) : $title2;
            $expected = '<div class="link-item">';
            $expected .= '<div class="link-title">' . Html::escape($title) . '</div>';
            $expected .= '<div class="link-url"><a href="' . Html::escape($url) . '">' . Html::escape($url_title) . '</a></div>';
            $expected .= '</div>';
            $this->assertContains($expected, $output);

            $url = $url3;
            $url_title = isset($new_value) ? Unicode::truncate($url, $new_value, FALSE, TRUE) : $url;
            $title = isset($new_value) ? Unicode::truncate($title3, $new_value, FALSE, TRUE) : $title3;
            $expected = '<div class="link-item">';
            $expected .= '<div class="link-title">' . Html::escape($title) . '</div>';
            $expected .= '<div class="link-url"><a href="' . Html::escape($url) . '">' . Html::escape($url_title) . '</a></div>';
            $expected .= '</div>';
            $this->assertContains($expected, $output);
            break;

          case 'rel':
            $rel = isset($new_value) ? ' rel="' . $new_value . '"' : '';
            $this->assertContains('<div class="link-url"><a href="' . Html::escape($url1) . '"' . $rel . '>' . Html::escape($url1) . '</a></div>', $output);
            $this->assertContains('<div class="link-url"><a href="' . Html::escape($url2) . '"' . $rel . '>' . Html::escape($url2) . '</a></div>', $output);
            $this->assertContains('<div class="link-url"><a href="' . Html::escape($url3) . '"' . $rel . '>' . Html::escape($url3) . '</a></div>', $output);
            break;

          case 'target':
            $target = isset($new_value) ? ' target="' . $new_value . '"' : '';
            $this->assertContains('<div class="link-url"><a href="' . Html::escape($url1) . '"' . $target . '>' . Html::escape($url1) . '</a></div>', $output);
            $this->assertContains('<div class="link-url"><a href="' . Html::escape($url2) . '"' . $target . '>' . Html::escape($url2) . '</a></div>', $output);
            $this->assertContains('<div class="link-url"><a href="' . Html::escape($url3) . '"' . $target . '>' . Html::escape($url3) . '</a></div>', $output);
            break;
        }
      }
    }
  }


  /**
   * Tests editing a link to a non-node entity.
   */
  public function testEditNonNodeEntityLink() {

    $entity_type_manager = \Drupal::entityTypeManager();
    $entity_test_storage = $entity_type_manager->getStorage('entity_test');

    // Create a field with settings to validate.
    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'field_link',
      'entity_type' => 'entity_test',
      'type' => 'link',
      'cardinality' => 1,
    ]);
    $this->fieldStorage->save();
    FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'label' => 'Read more about this entity',
      'bundle' => 'entity_test',
      'settings' => [
        'title' => DRUPAL_OPTIONAL,
      ],
    ])->save();

    $entity_type_manager
      ->getStorage('entity_form_display')
      ->load('entity_test.entity_test.default')
      ->setComponent('field_link', [
        'type' => 'link_default',
      ])
      ->save();

    // Create a node and a test entity to have a possibly valid reference for
    // both. Create another test entity that references the first test entity.
    $entity_test_link = $entity_test_storage->create(['name' => 'correct link target']);
    $entity_test_link->save();

    $node = $this->drupalCreateNode(['wrong link target']);

    $correct_link = 'entity:entity_test/' . $entity_test_link->id();
    $entity_test = $entity_test_storage->create([
      'name' => 'correct link target',
      'field_link' => $correct_link,
    ]);
    $entity_test->save();

    // Edit the entity and save it, verify the correct link is kept and not
    // changed to point to a node. Currently, widget does not support non-node
    // autocomplete and therefore must show the link unaltered.
    $this->drupalGet($entity_test->toUrl('edit-form'));
    $this->assertSession()->fieldValueEquals('field_link[0][uri]', $correct_link);
    $this->drupalPostForm(NULL, [], 'Save');

    $entity_test_storage->resetCache();
    $entity_test = $entity_test_storage->load($entity_test->id());

    $this->assertEquals($correct_link, $entity_test->get('field_link')->uri);
  }

  /**
   * Renders a test_entity and returns the output.
   *
   * @param int $id
   *   The test_entity ID to render.
   * @param string $view_mode
   *   (optional) The view mode to use for rendering.
   * @param bool $reset
   *   (optional) Whether to reset the entity_test storage cache. Defaults to
   *   TRUE to simplify testing.
   *
   * @return string
   *   The rendered HTML output.
   */
  protected function renderTestEntity($id, $view_mode = 'full', $reset = TRUE) {
    if ($reset) {
      $this->container->get('entity.manager')->getStorage('entity_test')->resetCache([$id]);
    }
    $entity = EntityTest::load($id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), $view_mode);
    $content = $display->build($entity);
    $output = \Drupal::service('renderer')->renderRoot($content);
    $output = (string) $output;
    $this->verbose($output);
    return $output;
  }

}
