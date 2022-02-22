import propTypes from 'prop-types';

const Spinner = ( { msg = 'Verifying...' } ) => (
  <div className="inside" id="index-spinner">
    <div className="spinner is-active gpalab-spinner-animation">
      <span id="index-spinner-text">{ msg }</span>
    </div>
  </div>
);

Spinner.propTypes = {
  msg: propTypes.string,
};

export default Spinner;
