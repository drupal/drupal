const chromedriver = require('chromedriver');
const testingMode = process.env.TESTING_MODE || 'local';

if (testingMode === 'local') {
  module.exports = {
    before: function(done) {
      chromedriver.start();
      done();
    },
    after: function(done) {
      chromedriver.stop();
      done();
    }
  };
}
else {
  module.exports = {
    before: function (done) {
      done();
    },
    after: function (done) {
      done();
    }
  }
}
