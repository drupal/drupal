<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Installer;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\Translator\FileTranslation;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\TestWith;
use Psr\Http\Message\RequestInterface;

/**
 * Tests that profile translations are downloaded by the installer.
 */
#[Group('Installer')]
#[RunTestsInSeparateProcesses]
class InstallerTranslationDownloadTest extends KernelTestBase {

  /**
   * The original error handler, which will be overwritten by the installer.
   *
   * @var callable|null
   */
  private $errorHandler;

  /**
   * An array of files that were requested, and by which methods.
   */
  private array $requests = [];

  /**
   * The parameters to pass to install_drupal().
   */
  private array $installParameters = [
    'parameters' => [
      'langcode' => 'de',
    ],
    'forms' => [
      'install_configure_form' => [
        'site_name' => 'Test',
        'site_mail' => 'admin@example.com',
        'account' => [
          'name' => 'admin',
          'mail' => 'admin@example.com',
          'pass' => [
            'pass1' => 'admin',
            'pass2' => 'admin',
          ],
        ],
      ],
    ],
    'interactive' => FALSE,
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // We will be calling installer functions directly.
    require_once 'core/includes/install.core.inc';

    // We need to restore the error handler before tearDown, or the testing
    // system will yell at us.
    $this->errorHandler = get_error_handler();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    set_error_handler($this->errorHandler);
    parent::tearDown();
  }

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container): void {
    $handler = function (RequestInterface $request): Response {
      $this->requests[] = $request->getMethod() . ' ' . basename($request->getUri()->getPath());
      // We don't care about the response; we're just ensuring that certain
      // requests are made.
      return new Response(body: "msgid: \"\"\nmsgstr \"\"");
    };
    $client = new Client(['handler' => $handler]);
    $container->set('http_client', $client);

    parent::register($container);
  }

  /**
   * Tests that the translations are downloaded for a custom profile.
   *
   * @param string $version
   *   The version of the custom profile.
   */
  #[TestWith(['1.2.3'], 'Semantic version')]
  #[TestWith(['2.x-dev'], 'Dev branch with -dev suffix')]
  #[TestWith(['2.x'], 'Dev branch without -dev suffix')]
  #[TestWith(['8.x-1.3'], 'Legacy tagged version')]
  #[TestWith(['2.1.0-alpha2'], 'Pre-release semantic version')]
  #[TestWith(['8.x-1.x-dev'], 'Legacy dev branch with -dev suffix')]
  #[TestWith(['8.x-1.x'], 'Legacy dev branch without -dev suffix')]
  public function testCustomProfileTranslationsAreDownloaded(string $version): void {
    // In kernel tests, the site directory is a VFS URI. We need to make it real
    // so the Extension class can access it.
    $pathname = parse_url($this->siteDirectory, PHP_URL_PATH);
    $pathname = trim($pathname, '/');
    $pathname .= '/profiles/foo';
    mkdir($pathname, recursive: TRUE);

    // Make a fake, non-core profile and explicitly expose it to the profile
    // discovery.
    $pathname .= '/foo.info.yml';
    $info = <<<EOF
name: Test
type: profile
version: $version
core_version_requirement: '*'
EOF;
    file_put_contents($pathname, $info);

    // We just created a new profile, but extensions have already been scanned.
    // Therefore, clear the extension discovery cache so the installer can
    // find the new profile.
    (new \ReflectionProperty(ExtensionDiscovery::class, 'files'))
      ->setValue(NULL, []);

    // Expose our fake profile directly to the installer.
    $this->installParameters['profiles']['foo'] = new Extension($this->root, 'profile', $pathname);
    $this->installParameters['parameters']['profile'] = 'foo';
    install_drupal($this->classLoader, $this->installParameters);

    // Four requests should have been made: a HEAD and a GET request for each
    // of two files.
    $core_version = \Drupal::VERSION;
    $expected_requests = [
      "HEAD drupal-$core_version.de.po",
      "GET drupal-$core_version.de.po",
      "HEAD foo-$version.de.po",
      "GET foo-$version.de.po",
    ];
    $this->assertSame($expected_requests, $this->requests);

    // Confirm that the files were actually downloaded.
    $destination = dirname($pathname, 3) . '/files/translations';
    $this->assertFileExists($destination . "/drupal-$core_version.de.po");
    $this->assertFileExists($destination . "/foo-$version.de.po");
    // Put another unrelated translation file into the directory to ensure that
    // FileTranslation doesn't see it.
    touch($destination . "/unrelated-$version.de.po");

    // Confirm that the FileTranslation service used during the early installer
    // finds the downloaded files.
    $translation = new FileTranslation(
      $destination,
      \Drupal::service(FileSystemInterface::class),
      'foo',
    );
    $found_translations = array_column($translation->findTranslationFiles(), 'filename');
    sort($found_translations);
    $expected_translations = [
      "drupal-$core_version.de.po",
      "foo-$version.de.po",
    ];
    $this->assertSame($expected_translations, $found_translations);
  }

  /**
   * Tests that translations are not downloaded for core profiles.
   */
  public function testCoreProfileTranslationsAreNotDownloaded(): void {
    $this->installParameters['parameters']['profile'] = 'testing';
    install_drupal($this->classLoader, $this->installParameters);

    $this->assertCount(2, $this->requests);
    $this->assertStringStartsWith("HEAD drupal-", $this->requests[0]);
    $this->assertStringStartsWith("GET drupal-", $this->requests[1]);
  }

}
