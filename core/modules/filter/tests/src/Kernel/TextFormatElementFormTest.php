<?php

namespace Drupal\Tests\filter\Kernel;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Entity\FilterFormat;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Tests PathElement validation and conversion functionality.
 *
 * @group Form
 */
class TextFormatElementFormTest extends KernelTestBase implements FormInterface {

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
  protected static $modules = [
    'system',
    'user',
    'filter',
    'filter_test',
    'editor',
  ];

  /**
   * Sets up the test.
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installSchema('system', ['sequences']);
    $this->installConfig(['filter', 'filter_test']);
    // Filter tips link to the full-page.
    \Drupal::service('router.builder')->rebuild();
    /* @var \Drupal\Core\Render\ElementInfoManager $manager */
    $manager = \Drupal::service('plugin.manager.element_info');
    $manager->clearCachedDefinitions();
    $manager->getDefinitions();
    /* @var \Drupal\filter\FilterFormatInterface $filter_test_format */
    $filter_test_format = FilterFormat::load('filter_test');

    /* @var \Drupal\user\RoleInterface $role */
    $role = Role::create([
      'id' => 'admin',
      'label' => 'admin',
    ]);
    $role->grantPermission($filter_test_format->getPermissionName());
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
    return 'test_text_area_element';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // A textformat field.
    $form['textformat'] = [
      '#type' => 'text_format',
      '#required' => TRUE,
      '#title' => 'Text',
      '#base_type' => 'textfield',
      '#format' => NULL,
      '#default_value' => 'test value',
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
   * Tests that values are returned.
   */
  public function testTextFormatElement() {
    /* @var \Drupal\Core\Form\FormBuilder $form_builder */
    $form_builder = $this->container->get('form_builder');
    $form = $form_builder->getForm($this);
    $output = $this->render($form);
    $this->setRawContent($output);
    $this->assertFieldByName('textformat[value]');
    $this->assertRaw('<h4>Full HTML</h4>');
    $this->assertRaw('<h4>Filtered HTML</h4>');
    $this->assertRaw('<h4>Test format</h4>');
    $this->assertNoPattern('|<h4[^>]*></h4>|', 'No empty H4 element found.');
  }

  /**
   * {@inheritdoc}
   */
  protected function getUrl() {
    // \Drupal\simpletest\AssertContentTrait needs this for ::assertFieldByName
    // to work.
    return 'Internal rendering';
  }

}
