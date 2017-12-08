const chromedriver = require('chromedriver');

const testingMode = process.env.TESTING_MODE || 'local';

if (testingMode === 'local') {
  module.exports = {
    before: (done) => {
      chromedriver.start();
      done();
    },
    after: (done) => {
      chromedriver.stop();
      done();
    },
  };
}
else {
  module.exports = {
    before: (done) => {
      done();
    },
    after: (done) => {
      done();
    },
  };
}
