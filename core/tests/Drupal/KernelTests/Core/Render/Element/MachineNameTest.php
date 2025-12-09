<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Render\Element;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\MachineName;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\Core\Render\Element\MachineName.
 */
#[CoversClass(MachineName::class)]
#[Group('Render')]
#[RunTestsInSeparateProcesses]
class MachineNameTest extends KernelTestBase implements FormInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return __CLASS__;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
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
