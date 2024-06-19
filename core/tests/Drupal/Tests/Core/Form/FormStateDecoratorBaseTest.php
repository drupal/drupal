<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateDecoratorBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormStateDecoratorBase
 *
 * @group Form
 */
class FormStateDecoratorBaseTest extends UnitTestCase {

  /**
   * The decorated form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $decoratedFormState;

  /**
   * The form state decorator base under test.
   *
   * @var \Drupal\Core\Form\FormStateDecoratorBase
   */
  protected $formStateDecoratorBase;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->decoratedFormState = $this->prophesize(FormStateInterface::class);

    $this->formStateDecoratorBase = new NonAbstractFormStateDecoratorBase($this->decoratedFormState->reveal());
  }

  /**
   * Provides data to test methods that take a single boolean argument.
   */
  public static function providerSingleBooleanArgument() {
    return [
      [TRUE],
      [FALSE],
    ];
  }

  /**
   * @covers ::setFormState
   */
  public function testSetFormState(): void {
    $form_state_additions = [
      'foo' => 'bar',
    ];

    $this->decoratedFormState->setFormState($form_state_additions)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setFormState($form_state_additions));
  }

  /**
   * @covers ::setAlwaysProcess
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testSetAlwaysProcess($always_process): void {
    $this->decoratedFormState->setAlwaysProcess($always_process)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setAlwaysProcess($always_process));
  }

  /**
   * @covers ::getAlwaysProcess
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testGetAlwaysProcess($always_process): void {
    $this->decoratedFormState->getAlwaysProcess()
      ->willReturn($always_process)
      ->shouldBeCalled();

    $this->assertSame($always_process, $this->formStateDecoratorBase->getAlwaysProcess());
  }

  /**
   * @covers ::setButtons
   */
  public function testSetButtons(): void {
    $buttons = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->setButtons($buttons)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setButtons($buttons));
  }

  /**
   * @covers ::getButtons
   */
  public function testGetButtons(): void {
    $buttons = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->getButtons()
      ->willReturn($buttons)
      ->shouldBeCalled();

    $this->assertSame($buttons, $this->formStateDecoratorBase->getButtons());
  }

  /**
   * @covers ::setCached
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testSetCached($cache): void {
    $this->decoratedFormState->setCached($cache)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setCached($cache));
  }

  /**
   * @covers ::isCached
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testIsCached($cache): void {
    $this->decoratedFormState->isCached()
      ->willReturn($cache)
      ->shouldBeCalled();
    $this->assertSame($cache, $this->formStateDecoratorBase->isCached());
  }

  /**
   * @covers ::setCached
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testSetCachedWithLogicException($cache): void {
    $this->decoratedFormState->setCached($cache)
      ->willThrow(\LogicException::class);
    $this->expectException(\LogicException::class);
    $this->formStateDecoratorBase->setCached($cache);
  }

  /**
   * @covers ::disableCache
   */
  public function testDisableCache(): void {
    $this->decoratedFormState->disableCache()
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->disableCache());
  }

  /**
   * @covers ::setExecuted
   */
  public function testSetExecuted(): void {
    $this->decoratedFormState->setExecuted()
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setExecuted());
  }

  /**
   * @covers ::isExecuted
   *
   * @dataProvider providerSingleBooleanArgument
   *
   * @param bool $executed
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::isExecuted()'s
   *   return value.
   */
  public function testIsExecuted($executed): void {
    $this->decoratedFormState->isExecuted()
      ->willReturn($executed)
      ->shouldBeCalled();

    $this->assertSame($executed, $this->formStateDecoratorBase->isExecuted());
  }

  /**
   * @covers ::setGroups
   */
  public function testSetGroups(): void {
    $groups = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->setGroups($groups)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setGroups($groups));
  }

  /**
   * @covers ::getGroups
   */
  public function testGetGroups(): void {
    $groups = [
      'FOO' => 'BAR',
    ];

    // Use PHPUnit for mocking, because Prophecy cannot mock methods that return
    // by reference. See \Prophecy\Doubler\Generator\Node::getCode().
    $decorated_form_state = $this->createMock(FormStateInterface::class);
    $decorated_form_state->expects($this->once())
      ->method('getGroups')
      ->willReturn($groups);

    $this->formStateDecoratorBase = new NonAbstractFormStateDecoratorBase($decorated_form_state);

    $this->assertSame($groups, $this->formStateDecoratorBase->getGroups());
  }

  /**
   * @covers ::setHasFileElement
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testSetHasFileElement($has_file_element): void {
    $this->decoratedFormState->setHasFileElement($has_file_element)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setHasFileElement($has_file_element));
  }

  /**
   * @covers ::hasFileElement
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testHasFileElement($has_file_element): void {
    $this->decoratedFormState->hasFileElement()
      ->willReturn($has_file_element)
      ->shouldBeCalled();

    $this->assertSame($has_file_element, $this->formStateDecoratorBase->hasFileElement());
  }

  /**
   * @covers ::setLimitValidationErrors
   *
   * @dataProvider providerLimitValidationErrors
   */
  public function testSetLimitValidationErrors($limit_validation_errors): void {
    $this->decoratedFormState->setLimitValidationErrors($limit_validation_errors)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setLimitValidationErrors($limit_validation_errors));
  }

  /**
   * @covers ::getLimitValidationErrors
   *
   * @dataProvider providerLimitValidationErrors
   */
  public function testGetLimitValidationErrors($limit_validation_errors): void {
    $this->decoratedFormState->getLimitValidationErrors()
      ->willReturn($limit_validation_errors)
      ->shouldBeCalled();

    $this->assertSame($limit_validation_errors, $this->formStateDecoratorBase->getLimitValidationErrors());
  }

  /**
   * Provides data to self::testGetLimitValidationErrors() and self::testGetLimitValidationErrors().
   */
  public static function providerLimitValidationErrors() {
    return [
      [NULL],
      [
        [
          ['foo', 'bar', 'baz'],
        ],
      ],
    ];
  }

  /**
   * @covers ::setMethod
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testSetMethod($method): void {
    $this->decoratedFormState->setMethod($method)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setMethod($method));
  }

  /**
   * @covers ::isMethodType
   *
   * @dataProvider providerIsMethodType
   */
  public function testIsMethodType($expected_return_value, $method_type): void {
    $this->decoratedFormState->isMethodType($method_type)
      ->willReturn($expected_return_value)
      ->shouldBeCalled();

    $this->assertSame($expected_return_value, $this->formStateDecoratorBase->isMethodType($method_type));
  }

  /**
   * Provides data to self::testIsMethodType().
   */
  public static function providerIsMethodType() {
    return [
      [TRUE, 'GET'],
      [TRUE, 'POST'],
      [FALSE, 'GET'],
      [FALSE, 'POST'],
    ];
  }

  /**
   * @covers ::setRequestMethod
   *
   * @dataProvider providerSetRequestMethod
   */
  public function testSetRequestMethod($method): void {
    $this->decoratedFormState->setRequestMethod($method)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setRequestMethod($method));
  }

  /**
   * Provides data to self::testSetMethod().
   */
  public static function providerSetRequestMethod() {
    return [
      ['GET'],
      ['POST'],
    ];
  }

  /**
   * @covers ::setValidationEnforced
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testSetValidationEnforced($must_validate): void {
    $this->decoratedFormState->setValidationEnforced($must_validate)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setValidationEnforced($must_validate));
  }

  /**
   * @covers ::isValidationEnforced
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testIsValidationEnforced($must_validate): void {
    $this->decoratedFormState->isValidationEnforced()
      ->willReturn($must_validate)
      ->shouldBeCalled();

    $this->assertSame($must_validate, $this->formStateDecoratorBase->isValidationEnforced());
  }

  /**
   * @covers ::disableRedirect
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testDisableRedirect($no_redirect): void {
    $this->decoratedFormState->disableRedirect($no_redirect)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->disableRedirect($no_redirect));
  }

  /**
   * @covers ::isRedirectDisabled
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testIsRedirectDisabled($no_redirect): void {
    $this->decoratedFormState->isRedirectDisabled()
      ->willReturn($no_redirect)
      ->shouldBeCalled();

    $this->assertSame($no_redirect, $this->formStateDecoratorBase->isRedirectDisabled());
  }

  /**
   * @covers ::setProcessInput
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testSetProcessInput($process_input): void {
    $this->decoratedFormState->setProcessInput($process_input)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setProcessInput($process_input));
  }

  /**
   * @covers ::isProcessingInput
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testIsProcessingInput($process_input): void {
    $this->decoratedFormState->isProcessingInput()
      ->willReturn($process_input)
      ->shouldBeCalled();

    $this->assertSame($process_input, $this->formStateDecoratorBase->isProcessingInput());
  }

  /**
   * @covers ::setProgrammed
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testSetProgrammed($programmed): void {
    $this->decoratedFormState->setProgrammed($programmed)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setProgrammed($programmed));
  }

  /**
   * @covers ::isProgrammed
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testIsProgrammed($programmed): void {
    $this->decoratedFormState->isProgrammed()
      ->willReturn($programmed)
      ->shouldBeCalled();

    $this->assertSame($programmed, $this->formStateDecoratorBase->isProgrammed());
  }

  /**
   * @covers ::setProgrammedBypassAccessCheck
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testSetProgrammedBypassAccessCheck($programmed_bypass_access_check): void {
    $this->decoratedFormState->setProgrammedBypassAccessCheck($programmed_bypass_access_check)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setProgrammedBypassAccessCheck($programmed_bypass_access_check));
  }

  /**
   * @covers ::isBypassingProgrammedAccessChecks
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testIsBypassingProgrammedAccessChecks($programmed_bypass_access_check): void {
    $this->decoratedFormState->isBypassingProgrammedAccessChecks()
      ->willReturn($programmed_bypass_access_check)
      ->shouldBeCalled();

    $this->assertSame($programmed_bypass_access_check, $this->formStateDecoratorBase->isBypassingProgrammedAccessChecks());
  }

  /**
   * @covers ::setRebuildInfo
   */
  public function testSetRebuildInfo(): void {
    $rebuild_info = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->setRebuildInfo($rebuild_info)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setRebuildInfo($rebuild_info));
  }

  /**
   * @covers ::getRebuildInfo
   */
  public function testGetRebuildInfo(): void {
    $rebuild_info = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->getRebuildInfo()
      ->willReturn($rebuild_info)
      ->shouldBeCalled();

    $this->assertSame($rebuild_info, $this->formStateDecoratorBase->getRebuildInfo());
  }

  /**
   * @covers ::addRebuildInfo
   */
  public function testAddRebuildInfo(): void {
    $property = 'FOO';
    $value = 'BAR';

    $this->decoratedFormState->addRebuildInfo($property, $value);

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->addRebuildInfo($property, $value));
  }

  /**
   * @covers ::setStorage
   */
  public function testSetStorage(): void {
    $storage = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->setStorage($storage)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setStorage($storage));
  }

  /**
   * @covers ::getStorage
   */
  public function testGetStorage(): void {
    $storage = [
      'FOO' => 'BAR',
    ];

    // Use PHPUnit for mocking, because Prophecy cannot mock methods that return
    // by reference. See \Prophecy\Doubler\Generator\Node::getCode().
    $decorated_form_state = $this->createMock(FormStateInterface::class);
    $decorated_form_state->expects($this->once())
      ->method('getStorage')
      ->willReturn($storage);

    $this->formStateDecoratorBase = new NonAbstractFormStateDecoratorBase($decorated_form_state);

    $this->assertSame($storage, $this->formStateDecoratorBase->getStorage());
  }

  /**
   * @covers ::setSubmitHandlers
   */
  public function testSetSubmitHandlers(): void {
    $submit_handlers = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->setSubmitHandlers($submit_handlers)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setSubmitHandlers($submit_handlers));
  }

  /**
   * @covers ::getSubmitHandlers
   */
  public function testGetSubmitHandlers(): void {
    $submit_handlers = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->getSubmitHandlers()
      ->willReturn($submit_handlers)
      ->shouldBeCalled();

    $this->assertSame($submit_handlers, $this->formStateDecoratorBase->getSubmitHandlers());
  }

  /**
   * @covers ::setSubmitted
   */
  public function testSetSubmitted(): void {
    $this->decoratedFormState->setSubmitted()
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setSubmitted());
  }

  /**
   * @covers ::isSubmitted
   *
   * @dataProvider providerSingleBooleanArgument
   *
   * @param bool $submitted
   *   Any valid value for
   *   \Drupal\Core\Form\FormStateInterface::isSubmitted()'s return
   *   value.
   */
  public function testIsSubmitted($submitted): void {
    $this->decoratedFormState->isSubmitted()
      ->willReturn($submitted);

    $this->assertSame($submitted, $this->formStateDecoratorBase->isSubmitted());
  }

  /**
   * @covers ::setTemporary
   */
  public function testSetTemporary(): void {
    $temporary = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->setTemporary($temporary)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setTemporary($temporary));
  }

  /**
   * @covers ::getTemporary
   */
  public function testGetTemporary(): void {
    $temporary = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->getTemporary()
      ->willReturn($temporary)
      ->shouldBeCalled();

    $this->assertSame($temporary, $this->formStateDecoratorBase->getTemporary());
  }

  /**
   * @covers ::setTemporaryValue
   *
   * @dataProvider providerSetTemporaryValue
   *
   * @param string $key
   *   Any valid value for
   *   \Drupal\Core\Form\FormStateInterface::setTemporaryValue()'s $key
   *   argument.
   * @param mixed $value
   *   Any valid value for
   *   \Drupal\Core\Form\FormStateInterface::setTemporaryValue()'s $value
   *   argument.
   */
  public function testSetTemporaryValue($key, $value): void {
    $this->decoratedFormState->setTemporaryValue($key, $value)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setTemporaryValue($key, $value));
  }

  /**
   * Provides data to self::testSetTemporaryValue().
   */
  public static function providerSetTemporaryValue() {
    return [
      ['FOO', 'BAR'],
      ['FOO', NULL],
    ];
  }

  /**
   * @covers ::getTemporaryValue
   *
   * @dataProvider providerGetTemporaryValue
   *
   * @param string $key
   *   Any valid value for
   *   \Drupal\Core\Form\FormStateInterface::getTemporaryValue()'s $key
   *   argument.
   * @param mixed $value
   *   (optional) Any valid value for
   *   \Drupal\Core\Form\FormStateInterface::getTemporaryValue()'s return
   *   value.
   */
  public function testGetTemporaryValue($key, $value = NULL): void {
    // Use PHPUnit for mocking, because Prophecy cannot mock methods that return
    // by reference. See \Prophecy\Doubler\Generator\Node::getCode().
    $decorated_form_state = $this->createMock(FormStateInterface::class);
    $decorated_form_state->expects($this->once())
      ->method('getTemporaryValue')
      ->with($key)
      ->willReturn($value);

    $this->formStateDecoratorBase = new NonAbstractFormStateDecoratorBase($decorated_form_state);

    $this->assertSame($value, $this->formStateDecoratorBase->getTemporaryValue($key));
  }

  /**
   * Provides data to self::testGetTemporaryValue().
   */
  public static function providerGetTemporaryValue() {
    return [
      [TRUE, 'FOO', 'BAR'],
      [TRUE, 'FOO', NULL],
    ];
  }

  /**
   * @covers ::hasTemporaryValue
   *
   * @dataProvider providerHasTemporaryValue
   *
   * @param bool $exists
   *   Any valid value for
   *   \Drupal\Core\Form\FormStateInterface::hasTemporaryValue()'s return
   *   value.
   * @param string $key
   *   Any valid value for
   *   \Drupal\Core\Form\FormStateInterface::hasTemporaryValue()'s $key
   *   argument.
   */
  public function testHasTemporaryValue($exists, $key): void {
    $this->decoratedFormState->hasTemporaryValue($key)
      ->willReturn($exists)
      ->shouldBeCalled();

    $this->assertSame($exists, $this->formStateDecoratorBase->hasTemporaryValue($key));
  }

  /**
   * Provides data to self::testHasTemporaryValue().
   */
  public static function providerHasTemporaryValue() {
    return [
      [TRUE, 'FOO'],
      [FALSE, 'FOO'],
    ];
  }

  /**
   * @covers ::setTriggeringElement
   */
  public function testSetTriggeringElement(): void {
    $triggering_element = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->setTriggeringElement($triggering_element)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setTriggeringElement($triggering_element));
  }

  /**
   * @covers ::getTriggeringElement
   */
  public function testGetTriggeringElement(): void {
    $triggering_element = [
      'FOO' => 'BAR',
    ];

    // Use PHPUnit for mocking, because Prophecy cannot mock methods that return
    // by reference. See \Prophecy\Doubler\Generator\Node::getCode().
    $decorated_form_state = $this->createMock(FormStateInterface::class);
    $decorated_form_state->expects($this->once())
      ->method('getTriggeringElement')
      ->willReturn($triggering_element);

    $this->formStateDecoratorBase = new NonAbstractFormStateDecoratorBase($decorated_form_state);

    $this->assertSame($triggering_element, $this->formStateDecoratorBase->getTriggeringElement());
  }

  /**
   * @covers ::setValidateHandlers
   */
  public function testSetValidateHandlers(): void {
    $validate_handlers = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->setValidateHandlers($validate_handlers)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setValidateHandlers($validate_handlers));
  }

  /**
   * @covers ::getValidateHandlers
   */
  public function testGetValidateHandlers(): void {
    $validate_handlers = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->getValidateHandlers()
      ->willReturn($validate_handlers)
      ->shouldBeCalled();

    $this->assertSame($validate_handlers, $this->formStateDecoratorBase->getValidateHandlers());
  }

  /**
   * @covers ::setValidationComplete
   *
   * @dataProvider providerSingleBooleanArgument
   *
   * @param bool $complete
   *   Any valid value for
   *   \Drupal\Core\Form\FormStateInterface::setValidationComplete()'s $complete
   *   argument.
   */
  public function testSetValidationComplete($complete): void {
    $this->decoratedFormState->setValidationComplete($complete)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setValidationComplete($complete));
  }

  /**
   * @covers ::isValidationComplete
   *
   * @dataProvider providerSingleBooleanArgument
   *
   * @param bool $complete
   *   Any valid value for
   *   \Drupal\Core\Form\FormStateInterface::isValidationComplete()'s return
   *   value.
   */
  public function testIsValidationComplete($complete): void {
    $this->decoratedFormState->isValidationComplete()
      ->willReturn($complete)
      ->shouldBeCalled();

    $this->assertSame($complete, $this->formStateDecoratorBase->isValidationComplete());
  }

  /**
   * @covers ::loadInclude
   *
   * @dataProvider providerLoadInclude
   *
   * @param string|false $expected
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::loadInclude()'s
   *   return value.
   * @param string $module
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::loadInclude()'s
   *   $module argument.
   * @param string $type
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::loadInclude()'s
   *   $type argument.
   * @param string|null $name
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::loadInclude()'s
   *   $name argument.
   */
  public function testLoadInclude($expected, $module, $type, $name): void {
    $this->decoratedFormState->loadInclude($module, $type, $name)
      ->willReturn($expected)
      ->shouldBeCalled();

    $this->assertSame($expected, $this->formStateDecoratorBase->loadInclude($module, $type, $name));
  }

  /**
   * Provides data to self::testLoadInclude().
   */
  public static function providerLoadInclude() {
    return [
      // Existing files.
      [__FILE__, 'foo', 'inc', 'foo'],
      [__FILE__, 'foo', 'inc', 'foo.admin'],
      [__FILE__, 'bar', 'inc', 'bar'],
      // Non-existent files.
      [FALSE, 'foo', 'php', 'foo'],
      [FALSE, 'bar', 'php', 'foo'],
    ];
  }

  /**
   * @covers ::getCacheableArray
   */
  public function testGetCacheableArray(): void {
    $cacheable_array = [
      'foo' => 'bar',
    ];

    $this->decoratedFormState->getCacheableArray()
      ->willReturn($cacheable_array)
      ->shouldBeCalled();

    $this->assertSame($cacheable_array, $this->formStateDecoratorBase->getCacheableArray());
  }

  /**
   * @covers ::setCompleteForm
   */
  public function testSetCompleteForm(): void {
    $complete_form = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->setCompleteForm($complete_form)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setCompleteForm($complete_form));
  }

  /**
   * @covers ::getCompleteForm
   */
  public function testGetCompleteForm(): void {
    $complete_form = [
      'FOO' => 'BAR',
    ];

    // Use PHPUnit for mocking, because Prophecy cannot mock methods that return
    // by reference. See \Prophecy\Doubler\Generator\Node::getCode().
    $decorated_form_state = $this->createMock(FormStateInterface::class);
    $decorated_form_state->expects($this->once())
      ->method('getCompleteForm')
      ->willReturn($complete_form);

    $this->formStateDecoratorBase = new NonAbstractFormStateDecoratorBase($decorated_form_state);

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setCompleteForm($complete_form));
    $this->assertSame($complete_form, $this->formStateDecoratorBase->getCompleteForm());
  }

  /**
   * @covers ::set
   *
   * @dataProvider providerSet
   *
   * @param string $key
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::set()'s $key
   *   argument.
   * @param mixed $value
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::set()'s $value
   *   argument.
   */
  public function testSet($key, $value): void {
    $this->decoratedFormState->set($key, $value)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->set($key, $value));
  }

  /**
   * Provides data to self::testSet().
   */
  public static function providerSet(): array {
    return [
      ['FOO', 'BAR'],
      ['FOO', NULL],
    ];
  }

  /**
   * @covers ::get
   *
   * @dataProvider providerGet
   *
   * @param string $key
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::get()'s $key
   *   argument.
   * @param mixed $value
   *   (optional) Any valid value for
   *   \Drupal\Core\Form\FormStateInterface::get()'s return value.
   */
  public function testGet($key, $value = NULL): void {

    // Use PHPUnit for mocking, because Prophecy cannot mock methods that return
    // by reference. See \Prophecy\Doubler\Generator\Node::getCode().
    $decorated_form_state = $this->createMock(FormStateInterface::class);
    $decorated_form_state->expects($this->once())
      ->method('get')
      ->with($key)
      ->willReturn($value);

    $this->formStateDecoratorBase = new NonAbstractFormStateDecoratorBase($decorated_form_state);

    $this->assertSame($value, $this->formStateDecoratorBase->get($key));
  }

  /**
   * Provides data to self::testGet().
   */
  public static function providerGet(): array {
    return [
      ['FOO', 'BAR'],
      ['FOO', NULL],
    ];
  }

  /**
   * @covers ::has
   *
   * @dataProvider providerHas
   *
   * @param bool $exists
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::has()'s return
   *   value.
   * @param string $key
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::has()'s $key
   *   argument.
   */
  public function testHas($exists, $key): void {
    $this->decoratedFormState->has($key)
      ->willReturn($exists)
      ->shouldBeCalled();

    $this->assertSame($exists, $this->formStateDecoratorBase->has($key));
  }

  /**
   * Provides data to self::testHas().
   */
  public static function providerHas(): array {
    return [
      [TRUE, 'FOO'],
      [FALSE, 'FOO'],
    ];
  }

  /**
   * @covers ::setBuildInfo
   */
  public function testSetBuildInfo(): void {
    $build_info = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->setBuildInfo($build_info)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setBuildInfo($build_info));
  }

  /**
   * @covers ::getBuildInfo
   */
  public function testGetBuildInfo(): void {
    $build_info = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->getBuildInfo()
      ->willReturn($build_info)
      ->shouldBeCalled();

    $this->assertSame($build_info, $this->formStateDecoratorBase->getBuildInfo());
  }

  /**
   * @covers ::addBuildInfo
   */
  public function testAddBuildInfo(): void {
    $property = 'FOO';
    $value = 'BAR';

    $this->decoratedFormState->addBuildInfo($property, $value)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->addBuildInfo($property, $value));
  }

  /**
   * @covers ::setUserInput
   */
  public function testSetUserInput(): void {
    $user_input = [
      'FOO' => 'BAR',
    ];

    $this->decoratedFormState->setUserInput($user_input)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setUserInput($user_input));
  }

  /**
   * @covers ::getUserInput
   */
  public function testGetUserInput(): void {
    $user_input = [
      'FOO' => 'BAR',
    ];

    // Use PHPUnit for mocking, because Prophecy cannot mock methods that return
    // by reference. See \Prophecy\Doubler\Generator\Node::getCode().
    $decorated_form_state = $this->createMock(FormStateInterface::class);
    $decorated_form_state->expects($this->once())
      ->method('getUserInput')
      ->willReturn($user_input);

    $this->formStateDecoratorBase = new NonAbstractFormStateDecoratorBase($decorated_form_state);

    $this->assertSame($user_input, $this->formStateDecoratorBase->getUserInput());
  }

  /**
   * @covers ::getValues
   */
  public function testGetValues(): void {
    $values = [
      'FOO' => 'BAR',
    ];

    // Use PHPUnit for mocking, because Prophecy cannot mock methods that return
    // by reference. See \Prophecy\Doubler\Generator\Node::getCode().
    $decorated_form_state = $this->createMock(FormStateInterface::class);
    $decorated_form_state->expects($this->once())
      ->method('getValues')
      ->willReturn($values);

    $this->formStateDecoratorBase = new NonAbstractFormStateDecoratorBase($decorated_form_state);

    $this->assertSame($values, $this->formStateDecoratorBase->getValues());
  }

  /**
   * @covers ::getValue
   */
  public function testGetValue(): void {
    $key = 'FOO';
    $value = 'BAR';

    // Use PHPUnit for mocking, because Prophecy cannot mock methods that return
    // by reference. See \Prophecy\Doubler\Generator\Node::getCode().
    $decorated_form_state = $this->createMock(FormStateInterface::class);
    $decorated_form_state->expects($this->once())
      ->method('getValue')
      ->with($key, $value)
      ->willReturn($value);

    $this->formStateDecoratorBase = new NonAbstractFormStateDecoratorBase($decorated_form_state);

    $this->assertSame($value, $this->formStateDecoratorBase->getValue($key, $value));
  }

  /**
   * @covers ::setValues
   */
  public function testSetValues(): void {
    $values = [
      'foo' => 'Foo',
      'bar' => ['Bar'],
    ];

    $this->decoratedFormState->setValues($values)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setValues($values));
  }

  /**
   * @covers ::setValue
   */
  public function testSetValue(): void {
    $key = 'FOO';
    $value = 'BAR';

    $this->decoratedFormState->setValue($key, $value)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setValue($key, $value));
  }

  /**
   * @covers ::unsetValue
   */
  public function testUnsetValue(): void {
    $key = 'FOO';

    $this->decoratedFormState->unsetValue($key)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->unsetValue($key));
  }

  /**
   * @covers ::hasValue
   */
  public function testHasValue(): void {
    $key = ['foo', 'bar'];
    $has = TRUE;

    $this->decoratedFormState->hasValue($key)
      ->willReturn($has)
      ->shouldBeCalled();

    $this->assertSame($has, $this->formStateDecoratorBase->hasValue($key));
  }

  /**
   * @covers ::isValueEmpty
   */
  public function testIsValueEmpty(): void {
    $key = ['foo', 'bar'];
    $is_empty = TRUE;

    $this->decoratedFormState->isValueEmpty($key)
      ->willReturn($is_empty)
      ->shouldBeCalled();

    $this->assertSame($is_empty, $this->formStateDecoratorBase->isValueEmpty($key));
  }

  /**
   * @covers ::setValueForElement
   */
  public function testSetValueForElement(): void {
    $element = [
      '#type' => 'foo',
    ];
    $value = 'BAR';

    $this->decoratedFormState->setValueForElement($element, $value)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setValueForElement($element, $value));
  }

  /**
   * @covers ::setResponse
   */
  public function testSetResponse(): void {
    $response = $this->createMock(Response::class);

    $this->decoratedFormState->setResponse($response)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setResponse($response));
  }

  /**
   * @covers ::getResponse
   */
  public function testGetResponse(): void {
    $response = $this->createMock(Response::class);

    $this->decoratedFormState->getResponse()
      ->willReturn($response)
      ->shouldBeCalled();

    $this->assertSame($response, $this->formStateDecoratorBase->getResponse());
  }

  /**
   * @covers ::setRedirect
   */
  public function testSetRedirect(): void {
    $route_name = 'foo';
    $route_parameters = [
      'bar' => 'baz',
    ];
    $options = [
      'qux' => 'foo',
    ];

    $this->decoratedFormState->setRedirect($route_name, $route_parameters, $options)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setRedirect($route_name, $route_parameters, $options));
  }

  /**
   * @covers ::setRedirectUrl
   */
  public function testSetRedirectUrl(): void {
    $url = new Url('foo');

    $this->decoratedFormState->setRedirectUrl($url)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setRedirectUrl($url));
  }

  /**
   * @covers ::getRedirect
   *
   * @dataProvider providerGetRedirect
   *
   * @param bool $expected
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::getRedirect()'s
   *   return value.
   */
  public function testGetRedirect($expected): void {
    $this->decoratedFormState->getRedirect()
      ->willReturn($expected)
      ->shouldBeCalled();

    $this->assertSame($expected, $this->formStateDecoratorBase->getRedirect());
  }

  /**
   * Provides data to self::testGetRedirect().
   */
  public static function providerGetRedirect() {
    return [
      [NULL],
      [FALSE],
      [new Url('foo')],
      [new RedirectResponse('http://example.com')],
    ];
  }

  /**
   * @covers ::setErrorByName
   */
  public function testSetErrorByName(): void {
    $name = 'foo';
    $message = 'bar';

    $this->decoratedFormState->setErrorByName($name, $message)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setErrorByName($name, $message));
  }

  /**
   * @covers ::setError
   */
  public function testSetError(): void {
    $element = [
      '#foo' => 'bar',
    ];
    $message = 'bar';

    $this->decoratedFormState->setError($element, $message)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setError($element, $message));
  }

  /**
   * @covers ::clearErrors
   */
  public function testClearErrors(): void {
    $this->decoratedFormState->clearErrors()
      ->shouldBeCalled();

    $this->formStateDecoratorBase->clearErrors();
  }

  /**
   * @covers ::getError
   */
  public function testGetError(): void {
    $element = [
      '#foo' => 'bar',
    ];
    $message = 'bar';

    $this->decoratedFormState->getError($element)
      ->willReturn($message)
      ->shouldBeCalled();

    $this->assertSame($message, $this->formStateDecoratorBase->getError($element));
  }

  /**
   * @covers ::getErrors
   */
  public function testGetErrors(): void {
    $errors = [
      'foo' => 'bar',
    ];
    $this->decoratedFormState->getErrors()
      ->willReturn($errors)
      ->shouldBeCalled();

    $this->assertSame($errors, $this->formStateDecoratorBase->getErrors());
  }

  /**
   * @covers ::setRebuild
   *
   * @dataProvider providerSingleBooleanArgument
   *
   * @param bool $rebuild
   *   Any valid value for \Drupal\Core\Form\FormStateInterface::setRebuild()'s
   *   $rebuild argument.
   */
  public function testSetRebuild($rebuild): void {
    $this->decoratedFormState->setRebuild($rebuild)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setRebuild($rebuild));
  }

  /**
   * @covers ::isRebuilding
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testIsRebuilding($rebuild): void {
    $this->decoratedFormState->isRebuilding()
      ->willReturn($rebuild)
      ->shouldBeCalled();

    $this->assertSame($rebuild, $this->formStateDecoratorBase->isRebuilding());
  }

  /**
   * @covers ::setInvalidToken
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testSetInvalidToken($expected): void {
    $this->decoratedFormState->setInvalidToken($expected)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setInvalidToken($expected));
  }

  /**
   * @covers ::hasInvalidToken
   *
   * @dataProvider providerSingleBooleanArgument
   */
  public function testHasInvalidToken($expected): void {
    $this->decoratedFormState->hasInvalidToken()
      ->willReturn($expected)
      ->shouldBeCalled();

    $this->assertSame($expected, $this->formStateDecoratorBase->hasInvalidToken());
  }

  /**
   * @covers ::prepareCallback
   *
   * @dataProvider providerPrepareCallback
   */
  public function testPrepareCallback($unprepared_callback, callable $prepared_callback): void {
    $this->decoratedFormState->prepareCallback(Argument::is($unprepared_callback))
      ->willReturn($prepared_callback)
      ->shouldBeCalled();

    $this->assertSame($prepared_callback, $this->formStateDecoratorBase->prepareCallback($unprepared_callback));
  }

  /**
   * Provides data to self::testPrepareCallback().
   */
  public static function providerPrepareCallback(): array {
    $function = 'sleep';
    $shorthand_form_method = '::submit()';
    $closure = function () {};
    $static_method_string = __METHOD__;
    $static_method_array = [__CLASS__, __FUNCTION__];
    $object_method_array = [new static('test'), __FUNCTION__];

    return [
      // A shorthand form method is generally expanded to become a method on an
      // object.
      [$shorthand_form_method, $object_method_array],
      // Functions, closures, and static method calls generally remain the same.
      [$function, $function],
      [$closure, $closure],
      [$static_method_string, $static_method_string],
      [$static_method_array, $static_method_array],
    ];
  }

  /**
   * @covers ::setFormObject
   */
  public function testSetFormObject(): void {
    $form = $this->createMock(FormInterface::class);

    $this->decoratedFormState->setFormObject($form)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setFormObject($form));
  }

  /**
   * @covers ::getFormObject
   */
  public function testGetFormObject(): void {
    $form = $this->createMock(FormInterface::class);

    $this->decoratedFormState->getFormObject()
      ->willReturn($form)
      ->shouldBeCalled();

    $this->assertSame($form, $this->formStateDecoratorBase->getFormObject());
  }

  /**
   * @covers ::setCleanValueKeys
   */
  public function testSetCleanValueKeys(): void {
    $keys = ['BAR'];

    $this->decoratedFormState->setCleanValueKeys($keys)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->setCleanValueKeys($keys));
  }

  /**
   * @covers ::getCleanValueKeys
   */
  public function testGetCleanValueKeys(): void {
    $keys = ['BAR'];

    $this->decoratedFormState->getCleanValueKeys()
      ->willReturn($keys)
      ->shouldBeCalled();

    $this->assertSame($keys, $this->formStateDecoratorBase->getCleanValueKeys());
  }

  /**
   * @covers ::addCleanValueKey
   */
  public function testAddCleanValueKey(): void {
    $key = 'BAR';

    $this->decoratedFormState->addCleanValueKey($key)
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->addCleanValueKey($key));
  }

  /**
   * @covers ::cleanValues
   */
  public function testCleanValues(): void {
    $this->decoratedFormState->cleanValues()
      ->shouldBeCalled();

    $this->assertSame($this->formStateDecoratorBase, $this->formStateDecoratorBase->cleanValues());
  }

}

/**
 * Provides a non-abstract version of the class under test.
 */
class NonAbstractFormStateDecoratorBase extends FormStateDecoratorBase {

  /**
   * Creates a new instance.
   *
   * @param \Drupal\Core\Form\FormStateInterface $decorated_form_state
   *   The decorated form state.
   */
  public function __construct(FormStateInterface $decorated_form_state) {
    $this->decoratedFormState = $decorated_form_state;
  }

}
