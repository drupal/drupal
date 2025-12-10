<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Form;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\EventSubscriber\RedirectResponseSubscriber;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormSubmitter;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\CallableResolver;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Prophecy\Argument;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests Drupal\Core\Form\FormSubmitter.
 */
#[CoversClass(FormSubmitter::class)]
#[Group('Form')]
class FormSubmitterTest extends UnitTestCase {

  /**
   * The mocked URL generator.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * The mocked unrouted URL assembler.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\Utility\UnroutedUrlAssemblerInterface
   */
  protected $unroutedUrlAssembler;

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\Core\EventSubscriber\RedirectResponseSubscriber
   */
  protected $redirectResponseSubscriber;

  /**
   * The callable resolver.
   */
  protected CallableResolver | Stub $callableResolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
    $this->unroutedUrlAssembler = $this->createMock(UnroutedUrlAssemblerInterface::class);
    $this->redirectResponseSubscriber = $this->createMock(RedirectResponseSubscriber::class);
    $this->callableResolver = $this->createStub(CallableResolver::class);
  }

  /**
   * Tests handle form submission not submitted.
   *
   * @legacy-covers ::doSubmitForm
   */
  public function testHandleFormSubmissionNotSubmitted(): void {
    $form_submitter = $this->getFormSubmitter();
    $form = [];
    $form_state = new FormState();

    $return = $form_submitter->doSubmitForm($form, $form_state);
    $this->assertFalse($form_state->isExecuted());
    $this->assertNull($return);
  }

  /**
   * Tests handle form submission no redirect.
   *
   * @legacy-covers ::doSubmitForm
   */
  public function testHandleFormSubmissionNoRedirect(): void {
    $form_submitter = $this->getFormSubmitter();
    $form = [];
    $form_state = (new FormState())
      ->setSubmitted()
      ->disableRedirect();

    $return = $form_submitter->doSubmitForm($form, $form_state);
    $this->assertTrue($form_state->isExecuted());
    $this->assertNull($return);
  }

  /**
   * Tests handle form submission with responses.
   *
   * @legacy-covers ::doSubmitForm
   */
  #[DataProvider('providerTestHandleFormSubmissionWithResponses')]
  public function testHandleFormSubmissionWithResponses($class, $form_state_key): void {
    $response = $this->getMockBuilder($class)
      ->disableOriginalConstructor()
      ->getMock();
    $response->expects($this->any())
      ->method('prepare')
      ->willReturn($response);

    $form_state = (new FormState())
      ->setSubmitted()
      ->setFormState([$form_state_key => $response]);

    $form_submitter = $this->getFormSubmitter();
    $form = [];
    $return = $form_submitter->doSubmitForm($form, $form_state);

    $this->assertInstanceOf('Symfony\Component\HttpFoundation\Response', $return);
  }

  public static function providerTestHandleFormSubmissionWithResponses(): array {
    return [
      ['Symfony\Component\HttpFoundation\Response', 'response'],
      ['Symfony\Component\HttpFoundation\RedirectResponse', 'redirect'],
    ];
  }

  /**
   * Tests the redirectForm() method when the redirect is NULL.
   *
   * @legacy-covers ::redirectForm
   */
  public function testRedirectWithNull(): void {
    $form_submitter = $this->getFormSubmitter();

    $form_state = $this->createMock('Drupal\Core\Form\FormStateInterface');
    $form_state->expects($this->once())
      ->method('getRedirect')
      ->willReturn(NULL);

    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->with('<current>', [], ['query' => [], 'absolute' => TRUE])
      ->willReturn('http://localhost/test-path');

    $redirect = $form_submitter->redirectForm($form_state);
    // If we have no redirect, we redirect to the current URL.
    $this->assertSame('http://localhost/test-path', $redirect->getTargetUrl());
    $this->assertSame(303, $redirect->getStatusCode());
  }

  /**
   * Tests redirectForm() when a redirect is a Url object.
   *
   * @legacy-covers ::redirectForm
   */
  #[DataProvider('providerTestRedirectWithUrl')]
  public function testRedirectWithUrl(Url $redirect_value, $result, $status = 303): void {
    $container = new ContainerBuilder();
    $container->set('url_generator', $this->urlGenerator);
    \Drupal::setContainer($container);
    $form_submitter = $this->getFormSubmitter();
    $this->urlGenerator->expects($this->once())
      ->method('generateFromRoute')
      ->willReturnMap(
        [
          ['test_route_a', [], ['absolute' => TRUE], FALSE, 'test-route'],
          [
            'test_route_b',
            ['key' => 'value'],
            ['absolute' => TRUE],
            FALSE,
            'test-route/value',
          ],
        ]
      );

    $form_state = $this->createMock('Drupal\Core\Form\FormStateInterface');
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
  public static function providerTestRedirectWithUrl(): array {
    return [
      [new Url('test_route_a', [], ['absolute' => TRUE]), 'test-route'],
      [new Url('test_route_b', ['key' => 'value'], ['absolute' => TRUE]), 'test-route/value'],
    ];
  }

  /**
   * Tests the redirectForm() method with a response object.
   *
   * @legacy-covers ::redirectForm
   */
  public function testRedirectWithResponseObject(): void {
    $form_submitter = $this->getFormSubmitter();
    $redirect = new RedirectResponse('/example');
    $form_state = $this->createMock('Drupal\Core\Form\FormStateInterface');
    $form_state->expects($this->once())
      ->method('getRedirect')
      ->willReturn($redirect);

    $result_redirect = $form_submitter->redirectForm($form_state);

    $this->assertSame($redirect, $result_redirect);
  }

  /**
   * Tests the redirectForm() method when no redirect is expected.
   *
   * @legacy-covers ::redirectForm
   */
  public function testRedirectWithoutResult(): void {
    $form_submitter = $this->getFormSubmitter();
    $this->urlGenerator->expects($this->never())
      ->method('generateFromRoute');
    $this->unroutedUrlAssembler->expects($this->never())
      ->method('assemble');
    $container = new ContainerBuilder();
    $container->set('url_generator', $this->urlGenerator);
    $container->set('unrouted_url_assembler', $this->unroutedUrlAssembler);
    \Drupal::setContainer($container);
    $form_state = $this->createMock('Drupal\Core\Form\FormStateInterface');
    $form_state->expects($this->once())
      ->method('getRedirect')
      ->willReturn(FALSE);
    $redirect = $form_submitter->redirectForm($form_state);
    $this->assertNull($redirect);
  }

  /**
   * Tests execute submit handlers.
   *
   * @legacy-covers ::executeSubmitHandlers
   */
  public function testExecuteSubmitHandlers(): void {
    $form_submitter = $this->getFormSubmitter();
    $mock = $this->prophesize(MockFormBase::class);
    $mock
      ->hash_submit(Argument::type('array'), Argument::type(FormStateInterface::class))
      ->shouldBeCalledOnce();
    $mock
      ->submit_handler(Argument::type('array'), Argument::type(FormStateInterface::class))
      ->shouldBeCalledOnce();
    $mock
      ->simple_string_submit(Argument::type('array'), Argument::type(FormStateInterface::class))
      ->shouldBeCalledOnce();

    $form = [];
    $form_state = new FormState();
    $form_submitter->executeSubmitHandlers($form, $form_state);

    $this->callableResolver->method('getCallableFromDefinition')
      ->willReturn(
        [$mock->reveal(), 'hash_submit'],
        [$mock->reveal(), 'submit_handler'],
        [$mock->reveal(), 'simple_string_submit'],
      );

    $form['#submit'][] = [$mock->reveal(), 'hash_submit'];
    $form_submitter->executeSubmitHandlers($form, $form_state);

    // $form_state submit handlers will supersede $form handlers.
    $form_state->setSubmitHandlers([[$mock->reveal(), 'submit_handler']]);
    $form_submitter->executeSubmitHandlers($form, $form_state);

    // Methods directly on the form object can be specified as a string.
    $form_state = (new FormState())
      ->setFormObject($mock->reveal())
      ->setSubmitHandlers(['::simple_string_submit']);
    $form_submitter->executeSubmitHandlers($form, $form_state);
  }

  /**
   * @return \Drupal\Core\Form\FormSubmitterInterface
   *   A mocked instance of FormSubmitter.
   */
  protected function getFormSubmitter(): FormSubmitter&MockObject {
    $request_stack = new RequestStack();
    $request_stack->push(Request::create('/test-path'));
    return $this->getMockBuilder(FormSubmitter::class)
      ->setConstructorArgs([
        $request_stack,
        $this->urlGenerator,
        $this->redirectResponseSubscriber,
        $this->callableResolver,
      ])
      ->onlyMethods(['batchGet'])
      ->getMock();
  }

}

/**
 * Interface used in the mocking process of this test.
 */
abstract class MockFormBase extends FormBase {

  /**
   * Function used in the mocking process of this test.
   */
  public function submit_handler(array $array, FormStateInterface $form_state): void {
  }

  /**
   * Function used in the mocking process of this test.
   */
  public function hash_submit(array $array, FormStateInterface $form_state): void {
  }

  /**
   * Function used in the mocking process of this test.
   */
  public function simple_string_submit(array $array, FormStateInterface $form_state): void {
  }

}
