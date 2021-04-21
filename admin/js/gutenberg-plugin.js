import { registerPlugin } from '@wordpress/plugins';

import IndexOptionsPanel from './components/IndexOptionsPanel';

registerPlugin( 'gpalab-feeder-panel', {
  icon: null,
  render: IndexOptionsPanel,
} );
