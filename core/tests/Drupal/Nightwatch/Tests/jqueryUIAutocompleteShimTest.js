// These are Nightwatch equivalents of jQuery UI's autocomplete tests. These
// are present to confirm that tests that pass with jQuery UI autocomplete
// also pass with the shimmed core autocomplete.

function arrowsInvokeSearch(id, isKeyUp, shouldMove) {
  let didMove = false;
  const element = jQuery(id).autocomplete({
    source: ['a'],
    delay: 0,
    minLength: 0,
  });
  // override highlight item
  if (
    Drupal.hasOwnProperty('Autocomplete') &&
    Drupal.Autocomplete.hasOwnProperty('instances')
  ) {
    Drupal.Autocomplete.instances[
      element.attr('id')
    ].highlightItem = function () {
      didMove = true;
    };
  } else {
    element.autocomplete('instance')._move = () => {
      didMove = true;
    };
  }

  element.simulate('keydown', {
    keyCode: isKeyUp ? jQuery.ui.keyCode.UP : jQuery.ui.keyCode.DOWN,
  });
  return didMove === shouldMove;
}

function arrowsMoveFocus(id, isKeyUp) {
  let didMove = false;
  const element = jQuery(id).autocomplete({
    source: ['a'],
    delay: 0,
    minLength: 0,
  });
  // override highlight item
  if (
    Drupal.hasOwnProperty('Autocomplete') &&
    Drupal.Autocomplete.hasOwnProperty('instances')
  ) {
    Drupal.Autocomplete.instances[
      element.attr('id')
    ].highlightItem = function () {
      didMove = true;
    };
  } else {
    element.autocomplete('instance')._move = () => {
      didMove = true;
    };
  }
  element.autocomplete('search');
  element.simulate('keydown', {
    keyCode: isKeyUp ? jQuery.ui.keyCode.UP : jQuery.ui.keyCode.DOWN,
  });
  return didMove;
}

function arrowsNavigateElement(id, isKeyUp, shouldMove) {
  let didMove = false;
  const element = jQuery(id).autocomplete({
    source: ['a'],
    delay: 0,
    minLength: 0,
  });

  element.on('keypress', (e) => {
    didMove = document.activeElement.tagName === 'LI';
  });
  element.simulate('keydown', {
    keyCode: isKeyUp ? jQuery.ui.keyCode.UP : jQuery.ui.keyCode.DOWN,
  });

  element.simulate('keypress');
  return shouldMove === (document.activeElement.tagName !== 'LI');
}

function sourceTest(source, async, done) {
  const toReturn = {};
  toReturn.errors = [];

  const element = jQuery('#autocomplete').autocomplete({
    source,
  });
  const menu = element.autocomplete('widget');

  function itemMatch(item, name) {
    return item.value === name && item.label === name;
  }

  function result() {
    const items = menu.find('.ui-menu-item');
    toReturn.calledResult = true;
    if (items.length !== 3) {
      toReturn.errors.push('Should find three results.');
    }
    if (!itemMatch(items.eq(0).data('ui-autocomplete-item'), 'java')) {
      toReturn.errors.push('Item 0 expected value');
    }
    if (!itemMatch(items.eq(1).data('ui-autocomplete-item'), 'javascript')) {
      toReturn.errors.push('Item 1 expected value');
    }
    if (!itemMatch(items.eq(2).data('ui-autocomplete-item'), 'clojure')) {
      toReturn.errors.push('Item 2 expected value');
    }

    element.autocomplete('destroy');
    if (async) {
      toReturn.isAsync = true;
      done(toReturn);
    } else {
      toReturn.notAtAllAsync = true;
    }
  }
  if (async) {
    jQuery(document).on('ajaxStop', result);
    element[0].addEventListener('autocomplete-open', result);
  }
  element.val('j').autocomplete('search');
  if (!async) {
    result();
    return toReturn;
  }
}

