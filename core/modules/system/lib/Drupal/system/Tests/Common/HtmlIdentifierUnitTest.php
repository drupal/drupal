<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\HtmlIdentifierUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\UnitTestBase;

/**
 * Test for cleaning HTML identifiers.
 */
class HtmlIdentifierUnitTest extends UnitTestBase {
  public static function getInfo() {
    return array(
      'name' => 'HTML identifiers',
      'description' => 'Test the functions drupal_html_class(), drupal_html_id() and drupal_clean_css_identifier() for expected behavior',
      'group' => 'Common',
    );
  }

  /**
   * Tests that drupal_clean_css_identifier() cleans the identifier properly.
   */
  function testDrupalCleanCSSIdentifier() {
    // Verify that no valid ASCII characters are stripped from the identifier.
    $identifier = 'abcdefghijklmnopqrstuvwxyz_ABCDEFGHIJKLMNOPQRSTUVWXYZ-0123456789';
    $this->assertIdentical(drupal_clean_css_identifier($identifier, array()), $identifier, t('Verify valid ASCII characters pass through.'));

    // Verify that valid UTF-8 characters are not stripped from the identifier.
    $identifier = '¡¢£¤¥';
    $this->assertIdentical(drupal_clean_css_identifier($identifier, array()), $identifier, t('Verify valid UTF-8 characters pass through.'));

    // Verify that invalid characters (including non-breaking space) are stripped from the identifier.
    $this->assertIdentical(drupal_clean_css_identifier('invalid !"#$%&\'()*+,./:;<=>?@[\\]^`{|}~ identifier', array()), 'invalididentifier', t('Strip invalid characters.'));
  }

  /**
   * Tests that drupal_html_class() cleans the class name properly.
   */
  function testDrupalHTMLClass() {
    // Verify Drupal coding standards are enforced.
    $this->assertIdentical(drupal_html_class('CLASS NAME_[Ü]'), 'class-name--ü', t('Enforce Drupal coding standards.'));
  }

  /**
   * Tests that drupal_html_id() cleans the ID properly.
   */
  function testDrupalHTMLId() {
    // Verify that letters, digits, and hyphens are not stripped from the ID.
    $id = 'abcdefghijklmnopqrstuvwxyz-0123456789';
    $this->assertIdentical(drupal_html_id($id), $id, t('Verify valid characters pass through.'));

    // Verify that invalid characters are stripped from the ID.
    $this->assertIdentical(drupal_html_id('invalid,./:@\\^`{Üidentifier'), 'invalididentifier', t('Strip invalid characters.'));

    // Verify Drupal coding standards are enforced.
    $this->assertIdentical(drupal_html_id('ID NAME_[1]'), 'id-name-1', t('Enforce Drupal coding standards.'));

    // Reset the static cache so we can ensure the unique id count is at zero.
    drupal_static_reset('drupal_html_id');

    // Clean up IDs with invalid starting characters.
    $this->assertIdentical(drupal_html_id('test-unique-id'), 'test-unique-id', t('Test the uniqueness of IDs #1.'));
    $this->assertIdentical(drupal_html_id('test-unique-id'), 'test-unique-id--2', t('Test the uniqueness of IDs #2.'));
    $this->assertIdentical(drupal_html_id('test-unique-id'), 'test-unique-id--3', t('Test the uniqueness of IDs #3.'));
  }
}
