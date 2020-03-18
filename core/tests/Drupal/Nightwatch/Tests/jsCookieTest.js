const deprecatedMessageSuffix = `is deprecated in Drupal 9.0.0 and will be removed in Drupal 10.0.0. Use the core/js-cookie library instead. See https://www.drupal.org/node/3104677`;
// Nightwatch suggests non-ES6 functions when using the execute method.
// eslint-disable-next-line func-names, prefer-arrow-callback
const getJqueryCookie = function(cookieName) {
  return undefined !== cookieName ? jQuery.cookie(cookieName) : jQuery.cookie();
};
// eslint-disable-next-line func-names, prefer-arrow-callback
const setJqueryCookieWithOptions = function(
  cookieName,
  cookieValue,
  options = {},
) {
  return jQuery.cookie(cookieName, cookieValue, options);
};
module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'JS Cookie Test')
        .waitForElementVisible(
          'input[name="modules[js_cookie_test][enable]"]',
          1000,
        )
        .click('input[name="modules[js_cookie_test][enable]"]')
        .click('input[type="submit"]'); // Submit module form.
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Test jquery.cookie Shim Simple Value and jquery.removeCookie': browser => {
    browser
      .drupalRelativeURL('/js_cookie_with_shim_test')
      .waitForElementVisible('.js_cookie_test_add_button', 1000)
      .click('.js_cookie_test_add_button')
      // prettier-ignore
      .execute(getJqueryCookie, ['js_cookie_test'], result => {
        browser.assert.equal(
          result.value,
          'red panda',
          '$.cookie returns cookie value',
        );
      })
      .waitForElementVisible('.js_cookie_test_remove_button', 1000)
      .click('.js_cookie_test_remove_button')
      .execute(getJqueryCookie, ['js_cookie_test_remove'], result => {
        browser.assert.equal(result.value, null, 'cookie removed');
      })
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Test jquery.cookie Shim Empty Value': browser => {
    browser
      .setCookie({
        name: 'js_cookie_test_empty',
        value: '',
      })
      // prettier-ignore
      .execute(getJqueryCookie, ['js_cookie_test_empty'], result => {
        browser.assert.equal(
          result.value,
          '',
          '$.cookie returns empty cookie value',
        );
      })
      .getCookie('js_cookie_test_empty', result => {
        browser.assert.equal(result.value, '', 'Cookie value is empty.');
      })
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Test jquery.cookie Shim Undefined': browser => {
    browser
      .deleteCookie('js_cookie_test_undefined', () => {
        browser.execute(
          getJqueryCookie,
          ['js_cookie_test_undefined'],
          result => {
            browser.assert.equal(
              result.value,
              undefined,
              '$.cookie returns undefined cookie value',
            );
          },
        );
      })
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Test jquery.cookie Shim Decode': browser => {
    browser
      .setCookie({
        name: encodeURIComponent(' js_cookie_test_encoded'),
        value: encodeURIComponent(' red panda'),
      })
      .execute(getJqueryCookie, [' js_cookie_test_encoded'], result => {
        browser.assert.equal(
          result.value,
          ' red panda',
          '$.cookie returns decoded cookie value',
        );
      })
      .setCookie({
        name: 'js_cookie_test_encoded_plus_to_space',
        value: 'red+panda',
      })
      .execute(
        getJqueryCookie,
        ['js_cookie_test_encoded_plus_to_space'],
        result => {
          browser.assert.equal(
            result.value,
            'red panda',
            '$.cookie returns decoded plus to space in cookie value',
          );
        },
      )
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Test jquery.cookie Shim With raw': browser => {
    browser
      .drupalRelativeURL('/js_cookie_with_shim_test')
      .waitForElementVisible('.js_cookie_test_add_raw_button', 1000)
      .click('.js_cookie_test_add_raw_button')
      .execute(getJqueryCookie, ['js_cookie_test_raw'], result => {
        browser.assert.equal(
          result.value,
          'red%20panda',
          '$.cookie returns raw cookie value',
        );
      })
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Test jquery.cookie Shim With JSON': browser => {
    browser
      .drupalRelativeURL('/js_cookie_with_shim_test')
      .waitForElementVisible('.js_cookie_test_add_json_button', 1000)
      .click('.js_cookie_test_add_json_button')
      .execute(getJqueryCookie, ['js_cookie_test_json'], result => {
        browser.assert.deepEqual(
          result.value,
          { panda: 'red' },
          'Stringified JSON is returned as JSON.',
        );
      })
      .getCookie('js_cookie_test_json', result => {
        browser.assert.equal(
          result.value,
          '%7B%22panda%22%3A%22red%22%7D',
          'Cookie value is encoded backwards-compatible with jquery.cookie.',
        );
      })
      .execute(getJqueryCookie, ['js_cookie_test_json_simple'], result => {
        browser.assert.equal(
          result.value,
          'red panda',
          '$.cookie returns simple cookie value with JSON enabled',
        );
      })
      .waitForElementVisible('.js_cookie_test_add_json_string_button', 1000)
      .click('.js_cookie_test_add_json_string_button')
      .execute(getJqueryCookie, ['js_cookie_test_json_string'], result => {
        browser.assert.deepEqual(
          result.value,
          '[object Object]',
          'JSON used without json option is return as a string.',
        );
      })
      .getCookie('js_cookie_test_json_string', result => {
        browser.assert.equal(
          result.value,
          '%5Bobject%20Object%5D',
          'Cookie value is encoded backwards-compatible with jquery.cookie.',
        );
      })
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Test jquery.cookie Shim invalid URL encoding': browser => {
    browser
      .setCookie({
        name: 'js_cookie_test_bad',
        value: 'red panda%',
      })
      .execute(getJqueryCookie, ['js_cookie_test_bad'], result => {
        browser.assert.equal(
          result.value,
          undefined,
          '$.cookie won`t throw exception, returns undefined',
        );
      })
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Test jquery.cookie Shim Read all when there are cookies or return empty object': browser => {
    browser
      .getCookie('SIMPLETEST_USER_AGENT', simpletestCookie => {
        const simpletestCookieValue = simpletestCookie.value;
        browser
          .drupalRelativeURL('/js_cookie_with_shim_test')
          .deleteCookies(() => {
            browser
              .execute(getJqueryCookie, [], result => {
                browser.assert.deepEqual(
                  result.value,
                  {},
                  '$.cookie() returns empty object',
                );
              })
              .setCookie({
                name: 'js_cookie_test_first',
                value: 'red panda',
              })
              .setCookie({
                name: 'js_cookie_test_second',
                value: 'second red panda',
              })
              .setCookie({
                name: 'js_cookie_test_third',
                value: 'third red panda id bad%',
              })
              .execute(getJqueryCookie, [], result => {
                browser.assert.deepEqual(
                  result.value,
                  {
                    js_cookie_test_first: 'red panda',
                    js_cookie_test_second: 'second red panda',
                  },
                  '$.cookie() returns object containing all cookies',
                );
              })
              .setCookie({
                name: 'SIMPLETEST_USER_AGENT',
                value: simpletestCookieValue,
              });
          });
      })
      .drupalLogAndEnd({ onlyOnError: false });
  },
  'Test jquery.cookie Shim expires option as Date instance': browser => {
    const sevenDaysFromNow = new Date();
    sevenDaysFromNow.setDate(sevenDaysFromNow.getDate() + 7);
    browser
      .execute(
        setJqueryCookieWithOptions,
        ['c', 'v', { expires: sevenDaysFromNow }],
        result => {
          browser.assert.equal(
            result.value,
            `c=v; expires=${sevenDaysFromNow.toUTCString()}`,
            'should write the cookie string with expires',
          );
        },
      )
      .drupalLogAndEnd({ onlyOnError: false });
  },
};
