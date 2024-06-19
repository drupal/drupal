<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Form;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests form storage from cached pages.
 *
 * @group Form
 */
class FormStoragePageCacheTest extends BrowserTestBase {

  /**
   * @var array
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 300);
    $config->save();
  }

  /**
   * Return the build id of the current form.
   */
  protected function getFormBuildId() {
    // Ensure the hidden 'form_build_id' field is unique.
    $this->assertSession()->elementsCount('xpath', '//input[@name="form_build_id"]', 1);
    return (string) $this->assertSession()->hiddenFieldExists('form_build_id')->getAttribute('value');
  }

  /**
   * Build-id is regenerated when validating cached form.
   */
  public function testValidateFormStorageOnCachedPage(): void {
    $this->drupalGet('form-test/form-storage-page-cache');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertSession()->pageTextContains('No old build id');
    $build_id_initial = $this->getFormBuildId();

    // Trigger validation error by submitting an empty title.
    $edit = ['title' => ''];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('No old build id');
    $build_id_first_validation = $this->getFormBuildId();
    $this->assertNotEquals($build_id_initial, $build_id_first_validation, 'Build id changes when form validation fails');

    // Trigger validation error by again submitting an empty title.
    $edit = ['title' => ''];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('No old build id');
    $build_id_second_validation = $this->getFormBuildId();
    $this->assertEquals($build_id_first_validation, $build_id_second_validation, 'Build id remains the same when form validation fails subsequently');

    // Repeat the test sequence but this time with a page loaded from the cache.
    $this->drupalGet('form-test/form-storage-page-cache');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'HIT');
    $this->assertSession()->pageTextContains('No old build id');
    $build_id_from_cache_initial = $this->getFormBuildId();
    $this->assertEquals($build_id_initial, $build_id_from_cache_initial, 'Build id is the same as on the first request');

    // Trigger validation error by submitting an empty title.
    $edit = ['title' => ''];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('No old build id');
    $build_id_from_cache_first_validation = $this->getFormBuildId();
    $this->assertNotEquals($build_id_initial, $build_id_from_cache_first_validation, 'Build id changes when form validation fails');
    $this->assertNotEquals($build_id_first_validation, $build_id_from_cache_first_validation, 'Build id from first user is not reused');

    // Trigger validation error by again submitting an empty title.
    $edit = ['title' => ''];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('No old build id');
    $build_id_from_cache_second_validation = $this->getFormBuildId();
    $this->assertEquals($build_id_from_cache_first_validation, $build_id_from_cache_second_validation, 'Build id remains the same when form validation fails subsequently');
  }

  /**
   * Build-id is regenerated when rebuilding cached form.
   */
  public function testRebuildFormStorageOnCachedPage(): void {
    $this->drupalGet('form-test/form-storage-page-cache');
    $this->assertSession()->responseHeaderEquals('X-Drupal-Cache', 'MISS');
    $this->assertSession()->pageTextContains('No old build id');
    $build_id_initial = $this->getFormBuildId();

    // Trigger rebuild, should regenerate build id. When a submit handler
    // triggers a rebuild, the form is built twice in the same POST request,
    // and during the second build, there is an old build ID, but because the
    // form is not cached during the initial GET request, it is different from
    // that initial build ID.
    $edit = ['title' => 'something'];
    $this->submitForm($edit, 'Rebuild');
    $this->assertSession()->pageTextNotContains('No old build id');
    $this->assertSession()->pageTextNotContains($build_id_initial);
    $build_id_first_rebuild = $this->getFormBuildId();
    $this->assertNotEquals($build_id_initial, $build_id_first_rebuild, 'Build id changes on first rebuild.');

    // Trigger subsequent rebuild, should regenerate the build id again.
    $edit = ['title' => 'something'];
    $this->submitForm($edit, 'Rebuild');
    $this->assertSession()->pageTextContains($build_id_first_rebuild);
    $build_id_second_rebuild = $this->getFormBuildId();
    $this->assertNotEquals($build_id_first_rebuild, $build_id_second_rebuild, 'Build id changes on second rebuild.');
  }

}
