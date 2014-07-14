<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Common\HtmlIdentifierUnitTest.
 */

namespace Drupal\system\Tests\Common;

use Drupal\simpletest\KernelTestBase;

/**
 * Tests the functions drupal_html_class(), drupal_html_id() and
 * drupal_clean_css_identifier() for expected behavior.
 *
 * @group Common
 */
class HtmlIdentifierUnitTest extends KernelTestBase {
  /**
   * Tests that drupal_clean_css_identifier() cleans the identifier properly.
   */
  function testDrupalCleanCSSIdentifier() {
    // Verify that no valid ASCII characters are stripped from the identifier.
    $identifier = 'abcdefghijklmnopqrstuvwxyz_ABCDEFGHIJKLMNOPQRSTUVWXYZ-0123456789';
    $this->assertIdentical(drupal_clean_css_identifier($identifier, array()), $identifier, 'Verify valid ASCII characters pass through.');

    // Verify that valid UTF-8 characters are not stripped from the identifier.
    $identifier = '¡¢£¤¥';
    $this->assertIdentical(drupal_clean_css_identifier($identifier, array()), $identifier, 'Verify valid UTF-8 characters pass through.');

    // Verify that invalid characters (including non-breaking space) are stripped from the identifier.
    $this->assertIdentical(drupal_clean_css_identifier('invalid !"#$%&\'()*+,./:;<=>?@[\\]^`{|}~ identifier', array()), 'invalididentifier', 'Strip invalid characters.');

    // Verify that double underscores are not stripped from the identifier.
    $identifier = 'css__identifier__with__double__underscores';
    $this->assertIdentical(drupal_clean_css_identifier($identifier), $identifier, 'Verify double underscores pass through.');

    // Verify that an identifier starting with a digit is replaced.
    $this->assertIdentical(drupal_clean_css_identifier('1cssidentifier', array()), '_cssidentifier', 'Verify identifier starting with a digit is replaced.');

    // Verify that an identifier starting with a hyphen followed by a digit is
    // replaced.
    $this->assertIdentical(drupal_clean_css_identifier('-1cssidentifier', array()), '__cssidentifier', 'Verify identifier starting with a hyphen followed by a digit is replaced.');

    // Verify that an identifier starting with two hyphens is replaced.
    $this->assertIdentical(drupal_clean_css_identifier('--cssidentifier', array()), '__cssidentifier', 'Verify identifier starting with two hyphens is replaced.');
  }

  /**
   * Tests that drupal_html_class() cleans the class name properly.
   */
  function testDrupalHTMLClass() {
    // Verify Drupal coding standards are enforced.
    $this->assertIdentical(drupal_html_class('CLASS NAME_[Ü]'), 'class-name--ü', 'Enforce Drupal coding standards.');
  }

  /**
   * Tests that drupal_html_id() cleans the ID properly.
   */
  function testDrupalHTMLId() {
    // Verify that letters, digits, and hyphens are not stripped from the ID.
    $id = 'abcdefghijklmnopqrstuvwxyz-0123456789';
    $this->assertIdentical(drupal_html_id($id), $id, 'Verify valid characters pass through.');

    // Verify that invalid characters are stripped from the ID.
    $this->assertIdentical(drupal_html_id('invalid,./:@\\^`{Üidentifier'), 'invalididentifier', 'Strip invalid characters.');

    // Verify Drupal coding standards are enforced.
    $this->assertIdentical(drupal_html_id('ID NAME_[1]'), 'id-name-1', 'Enforce Drupal coding standards.');

    // Reset the static cache so we can ensure the unique id count is at zero.
    drupal_static_reset('drupal_html_id');

    // Clean up IDs with invalid starting characters.
    $this->assertIdentical(drupal_html_id('test-unique-id'), 'test-unique-id', 'Test the uniqueness of IDs #1.');
    $this->assertIdentical(drupal_html_id('test-unique-id'), 'test-unique-id--2', 'Test the uniqueness of IDs #2.');
    $this->assertIdentical(drupal_html_id('test-unique-id'), 'test-unique-id--3', 'Test the uniqueness of IDs #3.');
  }
}
