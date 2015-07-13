<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Form\FormStoragePageCacheTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests form storage from cached pages.
 *
 * @group Form
 */
class FormStoragePageCacheTest extends WebTestBase {

  /**
   * @var array
   */
  public static $modules = array('form_test');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();
  }

  /**
   * Return the build id of the current form.
   */
  protected function getFormBuildId() {
    $build_id_fields = $this->xpath('//input[@name="form_build_id"]');
    $this->assertEqual(count($build_id_fields), 1, 'One form build id field on the page');
    return (string) $build_id_fields[0]['value'];
  }

  /**
   * Build-id is regenerated when validating cached form.
   */
  public function testValidateFormStorageOnCachedPage() {
    $this->drupalGet('form-test/form-storage-page-cache');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Page was not cached.');
    $this->assertText('No old build id', 'No old build id on the page');
    $build_id_initial = $this->getFormBuildId();

    // Trigger validation error by submitting an empty title.
    $edit = ['title' => ''];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('No old build id', 'No old build id on the page');
    $build_id_first_validation = $this->getFormBuildId();
    $this->assertNotEqual($build_id_initial, $build_id_first_validation, 'Build id changes when form validation fails');

    // Trigger validation error by again submitting an empty title.
    $edit = ['title' => ''];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('No old build id', 'No old build id on the page');
    $build_id_second_validation = $this->getFormBuildId();
    $this->assertEqual($build_id_first_validation, $build_id_second_validation, 'Build id remains the same when form validation fails subsequently');

    // Repeat the test sequence but this time with a page loaded from the cache.
    $this->drupalGet('form-test/form-storage-page-cache');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
    $this->assertText('No old build id', 'No old build id on the page');
    $build_id_from_cache_initial = $this->getFormBuildId();
    $this->assertEqual($build_id_initial, $build_id_from_cache_initial, 'Build id is the same as on the first request');

    // Trigger validation error by submitting an empty title.
    $edit = ['title' => ''];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('No old build id', 'No old build id on the page');
    $build_id_from_cache_first_validation = $this->getFormBuildId();
    $this->assertNotEqual($build_id_initial, $build_id_from_cache_first_validation, 'Build id changes when form validation fails');
    $this->assertNotEqual($build_id_first_validation, $build_id_from_cache_first_validation, 'Build id from first user is not reused');

    // Trigger validation error by again submitting an empty title.
    $edit = ['title' => ''];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('No old build id', 'No old build id on the page');
    $build_id_from_cache_second_validation = $this->getFormBuildId();
    $this->assertEqual($build_id_from_cache_first_validation, $build_id_from_cache_second_validation, 'Build id remains the same when form validation fails subsequently');
  }

  /**
   * Build-id is regenerated when rebuilding cached form.
   */
  public function testRebuildFormStorageOnCachedPage() {
    $this->drupalGet('form-test/form-storage-page-cache');
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Page was not cached.');
    $this->assertText('No old build id', 'No old build id on the page');
    $build_id_initial = $this->getFormBuildId();

    // Trigger rebuild, should regenerate build id. When a submit handler
    // triggers a rebuild, the form is built twice in the same POST request,
    // and during the second build, there is an old build ID, but because the
    // form is not cached during the initial GET request, it is different from
    // that initial build ID.
    $edit = ['title' => 'something'];
    $this->drupalPostForm(NULL, $edit, 'Rebuild');
    $this->assertNoText('No old build id', 'There is no old build id on the page.');
    $this->assertNoText($build_id_initial, 'The old build id is not the initial build id.');
    $build_id_first_rebuild = $this->getFormBuildId();
    $this->assertNotEqual($build_id_initial, $build_id_first_rebuild, 'Build id changes on first rebuild.');

    // Trigger subsequent rebuild, should regenerate the build id again.
    $edit = ['title' => 'something'];
    $this->drupalPostForm(NULL, $edit, 'Rebuild');
    $this->assertText($build_id_first_rebuild, 'First build id as old build id on the page');
    $build_id_second_rebuild = $this->getFormBuildId();
    $this->assertNotEqual($build_id_first_rebuild, $build_id_second_rebuild, 'Build id changes on second rebuild.');
  }

}
