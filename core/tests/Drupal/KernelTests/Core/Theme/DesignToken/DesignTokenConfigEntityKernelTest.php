<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Theme\DesignToken;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\DesignToken\DesignTokenValuePluginManagerInterface;
use Drupal\Core\Theme\DesignToken\Enum\DimensionUnit;
use Drupal\Core\Theme\DesignToken\Enum\FontWeightAlias;
use Drupal\Core\Theme\Entity\DesignToken;
use Drupal\Core\Theme\Entity\DesignTokenInterface;
use Drupal\Core\Theme\Plugin\DesignTokenValue\Dimension;
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
#[CoversClass(DesignToken::class)]
#[Group('design_token')]
#[RunTestsInSeparateProcesses]
class DesignTokenConfigEntityKernelTest extends KernelTestBase {

  /**
   * The entity type manager.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The DesignTokenValuePluginManager service.
   */
  protected DesignTokenValuePluginManagerInterface $designTokenValuePluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->container->get(EntityTypeManagerInterface::class);
    $this->designTokenValuePluginManager = $this->container->get(DesignTokenValuePluginManagerInterface::class);
  }

  /**
   * Tests preSave.
   */
  public function testPreSave(): void {
    $values = [
      'id' => 'my_id',
      'path' => 'foo.bar.baz',
      'type' => 'dimension',
      'scopes' => [
        ':root' => [
          'value' => 12,
          'unit' => 'px',
        ],
        '.card' => [
          'value' => 14,
          'unit' => 'rem',
        ],
        '%breadcrumb' => [
          'value' => 14,
          'unit' => 'rem',
        ],
      ],
    ];
    $expected = [
      ':root' => [
        'value' => 12,
        'unit' => 'px',
      ],
      '%card' => [
        'value' => 14,
        'unit' => 'rem',
      ],
      '%breadcrumb' => [
        'value' => 14,
        'unit' => 'rem',
      ],
    ];
    $expectedProcessed = [
      ':root' => new Dimension([
        'value' => 12,
        'unit' => 'px',
      ],
      'dimension',
      [
        'class' => Dimension::class,
        'provider' => 'core',
        'id' => 'dimension',
        'label' => new TranslatableMarkup('Dimension'),
      ],
      ),
      '%card' => new Dimension([
        'value' => 14,
        'unit' => 'rem',
      ],
      'dimension',
      [
        'class' => Dimension::class,
        'provider' => 'core',
        'id' => 'dimension',
        'label' => new TranslatableMarkup('Dimension'),
      ],
      ),
      '%breadcrumb' => new Dimension([
        'value' => 14,
        'unit' => 'rem',
      ],
        'dimension',
        [
          'class' => Dimension::class,
          'provider' => 'core',
          'id' => 'dimension',
          'label' => new TranslatableMarkup('Dimension'),
        ],
      ),
    ];

    /** @var \Drupal\Core\Theme\Entity\DesignTokenInterface $config */
    $config = $this->entityTypeManager->getStorage('design_token')
      ->create($values);
    $config->save();

    $reflection = new \ReflectionClass($config);
    $property = $reflection->getProperty('scopes');

    // Ensure scopes had been converted for storage.
    $this->assertEquals($expected, $property->getValue($config));
    // Ensure conversion for storage does not impact export.
    foreach ($expectedProcessed as $scope => $pluginInstance) {
      $this->assertEquals($pluginInstance, $config->getScopes()->get($scope));
    }
  }

  /**
   * Tests CSS export.
   */
  public function testToCss(): void {
    $values = [
      'id' => 'my_id',
      'path' => 'foo.bar.baz',
      'type' => 'dimension',
      'scopes' => [
        ':root' => [
          'value' => 12,
          'unit' => 'px',
        ],
        '%card' => [
          'value' => 14,
          'unit' => 'rem',
        ],
      ],
    ];
    $expected = ':root{--foo-bar-baz:12px;}.card{--foo-bar-baz:14rem;}';

    /** @var \Drupal\Core\Theme\Entity\DesignTokenInterface $config */
    $config = $this->entityTypeManager->getStorage('design_token')
      ->create($values);
    $this->assertEquals($expected, DesignToken::toCss([$config]));
  }

  /**
   * Tests config schema.
   */
  public function testConfigSchema(): void {
    $types = [
      'dimension' => [
        'id' => 'dimension',
        'path' => 'dimension',
        'type' => 'dimension',
        'scopes' => [
          ':root' => [
            'value' => 12,
            'unit' => 'px',
          ],
        ],
      ],
      'dimension with enum' => [
        'id' => 'dimension_with_enum',
        'path' => 'dimension',
        'type' => 'dimension',
        'scopes' => [
          ':root' => [
            'value' => 12,
            'unit' => DimensionUnit::Rem,
          ],
        ],
      ],
      'fontFamily' => [
        'id' => 'font_family',
        'path' => 'font.family',
        'type' => 'fontFamily',
        'scopes' => [
          ':root' => [
            'Arial',
          ],
        ],
      ],
      'fontFamily with string' => [
        'id' => 'font_family_with_family',
        'path' => 'font.family',
        'type' => 'fontFamily',
        'scopes' => [
          ':root' => 'Arial',
        ],
      ],
      'fontWeight' => [
        'id' => 'font_weight',
        'path' => 'font.weight',
        'type' => 'fontWeight',
        'scopes' => [
          ':root' => 202,
        ],
      ],
      'fontWeight with string' => [
        'id' => 'font_weight_with_string',
        'path' => 'font.weight',
        'type' => 'fontWeight',
        'scopes' => [
          ':root' => 'regular',
        ],
      ],
      'fontWeight with enum' => [
        'id' => 'font_weight_with_enum',
        'path' => 'font.weight',
        'type' => 'fontWeight',
        'scopes' => [
          ':root' => FontWeightAlias::Bold,
        ],
      ],
      'number' => [
        'id' => 'number',
        'path' => 'number',
        'type' => 'number',
        'scopes' => [
          ':root' => 12,
        ],
      ],
      'typography' => [
        'id' => 'typography',
        'path' => 'typography',
        'type' => 'typography',
        'scopes' => [
          ':root' => [
            'fontFamily' => [
              'Arial',
              'Helvetica',
            ],
            'fontSize' => [
              'value' => 10,
              'unit' => 'px',
            ],
            'fontWeight' => 'bold',
            'letterSpacing' => [
              'value' => 5,
              'unit' => 'px',
            ],
            'lineHeight' => 5,
          ],
        ],
      ],
      'typography with other config structure' => [
        'id' => 'typography_with_other',
        'path' => 'typography',
        'type' => 'typography',
        'scopes' => [
          ':root' => [
            'fontFamily' => 'Arial',
            'fontSize' => [
              'value' => 10,
              'unit' => DimensionUnit::Rem,
            ],
            'fontWeight' => 'bold',
            'letterSpacing' => [
              'value' => 5,
              'unit' => DimensionUnit::Px,
            ],
            'lineHeight' => 5,
          ],
        ],
      ],
      'valid_path' => [
        'id' => 'valid_path',
        'path' => 'foo bar.baz_foo-bar',
        'type' => 'number',
        'scopes' => [
          ':root' => 12,
        ],
      ],
    ];

    foreach ($types as $values) {
      /** @var \Drupal\Core\Theme\Entity\DesignTokenInterface $config */
      $config = $this->entityTypeManager->getStorage('design_token')
        ->create($values);
      $config->save();
      $this->assertInstanceOf(DesignTokenInterface::class, $config);
    }
  }

  /**
   * Tests invalid config schema.
   */
  public function testInvalidConfigSchema(): void {
    $definitions = $this->designTokenValuePluginManager->getDefinitions();
    $validIds = implode(', ', array_keys($definitions));
    $cases = [
      'invalid_id' => [
        'exceptionMessage' => 'Schema errors for core.design_token.invalidId with the following errors: 0 [id] The &lt;em class=&quot;placeholder&quot;&gt;&amp;quot;invalidId&amp;quot;&lt;/em&gt; machine name is not valid.',
        'config' => [
          'id' => 'invalidId',
          'path' => 'foo',
          'type' => 'number',
          'scopes' => [
            ':root' => 12,
          ],
        ],
      ],
      'invalid_path' => [
        'exceptionMessage' => 'Schema errors for core.design_token.invalid_path with the following errors: 0 [path] The &lt;em class=&quot;placeholder&quot;&gt;&amp;quot;foo bar.baz_foo-bar Capital&amp;quot;&lt;/em&gt; path is not valid.',
        'config' => [
          'id' => 'invalid_path',
          'path' => 'foo bar.baz_foo-bar Capital',
          'type' => 'number',
          'scopes' => [
            ':root' => 12,
          ],
        ],
      ],
      'invalid_type' => [
        'exceptionMessage' => 'The "foo" plugin does not exist. Valid plugin IDs for Drupal\Core\Theme\DesignToken\DesignTokenValuePluginManager are: ' . $validIds,
        'config' => [
          'id' => 'invalid_type',
          'path' => 'bar',
          'type' => 'foo',
          'scopes' => [
            ':root' => [],
          ],
        ],
      ],
      'invalid_scope' => [
        'exceptionMessage' => 'Schema errors for core.design_token.invalid_scope with the following errors: 0 [scopes] The CSS selector {invalid_scope} is invalid., 1 [scopes] The keys of the sequence do not match the given constraints.',
        'config' => [
          'id' => 'invalid_scope',
          'path' => 'invalid_scope',
          'type' => 'fontFamily',
          'scopes' => [
            '{invalid_scope}' => ['Arial'],
          ],
        ],
      ],
      'invalid_value_structure' => [
        'exceptionMessage' => "The configuration property scopes.:root.0.0 doesn't exist.",
        'config' => [
          'id' => 'font_family',
          'path' => 'font.family',
          'type' => 'fontFamily',
          'scopes' => [
            ':root' => [
              [6],
            ],
          ],
        ],
      ],
      'invalid_font_weight_range' => [
        'exceptionMessage' => "Schema errors for core.design_token.font_weight_range with the following errors: 0 [scopes.:root] This value should be between &lt;em class=&quot;placeholder&quot;&gt;1&lt;/em&gt; and &lt;em class=&quot;placeholder&quot;&gt;1000&lt;/em&gt;.",
        'config' => [
          'id' => 'font_weight_range',
          'path' => 'font.family',
          'type' => 'fontWeight',
          'scopes' => [
            ':root' => 1500,
          ],
        ],
      ],
    ];

    foreach ($cases as $key => $case) {
      try {
        /** @var \Drupal\Core\Theme\Entity\DesignTokenInterface $config */
        $config = $this->entityTypeManager->getStorage('design_token')
          ->create($case['config']);
        $config->save();
        $this->fail("There should have been a config schema exception with the case $key.");
      }
      catch (\Exception $exception) {
        $this->assertEquals($case['exceptionMessage'], $exception->getMessage());
        $this->assertInstanceOf(\Exception::class, $exception);
      }
    }
  }

}
