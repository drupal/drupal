<?php

declare(strict_types=1);

namespace Drupal\Tests\sdc\Unit;

use Drupal\Core\Template\Attribute;
use Drupal\sdc\Component\ComponentValidator;
use Drupal\sdc\Exception\InvalidComponentException;
use Drupal\sdc\Plugin\Component;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Unit tests for the component validation.
 *
 * @coversDefaultClass \Drupal\sdc\Component\ComponentValidator
 * @group sdc
 *
 * @internal
 */
final class ComponentValidatorTest extends TestCase {

  /**
   * Tests that valid component definitions don't cause errors.
   *
   * @dataProvider dataProviderValidateDefinitionValid
   *
   * @throws \Drupal\sdc\Exception\InvalidComponentException
   */
  public function testValidateDefinitionValid(array $definition): void {
    $component_validator = new ComponentValidator();
    $component_validator->setValidator();
    $this->assertTrue(
      $component_validator->validateDefinition($definition, TRUE),
      'The invalid component definition did not throw an error.'
    );
  }

  /**
   * Data provider with valid component definitions.
   *
   * @return array
   *   The data.
   */
  public static function dataProviderValidateDefinitionValid(): array {
    return [
      array_map(
        [static::class, 'loadComponentDefinitionFromFs'],
        ['my-banner', 'my-button', 'my-cta'],
      ),
    ];
  }

  /**
   * Tests invalid component definitions.
   *
   * @dataProvider dataProviderValidateDefinitionInvalid
   */
  public function testValidateDefinitionInvalid(array $definition): void {
    $this->expectException(InvalidComponentException::class);
    $component_validator = new ComponentValidator();
    $component_validator->setValidator();
    $component_validator->validateDefinition($definition, TRUE);
  }

  /**
   * Data provider with invalid component definitions.
   *
   * @return array
   *   The data.
   */
  public static function dataProviderValidateDefinitionInvalid(): array {
    $valid_cta = static::loadComponentDefinitionFromFs('my-cta');
    $cta_with_missing_required = $valid_cta;
    unset($cta_with_missing_required['path']);
    $cta_with_invalid_class = $valid_cta;
    $cta_with_invalid_class['props']['properties']['attributes']['type'] = 'Drupal\Foo\Invalid';
    $cta_with_invalid_enum = array_merge(
      $valid_cta,
      ['extension_type' => 'invalid'],
    );
    return [
      [$cta_with_missing_required],
      [$cta_with_invalid_class],
      [$cta_with_invalid_enum],
    ];
  }

  /**
   * Tests that valid props are handled properly.
   *
   * @dataProvider dataProviderValidatePropsValid
   *
   * @throws \Drupal\sdc\Exception\InvalidComponentException
   */
  public function testValidatePropsValid(array $context, string $component_id, array $definition): void {
    $component = new Component(
      ['app_root' => '/fake/path/root'],
      'sdc_test:' . $component_id,
      $definition
    );
    $component_validator = new ComponentValidator();
    $component_validator->setValidator();
    $this->assertTrue(
      $component_validator->validateProps($context, $component),
      'The valid component props threw an error.'
    );
  }

  /**
   * Data provider with valid component props.
   *
   * @return array
   *   The data.
   */
  public static function dataProviderValidatePropsValid(): array {
    return [
      [
        [
          'text' => 'Can Pica',
          'href' => 'https://www.drupal.org',
          'target' => '_blank',
          'attributes' => new Attribute(['key' => 'value']),
        ],
        'my-cta',
        static::loadComponentDefinitionFromFs('my-cta'),
      ],
      [[], 'my-banner', static::loadComponentDefinitionFromFs('my-banner')],
      [
        ['nonProp' => new \stdClass()],
        'my-banner',
        static::loadComponentDefinitionFromFs('my-banner'),
      ],
    ];
  }

  /**
   * Tests that invalid props are handled properly.
   *
   * @dataProvider dataProviderValidatePropsInvalid
   *
   * @throws \Drupal\sdc\Exception\InvalidComponentException
   */
  public function testValidatePropsInvalid(array $context, string $component_id, array $definition): void {
    $component = new Component(
      ['app_root' => '/fake/path/root'],
      'sdc_test:' . $component_id,
      $definition
    );
    $this->expectException(InvalidComponentException::class);
    $component_validator = new ComponentValidator();
    $component_validator->setValidator();
    $component_validator->validateProps($context, $component);
  }

  /**
   * Data provider with invalid component props.
   *
   * @return array
   *   The data.
   */
  public static function dataProviderValidatePropsInvalid(): array {
    return [
      'missing required prop' => [
        [
          'href' => 'https://www.drupal.org',
          'target' => '_blank',
          'attributes' => new Attribute(['key' => 'value']),
        ],
        'my-cta',
        static::loadComponentDefinitionFromFs('my-cta'),
      ],
      'attributes with invalid object class' => [
        [
          'text' => 'Can Pica',
          'href' => 'https://www.drupal.org',
          'target' => '_blank',
          'attributes' => new \stdClass(),
        ],
        'my-cta',
        static::loadComponentDefinitionFromFs('my-cta'),
      ],
      'ctaTarget violates the allowed properties in the enum' => [
        ['ctaTarget' => 'foo'],
        'my-banner',
        static::loadComponentDefinitionFromFs('my-banner'),
      ],
    ];
  }

  /**
   * Loads a component definition from the component name.
   *
   * @param string $component_name
   *   The component name.
   *
   * @return array
   *   The component definition
   */
  private static function loadComponentDefinitionFromFs(string $component_name): array {
    return array_merge(
      Yaml::parseFile(
        sprintf('%s/modules/sdc_test/components/%s/%s.component.yml', dirname(__DIR__, 2), $component_name, $component_name),
      ),
      [
        'machineName' => $component_name,
        'extension_type' => 'module',
        'id' => 'sdc_test:' . $component_name,
        'library' => ['css' => ['component' => ['foo.css' => []]]],
        'path' => '',
        'provider' => 'sdc_test',
        'template' => $component_name . '.twig',
        'group' => 'my-group',
        'description' => 'My description',
      ]
    );
  }

}