module.exports = {
  '@tags': ['core'],
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'autocomplete Shim Test')
        .waitForElementVisible(
          'input[name="modules[autocomplete_shim_test][enable]"]',
          1000,
        )
        .click('input[name="modules[autocomplete_shim_test][enable]"]')
        .click('input[type="submit"]');
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'test autocomplete': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          const element = jQuery('#autocomplete').autocomplete();
          const menu = element.autocomplete('widget');
          return {
            inputHasClasses: element.hasClass('ui-autocomplete-input'),
            menuHasClasses:
              menu.hasClass('ui-autocomplete') &&
              menu.hasClass('ui-widget') &&
              menu.hasClass('ui-widget-content'),
          };
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value.inputHasClasses,
            true,
            'input has expected classes',
          );
          browser.assert.equal(
            result.value.menuHasClasses,
            true,
            'menu has expected classes',
          );
        },
      );
  },
  'prevent form submit on enter when menu is active': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          const toReturn = {};
          const element = jQuery('#autocomplete')
            .autocomplete({
              source: ['java', 'javascript'],
            })
            .val('ja')
            .autocomplete('search');
          const menu = element.autocomplete('widget');

          // let event = jQuery.Event('keydown');
          let event = new KeyboardEvent('keydown', {
            keyCode: jQuery.ui.keyCode.DOWN,
            cancelable: true,
          });
          element[0].dispatchEvent(event);
          toReturn.menuItemIsActive =
            menu.find('.ui-menu-item-wrapper.ui-state-active').length === 1;

          // event = jQuery.Event('keydown');
          event = new KeyboardEvent('keydown', {
            keyCode: jQuery.ui.keyCode.ENTER,
            cancelable: true,
          });
          element[0].dispatchEvent(event);
          toReturn.isDefaultPrevented = event.defaultPrevented;
          return toReturn;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value.menuItemIsActive,
            true,
            'menu item is active',
          );
          browser.assert.equal(
            result.value.isDefaultPrevented,
            true,
            'default action is prevented',
          );
        },
      );
  },
  'allow form submit on enter when menu is not active': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          const element = jQuery('#autocomplete')
            .autocomplete({
              autoFocus: false,
              source: ['java', 'javascript'],
            })
            .val('ja')
            .autocomplete('search');

          const event = jQuery.Event('keydown');
          event.keyCode = jQuery.ui.keyCode.ENTER;
          element.trigger(event);
          return {
            opposite: event.isDefaultPrevented(),
            isDefaultPrevented: !event.isDefaultPrevented(),
          };
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value.isDefaultPrevented,
            true,
            'default action is prevented',
          );
        },
      );
  },
  'up arrow invokes search - input': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsInvokeSearchAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsInvokeSearchFn = new Function(
            `return ${arrowsInvokeSearchAsString}`,
          )();
          const respondedToArrow = arrowsInvokeSearchFn(
            '#autocomplete',
            true,
            true,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsInvokeSearch.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'down arrow invokes search - input': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsInvokeSearchAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsInvokeSearchFn = new Function(
            `return ${arrowsInvokeSearchAsString}`,
          )();
          const respondedToArrow = arrowsInvokeSearchFn(
            '#autocomplete',
            false,
            true,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsInvokeSearch.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'up arrow invokes search - textarea': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsInvokeSearchAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsInvokeSearchFn = new Function(
            `return ${arrowsInvokeSearchAsString}`,
          )();
          const respondedToArrow = arrowsInvokeSearchFn(
            '#autocomplete-textarea',
            true,
            false,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsInvokeSearch.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'down arrow invokes search - textarea': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsInvokeSearchAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsInvokeSearchFn = new Function(
            `return ${arrowsInvokeSearchAsString}`,
          )();
          const respondedToArrow = arrowsInvokeSearchFn(
            '#autocomplete-textarea',
            false,
            false,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsInvokeSearch.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'up arrow invokes search - contenteditable': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsInvokeSearchAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsInvokeSearchFn = new Function(
            `return ${arrowsInvokeSearchAsString}`,
          )();
          const respondedToArrow = arrowsInvokeSearchFn(
            '#autocomplete-contenteditable',
            true,
            false,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsInvokeSearch.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'down arrow invokes search - contenteditable': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsInvokeSearchAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsInvokeSearchFn = new Function(
            `return ${arrowsInvokeSearchAsString}`,
          )();
          const respondedToArrow = arrowsInvokeSearchFn(
            '#autocomplete-contenteditable',
            false,
            false,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsInvokeSearch.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'up arrow moves focus - input': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsMoveFocusAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsMoveFocusFn = new Function(
            `return ${arrowsMoveFocusAsString}`,
          )();
          const focusMoved = arrowsMoveFocusFn('#autocomplete', true);
          return {
            focusMoved,
          };
        },
        [arrowsMoveFocus.toString()],
        (result) => {
          browser.assert.equal(
            result.value.focusMoved,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'down arrow moves focus - input': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsMoveFocusAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsMoveFocusFn = new Function(
            `return ${arrowsMoveFocusAsString}`,
          )();
          const focusMoved = arrowsMoveFocusFn('#autocomplete', false);
          return {
            focusMoved,
          };
        },
        [arrowsMoveFocus.toString()],
        (result) => {
          browser.assert.equal(
            result.value.focusMoved,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'up arrow moves focus - textarea': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsMoveFocusAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsMoveFocusFn = new Function(
            `return ${arrowsMoveFocusAsString}`,
          )();
          const focusMoved = arrowsMoveFocusFn('#autocomplete-textarea', true);
          return {
            focusMoved,
          };
        },
        [arrowsMoveFocus.toString()],
        (result) => {
          browser.assert.equal(
            result.value.focusMoved,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'down arrow moves focus - textarea': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsMoveFocusAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsMoveFocusFn = new Function(
            `return ${arrowsMoveFocusAsString}`,
          )();
          const focusMoved = arrowsMoveFocusFn('#autocomplete-textarea', false);
          return {
            focusMoved,
          };
        },
        [arrowsMoveFocus.toString()],
        (result) => {
          browser.assert.equal(
            result.value.focusMoved,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'up arrow moves focus - contenteditable': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsMoveFocusAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsMoveFocusFn = new Function(
            `return ${arrowsMoveFocusAsString}`,
          )();
          const focusMoved = arrowsMoveFocusFn(
            '#autocomplete-contenteditable',
            true,
          );
          return {
            focusMoved,
          };
        },
        [arrowsMoveFocus.toString()],
        (result) => {
          browser.assert.equal(
            result.value.focusMoved,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'down arrow moves focus - contenteditable': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsMoveFocusAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsMoveFocusFn = new Function(
            `return ${arrowsMoveFocusAsString}`,
          )();
          const focusMoved = arrowsMoveFocusFn(
            '#autocomplete-contenteditable',
            false,
          );
          return {
            focusMoved,
          };
        },
        [arrowsMoveFocus.toString()],
        (result) => {
          browser.assert.equal(
            result.value.focusMoved,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'up arrow moves cursor - input': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsNavigateElementAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsNavigateElementFn = new Function(
            `return ${arrowsNavigateElementAsString}`,
          )();
          const respondedToArrow = arrowsNavigateElementFn(
            '#autocomplete',
            true,
            false,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsNavigateElement.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'down arrow moves cursor - input': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsNavigateElementAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsNavigateElementFn = new Function(
            `return ${arrowsNavigateElementAsString}`,
          )();
          const respondedToArrow = arrowsNavigateElementFn(
            '#autocomplete',
            false,
            false,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsNavigateElement.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'up arrow moves cursor - textarea': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsNavigateElementAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsNavigateElementFn = new Function(
            `return ${arrowsNavigateElementAsString}`,
          )();
          const respondedToArrow = arrowsNavigateElementFn(
            '#autocomplete-textarea',
            true,
            true,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsNavigateElement.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'down arrow moves cursor - textarea': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsNavigateElementAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsNavigateElementFn = new Function(
            `return ${arrowsNavigateElementAsString}`,
          )();
          const respondedToArrow = arrowsNavigateElementFn(
            '#autocomplete-textarea',
            false,
            true,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsNavigateElement.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'up arrow moves cursor - contenteditable': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsNavigateElementAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsNavigateElementFn = new Function(
            `return ${arrowsNavigateElementAsString}`,
          )();
          const respondedToArrow = arrowsNavigateElementFn(
            '#autocomplete-contenteditable',
            true,
            true,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsNavigateElement.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'down arrow moves cursor - contenteditable': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (arrowsNavigateElementAsString) {
          // eslint-disable-next-line no-new-func
          const arrowsNavigateElementFn = new Function(
            `return ${arrowsNavigateElementAsString}`,
          )();
          const respondedToArrow = arrowsNavigateElementFn(
            '#autocomplete-contenteditable',
            false,
            true,
          );
          return {
            respondedToArrow,
          };
        },
        [arrowsNavigateElement.toString()],
        (result) => {
          browser.assert.equal(
            result.value.respondedToArrow,
            true,
            'responded to arrow',
          );
        },
      );
  },
  'past end of menu in multiline autocomplete': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (done) {
          const toReturn = {};
          const customVal = 'custom value';
          const element = jQuery('#autocomplete-contenteditable').autocomplete({
            delay: 0,
            source: ['javascript'],
            focus(event, ui) {
              if (ui.item.value === 'javascript') {
                toReturn.itemGainedFocus = ui.item.value === 'javascript';
              }
              jQuery(this).text(customVal);
              event.preventDefault();
            },
          });
          element.simulate('focus').autocomplete('search', 'ja');

          setTimeout(() => {
            element.simulate('keydown', { keyCode: jQuery.ui.keyCode.DOWN });
            element.simulate('keydown', { keyCode: jQuery.ui.keyCode.DOWN });
            toReturn.itemHasExpectedValue = element.text() === customVal;
            done(toReturn);
          });
        },
        [],
        (result) => {
          const expectedTrue = {
            itemGainedFocus: 'item gained focus',
            itemHasExpectedValue: 'item has expected value',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              `${property}: ${expectedTrue[property]}`,
            );
          });
        },
      );
  },
  'ESCAPE in multiline autocomplete': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          // eslint-disable-next-line no-new-func
          const customVal = 'custom value';
          const element = jQuery('#autocomplete-contenteditable').autocomplete({
            delay: 0,
            source: ['javascript'],
            focus(event, ui) {
              if (ui.item.value === 'javascript') {
                event.target.classList.add('the-item-gained-focus');
              }
              jQuery(this).text(customVal);
              event.preventDefault();
            },
          });
          element.simulate('focus').autocomplete('search', 'ja');

          setTimeout(() => {
            element.simulate('keydown', { keyCode: jQuery.ui.keyCode.DOWN });
            element.simulate('keydown', { keyCode: jQuery.ui.keyCode.ESCAPE });
            if (element.text() === customVal) {
              element.addClass('has-expected-value');
            }
          });
        },
      )
      .waitForElementPresent(
        '#autocomplete-contenteditable.the-item-gained-focus',
        1000,
        'Item gained focus',
      )
      .waitForElementPresent(
        '#autocomplete-contenteditable.has-expected-value',
        1000,
        'Item has expected value',
      );
  },
  'simultaneous searches': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (done) {
          const toReturn = {};
          const element = jQuery('#autocomplete').autocomplete({
            source(request, response) {
              // eslint-disable-next-line func-names
              setTimeout(function () {
                response([request.term]);
              });
            },
            response() {
              toReturn.firstItemResponded = true;
            },
          });

          const element2 = jQuery('#autocomplete-textarea').autocomplete({
            source(request, response) {
              // eslint-disable-next-line func-names
              setTimeout(function () {
                response([request.term]);
              });
            },
            response() {
              toReturn.secondItemResponded = true;
              done(toReturn);
            },
          });

          element.autocomplete('search', 'test');
          element2.autocomplete('search', 'test');
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value.firstItemResponded,
            true,
            'first item responded',
          );
          browser.assert.equal(
            result.value.secondItemResponded,
            true,
            'second item responded',
          );
        },
      );
  },
  '.replaceWith()': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          const element = jQuery('#autocomplete').autocomplete();
          const replacement = '<div>test</div>';
          // Remove the visually-hidden assistive tech span added by Drupal
          // autocomplete.
          jQuery('#assistive-hint-0').remove();
          const parent = element.parent();
          element.replaceWith(replacement);
          return parent.html().toLowerCase().trim() === replacement;
        },
        [],
        (result) => {
          browser.assert.equal(result.value, true, 'replaceWith() works');
        },
      );
  },
  'Search if the user retypes the same value': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (done) {
          const toReturn = {};
          const element = jQuery('#autocomplete').autocomplete({
            source: ['java', 'javascript'],
            delay: 0,
          });
          const menu = element.autocomplete('instance').menu.element;

          // In the jQuery version of this test, the search was triggered via
          // `element.val('j').simulate('keydown')`. This needs to be changed
          // due to Drupal autocomplete listening to the 'input' event, which
          // is not supported by simulate().
          element.autocomplete('search', 'j');
          setTimeout(() => {
            toReturn.menuDisplaysInitially = menu.is(':visible');
            element.trigger('blur');
            toReturn.menuHiddenAfterBlur = !menu.is(':visible');
            element.autocomplete('search', 'j');
            setTimeout(() => {
              toReturn.displaysAfterSameValue = menu.is(':visible');
              done(toReturn);
            });
          });
        },
        [],
        (result) => {
          const expectedTrue = {
            menuDisplaysInitially: 'menu displays initially',
            menuHiddenAfterBlur: 'menu hidden after blur',
            displaysAfterSameValue: 'menu displays after typing same value',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  'Close on click outside when focus remains': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (done) {
          const toReturn = {};
          const element = jQuery('#autocomplete').autocomplete({
            source: ['java', 'javascript'],
            delay: 0,
          });
          const menu = element.autocomplete('widget');

          jQuery('body').on('mousedown', (event) => {
            event.preventDefault();
          });
          element.val('j').autocomplete('search', 'j');
          setTimeout(() => {
            toReturn.menuDisplaysInitially = menu.is(':visible');
            jQuery('body').simulate('mousedown');
            setTimeout(() => {
              toReturn.menuClosedAfterClickingElseWhere = menu.is(':hidden');
              done(toReturn);
            });
          });
        },
        [],
        (result) => {
          const expectedTrue = {
            menuDisplaysInitially: 'menu displays initially',
            menuClosedAfterClickingElseWhere:
              'menu closed after clicking elsewhere',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  'All events': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (settingsArray, done) {
          const usingA11yAutocomplete =
            Drupal.hasOwnProperty('Autocomplete') &&
            Drupal.Autocomplete.hasOwnProperty('instances');
          const toReturn = {};
          const data = [
            'Clojure',
            'COBOL',
            'ColdFusion',
            'Java',
            'JavaScript',
            'Scala',
            'Scheme',
          ];

          function createTestingAutocomplete(settings, index) {
            toReturn[settings.type] = {};
            toReturn[settings.type].index = index;
            toReturn[settings.type].justASelector = settings.selector;
            toReturn[settings.type].element = jQuery(settings.selector);
            toReturn[settings.type].menu = {};
            toReturn[settings.type].element.autocomplete({
              autoFocus: false,
              delay: 0,
              source: data,
              search(event) {
                // toReturn[settings.type].originalEventIsKeydown = true;
                toReturn[settings.type].originalEventIsKeydown =
                  event.originalEvent.type === 'keydown';
              },
              response(event, ui) {
                // Stringify ui.content to avoid side effects of calling splice
                // immediately after.
                const uiContent = JSON.parse(JSON.stringify(ui.content));
                toReturn[settings.type].responseUiContent = uiContent;
                ui.content.splice(0, 1);
              },
              open() {
                toReturn[settings.type].menuOpenOnOpen = toReturn[
                  settings.type
                ].menu.is(':visible');
              },
              focus(event, ui) {
                toReturn[settings.type].focusOriginalEvent =
                  event.originalEvent.type === 'menufocus';
                toReturn[settings.type].uiItemOnFocus = ui.item;
                event.target.classList.add('focus-event-completed');
              },
              close(event) {
                toReturn[settings.type].closeOriginalEvent =
                  event.originalEvent.type === 'menuselect';
                toReturn[settings.type].menuClosedOnClosed = toReturn[
                  settings.type
                ].menu.is(':hidden');
              },
              select(event, ui) {
                toReturn[settings.type].selectOriginalEvent =
                  event.originalEvent.type === 'menuselect';
                toReturn[settings.type].selectUiItem = ui.item;
              },
              change(event, ui) {
                toReturn[settings.type].changeOriginalEvent =
                  event.originalEvent.type === 'blur';
                toReturn[settings.type].changeUiItem = ui.item;
                toReturn[settings.type].menuClosedOnChange = toReturn[
                  settings.type
                ].menu.is(':hidden');
              },
            });
            toReturn[settings.type].menu = toReturn[
              settings.type
            ].element.autocomplete('widget');

            // With Drupal autocomplete, triggering a search does not
            // happen with keydown.
            if (usingA11yAutocomplete) {
              toReturn[settings.type].usingDrupal = true;
              toReturn[settings.type].element.autocomplete('search', 'j');
            } else {
              toReturn[settings.type].element
                .simulate('focus')
                [settings.valueMethod]('j')
                .trigger('keydown');
            }

            setTimeout(() => {
              toReturn[settings.type].menuVisibleAfterDelay = toReturn[
                settings.type
              ].menu.is(':visible');
              toReturn[settings.type].element[0].dispatchEvent(
                new KeyboardEvent('keydown', {
                  keyCode: jQuery.ui.keyCode.DOWN,
                  cancelable: true,
                }),
              );
              setTimeout(() => {
                // The jQuery tests simulated typing enter on the input, but
                // this is changed to the list due to how Drupal autocomplete
                // listens to UI events. This is actually a more accurate
                // accurate simulation of what happens in the UI.
                toReturn[settings.type].menu[0].dispatchEvent(
                  new KeyboardEvent('keydown', {
                    keyCode: jQuery.ui.keyCode.ENTER,
                    cancelable: true,
                  }),
                );
                setTimeout(() => {
                  toReturn[settings.type].element.simulate('blur');

                  if (index === 2) {
                    setTimeout(() => {
                      done(toReturn);
                    }, 1000);
                  } else {
                    setTimeout(() => {
                      createTestingAutocomplete(
                        settingsArray[index + 1],
                        index + 1,
                      );
                    });
                  }
                });
              });
            });
          }
          createTestingAutocomplete(settingsArray[0], 0);
        },
        [
          [
            {
              type: 'input',
              selector: '#autocomplete',
              valueMethod: 'val',
            },
            {
              type: 'contenteditable',
              selector: '#autocomplete-contenteditable',
              valueMethod: 'text',
            },
            {
              type: 'textarea',
              selector: '#autocomplete-textarea',
              valueMethod: 'val',
            },
          ],
        ],
        (result) => {
          const expectedTrue = {
            originalEventIsKeydown: 'search originalEvent',
            menuOpenOnOpen: 'menu open on open',
            focusOriginalEvent: 'focus originalEvent',
            closeOriginalEvent: 'close originalEvent',
            menuClosedOnClosed: 'menu closed on close',
            selectOriginalEvent: 'select originalEvent',
            changeOriginalEvent: 'change originalEvent',
            menuClosedOnChange: 'menu closed on change',
            menuVisibleAfterDelay: 'menu is visible after delay',
          };
          ['input', 'contenteditable', 'textarea'].forEach((type) => {
            Object.keys(expectedTrue).forEach((property) => {
              browser.assert.equal(
                result.value[type][property],
                true,
                `${type}: ${expectedTrue[property]}`,
              );
            });

            ['uiItemOnFocus', 'selectUiItem', 'changeUiItem'].forEach(
              (property) => {
                browser.assert.deepEqual(
                  result.value[type][property],
                  { label: 'Java', value: 'Java' },
                  `${type}: ${property} property`,
                );
              },
            );

            browser.assert.deepEqual(
              result.value[type].responseUiContent,
              [
                { label: 'Clojure', value: 'Clojure' },
                { label: 'Java', value: 'Java' },
                { label: 'JavaScript', value: 'JavaScript' },
              ],
              `${type}: response ui.content`,
            );
          });
        },
      );
  },
  'change without selection': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        function (done) {
          const data = [
            'Clojure',
            'COBOL',
            'ColdFusion',
            'Java',
            'JavaScript',
            'Scala',
            'Scheme',
          ];
          const element = jQuery('#autocomplete').autocomplete({
            delay: 0,
            source: data,
            change(event, ui) {
              done(ui.item === null);
            },
          });
          element[0].dispatchEvent(new FocusEvent('focus'));
          element.val('ja');
          element[0].dispatchEvent(new FocusEvent('blur'));
        },
        [],
        (result) => {
          browser.assert.equal(result.value, true, 'item is null');
        },
      );
  },
  'cancel search': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        function (done) {
          const usingA11yAutocomplete =
            Drupal.hasOwnProperty('Autocomplete') &&
            Drupal.Autocomplete.hasOwnProperty('instances');
          const toReturn = {};
          const data = [
            'Clojure',
            'COBOL',
            'ColdFusion',
            'Java',
            'JavaScript',
            'Scala',
            'Scheme',
          ];
          let first = true;
          const element = jQuery('#autocomplete').autocomplete({
            delay: 0,
            source: data,
            search() {
              if (first) {
                toReturn.valOnFirstSearch = element.val() === 'ja';
                first = false;
                return false;
              }
              toReturn.valOnSecondSearch = element.val() === 'java';
            },
            open() {
              toReturn.menuOpened = true;
            },
          });
          const menu = element.autocomplete('widget');
          // With Drupal autocomplete, triggering a search does not
          // happen with keydown.
          if (usingA11yAutocomplete) {
            element.autocomplete('search', 'ja');
          } else {
            element.val('ja').trigger('keydown');
          }

          setTimeout(() => {
            toReturn.menuHiddenAfterFirstSearch = menu.is(':hidden');
            if (usingA11yAutocomplete) {
              element.autocomplete('search', 'java');
            } else {
              element.val('java').trigger('keydown');
            }
            setTimeout(() => {
              toReturn.menuVisibleAfterSecondSearch = menu.is(':visible');
              toReturn.numberMenuItems =
                menu.find('.ui-menu-item').length === 2;
              done(toReturn);
            });
          });
        },
        [],
        (result) => {
          const expectedTrue = {
            valOnFirstSearch: 'val on first search',
            menuHiddenAfterFirstSearch: 'menu is hidden after first search',
            valOnSecondSearch: 'val on second search',
            menuVisibleAfterSecondSearch: 'menu is visible after second search',
            menuOpened: 'menu opened',
            numberMenuItems: '# of menu items',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  'cancel focus': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        function (done) {
          const data = [
            'Clojure',
            'COBOL',
            'ColdFusion',
            'Java',
            'JavaScript',
            'Scala',
            'Scheme',
          ];
          const customVal = 'custom value';
          const element = jQuery('#autocomplete').autocomplete({
            delay: 0,
            source: data,
            focus() {
              jQuery(this).val(customVal);
              return false;
            },
          });
          element.autocomplete('search', 'ja');
          setTimeout(() => {
            element.simulate('keydown', { keyCode: jQuery.ui.keyCode.DOWN });
            setTimeout(() => {
              done(element.val() === customVal);
            });
          });
        },
        [],
        (result) => {
          browser.assert.equal(result.value, true, 'focus cancelled');
        },
      );
  },
  'cancel select': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names
        function (done) {
          const usingA11yAutocomplete =
            Drupal.hasOwnProperty('Autocomplete') &&
            Drupal.Autocomplete.hasOwnProperty('instances');
          const data = [
            'Clojure',
            'COBOL',
            'ColdFusion',
            'Java',
            'JavaScript',
            'Scala',
            'Scheme',
          ];
          const customVal = 'custom value';
          const element = jQuery('#autocomplete').autocomplete({
            delay: 0,
            source: data,
            select() {
              jQuery(this).val(customVal);
              return false;
            },
          });
          if (usingA11yAutocomplete) {
            element.autocomplete('search', 'ja');
          } else {
            element.val('ja').trigger('keydown');
          }
          setTimeout(() => {
            element.simulate('keydown', { keyCode: jQuery.ui.keyCode.DOWN });
            // Events don't translate from input to list items with Drupal
            // autocomplete, so the keydown happens directly to the focused
            // element.
            if (usingA11yAutocomplete) {
              jQuery(document.activeElement).simulate('keydown', {
                keyCode: jQuery.ui.keyCode.ENTER,
              });
            } else {
              element.simulate('keydown', { keyCode: jQuery.ui.keyCode.ENTER });
            }

            setTimeout(() => {
              done(element.val() === customVal);
            });
          });
        },
        [],
        (result) => {
          browser.assert.equal(result.value, true, 'select cancelled');
        },
      );
  },
  'blur during remote search': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names
        function (done) {
          const toReturn = {};
          const ac = jQuery('#autocomplete').autocomplete({
            delay: 0,
            source(request, response) {
              toReturn.triggerRequest = true; // trigger request
              ac.simulate('blur');
              setTimeout(() => {
                response(['result']);
                done(toReturn);
              }, 25);
            },
            open() {
              toReturn.openedAfterBlur = true;
            },
          });
          ac.autocomplete('search', 'ro');
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value.hasOwnProperty('triggerRequest'),
            true,
            'request was triggered ',
          );
          browser.assert.equal(
            !result.value.hasOwnProperty('openedAfterBlur'),
            true,
            'did not open after a blur',
          );
        },
      );
  },
  'search, close': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names
        function () {
          const toReturn = {};
          const data = [
            'c++',
            'java',
            'php',
            'coldfusion',
            'javascript',
            'asp',
            'ruby',
            'python',
            'c',
            'scala',
            'groovy',
            'haskell',
            'perl',
          ];
          const element = jQuery('#autocomplete').autocomplete({
            source: data,
            minLength: 0,
          });
          const menu = element.autocomplete('widget');
          toReturn.menuHiddenOnInit = menu.is(':hidden');
          element.autocomplete('search');
          toReturn.menuVisibleAfterSearch = menu.is(':visible');
          toReturn.allItemsForABlankSearch =
            menu.find('.ui-menu-item').length === data.length;

          element.val('has').autocomplete('search');
          toReturn.oneItemForSetInputValue =
            menu.find('.ui-menu-item').text() === 'haskell';

          element.autocomplete('search', 'ja');
          toReturn.onlyJavaAndJavaScriptForJa =
            menu.find('.ui-menu-item').length === 2;

          element.autocomplete('close');
          toReturn.menuHiddenAfterClose = menu.is(':hidden');
          return toReturn;
        },
        [],
        (result) => {
          const expectedTrue = {
            menuVisibleAfterSearch: 'menu is visible after search',
            allItemsForABlankSearch: 'all items for a blank search',
            oneItemForSetInputValue: 'only one item for set input value',
            menuHiddenOnInit: 'menu hidden on init',
            onlyJavaAndJavaScriptForJa: "only java and javascript for 'ja'",
            menuHiddenAfterClose: 'menu is hidden after close',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  'widget method': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names
        function () {
          const toReturn = {};
          const element = jQuery('#autocomplete').autocomplete();
          const widgetElement = element.autocomplete('widget');
          toReturn.oneElement = widgetElement.length === 1;
          toReturn.uiMenuClass = widgetElement.hasClass('ui-menu');
          return toReturn;
        },
        [],
        (result) => {
          const expectedTrue = {
            oneElement: 'one element',
            uiMenuClass: 'ui-menu',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  'appendTo: null': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names
        function () {
          const element = jQuery('#autocomplete').autocomplete();
          return element.autocomplete('widget').parent()[0] === document.body;
        },
        [],
        (result) => {
          browser.assert.equal(result.value, true, 'defaults to body');
        },
      );
  },
  'appendTo: explicit': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names
        function () {
          const toReturn = {};
          const detached = jQuery('<div>');
          const element = jQuery('#autocomplete');

          element.autocomplete({
            appendTo: '.autocomplete-wrap',
          });
          toReturn.firstFoundElement =
            element.autocomplete('widget').parent()[0] ===
            jQuery('#autocomplete-wrap1')[0];

          toReturn.onlyAppendsToOne =
            jQuery('#autocomplete-wrap2 .ui-autocomplete').length === 0;

          element.autocomplete('destroy');

          element
            .autocomplete()
            .autocomplete('option', 'appendTo', '#autocomplete-wrap1');
          toReturn.modifiedAfterInit =
            element.autocomplete('widget').parent()[0] ===
            jQuery('#autocomplete-wrap1')[0];

          element.autocomplete('destroy');
          element.autocomplete({
            appendTo: detached,
          });

          toReturn.detachedJqueryObject =
            element.autocomplete('widget').parent()[0] === detached[0];

          element.autocomplete('destroy');

          element.autocomplete({
            appendTo: detached[0],
          });
          toReturn.detachedDomElement =
            element.autocomplete('widget').parent()[0] === detached[0];

          element.autocomplete('destroy');

          element.autocomplete().autocomplete('option', 'appendTo', detached);
          toReturn.detachedViaOption =
            element.autocomplete('widget').parent()[0] === detached[0];

          return toReturn;
        },
        [],
        (result) => {
          const expectedTrue = {
            firstFoundElement: 'first found element',
            onlyAppendsToOne: 'only appends to one element',
            modifiedAfterInit: 'modified after init',
            detachedJqueryObject: 'detached jQuery object',
            detachedDomElement: 'detached DOM element',
            detachedViaOption: 'detached DOM element via option()',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  'appendTo: ui-front': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names
        function () {
          const toReturn = {};
          const element = jQuery('#autocomplete');

          jQuery('#autocomplete-wrap2').addClass('ui-front');
          element.autocomplete();
          toReturn.nullInsideUiFront =
            element.autocomplete('widget').parent()[0] ===
            jQuery('#autocomplete-wrap2')[0];
          element.autocomplete('destroy');

          element.autocomplete({
            appendTo: jQuery(),
          });
          toReturn.emptyObjectInsideUiFront =
            element.autocomplete('widget').parent()[0] ===
            jQuery('#autocomplete-wrap2')[0];

          return toReturn;
        },
        [],
        (result) => {
          const expectedTrue = {
            nullInsideUiFront: 'null, inside .ui-front',
            emptyObjectInsideUiFront:
              'empty jQuery object, inside .ui-front',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  'autoFocus: false': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names
        function (done) {
          const usingA11yAutocomplete =
            Drupal.hasOwnProperty('Autocomplete') &&
            Drupal.Autocomplete.hasOwnProperty('instances');
          const data = [
            'c++',
            'java',
            'php',
            'coldfusion',
            'javascript',
            'asp',
            'ruby',
            'python',
            'c',
            'scala',
            'groovy',
            'haskell',
            'perl',
          ];
          const element = jQuery('#autocomplete').autocomplete({
            autoFocus: false,
            delay: 0,
            source: data,
            open() {
              done(
                element
                  .autocomplete('widget')
                  .find('.ui-menu-item-wrapper.ui-state-active').length === 0,
              );
            },
          });
          if (usingA11yAutocomplete) {
            element.autocomplete('search', 'ja');
          } else {
            element.val('ja').trigger('keydown');
          }
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            true,
            'first item is not auto focused',
          );
        },
      );
  },
  'autoFocus: true': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names
        function (done) {
          const usingA11yAutocomplete =
            Drupal.hasOwnProperty('Autocomplete') &&
            Drupal.Autocomplete.hasOwnProperty('instances');
          const data = [
            'c++',
            'java',
            'php',
            'coldfusion',
            'javascript',
            'asp',
            'ruby',
            'python',
            'c',
            'scala',
            'groovy',
            'haskell',
            'perl',
          ];
          const element = jQuery('#autocomplete').autocomplete({
            autoFocus: true,
            delay: 0,
            source: data,
            open() {
              setTimeout(() => {
                done(
                  element
                    .autocomplete('widget')
                    .find('.ui-menu-item-wrapper.ui-state-active').length === 1,
                );
              });
            },
          });
          if (usingA11yAutocomplete) {
            element.autocomplete('search', 'ja');
          } else {
            element.val('ja').trigger('keydown');
          }
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value,
            true,
            'first item is auto focused',
          );
        },
      );
  },
  delay: (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names
        function (done) {
          const toReturn = {};
          const usingA11yAutocomplete =
            Drupal.hasOwnProperty('Autocomplete') &&
            Drupal.Autocomplete.hasOwnProperty('instances');
          const data = [
            'c++',
            'java',
            'php',
            'coldfusion',
            'javascript',
            'asp',
            'ruby',
            'python',
            'c',
            'scala',
            'groovy',
            'haskell',
            'perl',
          ];
          const element = jQuery('#autocomplete').autocomplete({
            source: data,
            delay: 25,
          });
          const menu = element.autocomplete('widget');
          if (usingA11yAutocomplete) {
            element.val('ja');
            element[0].dispatchEvent(new KeyboardEvent('input'));
          } else {
            element.val('ja').trigger('keydown');
          }

          toReturn.menuClosedImmediatelyAfterSearch = menu.is(':hidden');

          setTimeout(function () {
            toReturn.menuIsOpenAfterDelay = menu.is(':visible');
            done(toReturn);
          }, 150);
        },
        [],
        (result) => {
          const expectedTrue = {
            menuClosedImmediatelyAfterSearch:
              'menu is closed immediately after search',
            menuIsOpenAfterDelay: 'menu is open after delay',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  disabled: (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names
        function (done) {
          const toReturn = {};
          const usingA11yAutocomplete =
            Drupal.hasOwnProperty('Autocomplete') &&
            Drupal.Autocomplete.hasOwnProperty('instances');
          const data = [
            'c++',
            'java',
            'php',
            'coldfusion',
            'javascript',
            'asp',
            'ruby',
            'python',
            'c',
            'scala',
            'groovy',
            'haskell',
            'perl',
          ];
          const element = jQuery('#autocomplete').autocomplete({
            source: data,
            delay: 0,
          });
          const menu = element.autocomplete('disable').autocomplete('widget');
          element.val('ja').trigger('keydown');
          toReturn.menuIsHidden = menu.is(':hidden');
          toReturn.noUiStateDisabled = !element.hasClass('ui-state-disabled');
          toReturn.uiAutocompleteDisabled = menu.hasClass(
            'ui-autocomplete-disabled',
          );
          toReturn.noAriaDisabled = !element.attr('aria-disabled');

          setTimeout(function () {
            toReturn.menuStillHidden = menu.is(':hidden');

            done(toReturn);
          });
        },
        [],
        (result) => {
          const expectedTrue = {
            menuIsHidden: 'menu is hidden',
            noUiStateDisabled: 'ui-state-disabled',
            uiAutocompleteDisabled: 'ui-autocomplete-disabled',
            noAriaDisabled: 'aria-disabled',
            menuStillHidden: 'menu still hidden',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  minLength: (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names
        function () {
          const data = [
            'c++',
            'java',
            'php',
            'coldfusion',
            'javascript',
            'asp',
            'ruby',
            'python',
            'c',
            'scala',
            'groovy',
            'haskell',
            'perl',
          ];
          const toReturn = {};
          const element = jQuery('#autocomplete').autocomplete({
            source: data,
          });
          const menu = element.autocomplete('widget');
          element.autocomplete('search', '');
          toReturn.menuIsHidden = menu.is(':hidden');
          element.autocomplete('option', 'minLength', 0);
          element.autocomplete('search', '');
          toReturn.menuIsVisible = menu.is(':visible');
          return toReturn;
        },
        [],
        (result) => {
          const expectedTrue = {
            menuIsHidden: 'blank not enough for minLength: 1',
            menuIsVisible: 'blank enough for minLength: 0',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  'minLength, exceed then drop below': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names
        function (done) {
          const toReturn = {};
          const element = jQuery('#autocomplete').autocomplete({
            minLength: 2,
            source(req, res) {
              toReturn.correctSearchTerm = req.term === '12';
              setTimeout(() => {
                res(['item']);
              });
            },
          });
          const menu = element.autocomplete('widget');
          toReturn.menuIsHiddenFirst = menu.is(':hidden');
          element.autocomplete('search', '12');
          toReturn.menuIsHiddenSecond = menu.is(':hidden');
          element.autocomplete('search', '1');

          setTimeout(() => {
            toReturn.menuHiddenAfterSearches = menu.is(':hidden');
            done(toReturn);
          });
        },
        [],
        (result) => {
          const expectedTrue = {
            menuIsHiddenFirst: 'menu is hidden before first search',
            menuIsHiddenSecond: 'menu is hidden before second search',
            menuHiddenAfterSearches: 'menu is hidden after searches',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  'minLength, exceed then drop below then exceed': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names
        function () {
          const toReturn = {};
          const _res = [];
          const element = jQuery('#autocomplete').autocomplete({
            minLength: 2,
            source(req, res) {
              _res.push(res);
            },
          });
          const menu = element.autocomplete('widget');

          // Trigger a valid search
          toReturn.menuIsHiddenFirst = menu.is(':hidden');
          element.autocomplete('search', '12');
          toReturn.menuIsHiddenSecond = menu.is(':hidden');
          element.autocomplete('search', '1');

          // Trigger a valid search
          element.autocomplete('search', '13');

          // React as if the first search was cancelled (default ajax behavior)
          _res[0]([]);

          // React to second search
          _res[1](['13']);

          toReturn.menuVisibleAfterSearch = menu.is(':visible');
          return toReturn;
        },
        [],
        (result) => {
          const expectedTrue = {
            menuIsHiddenFirst: 'menu is hidden before first search',
            menuIsHiddenSecond: 'menu is hidden before second search',
            menuVisibleAfterSearch: 'menu is visible after searches',
          };
          Object.keys(expectedTrue).forEach((property) => {
            browser.assert.equal(
              result.value[property],
              true,
              expectedTrue[property],
            );
          });
        },
      );
  },
  'source, local string array': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names
        function () {
          const data = [
            'c++',
            'java',
            'php',
            'coldfusion',
            'javascript',
            'asp',
            'ruby',
            'python',
            'c',
            'scala',
            'groovy',
            'haskell',
            'perl',
          ];
          const element = jQuery('#autocomplete').autocomplete({
            source: data,
          });
          const menu = element.autocomplete('widget');

          element.val('ja').autocomplete('search');
          return menu.find('.ui-menu-item').text() === 'javajavascript';
        },
        [],
        (result) => {
          browser.assert.equal(result.value, true, 'source from string array');
        },
      );
  },
  'source, local object array, only labels': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (sourceTestAsString) {
          // eslint-disable-next-line no-new-func
          const sourceTestFn = new Function(`return ${sourceTestAsString}`)();
          return sourceTestFn([
            { label: 'java', value: null },
            { label: 'php', value: null },
            { label: 'coldfusion', value: '' },
            { label: 'javascript', value: '' },
            { label: 'clojure' },
          ]);
        },
        [sourceTest.toString()],
        (result) => {
          browser.assert.equal(
            result.value.errors.length === 0,
            true,
            result.value.errors.length === 0
              ? 'source has expected values'
              : result.value.errors.join(', '),
          );
        },
      );
  },
  'source, local object array, only values': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (sourceTestAsString) {
          // eslint-disable-next-line no-new-func
          const sourceTestFn = new Function(`return ${sourceTestAsString}`)();
          return sourceTestFn([
            { value: 'java', label: null },
            { value: 'php', label: null },
            { value: 'coldfusion', label: '' },
            { value: 'javascript', label: '' },
            { value: 'clojure' },
          ]);
        },
        [sourceTest.toString()],
        (result) => {
          browser.assert.equal(
            result.value.errors.length === 0,
            true,
            result.value.errors.length === 0
              ? 'source has expected values'
              : result.value.errors.join(', '),
          );
        },
      );
  },
  'source, url string with remote json string array': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (sourceTestAsString, done) {
          // eslint-disable-next-line no-new-func
          const sourceTestFn = new Function(`return ${sourceTestAsString}`)();
          sourceTestFn(
            `core/tests/Drupal/Nightwatch/Pages/autocomplete_remote_string_array.txt`,
            true,
            done,
          );
        },
        [sourceTest.toString()],
        (result) => {
          browser.assert.equal(
            result.value.errors.length === 0,
            true,
            result.value.errors.length === 0
              ? 'source has expected values'
              : result.value.errors.join(', '),
          );
        },
      );
  },
  'source, url string with remote json object array': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (sourceTestAsString, done) {
          // eslint-disable-next-line no-new-func
          const sourceTestFn = new Function(`return ${sourceTestAsString}`)();
          sourceTestFn(
            `core/tests/Drupal/Nightwatch/Pages/autocomplete_remote_object_array_values.txt`,
            true,
            done,
          );
        },
        [sourceTest.toString()],
        (result) => {
          browser.assert.equal(
            result.value.errors.length === 0,
            true,
            result.value.errors.length === 0
              ? 'source has expected values'
              : result.value.errors.join(', '),
          );
        },
      );
  },
  'source, url string with remote json object array, only label properties': (
    browser,
  ) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .executeAsync(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (sourceTestAsString, done) {
          // eslint-disable-next-line no-new-func
          const sourceTestFn = new Function(`return ${sourceTestAsString}`)();
          sourceTestFn(
            `core/tests/Drupal/Nightwatch/Pages/autocomplete_remote_object_array_labels.txt`,
            true,
            done,
          );
        },
        [sourceTest.toString()],
        (result) => {
          browser.assert.equal(
            result.value.errors.length === 0,
            true,
            result.value.errors.length === 0
              ? 'source has expected values'
              : result.value.errors.join(', '),
          );
        },
      );
  },
  'source, custom': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function (sourceTestAsString) {
          // eslint-disable-next-line no-new-func
          const sourceTestFn = new Function(`return ${sourceTestAsString}`)();
          let requestTerm = '';
          // eslint-disable-next-line func-names
          const sourceTestResults = sourceTestFn(function (request, response) {
            requestTerm = request.term;
            response([
              'java',
              { label: 'javascript', value: null },
              { value: 'clojure', label: null },
            ]);
          });
          sourceTestResults.requestTermJ = requestTerm === 'j';

          return sourceTestResults;
        },
        [sourceTest.toString()],
        (result) => {
          browser.assert.equal(
            result.value.errors.length === 0,
            true,
            result.value.errors.length === 0
              ? 'source has expected values'
              : result.value.errors.join(', '),
          );
          browser.assert.equal(
            result.value.requestTermJ,
            true,
            "'request term is 'j'",
          );
        },
      );
  },
  'source, update after init': (browser) => {
    browser
      .drupalRelativeURL('/autocomplete-shim-test')
      .waitForElementPresent('#autocomplete-wrap1', 1000)
      .execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback
        function () {
          const toReturn = {};
          const element = jQuery('#autocomplete').autocomplete({
            source: ['java', 'javascript', 'haskell'],
          });
          const menu = element.autocomplete('widget');
          element.val('ja').autocomplete('search');
          toReturn.expectedItems1 =
            menu.find('.ui-menu-item').text() === 'javajavascript';
          element.autocomplete('option', 'source', ['php', 'asp']);
          element.val('ph').autocomplete('search');
          toReturn.expectedItems2 = menu.find('.ui-menu-item').text() === 'php';
          return toReturn;
        },
        [],
        (result) => {
          browser.assert.equal(
            result.value.expectedItems1,
            true,
            'first search returns javajavascript',
          );
          browser.assert.equal(
            result.value.expectedItems2,
            true,
            'second search returns php',
          );
        },
      );
  },
};
