<?php

declare(strict_types=1);

namespace Drupal\Tests\content_translation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the content translation configuration javascript does't fail.
 */
#[Group('content_translation')]
#[RunTestsInSeparateProcesses]
class ContentTranslationConfigUITest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['content_translation', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Use the minimal profile.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Tests that the content translation configuration javascript does't fail.
   */
  public function testContentTranslationConfigUI(): void {
    $content_translation_manager = $this->container->get('content_translation.manager');
    $content_translation_manager->setEnabled('node', 'article', TRUE);
    $this->rebuildContainer();

    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);
    $this->drupalGet('/admin/config/regional/content-language');
    $this->failOnJavaScriptErrors();
  }

}
