<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the content translation settings API.
 */
#[Group('content_translation')]
#[RunTestsInSeparateProcesses]
class ContentTranslationSettingsApiTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'user',
    'entity_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_mul');
  }

  /**
   * Tests that enabling translation via the API triggers schema updates.
   */
  public function testSettingsApi(): void {
    $this->container->get('content_translation.manager')->setEnabled('entity_test_mul', 'entity_test_mul', TRUE);
    $schema = Database::getConnection()->schema();
    $result =
      $schema->fieldExists('entity_test_mul_property_data', 'content_translation_source') &&
      $schema->fieldExists('entity_test_mul_property_data', 'content_translation_outdated');
    $this->assertTrue($result, 'Schema updates correctly performed.');
  }

}
