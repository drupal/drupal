<?php

/**
 * @file
 * Contains \Drupal\aggregator\Tests\Plugin\AggregatorPluginSettingsBaseTest
 */

namespace Drupal\aggregator\Tests\Plugin {

use Drupal\aggregator\Form\SettingsForm;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;

/**
 * Tests settings configuration of individual aggregator plugins.
 *
 * @group aggregator
 */
class AggregatorPluginSettingsBaseTest extends UnitTestCase {

  /**
   * The aggregator settings form object under test.
   *
   * @var \Drupal\aggregator\Form\SettingsForm
   */
  protected $settingsForm;

  /**
   * The stubbed config factory object.
   *
   * @var \PHPUnit_Framework_MockObject_MockBuilder
   */
  protected $configFactory;

  /**
   * The stubbed aggregator plugin managers array.
   *
   * @var array
   */
  protected $managers;

  public function setUp() {
    $this->configFactory = $this->getConfigFactoryStub(
      array(
        'aggregator.settings' => array(
          'processors' => array('aggregator_test'),
        ),
        'aggregator_test.settings' => array(),
      )
    );
    foreach (array('fetcher', 'parser', 'processor') as $type) {
      $this->managers[$type] = $this->getMockBuilder('Drupal\aggregator\Plugin\AggregatorPluginManager')
        ->disableOriginalConstructor()
        ->getMock();
      $this->managers[$type]->expects($this->once())
        ->method('getDefinitions')
        ->will($this->returnValue(array('aggregator_test' => array('title' => '', 'description' => ''))));
    }

    $this->settingsForm = new SettingsForm(
      $this->configFactory,
      $this->managers['fetcher'],
      $this->managers['parser'],
      $this->managers['processor'],
      $this->getStringTranslationStub()
    );
  }

  /**
   * Test for AggregatorPluginSettingsBase.
   *
   * Ensure that the settings form calls build, validate and submit methods on
   * plugins that extend AggregatorPluginSettingsBase.
   */
  public function testSettingsForm() {
    // Emulate a form state of a sumbitted form.
    $form_state = new FormState(array('values' => array('dummy_length' => '', 'aggregator_allowed_html_tags' => '')));

    $test_processor = $this->getMock(
      'Drupal\aggregator_test\Plugin\aggregator\processor\TestProcessor',
      array('buildConfigurationForm', 'validateConfigurationForm', 'submitConfigurationForm'),
      array(array(), 'aggregator_test', array('description' => ''), $this->configFactory)
    );
    $test_processor->expects($this->at(0))
      ->method('buildConfigurationForm')
      ->with($this->anything(), $form_state)
      ->will($this->returnArgument(0));
    $test_processor->expects($this->at(1))
      ->method('validateConfigurationForm')
      ->with($this->anything(), $form_state);
    $test_processor->expects($this->at(2))
      ->method('submitConfigurationForm')
      ->with($this->anything(), $form_state);

    $this->managers['processor']->expects($this->once())
      ->method('createInstance')
      ->with($this->equalTo('aggregator_test'))
      ->will($this->returnValue($test_processor));

    $form = $this->settingsForm->buildForm(array(), $form_state);
    $this->settingsForm->validateForm($form, $form_state);
    $this->settingsForm->submitForm($form, $form_state);
  }

}

}

namespace {
  // @todo Delete after https://drupal.org/node/1858196 is in.
  if (!function_exists('drupal_set_message')) {
    function drupal_set_message() {}
  }
}
