import propTypes from 'prop-types';

const Spacer = ( { padding = '0.5rem' } ) => <div style={ { padding } } />;

Spacer.propTypes = {
  padding: propTypes.string,
};

export default Spacer;
