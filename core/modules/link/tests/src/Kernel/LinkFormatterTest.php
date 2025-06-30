<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the Field Formatter for the link field type.
 *
 * @group link
 */
class LinkFormatterTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['link'];

  /**
   * The entity type used in this test.
   *
   * @var string
   */
  protected string $entityType = 'entity_test';

  /**
   * The bundle used in this test.
   *
   * @var string
   */
  protected string $bundle = 'entity_test';

  /**
   * The name of the field used in this test.
   *
   * @var string
   */
  protected string $fieldName = 'field_test';

  /**
   * The entity to be tested.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Use Stark theme for testing markup output.
    \Drupal::service('theme_installer')->install(['stark']);
    $this->config('system.theme')->set('default', 'stark')->save();
    $this->installEntitySchema('entity_test');
    // Grant the 'view test entity' permission.
    $this->installConfig(['user']);
    Role::load(RoleInterface::ANONYMOUS_ID)
      ->grantPermission('view test entity')
      ->save();

    FieldStorageConfig::create([
      'field_name' => $this->fieldName,
      'type' => 'link',
      'entity_type' => $this->entityType,
      'cardinality' => 1,
    ])->save();

    FieldConfig::create([
      'field_name' => $this->fieldName,
      'entity_type' => $this->entityType,
      'bundle' => $this->bundle,
      'label' => 'Field test',
    ])->save();
  }

  /**
   * Tests the link formatters.
   *
   * @param string $formatter
   *   The name of the link formatter to test.
   *
   * @dataProvider providerLinkFormatter
   */
  public function testLinkFormatter(string $formatter): void {
    $entity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityType)
      ->create([
        'name' => $this->randomMachineName(),
        $this->fieldName => [
          'uri' => 'https://www.drupal.org/',
          'title' => 'Hello world',
          'options' => [
            'attributes' => [
              'class' => 'classy',
              'onmouseover' => 'alert(document.cookie)',
            ],
          ],
        ],
      ]);
    $entity->save();

    $build = $entity->get($this->fieldName)->view(['type' => $formatter]);

    $renderer = $this->container->get('renderer');
    $renderer->renderRoot($build[0]);

    $output = (string) $build[0]['#markup'];
    $this->assertStringContainsString('<a href="https://www.drupal.org/" class="classy">', $output);
    $this->assertStringNotContainsString('onmouseover=', $output);
  }

  /**
   * Data provider for ::testLinkFormatter.
   */
  public static function providerLinkFormatter(): array {
    return [
      'default formatter' => ['link'],
      'separate link text and URL' => ['link_separate'],
    ];
  }

}
