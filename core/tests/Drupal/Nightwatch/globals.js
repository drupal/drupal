import 'chromedriver' from chromedriver;

module.exports = {
  before: (done) => {
    if (process.env.NODE_ENV !== 'testbot') {
      chromedriver.start();
    }
    done();
  },
  after: (done) => {
    if (process.env.NODE_ENV !== 'testbot') {
      chromedriver.stop();
    }
    done();
  },
};
