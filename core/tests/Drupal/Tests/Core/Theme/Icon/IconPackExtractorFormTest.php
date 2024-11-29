<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Theme\Icon\IconPackExtractorForm;

/**
 * @coversDefaultClass \Drupal\Core\Theme\Icon\IconPackExtractorForm
 *
 * @group icon
 */
class IconPackExtractorFormTest extends UnitTestCase {

  /**
   * The icon pack form.
   *
   * @var \Drupal\Core\Theme\Icon\IconPackExtractorForm
   */
  private IconPackExtractorForm $iconPackForm;

  /**
   * The plugin form.
   *
   * @var \Drupal\Core\Plugin\PluginWithFormsInterface
   */
  private PluginWithFormsInterface $plugin;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $formState;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = new TestPluginWithForm();
    $this->formState = $this->prophesize(FormStateInterface::class);

    $this->iconPackForm = new IconPackExtractorForm();
    $this->iconPackForm->setPlugin($this->plugin);
  }

  /**
   * Test the IconPackExtractorForm::buildConfigurationForm method.
   */
  public function testBuildConfigurationForm(): void {
    $form = [
      'test_form' => 'test_form',
    ];
    /** @var \Drupal\Core\Form\FormStateInterface $formState */
    $formState = $this->formState->reveal();

    $result = $this->iconPackForm->buildConfigurationForm($form, $formState);

    $this->assertSame('plugin_build_form', $result['plugin_build_form']);
    $this->assertSame($form['test_form'], $result['test_form']);
  }

  /**
   * Test the IconPackExtractorForm::validateConfigurationForm method.
   */
  public function testValidateConfigurationForm(): void {
    $form = [];
    /** @var \Drupal\Core\Form\FormStateInterface $formState */
    $formState = $this->formState->reveal();

    $this->iconPackForm->validateConfigurationForm($form, $formState);
    $this->assertArrayHasKey('plugin_validate_form', $form);
  }

  /**
   * Test the IconPackExtractorForm::submitConfigurationForm method.
   */
  public function testSubmitConfigurationForm(): void {
    $form = [];
    /** @var \Drupal\Core\Form\FormStateInterface $formState */
    $formState = $this->formState->reveal();

    $this->iconPackForm->submitConfigurationForm($form, $formState);
    $this->assertArrayHasKey('plugin_submit_form', $form);
  }

}

/**
 * Test class for form.
 */
class TestPluginWithForm implements PluginWithFormsInterface {

  /**
   * {@inheritdoc}
   */
  public function getPluginId(): string {
    return 'test';
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function hasFormClass($operation): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormClass($operation): string {
    return 'form_class';
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['plugin_build_form'] = 'plugin_build_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $form['plugin_validate_form'] = 'plugin_validate_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $form['plugin_submit_form'] = 'plugin_submit_form';
  }

}
