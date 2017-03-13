<?php

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\Actions
 * @group Render
 */
class ActionsTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['key'] = [
      '#type' => 'submit',
      '#value' => 'Key',
      '#dropbutton' => 'submit',
      '#cache' => [
        'tags' => ['foo'],
      ],
      '#attached' => [
        'library' => [
          'system/base',
        ],
      ],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  public function testDropbuttonWithBubbleableMetadata() {
    $result = \Drupal::formBuilder()->getForm($this);
    \Drupal::service('renderer')->renderRoot($result);
    $this->assertEquals(['system/base', 'core/drupal.dropbutton'], $result['#attached']['library']);
    $this->assertEquals(['foo'], $result['#cache']['tags']);
  }

}
