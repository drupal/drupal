<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Ajax\AjaxFormPageCacheTest.
 */

namespace Drupal\system\Tests\Ajax;

/**
 * Performs tests on AJAX forms in cached pages.
 *
 * @group Ajax
 */
class AjaxFormPageCacheTest extends AjaxTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
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
   * Create a simple form, then POST to system/ajax to change to it.
   */
  public function testSimpleAJAXFormValue() {
   $this->drupalGet('ajax_forms_test_get_form');
   $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'MISS', 'Page was not cached.');
   $build_id_initial = $this->getFormBuildId();

   $edit = ['select' => 'green'];
   $commands = $this->drupalPostAjaxForm(NULL, $edit, 'select');
   $build_id_first_ajax = $this->getFormBuildId();
   $this->assertNotEqual($build_id_initial, $build_id_first_ajax, 'Build id is changed in the simpletest-DOM on first AJAX submission');
   $expected = [
     'command' => 'update_build_id',
     'old' => $build_id_initial,
     'new' => $build_id_first_ajax,
   ];
   $this->assertCommand($commands, $expected, 'Build id change command issued on first AJAX submission');

   $edit = ['select' => 'red'];
   $commands = $this->drupalPostAjaxForm(NULL, $edit, 'select');
   $build_id_second_ajax = $this->getFormBuildId();
   $this->assertNotEqual($build_id_first_ajax, $build_id_second_ajax, 'Build id changes on subsequent AJAX submissions');
   $expected = [
     'command' => 'update_build_id',
     'old' => $build_id_first_ajax,
     'new' => $build_id_second_ajax,
   ];
   $this->assertCommand($commands, $expected, 'Build id change command issued on subsequent AJAX submissions');

   // Repeat the test sequence but this time with a page loaded from the cache.
   $this->drupalGet('ajax_forms_test_get_form');
   $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), 'HIT', 'Page was cached.');
   $build_id_from_cache_initial = $this->getFormBuildId();
   $this->assertEqual($build_id_initial, $build_id_from_cache_initial, 'Build id is the same as on the first request');

   $edit = ['select' => 'green'];
   $commands = $this->drupalPostAjaxForm(NULL, $edit, 'select');
   $build_id_from_cache_first_ajax = $this->getFormBuildId();
   $this->assertNotEqual($build_id_from_cache_initial, $build_id_from_cache_first_ajax, 'Build id is changed in the simpletest-DOM on first AJAX submission');
   $this->assertNotEqual($build_id_first_ajax, $build_id_from_cache_first_ajax, 'Build id from first user is not reused');
   $expected = [
     'command' => 'update_build_id',
     'old' => $build_id_from_cache_initial,
     'new' => $build_id_from_cache_first_ajax,
   ];
   $this->assertCommand($commands, $expected, 'Build id change command issued on first AJAX submission');

   $edit = ['select' => 'red'];
   $commands = $this->drupalPostAjaxForm(NULL, $edit, 'select');
   $build_id_from_cache_second_ajax = $this->getFormBuildId();
   $this->assertNotEqual($build_id_from_cache_first_ajax, $build_id_from_cache_second_ajax, 'Build id changes on subsequent AJAX submissions');
   $expected = [
     'command' => 'update_build_id',
     'old' => $build_id_from_cache_first_ajax,
     'new' => $build_id_from_cache_second_ajax,
   ];
   $this->assertCommand($commands, $expected, 'Build id change command issued on subsequent AJAX submissions');
 }

  /**
   * Tests a form that uses an #ajax callback.
   *
   * @see \Drupal\system\Tests\Ajax\ElementValidationTest::testAjaxElementValidation()
   */
  public function testAjaxElementValidation() {
    $edit = ['drivertext' => t('some dumb text')];
    $this->drupalPostAjaxForm('ajax_validation_test', $edit, 'drivertext');
  }

}
