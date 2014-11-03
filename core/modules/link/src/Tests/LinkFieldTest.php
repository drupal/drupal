<?php

/**
 * @file
 * Contains Drupal\link\Tests\LinkFieldTest.
 */

namespace Drupal\link\Tests;

use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Unicode;
use Drupal\link\LinkItemInterface;
use Drupal\simpletest\WebTestBase;

/**
 * Tests link field widgets and formatters.
 *
 * @group link
 */
class LinkFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test', 'link');

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

  /**
   * A user with permission to view and manage test entities.
   *
   * @var object
   */
  protected $web_user;

  protected function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array(
      'view test entity',
      'administer entity_test content',
      'link to any page',
    ));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests link field URL validation.
   */
  function testURLValidation() {
    $field_name = drupal_strtolower($this->randomMachineName());
    // Create a field with settings to validate.
    $this->fieldStorage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'link',
    ));
    $this->fieldStorage->save();
    $this->field = entity_create('field_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'settings' => array(
        'title' => DRUPAL_DISABLED,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ),
    ));
    $this->field->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'link_default',
        'settings' => array(
          'placeholder_url' => 'http://example.com',
        ),
      ))
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, array(
        'type' => 'link',
      ))
      ->save();

    // Display creation form.
    $this->drupalGet('entity_test/add');
    $this->assertFieldByName("{$field_name}[0][url]", '', 'Link URL field is displayed');
    $this->assertRaw('placeholder="http://example.com"');

    // Create a path alias.
    \Drupal::service('path.alias_storage')->save('admin', 'a/path/alias');
    // Define some valid URLs.
    $valid_external_entries = array(
      'http://www.example.com/',
    );
    $valid_internal_entries = array(
      'entity_test/add',
      'a/path/alias',
    );

    // Define some invalid URLs.
    $invalid_external_entries = array(
      // Missing protcol
      'not-an-url',
      // Invalid protocol
      'invalid://not-a-valid-protocol',
      // Missing host name
      'http://',
    );
    $invalid_internal_entries = array(
      'non/existing/path',
    );

    // Test external and internal URLs for 'link_type' = LinkItemInterface::LINK_GENERIC.
    $this->assertValidEntries($field_name, $valid_external_entries + $valid_internal_entries);
    $this->assertInvalidEntries($field_name, $invalid_external_entries + $invalid_internal_entries);

    // Test external URLs for 'link_type' = LinkItemInterface::LINK_EXTERNAL.
    $this->field->settings['link_type'] = LinkItemInterface::LINK_EXTERNAL;
    $this->field->save();
    $this->assertValidEntries($field_name, $valid_external_entries);
    $this->assertInvalidEntries($field_name, $valid_internal_entries + $invalid_external_entries);

    // Test external URLs for 'link_type' = LinkItemInterface::LINK_INTERNAL.
    $this->field->settings['link_type'] = LinkItemInterface::LINK_INTERNAL;
    $this->field->save();
    $this->assertValidEntries($field_name, $valid_internal_entries);
    $this->assertInvalidEntries($field_name, $valid_external_entries + $invalid_internal_entries);
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
    foreach ($valid_entries as $value) {
      $edit = array(
        "{$field_name}[0][url]" => $value,
      );
      $this->drupalPostForm('entity_test/add', $edit, t('Save'));
      preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
      $id = $match[1];
      $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));
      $this->assertRaw($value);
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
    foreach ($invalid_entries as $invalid_value) {
      $edit = array(
        "{$field_name}[0][url]" => $invalid_value,
      );
      $this->drupalPostForm('entity_test/add', $edit, t('Save'));
      $this->assertText(t('The URL @url is not valid.', array('@url' => $invalid_value)));
    }
  }

  /**
   * Tests the link title settings of a link field.
   */
  function testLinkTitle() {
    $field_name = drupal_strtolower($this->randomMachineName());
    // Create a field with settings to validate.
    $this->fieldStorage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'link',
    ));
    $this->fieldStorage->save();
    $this->field = entity_create('field_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'label' => 'Read more about this entity',
      'settings' => array(
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ),
    ));
    $this->field->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'link_default',
        'settings' => array(
          'placeholder_url' => 'http://example.com',
          'placeholder_title' => 'Enter the text for this link',
        ),
      ))
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, array(
        'type' => 'link',
        'label' => 'hidden',
      ))
      ->save();

    // Verify that the link text field works according to the field setting.
    foreach (array(DRUPAL_DISABLED, DRUPAL_REQUIRED, DRUPAL_OPTIONAL) as $title_setting) {
      // Update the link title field setting.
      $this->field->settings['title'] = $title_setting;
      $this->field->save();

      // Display creation form.
      $this->drupalGet('entity_test/add');
      // Assert label is shown.
      $this->assertText('Read more about this entity');
      $this->assertFieldByName("{$field_name}[0][url]", '', 'URL field found.');
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
          $edit = array(
            "{$field_name}[0][url]" => 'http://www.example.com',
          );
          $this->drupalPostForm(NULL, $edit, t('Save'));
          $this->assertText(t('!name field is required.', array('!name' => t('Link text'))));

          // Verify that the link text is not required, if the URL is empty.
          $edit = array(
            "{$field_name}[0][url]" => '',
          );
          $this->drupalPostForm(NULL, $edit, t('Save'));
          $this->assertNoText(t('!name field is required.', array('!name' => t('Link text'))));

          // Verify that a URL and link text meets requirements.
          $this->drupalGet('entity_test/add');
          $edit = array(
            "{$field_name}[0][url]" => 'http://www.example.com',
            "{$field_name}[0][title]" => 'Example',
          );
          $this->drupalPostForm(NULL, $edit, t('Save'));
          $this->assertNoText(t('!name field is required.', array('!name' => t('Link text'))));
        }
      }
    }

    // Verify that a link without link text is rendered using the URL as text.
    $value = 'http://www.example.com/';
    $edit = array(
      "{$field_name}[0][url]" => $value,
      "{$field_name}[0][title]" => '',
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));

    $this->renderTestEntity($id);
    $expected_link = _l($value, $value);
    $this->assertRaw($expected_link);

    // Verify that a link with text is rendered using the link text.
    $title = $this->randomMachineName();
    $edit = array(
      "{$field_name}[0][title]" => $title,
    );
    $this->drupalPostForm("entity_test/manage/$id", $edit, t('Save'));
    $this->assertText(t('entity_test @id has been updated.', array('@id' => $id)));

    $this->renderTestEntity($id);
    $expected_link = _l($title, $value);
    $this->assertRaw($expected_link);
  }

  /**
   * Tests the default 'link' formatter.
   */
  function testLinkFormatter() {
    $field_name = drupal_strtolower($this->randomMachineName());
    // Create a field with settings to validate.
    $this->fieldStorage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'link',
      'cardinality' => 2,
    ));
    $this->fieldStorage->save();
    entity_create('field_config', array(
      'field_storage' => $this->fieldStorage,
      'label' => 'Read more about this entity',
      'bundle' => 'entity_test',
      'settings' => array(
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ),
    ))->save();
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'link_default',
      ))
      ->save();
    $display_options = array(
      'type' => 'link',
      'label' => 'hidden',
    );
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, $display_options)
      ->save();

    // Create an entity with two link field values:
    // - The first field item uses a URL only.
    // - The second field item uses a URL and link text.
    // For consistency in assertion code below, the URL is assigned to the title
    // variable for the first field.
    $this->drupalGet('entity_test/add');
    $url1 = 'http://www.example.com/content/articles/archive?author=John&year=2012#com';
    $url2 = 'http://www.example.org/content/articles/archive?author=John&year=2012#org';
    $title1 = $url1;
    // Intentionally contains an ampersand that needs sanitization on output.
    $title2 = 'A very long & strange example title that could break the nice layout of the site';
    $edit = array(
      "{$field_name}[0][url]" => $url1,
      // Note that $title1 is not submitted.
      "{$field_name}[0][title]" => '',
      "{$field_name}[1][url]" => $url2,
      "{$field_name}[1][title]" => $title2,
    );
    // Assert label is shown.
    $this->assertText('Read more about this entity');
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));

    // Verify that the link is output according to the formatter settings.
    // Not using generatePermutations(), since that leads to 32 cases, which
    // would not test actual link field formatter functionality but rather
    // _l() and options/attributes. Only 'url_plain' has a dependency on
    // 'url_only', so we have a total of ~10 cases.
    $options = array(
      'trim_length' => array(NULL, 6),
      'rel' => array(NULL, 'nofollow'),
      'target' => array(NULL, '_blank'),
      'url_only' => array(
        array('url_only' => FALSE),
        array('url_only' => FALSE, 'url_plain' => TRUE),
        array('url_only' => TRUE),
        array('url_only' => TRUE, 'url_plain' => TRUE),
      ),
    );
    foreach ($options as $setting => $values) {
      foreach ($values as $new_value) {
        // Update the field formatter settings.
        if (!is_array($new_value)) {
          $display_options['settings'] = array($setting => $new_value);
        }
        else {
          $display_options['settings'] = $new_value;
        }
        entity_get_display('entity_test', 'entity_test', 'full')
          ->setComponent($field_name, $display_options)
          ->save();

        $this->renderTestEntity($id);
        switch ($setting) {
          case 'trim_length':
            $url = $url1;
            $title = isset($new_value) ? Unicode::truncate($title1, $new_value, FALSE, TRUE) : $title1;
            $this->assertRaw('<a href="' . String::checkPlain($url) . '">' . String::checkPlain($title) . '</a>');

            $url = $url2;
            $title = isset($new_value) ? Unicode::truncate($title2, $new_value, FALSE, TRUE) : $title2;
            $this->assertRaw('<a href="' . String::checkPlain($url) . '">' . String::checkPlain($title) . '</a>');
            break;

          case 'rel':
            $rel = isset($new_value) ? ' rel="' . $new_value . '"' : '';
            $this->assertRaw('<a href="' . String::checkPlain($url1) . '"' . $rel . '>' . String::checkPlain($title1) . '</a>');
            $this->assertRaw('<a href="' . String::checkPlain($url2) . '"' . $rel . '>' . String::checkPlain($title2) . '</a>');
            break;

          case 'target':
            $target = isset($new_value) ? ' target="' . $new_value . '"' : '';
            $this->assertRaw('<a href="' . String::checkPlain($url1) . '"' . $target . '>' . String::checkPlain($title1) . '</a>');
            $this->assertRaw('<a href="' . String::checkPlain($url2) . '"' . $target . '>' . String::checkPlain($title2) . '</a>');
            break;

          case 'url_only':
            // In this case, $new_value is an array.
            if (!$new_value['url_only']) {
              $this->assertRaw('<a href="' . String::checkPlain($url1) . '">' . String::checkPlain($title1) . '</a>');
              $this->assertRaw('<a href="' . String::checkPlain($url2) . '">' . String::checkPlain($title2) . '</a>');
            }
            else {
              if (empty($new_value['url_plain'])) {
                $this->assertRaw('<a href="' . String::checkPlain($url1) . '">' . String::checkPlain($url1) . '</a>');
                $this->assertRaw('<a href="' . String::checkPlain($url2) . '">' . String::checkPlain($url2) . '</a>');
              }
              else {
                $this->assertNoRaw('<a href="' . String::checkPlain($url1) . '">' . String::checkPlain($url1) . '</a>');
                $this->assertNoRaw('<a href="' . String::checkPlain($url2) . '">' . String::checkPlain($url2) . '</a>');
                $this->assertRaw(String::checkPlain($url1));
                $this->assertRaw(String::checkPlain($url2));
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
  function testLinkSeparateFormatter() {
    $field_name = drupal_strtolower($this->randomMachineName());
    // Create a field with settings to validate.
    $this->fieldStorage = entity_create('field_storage_config', array(
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'link',
      'cardinality' => 2,
    ));
    $this->fieldStorage->save();
    entity_create('field_config', array(
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'settings' => array(
        'title' => DRUPAL_OPTIONAL,
        'link_type' => LinkItemInterface::LINK_GENERIC,
      ),
    ))->save();
    $display_options = array(
      'type' => 'link_separate',
      'label' => 'hidden',
    );
    entity_get_form_display('entity_test', 'entity_test', 'default')
      ->setComponent($field_name, array(
        'type' => 'link_default',
      ))
      ->save();
    entity_get_display('entity_test', 'entity_test', 'full')
      ->setComponent($field_name, $display_options)
      ->save();

    // Create an entity with two link field values:
    // - The first field item uses a URL only.
    // - The second field item uses a URL and link text.
    // For consistency in assertion code below, the URL is assigned to the title
    // variable for the first field.
    $this->drupalGet('entity_test/add');
    $url1 = 'http://www.example.com/content/articles/archive?author=John&year=2012#com';
    $url2 = 'http://www.example.org/content/articles/archive?author=John&year=2012#org';
    // Intentionally contains an ampersand that needs sanitization on output.
    $title2 = 'A very long & strange example title that could break the nice layout of the site';
    $edit = array(
      "{$field_name}[0][url]" => $url1,
      "{$field_name}[1][url]" => $url2,
      "{$field_name}[1][title]" => $title2,
    );
    $this->drupalPostForm(NULL, $edit, t('Save'));
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(t('entity_test @id has been created.', array('@id' => $id)));

    // Verify that the link is output according to the formatter settings.
    $options = array(
      'trim_length' => array(NULL, 6),
      'rel' => array(NULL, 'nofollow'),
      'target' => array(NULL, '_blank'),
    );
    foreach ($options as $setting => $values) {
      foreach ($values as $new_value) {
        // Update the field formatter settings.
        $display_options['settings'] = array($setting => $new_value);
        entity_get_display('entity_test', 'entity_test', 'full')
          ->setComponent($field_name, $display_options)
          ->save();

        $this->renderTestEntity($id);
        switch ($setting) {
          case 'trim_length':
            $url = $url1;
            $url_title = isset($new_value) ? Unicode::truncate($url, $new_value, FALSE, TRUE) : $url;
            $expected = '<div class="link-item">';
            $expected .= '<div class="link-url"><a href="' . String::checkPlain($url) . '">' . String::checkPlain($url_title) . '</a></div>';
            $expected .= '</div>';
            $this->assertRaw($expected);

            $url = $url2;
            $url_title = isset($new_value) ? Unicode::truncate($url, $new_value, FALSE, TRUE) : $url;
            $title = isset($new_value) ? Unicode::truncate($title2, $new_value, FALSE, TRUE) : $title2;
            $expected = '<div class="link-item">';
            $expected .= '<div class="link-title">' . String::checkPlain($title) . '</div>';
            $expected .= '<div class="link-url"><a href="' . String::checkPlain($url) . '">' . String::checkPlain($url_title) . '</a></div>';
            $expected .= '</div>';
            $this->assertRaw($expected);
            break;

          case 'rel':
            $rel = isset($new_value) ? ' rel="' . $new_value . '"' : '';
            $this->assertRaw('<div class="link-url"><a href="' . String::checkPlain($url1) . '"' . $rel . '>' . String::checkPlain($url1) . '</a></div>');
            $this->assertRaw('<div class="link-url"><a href="' . String::checkPlain($url2) . '"' . $rel . '>' . String::checkPlain($url2) . '</a></div>');
            break;

          case 'target':
            $target = isset($new_value) ? ' target="' . $new_value . '"' : '';
            $this->assertRaw('<div class="link-url"><a href="' . String::checkPlain($url1) . '"' . $target . '>' . String::checkPlain($url1) . '</a></div>');
            $this->assertRaw('<div class="link-url"><a href="' . String::checkPlain($url2) . '"' . $target . '>' . String::checkPlain($url2) . '</a></div>');
            break;
        }
      }
    }
  }

  /**
   * Renders a test_entity and sets the output in the internal browser.
   *
   * @param int $id
   *   The test_entity ID to render.
   * @param string $view_mode
   *   (optional) The view mode to use for rendering.
   * @param bool $reset
   *   (optional) Whether to reset the entity_test storage cache. Defaults to
   *   TRUE to simplify testing.
   */
  protected function renderTestEntity($id, $view_mode = 'full', $reset = TRUE) {
    if ($reset) {
      $this->container->get('entity.manager')->getStorage('entity_test')->resetCache(array($id));
    }
    $entity = entity_load('entity_test', $id);
    $display = entity_get_display($entity->getEntityTypeId(), $entity->bundle(), $view_mode);
    $content = $display->build($entity);
    $output = drupal_render($content);
    $this->drupalSetContent($output);
    $this->verbose($output);
  }

}
