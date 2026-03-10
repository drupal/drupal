<?php

declare(strict_types=1);

namespace Drupal\Tests\file\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the file uploading functions.
 */
#[Group('file')]
#[RunTestsInSeparateProcesses]
class DummyStreamWrapperFileTest extends FileManagedTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['file_test', 'entity_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * @var \Drupal\user\UserInterface
   */
  protected UserInterface $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->config('system.file')->set('default_scheme', 'dummy-private')->save();
    FieldStorageConfig::create([
      'field_name' => 'file_test',
      'entity_type' => 'entity_test',
      'type' => 'file',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'file_test',
      'bundle' => 'entity_test',
      'settings' => ['file_directory' => $this->getRandomGenerator()->name()],
    ])->save();
    $this->account = $this->drupalCreateUser(['view test entity']);
    $this->drupalLogin($this->account);

    $settings['settings']['file_additional_public_schemes'] = (object) [
      'value' => ['dummy-public'],
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Tests that overridden cache control works.
   */
  #[DataProvider('streamWrapperProvider')]
  public function testFileUpload(string $streamWrapper, string $cacheControlHeader, string $fileUrlBasePath): void {
    // Set the stream wrapper value, so we can validate and set the
    // Cache-Control header accordingly for testing.
    \Drupal::keyValue('file_test')->set('file_test_active_stream_wrapper', $streamWrapper);
    $file = \Drupal::service('file.repository')->writeData($this->randomMachineName(), \sprintf('%s://example.txt', $streamWrapper));

    $entity = EntityTest::create();
    $entity->file_test->target_id = $file->id();
    $entity->file_test->display = 1;
    $entity->name->value = $this->randomMachineName();
    $entity->save();
    self::assertStringContainsString($fileUrlBasePath, \Drupal::service('file_url_generator')->generateString($file->getFileUri()));
    $this->drupalGet(\Drupal::service('file_url_generator')->generateAbsoluteString($file->getFileUri()));
    $this->assertSession()->responseHeaderContains('Cache-Control', $cacheControlHeader);
  }

  /**
   * Data provider for the stream wrapper cache control test.
   */
  public static function streamWrapperProvider(): array {
    return [
      'dummy_public_scheme' => ['dummy-public', 'public', '/system/dummy-public'],
      'private_scheme' => ['private', 'private', '/system/files'],
      'dummy_private_scheme' => ['dummy-private', 'private', '/system/dummy'],
      'temporary_scheme' => ['temporary', 'private', '/system/temporary'],
    ];
  }

}
