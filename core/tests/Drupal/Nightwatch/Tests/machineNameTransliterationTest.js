// cSpell:disable
const MachineNameTestArray = [
  {
    machineName: 'Bob',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'bob',
  },
  {
    machineName: 'Äwesome',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'awesome',
  },
  {
    machineName: 'B?!"@\\/-ob@e',
    replacePattern: 'a-zA-Z0-9_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'b_ob_e',
  },
  {
    machineName: 'Bob@e\\0',
    replacePattern: 'a-zA-Z0-9_.~@',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'bob@e_0',
  },
  {
    machineName: 'Bobby',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'bobby',
  },
  {
    machineName: 'ǍǎǏ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'aai',
  },
  // The expected machine name are modified because we don't have
  // the removeDiacritics() function present in PhpTranliteration.php.
  {
    machineName: 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'aaaaaaaeceeeeiiii',
  },
  {
    machineName: 'ÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞß',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'dnoooooxouuuuuthss',
  },
  {
    machineName: 'àáâãäåæçèéêëìíîï',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'aaaaaaaeceeeeiiii',
  },
  {
    machineName: 'ðñòóôõö÷øùúûüýþÿ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'dnooooo_ouuuuythy',
  },
  {
    machineName: 'ĀāĂăĄąĆćĈĉĊċČčĎď',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'aaaaaaccccccccdd',
  },
  {
    machineName: 'ĐđĒēĔĕĖėĘęĚěĜĝĞğ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'ddeeeeeeeeeegggg',
  },
  {
    machineName: 'ĠġĢģĤĥĦħĨĩĪīĬĭĮį',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'gggghhhhiiiiiiii',
  },
  {
    machineName: 'İıĲĳĴĵĶķĸĹĺĻļĽľĿ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'iiijijjjkkklllllll',
  },
  {
    machineName: 'ŀŁłŃńŅņŇňŉŊŋŌōŎŏ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'lllnnnnnn_nngngoooo',
  },
  {
    machineName: 'ŐőŒœŔŕŖŗŘřŚśŜŝŞş',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'oooeoerrrrrrssssss',
  },
  {
    machineName: 'ŠšŢţŤťŦŧŨũŪūŬŭŮů',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'ssttttttuuuuuuuu',
  },
  {
    machineName: 'ŰűŲųŴŵŶŷŸŹźŻżŽž',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'uuuuwwyyyzzzzzz',
  },
  {
    machineName: 'ǐǑǒǓǔǕǖǗǘǙǚǛǜǝǞǟ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'ioouuuuuuuuuu_aa',
  },
  {
    machineName: 'ǠǡǢǣǤǥǦǧǨǩǪǫǬǭǮǯ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'aaaeaeggggkkoooozhzh',
  },
  {
    machineName: 'ǰǱǲǳǴǵǶǷǸǹǺǻǼǽǾǿ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'jdzddzgghvwnnaaaeaeoo',
  },
  {
    machineName: 'ȀȁȂȃȄȅȆȇȈȉȊȋȌȍȎȏ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'aaaaeeeeiiiioooo',
  },
  {
    machineName: 'ȐȑȒȓȔȕȖȗȘșȚțȜȝȞȟ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'rrrruuuussttyyhh',
  },
  {
    machineName: 'ȠȡȢȣȤȥȦȧȨȩȪȫȬȭȮȯ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'ndououzzaaeeoooooo',
  },
  {
    machineName: 'ȰȱȲȳȴȵȶȷȸȹȺȻȼȽȾȿ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'ooyylntjdbqpacclts',
  },
  {
    machineName: 'ɀɁɂɃɄɅɆɇɈɉɊɋɌɍɎɏ',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 64,
    expectedMachineName: 'z_buveejjqqrryy',
  },
  // Test for maximum length of machine-name
  {
    machineName: 'This is the test for max length',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 20,
    expectedMachineName: 'this_is_the_test_for',
  },
  {
    machineName: 'Ma@Chi!~',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '_',
    maxlength: 5,
    expectedMachineName: 'ma_ch',
  },
  {
    machineName: 'Test for custom replace character',
    replacePattern: 'a-zA-Z0-9-_.~',
    replaceChar: '-',
    maxlength: 64,
    expectedMachineName: 'test-for-custom-replace-character',
  },
  // cSpell:enable
];
module.exports = {
  before(browser) {
    browser.drupalInstall().drupalLoginAsAdmin(() => {
      browser
        .drupalRelativeURL('/admin/modules')
        .setValue('input[type="search"]', 'FormAPI')
        .waitForElementVisible('input[name="modules[form_test][enable]"]', 1000)
        .click('input[name="modules[form_test][enable]"]')
        .click('input[type="submit"]') // Submit module form.
        .click('input[type="submit"]'); // Confirm installation of dependencies.
    });
  },
  after(browser) {
    browser.drupalUninstall();
  },
  'Machine name generation test': (browser) => {
    browser.drupalRelativeURL('/form-test/machine-name');
    MachineNameTestArray.forEach((iteration) => {
      browser.execute(
        // eslint-disable-next-line func-names, prefer-arrow-callback, no-shadow
        function (object) {
          return Drupal.behaviors.machineName.transliterate(
            object.machineName,
            {
              replace_pattern: object.replacePattern,
              replace: object.replaceChar,
              maxlength: object.maxlength,
            },
          );
        },
        [iteration],
        (result) => {
          browser.assert.equal(result.value, iteration.expectedMachineName);
        },
      );
    });
  },
};
