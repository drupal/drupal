<?php

namespace Drupal\TestSite;

// cspell:ignore enregistrer

/**
 * Setup file used by TestSiteApplicationTest.
 *
 * @see \Drupal\Tests\Scripts\TestSiteApplicationTest
 */
class TestSiteMultilingualInstallTestScript implements TestSetupInterface, TestPreinstallInterface {

  /**
   * {@inheritdoc}
   */
  public function preinstall($db_prefix, $site_directory) {
    // Place a custom local translation in the translations directory.
    mkdir($site_directory . '/files/translations', 0777, TRUE);
    file_put_contents($site_directory . '/files/translations/drupal-8.0.0.fr.po', "msgid \"\"\nmsgstr \"\"\nmsgid \"Save and continue\"\nmsgstr \"Enregistrer et continuer\"");
  }

  /**
   * {@inheritdoc}
   */
  public function setup() {
    \Drupal::service('module_installer')->install(['test_page_test']);
  }

}
