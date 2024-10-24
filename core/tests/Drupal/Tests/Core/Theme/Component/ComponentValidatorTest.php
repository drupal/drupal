<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Component;

use Drupal\Core\Template\Attribute;
use Drupal\Core\Theme\Component\ComponentValidator;
use Drupal\Core\Render\Component\Exception\InvalidComponentException;
use Drupal\Core\Plugin\Component;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Unit tests for the component validation.
 *
 * @coversDefaultClass \Drupal\Core\Theme\Component\ComponentValidator
 * @group sdc
 */
class ComponentValidatorTest extends TestCase {

  /**
   * Tests that valid component definitions don't cause errors.
   *
   * @dataProvider dataProviderValidateDefinitionValid
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
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
   * @return \Generator
   *   Returns the generator with the invalid definitions.
   */
  public static function dataProviderValidateDefinitionInvalid(): \Generator {
    $valid_cta = static::loadComponentDefinitionFromFs('my-cta');

    $cta_with_missing_required = $valid_cta;
    unset($cta_with_missing_required['path']);
    yield 'missing required' => [$cta_with_missing_required];

    $cta_with_invalid_class = $valid_cta;
    $cta_with_invalid_class['props']['properties']['attributes']['type'] = 'Drupal\Foo\Invalid';
    yield 'invalid class' => [$cta_with_invalid_class];

    $cta_with_invalid_enum = array_merge(
      $valid_cta,
      ['extension_type' => 'invalid'],
    );
    yield 'invalid enum' => [$cta_with_invalid_enum];

    // A list of property types that are not strings, but can be provided via
    // YAML.
    $non_string_types = [NULL, 123, 123.45, TRUE];
    foreach ($non_string_types as $non_string_type) {
      $cta_with_non_string_prop_type = $valid_cta;
      $cta_with_non_string_prop_type['props']['properties']['text']['type'] = $non_string_type;
      yield "non string type ($non_string_type)" => [$cta_with_non_string_prop_type];

      // Same, but as a part of the list of allowed types.
      $cta_with_non_string_prop_type['props']['properties']['text']['type'] = ['string', $non_string_type];
      yield "non string type ($non_string_type) in a list of types" => [$cta_with_non_string_prop_type];
    }

    // The array is a valid value for the 'type' parameter, but it is not
    // allowed as the allowed type.
    $cta_with_non_string_prop_type['props']['properties']['text']['type'] = ['string', []];
    yield 'non string type (Array)' => [$cta_with_non_string_prop_type];
  }

  /**
   * Tests that valid props are handled properly.
   *
   * @dataProvider dataProviderValidatePropsValid
   *
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
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
   * @throws \Drupal\Core\Render\Component\Exception\InvalidComponentException
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
        sprintf('%s/modules/system/tests/modules/sdc_test/components/%s/%s.component.yml', dirname(__DIR__, 6), $component_name, $component_name),
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
