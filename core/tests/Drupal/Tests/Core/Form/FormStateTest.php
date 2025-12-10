<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Tests Drupal\Core\Form\FormState.
 */
#[CoversClass(FormState::class)]
#[Group('Form')]
class FormStateTest extends UnitTestCase {

  /**
   * Tests the getRedirect() method.
   *
   * @legacy-covers ::getRedirect
   */
  #[DataProvider('providerTestGetRedirect')]
  public function testGetRedirect($form_state_additions, $expected): void {
    $form_state = (new FormState())->setFormState($form_state_additions);
    $redirect = $form_state->getRedirect();
    $this->assertEquals($expected, $redirect);
  }

  /**
   * Provides test data for testing the getRedirect() method.
   *
   * @return array
   *   Returns some test data.
   */
  public static function providerTestGetRedirect(): array {
    $data = [];
    $data[] = [[], NULL];

    $redirect = new RedirectResponse('/example');
    $data[] = [['redirect' => $redirect], $redirect];

    $data[] = [['redirect' => new Url('test_route_b', ['key' => 'value'])], new Url('test_route_b', ['key' => 'value'])];

    $data[] = [['programmed' => TRUE], NULL];
    $data[] = [['rebuild' => TRUE], NULL];
    $data[] = [['no_redirect' => TRUE], NULL];

    return $data;
  }

  /**
   * Tests the setError() method.
   *
   * @legacy-covers ::setError
   */
  public function testSetError(): void {
    $form_state = new FormState();
    $element['#parents'] = ['foo', 'bar'];
    $form_state->setError($element, 'Fail');
    $this->assertSame(['foo][bar' => 'Fail'], $form_state->getErrors());
  }

  /**
   * Tests the getError() method.
   *
   * @legacy-covers ::getError
   */
  #[DataProvider('providerTestGetError')]
  public function testGetError($errors, $parents, $error = NULL): void {
    $element['#parents'] = $parents;
    $form_state = (new FormState())->setFormState([
      'errors' => $errors,
    ]);
    $this->assertSame($error, $form_state->getError($element));
  }

  public static function providerTestGetError(): array {
    return [
      [[], ['foo']],
      [['foo][bar' => 'Fail'], []],
      [['foo][bar' => 'Fail'], ['foo']],
      [['foo][bar' => 'Fail'], ['bar']],
      [['foo][bar' => 'Fail'], ['baz']],
      [['foo][bar' => 'Fail'], ['foo', 'bar'], 'Fail'],
      [['foo][bar' => 'Fail'], ['foo', 'bar', 'baz'], 'Fail'],
      [['foo][bar' => 'Fail 2'], ['foo']],
      [['foo' => 'Fail 1', 'foo][bar' => 'Fail 2'], ['foo'], 'Fail 1'],
      [['foo' => 'Fail 1', 'foo][bar' => 'Fail 2'], ['foo', 'bar'], 'Fail 1'],
    ];
  }

  /**
   * Tests set error by name.
   *
   * @legacy-covers ::setErrorByName
   */
  #[DataProvider('providerTestSetErrorByName')]
  public function testSetErrorByName($limit_validation_errors, $expected_errors): void {
    $form_state = new FormState();
    $form_state->setLimitValidationErrors($limit_validation_errors);
    $form_state->clearErrors();

    $form_state->setErrorByName('test', 'Fail 1');
    $form_state->setErrorByName('test', 'Fail 2');
    $form_state->setErrorByName('options');

    $this->assertSame(!empty($expected_errors), $form_state::hasAnyErrors());
    $this->assertSame($expected_errors, $form_state->getErrors());
  }

  public static function providerTestSetErrorByName(): array {
    return [
      // Only validate the 'options' element.
      [[['options']], ['options' => '']],
      // Do not limit a validation, ensure the first error is returned
      // for the 'test' element.
      [NULL, ['test' => 'Fail 1', 'options' => '']],
      // Limit all validation.
      [[], []],
    ];
  }

  /**
   * Tests that form errors during submission throw an exception.
   *
   * @legacy-covers ::setErrorByName
   */
  public function testFormErrorsDuringSubmission(): void {
    $form_state = new FormState();
    $form_state->setValidationComplete();
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Form errors cannot be set after form validation has finished.');
    $form_state->setErrorByName('test', 'message');
  }

