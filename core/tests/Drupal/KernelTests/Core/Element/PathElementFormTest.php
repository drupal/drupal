<?php

namespace Drupal\KernelTests\Core\Element;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\PathElement;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests PathElement validation and conversion functionality.
 *
 * @group Form
 */
class PathElementFormTest extends KernelTestBase implements FormInterface {

  /**
   * User for testing.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['system', 'user'];

  /**
   * Sets up the test.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::create([
      'id' => 'admin',
      'label' => 'admin',
    ]);
    $role->grantPermission('link to any page');
    $role->save();
    $this->testUser = User::create([
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ]);
    $this->testUser->addRole($role->id());
    $this->testUser->save();
    \Drupal::service('current_user')->setAccount($this->testUser);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_path_element';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // A required validated path.
    $form['required_validate'] = [
      '#type' => 'path',
      '#required' => TRUE,
      '#title' => 'required_validate',
      '#convert_path' => PathElement::CONVERT_NONE,
    ];

    // A non validated required path.
    $form['required_non_validate'] = [
      '#type' => 'path',
      '#required' => TRUE,
      '#title' => 'required_non_validate',
      '#convert_path' => PathElement::CONVERT_NONE,
      '#validate_path' => FALSE,
    ];

    // A non required validated path.
    $form['optional_validate'] = [
      '#type' => 'path',
      '#required' => FALSE,
      '#title' => 'optional_validate',
      '#convert_path' => PathElement::CONVERT_NONE,
    ];

    // A non required converted path.
    $form['optional_validate_route'] = [
      '#type' => 'path',
      '#required' => FALSE,
      '#title' => 'optional_validate_route',
      '#convert_path' => PathElement::CONVERT_ROUTE,
    ];

    // A converted required validated path.
    $form['required_validate_route'] = [
      '#type' => 'path',
      '#required' => TRUE,
      '#title' => 'required_validate_route',
    ];

    // A converted required validated path.
    $form['required_validate_url'] = [
      '#type' => 'path',
      '#required' => TRUE,
      '#title' => 'required_validate_url',
      '#convert_path' => PathElement::CONVERT_URL,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Tests that default handlers are added even if custom are specified.
   */
  public function testPathElement() {
    $form_state = (new FormState())
      ->setValues([
        'required_validate' => 'user/' . $this->testUser->id(),
        'required_non_validate' => 'magic-ponies',
        'required_validate_route' => 'user/' . $this->testUser->id(),
        'required_validate_url' => 'user/' . $this->testUser->id(),
        'optional_validate' => 'user/' . $this->testUser->id(),
        'optional_validate_route' => 'user/' . $this->testUser->id(),
      ]);
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    // Valid form state.
    $this->assertCount(0, $form_state->getErrors());
    $this->assertEquals(['route_name' => 'entity.user.canonical', 'route_parameters' => ['user' => $this->testUser->id()]], $form_state->getValue('required_validate_route'));
    /** @var \Drupal\Core\Url $url */
    $url = $form_state->getValue('required_validate_url');
    $this->assertInstanceOf(Url::class, $url);
    $this->assertEquals('entity.user.canonical', $url->getRouteName());
    $this->assertEquals(['user' => $this->testUser->id()], $url->getRouteParameters());
    $this->assertEquals($form_state->getValue('optional_validate_route'), [
      'route_name' => 'entity.user.canonical',
      'route_parameters' => [
        'user' => $this->testUser->id(),
      ],
    ]);

    // Test #required.
    $form_state = (new FormState())
      ->setValues([
        'required_non_validate' => 'magic-ponies',
        'required_validate_route' => 'user/' . $this->testUser->id(),
        'required_validate_url' => 'user/' . $this->testUser->id(),
      ]);
    $form_builder->submitForm($this, $form_state);
    $errors = $form_state->getErrors();
    // Should be missing 'required_validate' field.
    $this->assertCount(1, $errors);
    $this->assertEquals(['required_validate' => 'required_validate field is required.'], $errors);

    // Test invalid required parameters.
    $form_state = (new FormState())
      ->setValues([
        'required_validate' => 'user/74',
        'required_validate_route' => 'user/74',
        'required_validate_url' => 'user/74',
      ]);
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    // Valid form state.
    $errors = $form_state->getErrors();
    $this->assertCount(4, $errors);
    $this->assertEquals([
      'required_validate' => 'This path does not exist or you do not have permission to link to user/74.',
      'required_validate_route' => 'This path does not exist or you do not have permission to link to user/74.',
      'required_validate_url' => 'This path does not exist or you do not have permission to link to user/74.',
      'required_non_validate' => 'required_non_validate field is required.',
    ], $errors);

    // Test invalid optional parameters.
    $form_state = (new FormState())->setValues([
      'required_validate' => 'user/' . $this->testUser->id(),
      'required_non_validate' => 'magic-ponies',
      'required_validate_route' => 'user/' . $this->testUser->id(),
      'required_validate_url' => 'user/' . $this->testUser->id(),
      // Set invalid optional parameters should cause an error.
      'optional_validate' => 'user/74',
      'optional_validate_route' => 'user/74',
    ]);
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);
    // Valid form state.
    $errors = $form_state->getErrors();
    $this->assertEquals(count($errors), 2);
    $this->assertEquals($errors, [
      'optional_validate' => 'This path does not exist or you do not have permission to link to user/74.',
      'optional_validate_route' => 'This path does not exist or you do not have permission to link to user/74.',
    ]);
  }

}
