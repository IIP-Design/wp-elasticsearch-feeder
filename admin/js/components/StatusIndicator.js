import propTypes from 'prop-types';

import { i18nize } from '../utils/i18n';

const style = {
  display: 'flex',
  alignItems: 'center',
  justifyContent: 'space-between',
};

const StatusIndicator = ( { color, title } ) => (
  <div style={ style }>
    <strong>{ `${i18nize( 'Publish Status' )}:` }</strong>

    <div>
      <div
        className={ `sync-status sync-status-${color}` }
        style={ { marginRight: '0.2rem' } }
        title={ title }
      />
      <div className="sync-status-label">
        { title }
      </div>
    </div>
  </div>
);

StatusIndicator.propTypes = {
  color: propTypes.string,
  title: propTypes.string,
};

export default StatusIndicator;