  /**
   * Tests prepare callback valid method.
   *
   * @legacy-covers ::prepareCallback
   */
  public function testPrepareCallbackValidMethod(): void {
    $form_state = new FormState();
    $form_state->setFormObject(new PrepareCallbackTestForm());
    $processed_callback = $form_state->prepareCallback('::buildForm');
    $this->assertEquals([$form_state->getFormObject(), 'buildForm'], $processed_callback);
  }

  /**
   * Tests prepare callback in valid method.
   *
   * @legacy-covers ::prepareCallback
   */
  public function testPrepareCallbackInValidMethod(): void {
    $form_state = new FormState();
    $form_state->setFormObject(new PrepareCallbackTestForm());
    $processed_callback = $form_state->prepareCallback('not_a_method');
    // The callback was not changed as no such method exists.
    $this->assertEquals('not_a_method', $processed_callback);
  }

  /**
   * Tests prepare callback array.
   *
   * @legacy-covers ::prepareCallback
   */
  public function testPrepareCallbackArray(): void {
    $form_state = new FormState();
    $form_state->setFormObject(new PrepareCallbackTestForm());
    $callback = [$form_state->getFormObject(), 'buildForm'];
    $processed_callback = $form_state->prepareCallback($callback);
    $this->assertEquals($callback, $processed_callback);
  }

  /**
   * Tests load include.
   *
   * @legacy-covers ::loadInclude
   */
  public function testLoadInclude(): void {
    $type = 'some_type';
    $module = 'some_module';
    $name = 'some_name';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->onlyMethods(['moduleLoadInclude'])
      ->getMock();
    $form_state->expects($this->once())
      ->method('moduleLoadInclude')
      ->with($module, $type, $name)
      ->willReturn(TRUE);
    $this->assertTrue($form_state->loadInclude($module, $type, $name));
  }

  /**
   * Tests load include no name.
   *
   * @legacy-covers ::loadInclude
   */
  public function testLoadIncludeNoName(): void {
    $type = 'some_type';
    $module = 'some_module';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->onlyMethods(['moduleLoadInclude'])
      ->getMock();
    $form_state->expects($this->once())
      ->method('moduleLoadInclude')
      ->with($module, $type, $module)
      ->willReturn(TRUE);
    $this->assertTrue($form_state->loadInclude($module, $type));
  }

  /**
   * Tests load include not found.
   *
   * @legacy-covers ::loadInclude
   */
  public function testLoadIncludeNotFound(): void {
    $type = 'some_type';
    $module = 'some_module';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->onlyMethods(['moduleLoadInclude'])
      ->getMock();
    $form_state->expects($this->once())
      ->method('moduleLoadInclude')
      ->with($module, $type, $module)
      ->willReturn(FALSE);
    $this->assertFalse($form_state->loadInclude($module, $type));
  }

  /**
   * Tests load include already loaded.
   *
   * @legacy-covers ::loadInclude
   */
  public function testLoadIncludeAlreadyLoaded(): void {
    $type = 'some_type';
    $module = 'some_module';
    $name = 'some_name';
    $form_state = $this->getMockBuilder('Drupal\Core\Form\FormState')
      ->onlyMethods(['moduleLoadInclude'])
      ->getMock();

    $form_state->addBuildInfo('files', [
      'some_module:some_name.some_type' => [
        'type' => $type,
        'module' => $module,
        'name' => $name,
      ],
    ]);
    $form_state->expects($this->never())
      ->method('moduleLoadInclude');

    $this->assertFalse($form_state->loadInclude($module, $type, $name));
  }

  /**
   * Tests is cached.
   *
   * @legacy-covers ::isCached
   */
  #[DataProvider('providerTestIsCached')]
  public function testIsCached($cache_key, $no_cache_key, $expected): void {
    $form_state = (new FormState())->setFormState([
      'cache' => $cache_key,
      'no_cache' => $no_cache_key,
    ]);

    $form_state->setMethod('POST');
    $this->assertSame($expected, $form_state->isCached());

    $form_state->setMethod('GET');
    $this->assertSame($expected, $form_state->isCached());
  }

  /**
   * Provides test data for testIsCached().
   */
  public static function providerTestIsCached(): array {
    $data = [];
    $data[] = [
      TRUE,
      TRUE,
      FALSE,
    ];
    $data[] = [
      FALSE,
      TRUE,
      FALSE,
    ];
    $data[] = [
      FALSE,
      FALSE,
      FALSE,
    ];
    $data[] = [
      TRUE,
      FALSE,
      TRUE,
    ];
    $data[] = [
      TRUE,
      NULL,
      TRUE,
    ];
    $data[] = [
      FALSE,
      NULL,
      FALSE,
    ];
    return $data;
  }

