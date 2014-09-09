<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Element\PathElementFormTest.
 */

namespace Drupal\system\Tests\Element;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\PathElement;
use Drupal\Core\Url;
use Drupal\simpletest\KernelTestBase;
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
  public static $modules = array('system', 'user');

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();
    $this->installSchema('system', array('router', 'sequences'));
    $this->installEntitySchema('user');
    \Drupal::service('router.builder')->rebuild();
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::create(array(
      'id' => 'admin',
      'label' => 'admin',
    ));
    $role->grantPermission('link to any page');
    $role->save();
    $this->testUser = User::create(array(
      'name' => 'foobar',
      'mail' => 'foobar@example.com',
    ));
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
    $form['required_validate'] = array(
      '#type' => 'path',
      '#required' => TRUE,
      '#title' => 'required_validate',
      '#convert_path' => PathElement::CONVERT_NONE,
    );

    // A non validated required path.
    $form['required_non_validate'] = array(
      '#type' => 'path',
      '#required' => TRUE,
      '#title' => 'required_non_validate',
      '#convert_path' => PathElement::CONVERT_NONE,
      '#validate_path' => FALSE,
    );

    // A non required validated path.
    $form['optional_validate'] = array(
      '#type' => 'path',
      '#required' => FALSE,
      '#title' => 'optional_validate',
      '#convert_path' => PathElement::CONVERT_NONE,
    );

    // A non required converted path.
    $form['optional_validate'] = array(
      '#type' => 'path',
      '#required' => FALSE,
      '#title' => 'optional_validate',
      '#convert_path' => PathElement::CONVERT_ROUTE,
    );

    // A converted required validated path.
    $form['required_validate_route'] = array(
      '#type' => 'path',
      '#required' => TRUE,
      '#title' => 'required_validate_route',
    );

    // A converted required validated path.
    $form['required_validate_url'] = array(
      '#type' => 'path',
      '#required' => TRUE,
      '#title' => 'required_validate_url',
      '#convert_path' => PathElement::CONVERT_URL,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

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
    $form_state = new FormState(array(
      'values' => array(
        'required_validate' => 'user/' . $this->testUser->id(),
        'required_non_validate' => 'magic-ponies',
        'required_validate_route' => 'user/' . $this->testUser->id(),
        'required_validate_url' => 'user/' . $this->testUser->id(),
      ),
    ));
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    // Valid form state.
    $this->assertEqual(count($form_state->getErrors()), 0);
    $this->assertEqual($form_state->getValue('required_validate_route'), array(
      'route_name' => 'entity.user.canonical',
      'route_parameters' => array(
        'user' => $this->testUser->id(),
      ),
    ));
    /** @var \Drupal\Core\Url $url */
    $url = $form_state->getValue('required_validate_url');
    $this->assertTrue($url instanceof Url);
    $this->assertEqual($url->getRouteName(), 'entity.user.canonical');
    $this->assertEqual($url->getRouteParameters(), array(
      'user' => $this->testUser->id(),
    ));

    // Test #required.
    $form_state = new FormState(array(
      'values' => array(
        'required_non_validate' => 'magic-ponies',
        'required_validate_route' => 'user/' . $this->testUser->id(),
        'required_validate_url' => 'user/' . $this->testUser->id(),
      ),
    ));
    $form_builder->submitForm($this, $form_state);
    $errors = $form_state->getErrors();
    // Should be missing 'required_validate' field.
    $this->assertEqual(count($errors), 1);
    $this->assertEqual($errors, array('required_validate' => t('!name field is required.', array('!name' => 'required_validate'))));

    // Test invalid parameters.
    $form_state = new FormState(array(
      'values' => array(
        'required_validate' => 'user/74',
        'required_non_validate' => 'magic-ponies',
        'required_validate_route' => 'user/74',
        'required_validate_url' => 'user/74',
      ),
    ));
    $form_builder = $this->container->get('form_builder');
    $form_builder->submitForm($this, $form_state);

    // Valid form state.
    $errors = $form_state->getErrors();
    $this->assertEqual(count($errors), 3);
    $this->assertEqual($errors, array(
      'required_validate' => t('This path does not exist or you do not have permission to link to %path.', array('%path' => 'user/74')),
      'required_validate_route' => t('This path does not exist or you do not have permission to link to %path.', array('%path' => 'user/74')),
      'required_validate_url' => t('This path does not exist or you do not have permission to link to %path.', array('%path' => 'user/74')),
    ));
  }

}
