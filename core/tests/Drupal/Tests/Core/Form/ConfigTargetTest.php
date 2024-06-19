<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\ConfigTarget;
use Drupal\Core\Form\ToConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\RedundantEditableConfigNamesTrait;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Form\ConfigTarget
 * @group Form
 */
class ConfigTargetTest extends UnitTestCase {

  /**
   * @covers \Drupal\Core\Form\ConfigFormBase::storeConfigKeyToFormElementMap
   */
  public function testDuplicateTargetsNotAllowed(): void {
    $form = [
      'test' => [
        '#type' => 'text',
        '#default_value' => 'A test',
        '#config_target' => new ConfigTarget('system.site', 'admin_compact_mode', 'intval', 'boolval'),
        '#name' => 'test',
        '#array_parents' => ['test'],
      ],
      'duplicate' => [
        '#type' => 'text',
        '#config_target' => new ConfigTarget('system.site', 'admin_compact_mode', 'intval', 'boolval'),
        '#name' => 'duplicate',
        '#array_parents' => ['duplicate'],
      ],
    ];

    $test_form = new class(
      $this->prophesize(ConfigFactoryInterface::class)->reveal(),
      $this->prophesize(TypedConfigManagerInterface::class)->reveal(),
    ) extends ConfigFormBase {
      use RedundantEditableConfigNamesTrait;

      public function getFormId() {
        return 'test';
      }

    };
    $form_state = new FormState();

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Two #config_targets both target "admin_compact_mode" in the "system.site" config: `$form[\'test\']` and `$form[\'duplicate\']`.');
    $test_form->storeConfigKeyToFormElementMap($form, $form_state);
  }

  /**
   * @covers \Drupal\Core\Form\ConfigFormBase::storeConfigKeyToFormElementMap
   * @dataProvider providerTestFormCacheable
   */
  public function testFormCacheable(bool $expected, ?callable $fromConfig, ?callable $toConfig): void {
    $form = [
      'test' => [
        '#type' => 'text',
        '#default_value' => 'A test',
        '#config_target' => new ConfigTarget('system.site', 'admin_compact_mode', $fromConfig, $toConfig),
        '#name' => 'test',
        '#array_parents' => ['test'],
      ],
    ];

    $test_form = new class(
      $this->prophesize(ConfigFactoryInterface::class)->reveal(),
      $this->prophesize(TypedConfigManagerInterface::class)->reveal(),
    ) extends ConfigFormBase {
      use RedundantEditableConfigNamesTrait;

      public function getFormId() {
        return 'test';
      }

    };
    $form_state = new FormState();
    // Make the form cacheable.
    $form_state
      ->setRequestMethod('POST')
      ->setCached();

    $test_form->storeConfigKeyToFormElementMap($form, $form_state);

    $this->assertSame($expected, $form_state->isCached());
  }

  public static function providerTestFormCacheable(): array {
    $closure = fn (bool $something): string => $something ? 'Yes' : 'No';
    return [
      'No callables' => [TRUE, NULL, NULL],
      'Serializable fromConfig callable' => [TRUE, "intval", NULL],
      'Serializable toConfig callable' => [TRUE, NULL, "boolval"],
      'Serializable callables' => [TRUE, "intval", "boolval"],
      'Unserializable fromConfig callable' => [FALSE, $closure, NULL],
      'Unserializable toConfig callable' => [FALSE, NULL, $closure],
      'Unserializable callables' => [FALSE, $closure, $closure],
    ];
  }

  /**
   * @covers ::fromForm
   * @covers ::fromString
   */
  public function testFromFormString(): void {
    $form = [
      'group' => [
        '#type' => 'details',
        'test' => [
          '#type' => 'text',
          '#default_value' => 'A test',
          '#config_target' => 'system.site:name',
          '#name' => 'test',
          '#parents' => ['test'],
        ],
      ],
    ];
    $config_target = ConfigTarget::fromForm(['group', 'test'], $form);
    $this->assertSame('system.site', $config_target->configName);
    $this->assertSame(['name'], $config_target->propertyPaths);
    $this->assertSame(['test'], $config_target->elementParents);
  }

  /**
   * @covers ::fromForm
   */
  public function testFromFormConfigTarget(): void {
    $form = [
      'test' => [
        '#type' => 'text',
        '#default_value' => 'A test',
        '#config_target' => new ConfigTarget('system.site', 'admin_compact_mode', 'intval', 'boolval'),
        '#name' => 'test',
        '#parents' => ['test'],
      ],
    ];
    $config_target = ConfigTarget::fromForm(['test'], $form);
    $this->assertSame('system.site', $config_target->configName);
    $this->assertSame(['admin_compact_mode'], $config_target->propertyPaths);
    $this->assertSame(['test'], $config_target->elementParents);
    $this->assertSame(1, ($config_target->fromConfig)(TRUE));
    $this->assertFalse(($config_target->toConfig)('0'));
  }