  /**
   * Tests set cached post.
   *
   * @legacy-covers ::setCached
   */
  public function testSetCachedPost(): void {
    $form_state = new FormState();
    $form_state->setRequestMethod('POST');
    $form_state->setCached();
    $this->assertTrue($form_state->isCached());
  }

  /**
   * Tests set cached get.
   *
   * @legacy-covers ::setCached
   */
  public function testSetCachedGet(): void {
    $form_state = new FormState();
    $form_state->setRequestMethod('GET');
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Form state caching on GET requests is not allowed.');
    $form_state->setCached();
  }

  /**
   * Tests is method type.
   *
   * @legacy-covers ::isMethodType
   * @legacy-covers ::setMethod
   */
  #[DataProvider('providerTestIsMethodType')]
  public function testIsMethodType($set_method_type, $input, $expected): void {
    $form_state = (new FormState())
      ->setMethod($set_method_type);
    $this->assertSame($expected, $form_state->isMethodType($input));
  }

  /**
   * Provides test data for testIsMethodType().
   */
  public static function providerTestIsMethodType(): array {
    $data = [];
    $data[] = [
      'get',
      'get',
      TRUE,
    ];
    $data[] = [
      'get',
      'GET',
      TRUE,
    ];
    $data[] = [
      'GET',
      'GET',
      TRUE,
    ];
    $data[] = [
      'post',
      'get',
      FALSE,
    ];
    return $data;
  }

  /**
   * Tests temporary value.
   *
   * @legacy-covers ::getTemporaryValue
   * @legacy-covers ::hasTemporaryValue
   * @legacy-covers ::setTemporaryValue
   */
  public function testTemporaryValue(): void {
    $form_state = new FormState();
    $this->assertFalse($form_state->hasTemporaryValue('rainbow_sparkles'));
    $form_state->setTemporaryValue('rainbow_sparkles', 'yes');
    $this->assertSame($form_state->getTemporaryValue('rainbow_sparkles'), 'yes');
    $this->assertTrue($form_state->hasTemporaryValue('rainbow_sparkles'));
    $form_state->setTemporaryValue(['rainbow_sparkles', 'magic_ponies'], 'yes');
    $this->assertSame($form_state->getTemporaryValue(['rainbow_sparkles', 'magic_ponies']), 'yes');
    $this->assertTrue($form_state->hasTemporaryValue(['rainbow_sparkles', 'magic_ponies']));
  }

  /**
   * Tests get clean value keys.
   *
   * @legacy-covers ::getCleanValueKeys
   */
  public function testGetCleanValueKeys(): void {
    $form_state = new FormState();
    $this->assertSame($form_state->getCleanValueKeys(), ['form_id', 'form_token', 'form_build_id', 'op']);
  }

  /**
   * Tests set clean value keys.
   *
   * @legacy-covers ::setCleanValueKeys
   */
  public function testSetCleanValueKeys(): void {
    $form_state = new FormState();
    $form_state->setCleanValueKeys(['key1', 'key2']);
    $this->assertSame($form_state->getCleanValueKeys(), ['key1', 'key2']);
  }

  /**
   * Tests add clean value key.
   *
   * @legacy-covers ::addCleanValueKey
   */
  public function testAddCleanValueKey(): FormState {
    $form_state = new FormState();
    $form_state->setValue('value_to_clean', 'rainbow_sprinkles');
    $form_state->addCleanValueKey('value_to_clean');
    $this->assertSame(
      $form_state->getCleanValueKeys(),
      ['form_id', 'form_token', 'form_build_id', 'op', 'value_to_clean']
    );
    return $form_state;
  }

  /**
   * Tests clean values.
   *
   * @legacy-covers ::cleanValues
   */
  #[Depends('testAddCleanValueKey')]
  public function testCleanValues($form_state): void {
    $form_state->setValue('value_to_keep', 'magic_ponies');
    $this->assertSame($form_state->cleanValues()->getValues(), ['value_to_keep' => 'magic_ponies']);
  }

  /**
   * Tests get values.
   *
   * @legacy-covers ::setValues
   * @legacy-covers ::getValues
   */
  public function testGetValues(): void {
    $values = [
      'foo' => 'bar',
    ];
    $form_state = new FormState();
    $form_state->setValues($values);
    $this->assertSame($values, $form_state->getValues());
  }

}

/**
 * A test form used for the prepareCallback() tests.
 */
class PrepareCallbackTestForm implements FormInterface {

  public function getFormId(): string {
    return 'test_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    return [];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {}

  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
