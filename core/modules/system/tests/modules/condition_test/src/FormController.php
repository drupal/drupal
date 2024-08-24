<?php

declare(strict_types=1);

namespace Drupal\condition_test;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\Node;

/**
 * Routing controller class for condition_test testing of condition forms.
 */
class FormController implements FormInterface {
  use StringTranslationTrait;

  /**
   * The condition plugin we will be working with.
   *
   * @var \Drupal\Core\Condition\ConditionInterface
   */
  protected $condition;

  /**
   * The condition plugin current_theme.
   *
   * @var \Drupal\Core\Condition\ConditionInterface
   */
  protected $conditionCurrentTheme;

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
    $this->conditionCurrentTheme = $manager->createInstance('current_theme');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#tree'] = TRUE;
    $form['entity_bundle'] = [];
    $subformState = SubformState::createForSubform($form['entity_bundle'], $form, $form_state);
    $form['entity_bundle'] = $this->condition->buildConfigurationForm($form['entity_bundle'], $subformState);

    $form['current_theme'] = [];
    $subformState = SubformState::createForSubform($form['current_theme'], $form, $form_state);
    $form['current_theme'] = $this->conditionCurrentTheme->buildConfigurationForm($form['current_theme'], $subformState);

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $subformState = SubformState::createForSubform($form['entity_bundle'], $form, $form_state);
    $this->condition->validateConfigurationForm($form['entity_bundle'], $subformState);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $subformState = SubformState::createForSubform($form['entity_bundle'], $form, $form_state);
    $this->condition->submitConfigurationForm($form['entity_bundle'], $subformState);
    $subformState = SubformState::createForSubform($form['current_theme'], $form, $form_state);

    $this->conditionCurrentTheme->submitConfigurationForm($form['current_theme'], $subformState);
    $config = $this->condition->getConfig();
    foreach ($config['bundles'] as $bundle) {
      \Drupal::messenger()->addStatus('Bundle: ' . $bundle);
    }

    $article = Node::load(1);
    $this->condition->setContextValue('node', $article);
    if ($this->condition->execute()) {
      \Drupal::messenger()->addStatus($this->t('Executed successfully.'));
    }
    if ($this->conditionCurrentTheme->execute()) {
      \Drupal::messenger()->addStatus($this->conditionCurrentTheme->summary());
    }
  }

}
