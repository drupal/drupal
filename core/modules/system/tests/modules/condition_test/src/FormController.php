<?php

namespace Drupal\condition_test;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

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
    $this->condition = $manager->createInstance('entity_bundle:node');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = $this->condition->buildConfigurationForm($form, $form_state);
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $this->condition->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->condition->submitConfigurationForm($form, $form_state);
    $config = $this->condition->getConfig();
    foreach ($config['bundles'] as $bundle) {
      \Drupal::messenger()->addStatus('Bundle: ' . $bundle);
    }

    $article = Node::load(1);
    $this->condition->setContextValue('node', $article);
    if ($this->condition->execute()) {
      \Drupal::messenger()->addStatus(t('Executed successfully.'));
    }
  }

}
