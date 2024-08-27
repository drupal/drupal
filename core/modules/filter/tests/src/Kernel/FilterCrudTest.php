<?php

declare(strict_types=1);

namespace Drupal\Tests\filter\Kernel;

use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests creation, loading, updating, deleting of text formats and filters.
 *
 * @group filter
 */
class FilterCrudTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'filter_test', 'system', 'user'];

  /**
   * Tests CRUD operations for text formats and filters.
   */
  public function testTextFormatCrud(): void {
    // Add a text format with minimum data only.
    $format = FilterFormat::create([
      'format' => 'empty_format',
      'name' => 'Empty format',
    ]);
    $format->save();
    $this->verifyTextFormat($format);

    // Add another text format specifying all possible properties.
    $format = FilterFormat::create([
      'format' => 'custom_format',
      'name' => 'Custom format',
    ]);
    $format->setFilterConfig('filter_url', [
      'status' => 1,
      'settings' => [
        'filter_url_length' => 30,
      ],
    ]);
    $format->save();
    $this->verifyTextFormat($format);

    // Alter some text format properties and save again.
    $format->set('name', 'Altered format');
    $format->setFilterConfig('filter_url', [
      'status' => 0,
    ]);
    $format->setFilterConfig('filter_autop', [
      'status' => 1,
    ]);
    $format->save();
    $this->verifyTextFormat($format);

    // Add a filter_test_replace  filter and save again.
    $format->setFilterConfig('filter_test_replace', [
      'status' => 1,
    ]);
    $format->save();
    $this->verifyTextFormat($format);

    // Disable the text format.
    $format->disable()->save();

    $formats = filter_formats();
    $this->assertTrue(!isset($formats[$format->id()]), 'filter_formats: Disabled text format no longer exists.');
  }

  /**
   * Tests disabling the fallback text format.
   */
  public function testDisableFallbackFormat(): void {
    $this->installConfig(['filter']);
    $message = '\LogicException with message "The fallback text format \'plain_text\' cannot be disabled." was thrown.';
    try {
      FilterFormat::load('plain_text')->disable();
      $this->fail($message);
    }
    catch (\LogicException $e) {
      $this->assertSame("The fallback text format 'plain_text' cannot be disabled.", $e->getMessage(), $message);
    }
  }

  /**
   * Verifies that a text format is properly stored.
   */
  public function verifyTextFormat($format) {
    $default_langcode = \Drupal::languageManager()->getDefaultLanguage()->getId();

    // Verify the loaded filter has all properties.
    $filter_format = FilterFormat::load($format->id());
    $format_label = $format->label();
    $this->assertEquals($format->id(), $filter_format->id(), "filter_format_load: Proper format id for text format $format_label.");
    $this->assertEquals($format->label(), $filter_format->label(), "filter_format_load: Proper title for text format $format_label.");
    $this->assertEquals($format->get('weight'), $filter_format->get('weight'), "filter_format_load: Proper weight for text format $format_label.");
    // Check that the filter was created in site default language.
    $this->assertEquals($default_langcode, $format->language()->getId(), "filter_format_load: Proper language code for text format $format_label.");

    // Verify the permission exists and has the correct dependencies.
    $permissions = \Drupal::service('user.permissions')->getPermissions();
    $this->assertTrue(isset($permissions[$format->getPermissionName()]));
    $this->assertEquals(['config' => [$format->getConfigDependencyName()]], $permissions[$format->getPermissionName()]['dependencies']);
  }

}
