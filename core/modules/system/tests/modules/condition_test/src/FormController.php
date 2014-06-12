<?php

/**
 * @file
 * Contains \Drupal\condition_test\FormController.
 */

namespace Drupal\condition_test;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Condition\ConditionManager;

/**
 * Routing controller class for condition_test testing of condition forms.
 */
class FormController implements FormInterface {

  /**
   * The condition plugin we will be working with.
   *
   * @var \Drupal\Core\Condition\ConditionInterface
   */
  protected $condition;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'condition_node_type_test_form';
  }

  /**
   * Constructs a \Drupal\condition_test\FormController object.
   */
  public function __construct() {
    $manager = new ConditionManager(\Drupal::service('container.namespaces'), \Drupal::cache('discovery'), \Drupal::moduleHandler());
    $this->condition = $manager->createInstance('node_type');
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $form = $this->condition->buildConfigurationForm($form, $form_state);
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    $this->condition->validateConfigurationForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->condition->submitConfigurationForm($form, $form_state);
    $config = $this->condition->getConfig();
    $bundles = implode(' and ', $config['bundles']);
    drupal_set_message(t('The bundles are @bundles', array('@bundles' => $bundles)));
    $article = node_load(1);
    $this->condition->setContextValue('node', $article);
    if ($this->condition->execute()) {
      drupal_set_message(t('Executed successfully.'));
    }
  }
}
