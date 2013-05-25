<?php

/**
 * @file
 * Contains Drupal\link\Tests\LinkFieldTest.
 */

namespace Drupal\link\Tests;

use Drupal\Core\Language\Language;
use Drupal\simpletest\WebTestBase;

/**
 * Tests link field widgets and formatters.
 */
class LinkFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('field_test', 'link');

  public static function getInfo() {
    return array(
      'name' => 'Link field',
      'description' => 'Tests link field widgets and formatters.',
      'group' => 'Field types',
    );
  }

  function setUp() {
    parent::setUp();

    $this->web_user = $this->drupalCreateUser(array(
      'access field_test content',
      'administer field_test content',
    ));
    $this->drupalLogin($this->web_user);
  }

  /**
   * Tests link field URL validation.
   */
  function testURLValidation() {
    // Create a field with settings to validate.
    $this->field = array(
      'field_name' => drupal_strtolower($this->randomName()),
      'type' => 'link',
    );
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'settings' => array(
        'title' => DRUPAL_DISABLED,
      ),
    );
    field_create_instance($this->instance);
    entity_get_form_display('test_entity', 'test_bundle', 'default')
      ->setComponent($this->field['field_name'], array(
        'type' => 'link_default',
        'settings' => array(
          'placeholder_url' => 'http://example.com',
        ),
      ))
      ->save();
    entity_get_display('test_entity', 'test_bundle', 'full')
      ->setComponent($this->field['field_name'], array(
        'type' => 'link',
      ))
      ->save();

    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Display creation form.
    $this->drupalGet('test-entity/add/test_bundle');
    $this->assertFieldByName("{$this->field['field_name']}[$langcode][0][url]", '', 'Link URL field is displayed');
    $this->assertRaw('placeholder="http://example.com"');

    // Verify that a valid URL can be submitted.
    $value = 'http://www.example.com/';
    $edit = array(
      "{$this->field['field_name']}[$langcode][0][url]" => $value,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)));
    $this->assertRaw($value);

    // Verify that invalid URLs cannot be submitted.
    $wrong_entries = array(
      // Missing protcol
      'not-an-url',
      // Invalid protocol
      'invalid://not-a-valid-protocol',
      // Missing host name
      'http://',
    );
    $this->drupalGet('test-entity/add/test_bundle');
    foreach ($wrong_entries as $invalid_value) {
      $edit = array(
        "{$this->field['field_name']}[$langcode][0][url]" => $invalid_value,
      );
      $this->drupalPost(NULL, $edit, t('Save'));
      $this->assertText(t('The URL @url is not valid.', array('@url' => $invalid_value)));
    }
  }

  /**
   * Tests the title settings of a link field.
   */
  function testLinkTitle() {
    // Create a field with settings to validate.
    $this->field = array(
      'field_name' => drupal_strtolower($this->randomName()),
      'type' => 'link',
    );
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'label' => 'Read more about this entity',
      'settings' => array(
        'title' => DRUPAL_OPTIONAL,
      ),
    );
    field_create_instance($this->instance);
    entity_get_form_display('test_entity', 'test_bundle', 'default')
      ->setComponent($this->field['field_name'], array(
        'type' => 'link_default',
        'settings' => array(
          'placeholder_url' => 'http://example.com',
          'placeholder_title' => 'Enter a title for this link',
        ),
      ))
      ->save();
    entity_get_display('test_entity', 'test_bundle', 'full')
      ->setComponent($this->field['field_name'], array(
        'type' => 'link',
        'label' => 'hidden',
      ))
      ->save();

    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Verify that the title field works according to the field setting.
    foreach (array(DRUPAL_DISABLED, DRUPAL_REQUIRED, DRUPAL_OPTIONAL) as $title_setting) {
      // Update the title field setting.
      $this->instance['settings']['title'] = $title_setting;
      field_update_instance($this->instance);

      // Display creation form.
      $this->drupalGet('test-entity/add/test_bundle');
      // Assert label is shown.
      $this->assertText('Read more about this entity');
      $this->assertFieldByName("{$this->field['field_name']}[$langcode][0][url]", '', 'URL field found.');
      $this->assertRaw('placeholder="http://example.com"');

      if ($title_setting === DRUPAL_DISABLED) {
        $this->assertNoFieldByName("{$this->field['field_name']}[$langcode][0][title]", '', 'Title field not found.');
        $this->assertNoRaw('placeholder="Enter a title for this link"');
      }
      else {
        $this->assertRaw('placeholder="Enter a title for this link"');

        $this->assertFieldByName("{$this->field['field_name']}[$langcode][0][title]", '', 'Title field found.');
        if ($title_setting === DRUPAL_REQUIRED) {
          // Verify that the title is required, if the URL is non-empty.
          $edit = array(
            "{$this->field['field_name']}[$langcode][0][url]" => 'http://www.example.com',
          );
          $this->drupalPost(NULL, $edit, t('Save'));
          $this->assertText(t('!name field is required.', array('!name' => t('Title'))));

          // Verify that the title is not required, if the URL is empty.
          $edit = array(
            "{$this->field['field_name']}[$langcode][0][url]" => '',
          );
          $this->drupalPost(NULL, $edit, t('Save'));
          $this->assertNoText(t('!name field is required.', array('!name' => t('Title'))));

          // Verify that a URL and title meets requirements.
          $this->drupalGet('test-entity/add/test_bundle');
          $edit = array(
            "{$this->field['field_name']}[$langcode][0][url]" => 'http://www.example.com',
            "{$this->field['field_name']}[$langcode][0][title]" => 'Example',
          );
          $this->drupalPost(NULL, $edit, t('Save'));
          $this->assertNoText(t('!name field is required.', array('!name' => t('Title'))));
        }
      }
    }

    // Verify that a link without title is rendered using the URL as link text.
    $value = 'http://www.example.com/';
    $edit = array(
      "{$this->field['field_name']}[$langcode][0][url]" => $value,
      "{$this->field['field_name']}[$langcode][0][title]" => '',
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)));

    $this->renderTestEntity($id);
    $expected_link = l($value, $value);
    $this->assertRaw($expected_link);

    // Verify that a link with title is rendered using the title as link text.
    $title = $this->randomName();
    $edit = array(
      "{$this->field['field_name']}[$langcode][0][title]" => $title,
    );
    $this->drupalPost("test-entity/manage/$id/edit", $edit, t('Save'));
    $this->assertRaw(t('test_entity @id has been updated.', array('@id' => $id)));

    $this->renderTestEntity($id);
    $expected_link = l($title, $value);
    $this->assertRaw($expected_link);
  }

  /**
   * Tests the default 'link' formatter.
   */
  function testLinkFormatter() {
    // Create a field with settings to validate.
    $this->field = array(
      'field_name' => drupal_strtolower($this->randomName()),
      'type' => 'link',
      'cardinality' => 2,
    );
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field['field_name'],
      'entity_type' => 'test_entity',
      'label' => 'Read more about this entity',
      'bundle' => 'test_bundle',
      'settings' => array(
        'title' => DRUPAL_OPTIONAL,
      ),
    );
    $display_options = array(
      'type' => 'link',
      'label' => 'hidden',
    );
    field_create_instance($this->instance);
    entity_get_form_display('test_entity', 'test_bundle', 'default')
      ->setComponent($this->field['field_name'], array(
        'type' => 'link_default',
      ))
      ->save();
    entity_get_display('test_entity', 'test_bundle', 'full')
      ->setComponent($this->field['field_name'], $display_options)
      ->save();

    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Create an entity with two link field values:
    // - The first field item uses a URL only.
    // - The second field item uses a URL and title.
    // For consistency in assertion code below, the URL is assigned to the title
    // variable for the first field.
    $this->drupalGet('test-entity/add/test_bundle');
    $url1 = 'http://www.example.com/content/articles/archive?author=John&year=2012#com';
    $url2 = 'http://www.example.org/content/articles/archive?author=John&year=2012#org';
    $title1 = $url1;
    // Intentionally contains an ampersand that needs sanitization on output.
    $title2 = 'A very long & strange example title that could break the nice layout of the site';
    $edit = array(
      "{$this->field['field_name']}[$langcode][0][url]" => $url1,
      // Note that $title1 is not submitted.
      "{$this->field['field_name']}[$langcode][0][title]" => '',
      "{$this->field['field_name']}[$langcode][1][url]" => $url2,
      "{$this->field['field_name']}[$langcode][1][title]" => $title2,
    );
    // Assert label is shown.
    $this->assertText('Read more about this entity');
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)));

    // Verify that the link is output according to the formatter settings.
    // Not using generatePermutations(), since that leads to 32 cases, which
    // would not test actual link field formatter functionality but rather
    // theme_link() and options/attributes. Only 'url_plain' has a dependency on
    // 'url_only', so we have a total of ~10 cases.
    $options = array(
      'trim_length' => array(NULL, 6),
      'rel' => array(NULL, 'nofollow'),
      'target' => array(NULL, '_blank'),
      'url_only' => array(
        array('url_only' => array(FALSE)),
        array('url_only' => array(FALSE), 'url_plain' => TRUE),
        array('url_only' => array(TRUE)),
        array('url_only' => array(TRUE), 'url_plain' => TRUE),
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
        entity_get_display('test_entity', 'test_bundle', 'full')
          ->setComponent($this->field['field_name'], $display_options)
          ->save();

        $this->renderTestEntity($id);
        switch ($setting) {
          case 'trim_length':
            $url = $url1;
            $title = isset($new_value) ? truncate_utf8($title1, $new_value, FALSE, TRUE) : $title1;
            $this->assertRaw('<a href="' . check_plain($url) . '">' . check_plain($title) . '</a>');

            $url = $url2;
            $title = isset($new_value) ? truncate_utf8($title2, $new_value, FALSE, TRUE) : $title2;
            $this->assertRaw('<a href="' . check_plain($url) . '">' . check_plain($title) . '</a>');
            break;

          case 'rel':
            $rel = isset($new_value) ? ' rel="' . $new_value . '"' : '';
            $this->assertRaw('<a href="' . check_plain($url1) . '"' . $rel . '>' . check_plain($title1) . '</a>');
            $this->assertRaw('<a href="' . check_plain($url2) . '"' . $rel . '>' . check_plain($title2) . '</a>');
            break;

          case 'target':
            $target = isset($new_value) ? ' target="' . $new_value . '"' : '';
            $this->assertRaw('<a href="' . check_plain($url1) . '"' . $target . '>' . check_plain($title1) . '</a>');
            $this->assertRaw('<a href="' . check_plain($url2) . '"' . $target . '>' . check_plain($title2) . '</a>');
            break;

          case 'url_only':
            // In this case, $new_value is an array.
            if (!$new_value['url_only']) {
              $this->assertRaw('<a href="' . check_plain($url1) . '">' . check_plain($title1) . '</a>');
              $this->assertRaw('<a href="' . check_plain($url2) . '">' . check_plain($title2) . '</a>');
            }
            else {
              if (empty($new_value['url_plain'])) {
                $this->assertRaw('<a href="' . check_plain($url1) . '">' . check_plain($url1) . '</a>');
                $this->assertRaw('<a href="' . check_plain($url2) . '">' . check_plain($url2) . '</a>');
              }
              else {
                $this->assertNoRaw('<a href="' . check_plain($url1) . '">' . check_plain($url1) . '</a>');
                $this->assertNoRaw('<a href="' . check_plain($url2) . '">' . check_plain($url2) . '</a>');
                $this->assertRaw(check_plain($url1));
                $this->assertRaw(check_plain($url2));
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
    // Create a field with settings to validate.
    $this->field = array(
      'field_name' => drupal_strtolower($this->randomName()),
      'type' => 'link',
      'cardinality' => 2,
    );
    field_create_field($this->field);
    $this->instance = array(
      'field_name' => $this->field['field_name'],
      'entity_type' => 'test_entity',
      'bundle' => 'test_bundle',
      'settings' => array(
        'title' => DRUPAL_OPTIONAL,
      ),
    );
    $display_options = array(
      'type' => 'link_separate',
      'label' => 'hidden',
    );
    field_create_instance($this->instance);
    entity_get_form_display('test_entity', 'test_bundle', 'default')
      ->setComponent($this->field['field_name'], array(
        'type' => 'link_default',
      ))
      ->save();
    entity_get_display('test_entity', 'test_bundle', 'full')
      ->setComponent($this->field['field_name'], $display_options)
      ->save();

    $langcode = Language::LANGCODE_NOT_SPECIFIED;

    // Create an entity with two link field values:
    // - The first field item uses a URL only.
    // - The second field item uses a URL and title.
    // For consistency in assertion code below, the URL is assigned to the title
    // variable for the first field.
    $this->drupalGet('test-entity/add/test_bundle');
    $url1 = 'http://www.example.com/content/articles/archive?author=John&year=2012#com';
    $url2 = 'http://www.example.org/content/articles/archive?author=John&year=2012#org';
    // Intentionally contains an ampersand that needs sanitization on output.
    $title2 = 'A very long & strange example title that could break the nice layout of the site';
    $edit = array(
      "{$this->field['field_name']}[$langcode][0][url]" => $url1,
      "{$this->field['field_name']}[$langcode][1][url]" => $url2,
      "{$this->field['field_name']}[$langcode][1][title]" => $title2,
    );
    $this->drupalPost(NULL, $edit, t('Save'));
    preg_match('|test-entity/manage/(\d+)/edit|', $this->url, $match);
    $id = $match[1];
    $this->assertRaw(t('test_entity @id has been created.', array('@id' => $id)));

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
        entity_get_display('test_entity', 'test_bundle', 'full')
          ->setComponent($this->field['field_name'], $display_options)
          ->save();

        $this->renderTestEntity($id);
        switch ($setting) {
          case 'trim_length':
            $url = $url1;
            $url_title = isset($new_value) ? truncate_utf8($url, $new_value, FALSE, TRUE) : $url;
            $expected = '<div class="link-item">';
            $expected .= '<div class="link-url"><a href="' . check_plain($url) . '">' . check_plain($url_title) . '</a></div>';
            $expected .= '</div>';
            $this->assertRaw($expected);

            $url = $url2;
            $url_title = isset($new_value) ? truncate_utf8($url, $new_value, FALSE, TRUE) : $url;
            $title = isset($new_value) ? truncate_utf8($title2, $new_value, FALSE, TRUE) : $title2;
            $expected = '<div class="link-item">';
            $expected .= '<div class="link-title">' . check_plain($title) . '</div>';
            $expected .= '<div class="link-url"><a href="' . check_plain($url) . '">' . check_plain($url_title) . '</a></div>';
            $expected .= '</div>';
            $this->assertRaw($expected);
            break;

          case 'rel':
            $rel = isset($new_value) ? ' rel="' . $new_value . '"' : '';
            $this->assertRaw('<div class="link-url"><a href="' . check_plain($url1) . '"' . $rel . '>' . check_plain($url1) . '</a></div>');
            $this->assertRaw('<div class="link-url"><a href="' . check_plain($url2) . '"' . $rel . '>' . check_plain($url2) . '</a></div>');
            break;

          case 'target':
            $target = isset($new_value) ? ' target="' . $new_value . '"' : '';
            $this->assertRaw('<div class="link-url"><a href="' . check_plain($url1) . '"' . $target . '>' . check_plain($url1) . '</a></div>');
            $this->assertRaw('<div class="link-url"><a href="' . check_plain($url2) . '"' . $target . '>' . check_plain($url2) . '</a></div>');
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
   *   (optional) Whether to reset the test_entity controller cache. Defaults to
   *   TRUE to simplify testing.
   */
  protected function renderTestEntity($id, $view_mode = 'full', $reset = TRUE) {
    if ($reset) {
      $this->container->get('plugin.manager.entity')->getStorageController('test_entity')->resetCache(array($id));
    }
    $entity = field_test_entity_test_load($id);
    $display = entity_get_display($entity->entityType(), $entity->bundle(), $view_mode);
    field_attach_prepare_view('test_entity', array($entity->id() => $entity), array($entity->bundle() => $display));
    $entity->content = field_attach_view($entity, $display);

    $output = drupal_render($entity->content);
    $this->drupalSetContent($output);
    $this->verbose($output);
  }
}
