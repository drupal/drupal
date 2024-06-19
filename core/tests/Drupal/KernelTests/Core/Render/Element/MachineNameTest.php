<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Render\Element\MachineName
 * @group Render
 */
class MachineNameTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return __CLASS__;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $element = [
      '#id' => 'test',
      '#type' => 'machine_name',
      '#machine_name' => [
        'source' => [
          'test_source',
        ],
      ],
      '#name' => 'test_machine_name',
      '#default_value' => NULL,
    ];

    $complete_form = [
      'test_machine_name' => $element,
      'test_source' => [
        '#type' => 'textfield',
      ],
    ];
    return $complete_form;
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

  /**
   * Tests the order of the machine name field and the source.
   */
  public function testMachineNameOrderException(): void {
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('The machine name element "test_machine_name" is defined before the source element "test_source", it must be defined after or the source element must specify an id.');
    $form = \Drupal::formBuilder()->getForm($this);
    $this->render($form);
  }

}
