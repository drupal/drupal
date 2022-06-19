<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormStateTest.
 */

namespace Drupal\Tests\Core\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormState
 *
 * @group Form
 */
class FormStateTest extends UnitTestCase {

  /**
   * Tests the getRedirect() method.
   *
   * @covers ::getRedirect
   *
   * @dataProvider providerTestGetRedirect
   */
  public function testGetRedirect($form_state_additions, $expected) {
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
  public function providerTestGetRedirect() {
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
   * @covers ::setError
   */
  public function testSetError() {
    $form_state = new FormState();
    $element['#parents'] = ['foo', 'bar'];
    $form_state->setError($element, 'Fail');
    $this->assertSame(['foo][bar' => 'Fail'], $form_state->getErrors());
  }

  /**
   * Tests the getError() method.
   *
   * @covers ::getError
   *
   * @dataProvider providerTestGetError
   */
  public function testGetError($errors, $parents, $error = NULL) {
    $element['#parents'] = $parents;
    $form_state = (new FormState())->setFormState([
      'errors' => $errors,
    ]);
    $this->assertSame($error, $form_state->getError($element));
  }

  public function providerTestGetError() {
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
   * @covers ::setErrorByName
   *
   * @dataProvider providerTestSetErrorByName
   */
  public function testSetErrorByName($limit_validation_errors, $expected_errors) {
    $form_state = new FormState();
    $form_state->setLimitValidationErrors($limit_validation_errors);
    $form_state->clearErrors();

    $form_state->setErrorByName('test', 'Fail 1');
    $form_state->setErrorByName('test', 'Fail 2');
    $form_state->setErrorByName('options');

    $this->assertSame(!empty($expected_errors), $form_state::hasAnyErrors());
    $this->assertSame($expected_errors, $form_state->getErrors());
  }

  public function providerTestSetErrorByName() {
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
   * @covers ::setErrorByName
   */
  public function testFormErrorsDuringSubmission() {
    $form_state = new FormState();
    $form_state->setValidationComplete();
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Form errors cannot be set after form validation has finished.');
    $form_state->setErrorByName('test', 'message');
  }

  /**
   * @covers ::prepareCallback
   */
  public function testPrepareCallbackValidMethod() {
    $form_state = new FormState();
    $form_state->setFormObject(new PrepareCallbackTestForm());
    $processed_callback = $form_state->prepareCallback('::buildForm');
    $this->assertEquals([$form_state->getFormObject(), 'buildForm'], $processed_callback);
  }

  /**
   * @covers ::prepareCallback
   */
  public function testPrepareCallbackInValidMethod() {
    $form_state = new FormState();
    $form_state->setFormObject(new PrepareCallbackTestForm());
    $processed_callback = $form_state->prepareCallback('not_a_method');
    // The callback was not changed as no such method exists.
    $this->assertEquals('not_a_method', $processed_callback);
  }

  /**
   * @covers ::prepareCallback
   */
  public function testPrepareCallbackArray() {
    $form_state = new FormState();
    $form_state->setFormObject(new PrepareCallbackTestForm());
    $callback = [$form_state->getFormObject(), 'buildForm'];
    $processed_callback = $form_state->prepareCallback($callback);
    $this->assertEquals($callback, $processed_callback);
  }

  /**
   * @covers ::loadInclude
   */
  public function testLoadInclude() {
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
   * @covers ::loadInclude
   */
  public function testLoadIncludeNoName() {
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
   * @covers ::loadInclude
   */
  public function testLoadIncludeNotFound() {
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
   * @covers ::loadInclude
   */
  public function testLoadIncludeAlreadyLoaded() {
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
   * @covers ::isCached
   *
   * @dataProvider providerTestIsCached
   */
  public function testIsCached($cache_key, $no_cache_key, $expected) {
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
  public function providerTestIsCached() {
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
   * @covers ::setCached
   */
  public function testSetCachedPost() {
    $form_state = new FormState();
    $form_state->setRequestMethod('POST');
    $form_state->setCached();
    $this->assertTrue($form_state->isCached());
  }

  /**
   * @covers ::setCached
   */
  public function testSetCachedGet() {
    $form_state = new FormState();
    $form_state->setRequestMethod('GET');
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Form state caching on GET requests is not allowed.');
    $form_state->setCached();
  }

  /**
   * @covers ::isMethodType
   * @covers ::setMethod
   *
   * @dataProvider providerTestIsMethodType
   */
  public function testIsMethodType($set_method_type, $input, $expected) {
    $form_state = (new FormState())
      ->setMethod($set_method_type);
    $this->assertSame($expected, $form_state->isMethodType($input));
  }

  /**
   * Provides test data for testIsMethodType().
   */
  public function providerTestIsMethodType() {
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
   * @covers ::getTemporaryValue
   * @covers ::hasTemporaryValue
   * @covers ::setTemporaryValue
   */
  public function testTemporaryValue() {
    $form_state = new FormState();
    $this->assertFalse($form_state->hasTemporaryValue('rainbow_sparkles'));
    $form_state->setTemporaryValue('rainbow_sparkles', 'yes please');
    $this->assertSame($form_state->getTemporaryValue('rainbow_sparkles'), 'yes please');
    $this->assertTrue($form_state->hasTemporaryValue('rainbow_sparkles'), TRUE);
    $form_state->setTemporaryValue(['rainbow_sparkles', 'magic_ponies'], 'yes please');
    $this->assertSame($form_state->getTemporaryValue(['rainbow_sparkles', 'magic_ponies']), 'yes please');
    $this->assertTrue($form_state->hasTemporaryValue(['rainbow_sparkles', 'magic_ponies']), TRUE);
  }

  /**
   * @covers ::getCleanValueKeys
   */
  public function testGetCleanValueKeys() {
    $form_state = new FormState();
    $this->assertSame($form_state->getCleanValueKeys(), ['form_id', 'form_token', 'form_build_id', 'op']);
  }

  /**
   * @covers ::setCleanValueKeys
   */
  public function testSetCleanValueKeys() {
    $form_state = new FormState();
    $form_state->setCleanValueKeys(['key1', 'key2']);
    $this->assertSame($form_state->getCleanValueKeys(), ['key1', 'key2']);
  }

  /**
   * @covers ::addCleanValueKey
   */
  public function testAddCleanValueKey() {
    $form_state = new FormState();
    $form_state->setValue('value_to_clean', 'rainbow_sprinkles');
    $form_state->addCleanValueKey('value_to_clean');
    $this->assertSame($form_state->getCleanValueKeys(), ['form_id', 'form_token', 'form_build_id', 'op', 'value_to_clean']);
    return $form_state;
  }

  /**
   * @depends testAddCleanValueKey
   *
   * @covers ::cleanValues
   */
  public function testCleanValues($form_state) {
    $form_state->setValue('value_to_keep', 'magic_ponies');
    $this->assertSame($form_state->cleanValues()->getValues(), ['value_to_keep' => 'magic_ponies']);
  }

  /**
   * @covers ::setValues
   * @covers ::getValues
   */
  public function testGetValues() {
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

  public function getFormId() {
    return 'test_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    return [];
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {}

  public function submitForm(array &$form, FormStateInterface $form_state) {}

}
