<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Form;

use Drupal\form_test\FormTestObject;
use Drupal\KernelTests\ConfigFormTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests building a form from an object.
 */
#[Group('Form')]
#[RunTestsInSeparateProcesses]
class FormObjectTest extends ConfigFormTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->form = new FormTestObject($this->container->get('config.factory'), $this->container->get('config.typed'));
    $this->values = [
      'bananas' => [
        '#value' => $this->randomString(10),
        '#config_name' => 'form_test.object',
        '#config_key' => 'bananas',
      ],
    ];
  }

}
