<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Kernel;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Session\AccountInterface;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\layout_builder\Plugin\Block\FieldBlock;
use Prophecy\Argument;
use Prophecy\Promise\PromiseInterface;
use Prophecy\Promise\ReturnPromise;
use Prophecy\Promise\ThrowPromise;
use Prophecy\Prophecy\ProphecyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \Drupal\layout_builder\Plugin\Block\FieldBlock
 * @group Field
 */
class FieldBlockTest extends EntityKernelTestBase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);
    $this->logger = $this->prophesize(LoggerInterface::class);
  }

  /**
   * Tests entity access.
   *
   * @covers ::blockAccess
   * @dataProvider providerTestBlockAccessNotAllowed
   */
  public function testBlockAccessEntityNotAllowed($expected, $entity_access): void {
    $entity = $this->prophesize(FieldableEntityInterface::class);
    $block = $this->getTestBlock($entity);

    $account = $this->prophesize(AccountInterface::class);
    $entity->access('view', $account->reveal(), TRUE)->willReturn($entity_access);
    $entity->hasField()->shouldNotBeCalled();

    $access = $block->access($account->reveal(), TRUE);
    $this->assertSame($expected, $access->isAllowed());
  }

  /**
   * Provides test data for ::testBlockAccessEntityNotAllowed().
   */
  public static function providerTestBlockAccessNotAllowed() {
    $data = [];
    $data['entity_forbidden'] = [
      FALSE,
      AccessResult::forbidden(),
    ];
    $data['entity_neutral'] = [
      FALSE,
      AccessResult::neutral(),
    ];
    return $data;
  }

  /**
   * Tests unfieldable entity.
   *
   * @covers ::blockAccess
   */
  public function testBlockAccessEntityAllowedNotFieldable(): void {
    $entity = $this->prophesize(EntityInterface::class);
    $block = $this->getTestBlock($entity);

    $account = $this->prophesize(AccountInterface::class);
    $entity->access('view', $account->reveal(), TRUE)->willReturn(AccessResult::allowed());

    $access = $block->access($account->reveal(), TRUE);
    $this->assertFalse($access->isAllowed());
  }

  /**
   * Tests fieldable entity without a particular field.
   *
   * @covers ::blockAccess
   */
  public function testBlockAccessEntityAllowedNoField(): void {
    $entity = $this->prophesize(FieldableEntityInterface::class);
    $block = $this->getTestBlock($entity);

    $account = $this->prophesize(AccountInterface::class);
    $entity->access('view', $account->reveal(), TRUE)->willReturn(AccessResult::allowed());
    $entity->hasField('the_field_name')->willReturn(FALSE);
    $entity->get('the_field_name')->shouldNotBeCalled();

    $access = $block->access($account->reveal(), TRUE);
    $this->assertFalse($access->isAllowed());
  }

  /**
   * Tests field access.
   *
   * @covers ::blockAccess
   * @dataProvider providerTestBlockAccessNotAllowed
   */
  public function testBlockAccessEntityAllowedFieldNotAllowed($expected, $field_access): void {
    $entity = $this->prophesize(FieldableEntityInterface::class);
    $block = $this->getTestBlock($entity);

    $account = $this->prophesize(AccountInterface::class);
    $entity->access('view', $account->reveal(), TRUE)->willReturn(AccessResult::allowed());
    $entity->hasField('the_field_name')->willReturn(TRUE);
    $field = $this->prophesize(FieldItemListInterface::class);
    $entity->get('the_field_name')->willReturn($field->reveal());

    $field->access('view', $account->reveal(), TRUE)->willReturn($field_access);
    $field->isEmpty()->shouldNotBeCalled();

    $access = $block->access($account->reveal(), TRUE);
    $this->assertSame($expected, $access->isAllowed());
  }

  /**
   * Tests populated vs empty build.
   *
   * @covers ::blockAccess
   * @covers ::build
   * @dataProvider providerTestBlockAccessEntityAllowedFieldHasValue
   */
  public function testBlockAccessEntityAllowedFieldHasValue($expected, $is_empty, $default_value): void {
    $entity = $this->prophesize(FieldableEntityInterface::class);
    $block = $this->getTestBlock($entity);

    $account = $this->prophesize(AccountInterface::class);
    $entity->access('view', $account->reveal(), TRUE)->willReturn(AccessResult::allowed());
    $entity->hasField('the_field_name')->willReturn(TRUE);
    $field = $this->prophesize(FieldItemListInterface::class);
    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field->getFieldDefinition()->willReturn($field_definition->reveal());
    $field_definition->getDefaultValue($entity->reveal())->willReturn($default_value);
    $field_definition->getType()->willReturn('not_an_image');
    $entity->get('the_field_name')->willReturn($field->reveal());

    $field->access('view', $account->reveal(), TRUE)->willReturn(AccessResult::allowed());
    $field->isEmpty()->willReturn($is_empty)->shouldBeCalled();

    $access = $block->access($account->reveal(), TRUE);
    $this->assertSame($expected, $access->isAllowed());
  }

  /**
   * Provides test data for ::testBlockAccessEntityAllowedFieldHasValue().
   */
  public static function providerTestBlockAccessEntityAllowedFieldHasValue() {
    $data = [];
    $data['empty'] = [
      FALSE,
      TRUE,
      FALSE,
    ];
    $data['populated'] = [
      TRUE,
      FALSE,
      FALSE,
    ];
    $data['empty, with default'] = [
      TRUE,
      TRUE,
      TRUE,
    ];
    return $data;
  }

  /**
   * Instantiates a block for testing.
   *
   * @param \Prophecy\Prophecy\ProphecyInterface $entity_prophecy
   *   An entity prophecy for use as an entity context value.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   *
   * @return \Drupal\layout_builder\Plugin\Block\FieldBlock
   *   The block to test.
   */
  protected function getTestBlock(ProphecyInterface $entity_prophecy, array $configuration = [], array $plugin_definition = []) {
    $entity_prophecy->getCacheContexts()->willReturn([]);
    $entity_prophecy->getCacheTags()->willReturn([]);
    $entity_prophecy->getCacheMaxAge()->willReturn(0);

    $plugin_definition += [
      'provider' => 'test',
      'default_formatter' => '',
      'category' => 'Test',
      'admin_label' => 'Test Block',
      'bundles' => ['entity_test'],
      'context_definitions' => [
        'entity' => EntityContextDefinition::fromEntityTypeId('entity_test')->setLabel('Test'),
        'view_mode' => new ContextDefinition('string'),
      ],
    ];
    $formatter_manager = $this->prophesize(FormatterPluginManager::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $block = new FieldBlock(
      $configuration,
      'field_block:entity_test:entity_test:the_field_name',
      $plugin_definition,
      $this->entityFieldManager->reveal(),
      $formatter_manager->reveal(),
      $module_handler->reveal(),
      $this->logger->reveal()
    );
    $block->setContextValue('entity', $entity_prophecy->reveal());
    $block->setContextValue('view_mode', 'default');
    return $block;
  }

  /**
   * @covers ::build
   * @dataProvider providerTestBuild
   */
  public function testBuild(PromiseInterface $promise, $expected_markup, $log_message = '', $log_arguments = []): void {
    $entity = $this->prophesize(FieldableEntityInterface::class);
    $field = $this->prophesize(FieldItemListInterface::class);
    $entity->get('the_field_name')->willReturn($field->reveal());
    $field->view(Argument::type('array'))->will($promise);

    $field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $field_definition->getLabel()->willReturn('The Field Label');
    $this->entityFieldManager->getFieldDefinitions('entity_test', 'entity_test')->willReturn(['the_field_name' => $field_definition]);

    if ($log_message) {
      $this->logger->warning($log_message, $log_arguments)->shouldBeCalled();
    }
    else {
      $this->logger->warning(Argument::cetera())->shouldNotBeCalled();
    }

    $block = $this->getTestBlock($entity);
    $expected = [
      '#cache' => [
        'contexts' => [],
        'tags' => [],
        'max-age' => 0,
      ],
    ];
    if ($expected_markup) {
      $expected[0]['content']['#markup'] = $expected_markup;
    }

    $actual = $block->build();
    $this->assertEquals($expected, $actual);
  }

  /**
   * Provides test data for ::testBuild().
   */
  public static function providerTestBuild() {
    $data = [];
    $data['array'] = [
      new ReturnPromise([['content' => ['#markup' => 'The field value']]]),
      'The field value',
    ];
    $data['empty array'] = [
      new ReturnPromise([[]]),
      '',
    ];
    return $data;
  }

  /**
   * @covers ::build
   */
  public function testBuildException(): void {
    // In PHP 7.4 ReflectionClass cannot be serialized so this cannot be part of
    // providerTestBuild().
    $promise = new ThrowPromise(new \Exception('The exception message'));
    $this->testBuild(
      $promise,
      '',
      'The field "%field" failed to render with the error of "%error".',
      ['%field' => 'the_field_name', '%error' => 'The exception message']
    );
  }

  /**
   * Tests a field block that throws a form exception.
   *
   * @todo Remove in https://www.drupal.org/project/drupal/issues/2367555.
   */
  public function testBuildWithFormException(): void {
    $field = $this->prophesize(FieldItemListInterface::class);
    $field->view(Argument::type('array'))->willThrow(new EnforcedResponseException(new Response()));

    $entity = $this->prophesize(FieldableEntityInterface::class);
    $entity->get('the_field_name')->willReturn($field->reveal());

    $block = $this->getTestBlock($entity);
    $this->expectException(EnforcedResponseException::class);
    $block->build();
  }

}
