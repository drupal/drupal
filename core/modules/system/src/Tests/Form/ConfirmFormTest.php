<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Form\ConfirmFormTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Tests confirmation forms.
 *
 * @group Form
 */
class ConfirmFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  function testConfirmForm() {
    // Test the building of the form.
    $this->drupalGet('form-test/confirm-form');
    $site_name = $this->container->get('config.factory')->get('system.site')->get('name');
    $this->assertTitle(t('ConfirmFormTestForm::getQuestion(). | @site-name', array('@site-name' => $site_name)), 'The question was found as the page title.');
    $this->assertText(t('ConfirmFormTestForm::getDescription().'), 'The description was used.');
    $this->assertFieldByXPath('//input[@id="edit-submit"]', t('ConfirmFormTestForm::getConfirmText().'), 'The confirm text was used.');

    // Test canelling the form.
    $this->clickLink(t('ConfirmFormTestForm::getCancelText().'));
    $this->assertUrl('form-test/autocomplete', array(), "The form's cancel link was followed.");

    // Test submitting the form.
    $this->drupalPostForm('form-test/confirm-form', NULL, t('ConfirmFormTestForm::getConfirmText().'));
    $this->assertText('The ConfirmFormTestForm::submitForm() method was used for this form.');
    $this->assertUrl('', array(), "The form's redirect was followed.");

    // Test submitting the form with a destination.
    $this->drupalPostForm('form-test/confirm-form', NULL, t('ConfirmFormTestForm::getConfirmText().'), array('query' => array('destination' => 'admin/config')));
    $this->assertUrl('admin/config', array(), "The form's redirect was not followed, the destination query string was followed.");

    // Test cancelling the form with a complex destination.
    $this->drupalGet('form-test/confirm-form-array-path');
    $this->clickLink(t('ConfirmFormArrayPathTestForm::getCancelText().'));
    $this->assertUrl('form-test/confirm-form', array('query' => array('destination' => 'admin/config')), "The form's complex cancel link was followed.");
  }

}
