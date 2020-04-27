<?php

namespace Drupal\Tests\jsonapi\Unit\Query;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\Container;
use Drupal\jsonapi\Query\EntityCondition;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @coversDefaultClass \Drupal\jsonapi\Query\EntityCondition
 * @group jsonapi
 *
 * @internal
 */
class EntityConditionTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $container = new Container();
    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())
      ->willReturn(TRUE);
    $container->set('cache_contexts_manager', $cache_context_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::createFromQueryParameter
   * @dataProvider queryParameterProvider
   */
  public function testCreateFromQueryParameter($case) {
    $condition = EntityCondition::createFromQueryParameter($case);
    $this->assertEquals($case['path'], $condition->field());
    $this->assertEquals($case['value'], $condition->value());
    if (isset($case['operator'])) {
      $this->assertEquals($case['operator'], $condition->operator());
    }
  }

  /**
   * Data provider for testDenormalize.
   */
  public function queryParameterProvider() {
    return [
      [['path' => 'some_field', 'value' => NULL, 'operator' => '=']],
      [['path' => 'some_field', 'operator' => '=', 'value' => 'some_string']],
      [['path' => 'some_field', 'operator' => '<>', 'value' => 'some_string']],
      [
        [
          'path' => 'some_field',
          'operator' => 'NOT BETWEEN',
          'value' => 'some_string',
        ],
      ],
      [
        [
          'path' => 'some_field',
          'operator' => 'BETWEEN',
          'value' => ['some_string'],
        ],
      ],
    ];
  }

  /**
   * @covers ::validate
   * @dataProvider validationProvider
   */
  public function testValidation($input, $exception) {
    if ($exception) {
      $this->expectException(get_class($exception));
      $this->expectExceptionMessage($exception->getMessage());
    }
    EntityCondition::createFromQueryParameter($input);
    $this->assertNull($exception, 'No exception was expected.');
  }

  /**
   * Data provider for testValidation.
   */
  public function validationProvider() {
    return [
      [['path' => 'some_field', 'value' => 'some_value'], NULL],
      [
        ['path' => 'some_field', 'value' => 'some_value', 'operator' => '='],
        NULL,
      ],
      [['path' => 'some_field', 'operator' => 'IS NULL'], NULL],
      [['path' => 'some_field', 'operator' => 'IS NOT NULL'], NULL],
      [
        ['path' => 'some_field', 'operator' => 'IS', 'value' => 'some_value'],
        new BadRequestHttpException("The 'IS' operator is not allowed in a filter parameter."),
      ],
      [
        [
          'path' => 'some_field',
          'operator' => 'NOT_ALLOWED',
          'value' => 'some_value',
        ],
        new BadRequestHttpException("The 'NOT_ALLOWED' operator is not allowed in a filter parameter."),
      ],
      [
        [
          'path' => 'some_field',
          'operator' => 'IS NULL',
          'value' => 'should_not_be_here',
        ],
        new BadRequestHttpException("Filters using the 'IS NULL' operator should not provide a value."),
      ],
      [
        [
          'path' => 'some_field',
          'operator' => 'IS NOT NULL',
          'value' => 'should_not_be_here',
        ],
        new BadRequestHttpException("Filters using the 'IS NOT NULL' operator should not provide a value."),
      ],
      [
        ['path' => 'path_only'],
        new BadRequestHttpException("Filter parameter is missing a '" . EntityCondition::VALUE_KEY . "' key."),
      ],
      [
        ['value' => 'value_only'],
        new BadRequestHttpException("Filter parameter is missing a '" . EntityCondition::PATH_KEY . "' key."),
      ],
    ];
  }

}