  /**
   * @covers ::fromForm
   * @dataProvider providerTestFromFormException
   */
  public function testFromFormException(array $form, array $array_parents, string $exception_message): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage($exception_message);
    ConfigTarget::fromForm($array_parents, $form);
  }

  public static function providerTestFromFormException(): array {
    return [
      'No #config_target' => [
        [
          'test' => [
            '#type' => 'text',
            '#default_value' => 'A test',
          ],
        ],
        ['test'],
        'The form element [test] does not have the #config_target property set',
      ],
      'No #config_target nested' => [
        [
          'group' => [
            '#type' => 'details',
            'test' => [
              '#type' => 'text',
              '#default_value' => 'A test',
            ],
          ],
        ],
        ['group', 'test'],
        'The form element [group][test] does not have the #config_target property set',
      ],
      'Boolean #config_target nested' => [
        [
          'group' => [
            '#type' => 'details',
            'test' => [
              '#type' => 'text',
              '#config_target' => FALSE,
              '#default_value' => 'A test',
            ],
          ],
        ],
        ['group', 'test'],
        'The form element [group][test] #config_target property is not a string or a ConfigTarget object',
      ],
    ];
  }

  /**
   * @dataProvider providerMultiTargetWithoutCallables
   */
  public function testMultiTargetWithoutCallables(...$arguments): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The $fromConfig and $toConfig arguments must be passed to Drupal\Core\Form\ConfigTarget::__construct() if multiple property paths are targeted.');
    new ConfigTarget(...$arguments);
  }

  public static function providerMultiTargetWithoutCallables(): \Generator {
    yield "neither callable" => ['foo.settings', ['a', 'b']];
    yield "only fromConfig" => ['foo.settings', ['a', 'b'], "intval"];
    yield "only toConfig" => ['foo.settings', ['a', 'b'], NULL, "intval"];
  }

  public function testGetValueCorrectConfig(): void {
    $sut = new ConfigTarget('foo.settings', $this->randomMachineName());

    $config = $this->prophesize(Config::class);
    $config->getName()->willReturn('bar.settings');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Config target is associated with foo.settings but bar.settings given.');
    $sut->getValue($config->reveal());
  }

  public function testSetValueCorrectConfig(): void {
    $sut = new ConfigTarget('foo.settings', $this->randomMachineName());

    $config = $this->prophesize(Config::class);
    $config->getName()->willReturn('bar.settings');

    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Config target is associated with foo.settings but bar.settings given.');
    $sut->setValue($config->reveal(), $this->randomString(), $this->prophesize(FormStateInterface::class)->reveal());
  }

  public function testSingleTarget(): void {
    $config_target = new ConfigTarget(
      'foo.settings',
      'something',
      // This is an artificial example. Imagine a boolean value stored in config
      // (`foo.settings:something`) and that it is presented by the string "Yes"
      // or the string "No" in an <input type=text> in the form.
      fromConfig: fn (bool $something): string => $something ? 'Yes' : 'No',
      toConfig: fn (string $form_value): ToConfig|bool => match ($form_value) {
        'Yes' => TRUE,
        '<test:noop>' => ToConfig::NoOp,
        '<test:delete>' => ToConfig::DeleteKey,
        default => FALSE,
      },
    );
    // Assert the logic in the callables works as expected.
    $this->assertSame("Yes", ($config_target->fromConfig)(TRUE));
    $this->assertSame("No", ($config_target->fromConfig)(FALSE));
    $this->assertTrue(($config_target->toConfig)("Yes"));
    $this->assertFalse(($config_target->toConfig)("No"));
    $this->assertFalse(($config_target->toConfig)("some random string"));
    $this->assertSame(ToConfig::NoOp, ($config_target->toConfig)("<test:noop>"));
    $this->assertSame(ToConfig::DeleteKey, ($config_target->toConfig)("<test:delete>"));

    // Now simulate how this will be used in the form, and ensure it results in
    // the expected Config::set() calls.
    $config = $this->prophesize(Config::class);
    $config->getName()->willReturn('foo.settings');

    // First to transform the stored config value to the form value.
    $config->get('something')->willReturn(TRUE);
    $this->assertSame("Yes", $config_target->getValue($config->reveal()));

    // Then to transform the modified form value back to config.
    $config->set('something', TRUE)->shouldBeCalledTimes(1);
    $config_target->setValue($config->reveal(), 'Yes', $this->prophesize(FormStateInterface::class)->reveal());

    // Repeat, but for the other possible value.
    $config->get('something')->willReturn(FALSE);
    $this->assertSame("No", $config_target->getValue($config->reveal()));
    $config->set('something', FALSE)->shouldBeCalledTimes(1);
    $config_target->setValue($config->reveal(), 'No', $this->prophesize(FormStateInterface::class)->reveal());

    // Test `ConfigTargetValue::NoMapping`: nothing should happen to the Config.
    $config = $this->prophesize(Config::class);
    $config->getName()->willReturn('foo.settings');
    $config->set('something', Argument::any())->shouldBeCalledTimes(0);
    $config->clear('something', Argument::any())->shouldBeCalledTimes(0);
    $config_target->setValue($config->reveal(), '<test:noop>', $this->prophesize(FormStateInterface::class)->reveal());

    // Test `ConfigTargetValue::DeleteKey`: Config::clear() should be called.
    $config = $this->prophesize(Config::class);
    $config->getName()->willReturn('foo.settings');
    $config->clear('something')->shouldBeCalledTimes(1);
    $config_target->setValue($config->reveal(), '<test:delete>', $this->prophesize(FormStateInterface::class)->reveal());

  }

  public function testMultiTarget(): void {
    $config_target = new ConfigTarget(
      'foo.settings',
      [
        'first',
        'second',
      ],
      // This is an artificial example. Imagine two integer values are stored in
      // config (`foo.settings:first` and `foo.settings:second`) and that they
      // are presented by a single <input type=text> in the form. We could
      // present this to the user as two integers separated by the pipe symbol.
      fromConfig: fn (int $first, int $second): string => "$first|$second",
      toConfig: fn (string $form_value): array => [
        'first' => intval(explode('|', $form_value)[0]),
        'second' => intval(explode('|', $form_value)[1]),
      ],
    );
    // Assert the logic in the callables works as expected.
    $this->assertSame("42|-4", ($config_target->fromConfig)(42, -4));
    $this->assertSame(['first' => 9, 'second' => 19], ($config_target->toConfig)("9|19"));

    // Now simulate how this will be used in the form, and ensure it results in
    // the expected Config::set() calls.
    $config = $this->prophesize(Config::class);
    $config->getName()->willReturn('foo.settings');

    // First to transform the stored config value to the form value.
    $config->get('first')->willReturn(-17);
    $config->get('second')->willReturn(71);
    $this->assertSame("-17|71", $config_target->getValue($config->reveal()));

    // Then to transform the modified form value back to config.
    $config->set('first', 1988)->shouldBeCalledTimes(1);
    $config->set('second', 1992)->shouldBeCalledTimes(1);
    $config_target->setValue($config->reveal(), '1988|1992', $this->prophesize(FormStateInterface::class)->reveal());
  }

  /**
   * @testWith ["this string was returned by toConfig", "The toConfig callable returned a string, but it must be an array with a key-value pair for each of the targeted property paths."]
   *           [true, "The toConfig callable returned a boolean, but it must be an array with a key-value pair for each of the targeted property paths."]
   *           [42, "The toConfig callable returned a integer, but it must be an array with a key-value pair for each of the targeted property paths."]
   *           [[], "The toConfig callable returned an array that is missing key-value pairs for the following targeted property paths: first, second."]
   *           [{"yar": 42}, "The toConfig callable returned an array that is missing key-value pairs for the following targeted property paths: first, second."]
   *           [{"FIRST": 42, "SECOND": 1337}, "The toConfig callable returned an array that is missing key-value pairs for the following targeted property paths: first, second."]
   *           [{"second": 42}, "The toConfig callable returned an array that is missing key-value pairs for the following targeted property paths: first."]
   *           [{"first": 42}, "The toConfig callable returned an array that is missing key-value pairs for the following targeted property paths: second."]
   *           [{"first": 42, "second": 1337, "yar": "har"}, "The toConfig callable returned an array that contains key-value pairs that do not match targeted property paths: yar."]
   */
  public function testSetValueMultiTargetToConfigReturnValue(mixed $toConfigReturnValue, string $expected_exception_message): void {
    $config_target = new ConfigTarget(
      'foo.settings',
      [
        'first',
        'second',
      ],
      // In case of multiple targets, the return value must be an array with the
      // keys matching
      // @see ::testMultiTarget()
      fromConfig: fn (int $first, int $second): string => "$first|$second",
      toConfig: fn (): mixed => $toConfigReturnValue,
    );

    $config = $this->prophesize(Config::class);
    $config->getName()->willReturn('foo.settings');

    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage($expected_exception_message);
    $config_target->setValue($config->reveal(), '1988|1992', $this->prophesize(FormStateInterface::class)->reveal());
  }

}
