<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme\DesignToken;

use Drupal\Core\Theme\DesignToken\DesignTokenPluginManager;
use Drupal\Core\Theme\DesignToken\DesignTokenPluginManagerInterface;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests values are from test module design_tokens_test.
 *
 * Any change of the definition will impact the tests.
 *
 * @see core/modules/system/tests/modules/design_tokens_test/design_tokens.tokens.yml
 */
#[CoversClass(DesignTokenPluginManager::class)]
#[Group('design_token')]
#[RunTestsInSeparateProcesses]
class DesignTokenPluginManagerKernelTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'design_tokens_test',
  ];

  /**
   * The DesignTokenPluginManager service.
   */
  protected DesignTokenPluginManagerInterface $designTokenPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->designTokenPluginManager = $this->container->get(DesignTokenPluginManagerInterface::class);
  }

  /**
   * Tests that plugins can be provided by YAML files.
   */
  public function testDetectedPlugins(): void {
    $definitions = $this->designTokenPluginManager->getDefinitions();

    $expected = [
      'fontSize.small' => [
        'type' => 'dimension',
        'name' => 'small',
        'path' => 'fontSize.small',
        'cssValue' => '12px',
        'parent' => [
          'name' => 'fontSize',
        ],
      ],
      'fontSize.medium' => [
        'type' => 'dimension',
        'name' => 'medium',
        'path' => 'fontSize.medium',
        'cssValue' => '16px',
        'parent' => [
          'name' => 'fontSize',
        ],
      ],
      'fontSize.large' => [
        'type' => 'dimension',
        'name' => 'large',
        'path' => 'fontSize.large',
        'cssValue' => '24px',
        'parent' => [
          'name' => 'fontSize',
        ],
      ],
      'font_family.Primary_font' => [
        'type' => 'fontFamily',
        'name' => 'Primary font',
        'path' => 'font_family.Primary font',
        'cssValue' => 'Comic Sans MS',
        'parent' => [
          'name' => 'font_family',
        ],
      ],
      'font_family.Body_font' => [
        'type' => 'fontFamily',
        'name' => 'Body font',
        'path' => 'font_family.Body font',
        'cssValue' => 'Helvetica, Arial, sans-serif',
        'parent' => [
          'name' => 'font_family',
        ],
      ],
      'fontWeight.light' => [
        'type' => 'fontWeight',
        'name' => 'light',
        'path' => 'fontWeight.light',
        'cssValue' => '300',
        'parent' => [
          'name' => 'fontWeight',
        ],
      ],
      'fontWeight.normal' => [
        'type' => 'fontWeight',
        'name' => 'normal',
        'path' => 'fontWeight.normal',
        'cssValue' => '400',
        'parent' => [
          'name' => 'fontWeight',
        ],
      ],
      'fontWeight.bold' => [
        'type' => 'fontWeight',
        'name' => 'bold',
        'path' => 'fontWeight.bold',
        'cssValue' => '600',
        'parent' => [
          'name' => 'fontWeight',
        ],
      ],
      'spacing.small' => [
        'type' => 'dimension',
        'name' => 'small',
        'path' => 'spacing.small',
        'cssValue' => '12px',
        'parent' => [
          'name' => 'spacing',
        ],
      ],
      'spacing.medium' => [
        'type' => 'dimension',
        'name' => 'medium',
        'path' => 'spacing.medium',
        'cssValue' => '16px',
        'parent' => [
          'name' => 'spacing',
        ],
      ],
      'spacing.large' => [
        'type' => 'dimension',
        'name' => 'large',
        'path' => 'spacing.large',
        'cssValue' => '20px',
        'parent' => [
          'name' => 'spacing',
        ],
      ],
      'type_styles.heading-level-1' => [
        'type' => 'typography',
        'name' => 'heading-level-1',
        'path' => 'type styles.heading-level-1',
        'cssValue' => '700 42px/1.2 Roboto',
        'parent' => [
          'name' => 'type styles',
        ],
      ],
    ];

    foreach ($expected as $id => $infos) {
      $this->assertArrayHasKey($id, $definitions);
      $token = $definitions[$id];
      if (isset($infos['type'])) {
        $this->assertEquals($infos['type'], $token->getType());
      }
      if (isset($infos['name'])) {
        $this->assertEquals($infos['name'], $token->name);
      }
      if (isset($infos['path'])) {
        $this->assertEquals($infos['path'], $token->getPath());
      }
      if (isset($infos['cssValue'])) {
        $this->assertEquals($infos['cssValue'], $token->value->toCss());
      }

      if (isset($infos['parent'])) {
        if (isset($infos['parent']['name'])) {
          $this->assertEquals($infos['parent']['name'], $token->parent->name);
        }
      }
    }
  }

}
