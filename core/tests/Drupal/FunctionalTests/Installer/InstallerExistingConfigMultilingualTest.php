<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Verifies that installing from existing configuration works.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class InstallerExistingConfigMultilingualTest extends InstallerConfigDirectoryTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = 'testing_config_install_multilingual';

  /**
   * {@inheritdoc}
   */
  protected function getConfigLocation(): string {
    return __DIR__ . '/../../../fixtures/config_install/multilingual';
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment(): void {
    parent::prepareEnvironment();
    mkdir(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations', 0777, TRUE);

    // Test that contrib translations are not installed by
    // install_import_translations(). If they are then the translation from
    // locale_test_additional will not be overwritten by the translation from
    // locale_test_extra.
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/locale_test_additional-1.0.es.po', <<<PO
msgid ""
msgstr ""

msgid "Test string"
msgstr "Test string wrong"
PO
    );
    file_put_contents(DRUPAL_ROOT . '/' . $this->siteDirectory . '/files/translations/locale_test_extra-1.0.es.po', <<<PO
msgid ""
msgstr ""

msgid "Test string"
msgstr "Test string correct"
PO
    );
  }

  /**
   * {@inheritdoc}
   */
  public function testConfigSync(): void {
    parent::testConfigSync();

    // Ensure no warning, error, critical, alert or emergency messages have been
    // logged.
    $count = (int) \Drupal::database()->select('watchdog', 'w')->fields('w')->condition('severity', RfcLogLevel::WARNING, '<=')->countQuery()->execute()->fetchField();
    $this->assertSame(0, $count);

    // Ensure the correct message is logged from \Drupal\locale\LocaleConfigBatch::batchFinished().
    $count = (int) \Drupal::database()->select('watchdog', 'w')->fields('w')->condition('message', 'The configuration was successfully updated. %number configuration objects updated.')->countQuery()->execute()->fetchField();
    $this->assertSame(1, $count);

    $this->assertEquals('Test string correct', (string) new TranslatableMarkup('Test string', [], ['langcode' => 'es']));
  }

}
