<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Form\FormSubmitterTest.
 */

namespace Drupal\Tests\Core\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\Core\Form\FormSubmitter
 * @group Form
 */
class FormSubmitterTest extends UnitTestCase {

  /**
   * The mocked URL generator.
   *
   * @var \PHPUnit_Framework_MockObject_MockObject|\Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');
  }

  /**
   * @covers ::doSubmitForm
   */
  public function testHandleFormSubmissionNotSubmitted() {
    $form_submitter = $this->getFormSubmitter();
    $form = array();
    $form_state = new FormState();

    $return = $form_submitter->doSubmitForm($form, $form_state);
    $this->assertFalse($form_state['executed']);
    $this->assertNull($return);
  }

  /**
   * @covers ::doSubmitForm
   */
  public function testHandleFormSubmissionNoRedirect() {
    $form_submitter = $this->getFormSubmitter();
    $form = array();
    $form_state = new FormState(array(
      'submitted' => TRUE,
      'no_redirect' => TRUE,
    ));

    $return = $form_submitter->doSubmitForm($form, $form_state);
    $this->assertTrue($form_state['executed']);
    $this->assertNull($return);
  }

  /**
   * @covers ::doSubmitForm
   *
   * @dataProvider providerTestHandleFormSubmissionWithResponses
   */
  public function testHandleFormSubmissionWithResponses($class, $form_state_key) {
    $response = $this->getMockBuilder($class)
      ->disableOriginalConstructor()
      ->getMock();
    $response->expects($this->any())
      ->method('prepare')
      ->will($this->returnValue($response));

    $form_state = new FormState(array(
      'submitted' => TRUE,
      $form_state_key => $response,
    ));

    $form_submitter = $this->getFormSubmitter();
    $form = array();
    $return = $form_submitter->doSubmitForm($form, $form_state);

    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $return);
  }

  public function providerTestHandleFormSubmissionWithResponses() {
    return array(
      array('Symfony\Component\HttpFoundation\Response', 'response'),
      array('Symfony\Component\HttpFoundation\RedirectResponse', 'redirect'),
    );
  }

  /**
   * Tests the redirectForm() method when a redirect is expected.
   *
   * @covers ::redirectForm
   *
   * @dataProvider providerTestRedirectWithResult
   */
  public function testRedirectWithResult($redirect_value, $result, $status = 303) {
    $form_submitter = $this->getFormSubmitter();
    $this->urlGenerator->expects($this->once())
      ->method('generateFromPath')
      ->will($this->returnValueMap(array(
          array(NULL, array('query' => array(), 'absolute' => TRUE), '<front>'),
          array('foo', array('absolute' => TRUE), 'foo'),
          array('bar', array('query' => array('foo' => 'baz'), 'absolute' => TRUE), 'bar'),
          array('baz', array('absolute' => TRUE), 'baz'),
        ))
      );

    $form_state = $this->getMock('Drupal\Core\Form\FormStateInterface');
    $form_state->expects($this->once())
      ->method('getRedirect')
      ->willReturn($redirect_value);
    $redirect = $form_submitter->redirectForm($form_state);
    $this->assertSame($result, $redirect->getTargetUrl());
    $this->assertSame($status, $redirect->getStatusCode());
  }

  /**
   * Provides test data for testing the redirectForm() method with a redirect.
   *
   * @return array
   *   Returns some test data.
   */
  public function providerTestRedirectWithResult() {
    return array(
      array(NULL, '<front>'),
      array('foo', 'foo'),
      array(array('foo'), 'foo'),
      array(array('bar', array('query' => array('foo' => 'baz'))), 'bar'),
      array(array('baz', array(), 301), 'baz', 301),
    );
  }

  /**
   * Tests the redirectForm() with redirect_route when a redirect is expected.
   *
   * @covers ::redirectForm
   *
   * @dataProvider providerTestRedirectWithRouteWithResult
   */
  public function testRedirectWithRouteWithResult($redirect_value, $result, $status = 303) {
    $container = new ContainerBuilder();
    $container->set('url_generator', $this->urlGenerator);
    \Drupal::setContainer($container);
    $form_submitter = $this->getFormSubmitter();
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->will($this->returnValueMap(array(
          array('test_route_a', array(), array('absolute' => TRUE), 'test-route'),
          array('test_route_b', array('key' => 'value'), array('absolute' => TRUE), 'test-route/value'),
        ))
      );

    $form_state = $this->getMock('Drupal\Core\Form\FormStateInterface');
    $form_state->expects($this->once())
      ->method('getRedirect')
      ->willReturn($redirect_value);
    $redirect = $form_submitter->redirectForm($form_state);
    $this->assertSame($result, $redirect->getTargetUrl());
    $this->assertSame($status, $redirect->getStatusCode());
  }

  /**
   * Provides test data for testing the redirectForm() method with a route name.
   *
   * @return array
   *   Returns some test data.
   */
  public function providerTestRedirectWithRouteWithResult() {
    return array(
      array(new Url('test_route_a', array(), array('absolute' => TRUE)), 'test-route'),
      array(new Url('test_route_b', array('key' => 'value'), array('absolute' => TRUE)), 'test-route/value'),
    );
  }

  /**
   * Tests the redirectForm() method with a response object.
   *
   * @covers ::redirectForm
   */
  public function testRedirectWithResponseObject() {
    $form_submitter = $this->getFormSubmitter();
    $redirect = new RedirectResponse('/example');
    $form_state = $this->getMock('Drupal\Core\Form\FormStateInterface');
    $form_state->expects($this->once())
      ->method('getRedirect')
      ->willReturn($redirect);

    $result_redirect = $form_submitter->redirectForm($form_state);

    $this->assertSame($redirect, $result_redirect);
  }

  /**
   * Tests the redirectForm() method when no redirect is expected.
   *
   * @covers ::redirectForm
   */
  public function testRedirectWithoutResult() {
    $form_submitter = $this->getFormSubmitter();
    $this->urlGenerator->expects($this->never())
      ->method('generateFromPath');
    $this->urlGenerator->expects($this->never())
      ->method('generateFromRoute');
    $form_state = $this->getMock('Drupal\Core\Form\FormStateInterface');
    $form_state->expects($this->once())
      ->method('getRedirect')
      ->willReturn(FALSE);
    $redirect = $form_submitter->redirectForm($form_state);
    $this->assertNull($redirect);
  }

  /**
   * @covers ::executeSubmitHandlers
   */
  public function testExecuteSubmitHandlers() {
    $form_submitter = $this->getFormSubmitter();
    $mock = $this->getMock('stdClass', array('submit_handler', 'hash_submit'));
    $mock->expects($this->once())
      ->method('submit_handler')
      ->with($this->isType('array'), $this->isInstanceOf('Drupal\Core\Form\FormStateInterface'));
    $mock->expects($this->once())
      ->method('hash_submit')
      ->with($this->isType('array'), $this->isInstanceOf('Drupal\Core\Form\FormStateInterface'));

    $form = array();
    $form_state = new FormState();
    $form_submitter->executeSubmitHandlers($form, $form_state);

    $form['#submit'][] = array($mock, 'hash_submit');
    $form_submitter->executeSubmitHandlers($form, $form_state);

    // $form_state submit handlers will supersede $form handlers.
    $form_state['submit_handlers'][] = array($mock, 'submit_handler');
    $form_submitter->executeSubmitHandlers($form, $form_state);
  }

  /**
   * @return \Drupal\Core\Form\FormSubmitterInterface
   */
  protected function getFormSubmitter() {
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    return $this->getMockBuilder('Drupal\Core\Form\FormSubmitter')
      ->setConstructorArgs(array($request_stack, $this->urlGenerator))
      ->setMethods(array('batchGet', 'drupalInstallationAttempted'))
      ->getMock();
  }

}
