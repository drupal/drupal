<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Kernel;

use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests link field validation.
 *
 * @group link
 */
class LinkItemUrlValidationTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['link'];

  /**
   * Tests link validation.
   */
  public function testExternalLinkValidation(): void {
    $definition = \Drupal::typedDataManager()
      ->createDataDefinition('field_item:link');
    $link_item = \Drupal::typedDataManager()->create($definition);
    $test_links = $this->getTestLinks();

    foreach ($test_links as $data) {
      [$value, $expected_violations] = $data;
      $link_item->setValue($value);
      $violations = $link_item->validate();
      $expected_count = count($expected_violations);
      $this->assertCount($expected_count, $violations, sprintf('Violation message count error for %s', $value));
      if ($expected_count) {
        $i = 0;
        foreach ($expected_violations as $error_msg) {
          // If the expected message contains a '%' add the current link value.
          if (strpos($error_msg, '%')) {
            $error_msg = sprintf($error_msg, $value);
          }
          $this->assertEquals($error_msg, $violations[$i++]->getMessage());
        }
      }
    }
  }

  /**
   * Builds an array of links to test.
   *
   * @return array
   *   The first element of the array is the link value to test. The second
   *   value is an array of expected violation messages.
   */
  protected function getTestLinks() {
    $violation_0 = "The path '%s' is invalid.";
    $violation_1 = 'This value should be of the correct primitive type.';
    return [
      ['invalid://not-a-valid-protocol', [$violation_0]],
      ['http://www.example.com/', []],
      // Strings within parenthesis without leading space char.
      ['http://www.example.com/strings_(string_within_parenthesis)', []],
      // Numbers within parenthesis without leading space char.
      ['http://www.example.com/numbers_(9999)', []],
      ['http://www.example.com/?name=ferret&color=purple', []],
      ['http://www.example.com/page?name=ferret&color=purple', []],
      ['http://www.example.com?a=&b[]=c&d[]=e&d[]=f&h==', []],
      ['http://www.example.com#colors', []],
      // Use list of valid URLS from],
      // https://cran.r-project.org/web/packages/rex/vignettes/url_parsing.html.
      ["http://foo.com/blah_blah", []],
      ["http://foo.com/blah_blah/", []],
      ["http://foo.com/blah_blah_(wikipedia)", []],
      ["http://foo.com/blah_blah_(wikipedia)_(again)", []],
      ["http://www.example.com/wpstyle/?p=364", []],
      ["https://www.example.com/foo/?bar=baz&inga=42&quux", []],
      ["http://✪df.ws/123", []],
      ["http://userid:password@example.com:8080", []],
      ["http://userid:password@example.com:8080/", []],
      ["http://userid@example.com", []],
      ["http://userid@example.com/", []],
      ["http://userid@example.com:8080", []],
      ["http://userid@example.com:8080/", []],
      ["http://userid:password@example.com", []],
      ["http://userid:password@example.com/", []],
      ["http://➡.ws/䨹", []],
      ["http://⌘.ws", []],
      ["http://⌘.ws/", []],
      ["http://foo.com/blah_(wikipedia)#cite-1", []],
      ["http://foo.com/blah_(wikipedia)_blah#cite-1", []],
      // The following invalid URLs produce false positives.
      ["http://foo.com/unicode_(✪)_in_parens", []],
      ["http://foo.com/(something)?after=parens", []],
      ["http://☺.damowmow.com/", []],
      ["http://code.google.com/events/#&product=browser", []],
      ["http://j.mp", []],
      ["ftp://foo.bar/baz", []],
      ["http://foo.bar/?q=Test%20URL-encoded%20stuff", []],
      ["http://مثال.إختبار", []],
      ["http://例子.测试", []],
      ["http://-.~_!$&'()*+,;=:%40:80%2f::::::@example.com", []],
      ["http://1337.net", []],
      ["http://a.b-c.de", []],
      ["radar://1234", [$violation_0]],
      ["h://test", [$violation_0]],
      ["ftps://foo.bar/", [$violation_0]],
      // Use invalid URLS from
      // https://cran.r-project.org/web/packages/rex/vignettes/url_parsing.html.
      ['http://', [$violation_0, $violation_1]],
      ["http://?", [$violation_0, $violation_1]],
      ["http://??", [$violation_0, $violation_1]],
      ["http://??/", [$violation_0, $violation_1]],
      ["http://#", [$violation_0, $violation_1]],
      ["http://##", [$violation_0, $violation_1]],
      ["http://##/", [$violation_0, $violation_1]],
      ["//", [$violation_0, $violation_1]],
      ["///a", [$violation_0, $violation_1]],
      ["///", [$violation_0, $violation_1]],
      ["http:///a", [$violation_0, $violation_1]],
    ];
  }

}
