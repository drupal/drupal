const burgerButtonSelector = '.admin-toolbar-control-bar__burger';
const sidebarSelector = '.admin-toolbar';
const closeButtonSelector = '.admin-toolbar__close-button';

/**
 * Waits for the sidebar animation to complete by checking that the
 * data-admin-toolbar-animating attribute is removed from the html element.
 *
 * @param {object} browser - Nightwatch Browser object
 */
const waitForSidebarAnimation = (browser) => {
  browser.execute(
    // eslint-disable-next-line func-names, prefer-arrow-callback
    function () {
      return new Promise((resolve) => {
        const checkAnimation = () => {
          if (
            !document.documentElement.hasAttribute(
              'data-admin-toolbar-animating',
            )
          ) {
            resolve(true);
          } else {
            setTimeout(checkAnimation, 50);
          }
        };
        checkAnimation();
      });
    },
    [],
  );
};

module.exports = {
  '@tags': ['core', 'navigation'],
  before(browser) {
    browser
      .drupalInstall()
      .drupalInstallModule('navigation', true)
      // Set mobile viewport width (less than 1024px)
      .setWindowSize(375, 800);
  },
  after(browser) {
    browser.drupalUninstall();
  },

  'Verify focus trap using inert attribute on mobile': (browser) => {
    browser.drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/')
        .waitForElementVisible('body')
        // Verify sidebar is initially closed on mobile
        .assert.attributeEquals(burgerButtonSelector, 'aria-expanded', 'false')
        // Click burger button to open sidebar
        .click(burgerButtonSelector)
        .assert.attributeEquals(burgerButtonSelector, 'aria-expanded', 'true')
        // Wait for animation to complete
        .perform(() => waitForSidebarAnimation(browser))
        // Verify focus trap is active - elements outside toolbar should have inert attribute
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback
          function () {
            // Get all elements that should be inert (everything except toolbar and overlay)
            const allBodyChildren = Array.from(
              document.querySelectorAll('body > *'),
            );
            const inertElements = allBodyChildren.filter(
              (el) =>
                !el.matches('.admin-toolbar') &&
                !el.matches('.admin-toolbar-overlay') &&
                !el.matches('.admin-toolbar-control-bar'),
            );

            // Check if these elements have the inert attribute
            const hasInert = inertElements.every((el) =>
              el.hasAttribute('inert'),
            );
            const hasDataAttr = inertElements.every((el) =>
              el.hasAttribute('data-admin-toolbar-inert'),
            );

            return {
              hasInert,
              hasDataAttr,
              inertCount: inertElements.length,
            };
          },
          [],
          (result) => {
            browser.assert.ok(
              result.value.hasInert,
              'All elements outside toolbar should have inert attribute',
            );
            browser.assert.ok(
              result.value.hasDataAttr,
              'All elements outside toolbar should have data-admin-toolbar-inert attribute',
            );
            browser.assert.ok(
              result.value.inertCount > 0,
              'There should be inert elements on the page',
            );
          },
        )
        // Verify elements inside the toolbar can still receive focus
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback
          function () {
            const toolbar = document.querySelector('.admin-toolbar');
            return !toolbar.hasAttribute('inert');
          },
          [],
          (result) => {
            browser.assert.ok(
              result.value,
              'Toolbar itself should NOT be inert',
            );
          },
        )
        // Verify overlay is not inert
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback
          function () {
            const overlay = document.querySelector('.admin-toolbar-overlay');
            return !overlay.hasAttribute('inert');
          },
          [],
          (result) => {
            browser.assert.ok(result.value, 'Overlay should NOT be inert');
          },
        )
        // Try to focus an element that should be inert and verify it can't be focused
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback
          function () {
            // Find an inert element (like the main content)
            const inertElement = document.querySelector(
              '[data-admin-toolbar-inert]',
            );
            if (!inertElement) {
              return { found: false };
            }

            // Try to find a focusable element within the inert container
            const focusableElement = inertElement.querySelector(
              ':is(audio, button, canvas, details, iframe, input, select, summary, textarea, video, [accesskey], [contenteditable], [href], [tabindex]:not([tabindex*="-"])):not(:is([disabled], [inert]))',
            );
            if (!focusableElement) {
              return { found: false };
            }

            // Try to focus it
            focusableElement.focus();

            // Check if it actually got focus
            const gotFocus = document.activeElement === focusableElement;

            return {
              found: true,
              gotFocus,
              tagName: focusableElement.tagName,
            };
          },
          [],
          (result) => {
            // Only run this assertion if we found a focusable element
            if (result.value.found) {
              browser.assert.ok(
                !result.value.gotFocus,
                `Inert element (${result.value.tagName}) should not be able to receive focus`,
              );
            }
          },
        )
        // Close the sidebar
        .click(closeButtonSelector)
        .assert.attributeEquals(burgerButtonSelector, 'aria-expanded', 'false')
        // Wait for animation to complete
        .perform(() => waitForSidebarAnimation(browser))
        // Verify inert attributes are removed when sidebar closes
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback
          function () {
            const inertElements = document.querySelectorAll(
              '[data-admin-toolbar-inert]',
            );
            return inertElements.length === 0;
          },
          [],
          (result) => {
            browser.assert.ok(
              result.value,
              'All inert attributes should be removed when sidebar is closed',
            );
          },
        );
    });
  },

  'Verify overlay click closes sidebar and removes focus trap': (browser) => {
    browser.drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/')
        .waitForElementVisible('body')
        // Open sidebar
        .click(burgerButtonSelector)
        .assert.attributeEquals(burgerButtonSelector, 'aria-expanded', 'true')
        // Wait for animation to complete
        .perform(() => waitForSidebarAnimation(browser))
        // Verify inert is active
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback
          function () {
            return (
              document.querySelectorAll('[data-admin-toolbar-inert]').length > 0
            );
          },
          [],
          (result) => {
            browser.assert.ok(result.value, 'Inert should be active');
          },
        )
        // Click overlay to close using JavaScript since the element might be behind the sidebar
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback
          function () {
            document.querySelector('.admin-toolbar-overlay').click();
          },
          [],
        )
        .assert.attributeEquals(burgerButtonSelector, 'aria-expanded', 'false')
        // Wait for animation to complete
        .perform(() => waitForSidebarAnimation(browser))
        // Verify inert is removed
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback
          function () {
            return (
              document.querySelectorAll('[data-admin-toolbar-inert]').length ===
              0
            );
          },
          [],
          (result) => {
            browser.assert.ok(
              result.value,
              'Inert should be removed after closing via overlay',
            );
          },
        );
    });
  },

  'Verify focus trap only applies on mobile widths': (browser) => {
    browser.drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/')
        // Resize to desktop width
        .setWindowSize(1280, 800)
        // Wait for sidebar to be visible
        .waitForElementVisible(sidebarSelector, 1000)
        // Verify no inert attributes on desktop
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback
          function () {
            return (
              document.querySelectorAll('[data-admin-toolbar-inert]').length ===
              0
            );
          },
          [],
          (result) => {
            browser.assert.ok(
              result.value,
              'No inert attributes should exist on desktop width',
            );
          },
        )
        // Resize back to mobile
        .setWindowSize(375, 800)
        // Wait for mobile state - burger button should be visible
        .waitForElementVisible(burgerButtonSelector, 1000)
        // Open sidebar on mobile
        .click(burgerButtonSelector)
        .assert.attributeEquals(burgerButtonSelector, 'aria-expanded', 'true')
        // Wait for animation to complete
        .perform(() => waitForSidebarAnimation(browser))
        // Now inert should be active
        .execute(
          // eslint-disable-next-line func-names, prefer-arrow-callback
          function () {
            return (
              document.querySelectorAll('[data-admin-toolbar-inert]').length > 0
            );
          },
          [],
          (result) => {
            browser.assert.ok(
              result.value,
              'Inert attributes should be present on mobile width when sidebar is open',
            );
          },
        );
    });
  },
};
