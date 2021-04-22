import { registerPlugin } from '@wordpress/plugins';
import { warning } from '@wordpress/icons';

import DebuggerSidebar from './components/DebuggerSidebar';
import IndexOptionsPanel from './components/IndexOptionsPanel';

// Register CDP indexing metabox.
registerPlugin( 'gpalab-feeder-panel', {
  icon: null,
  render: IndexOptionsPanel,
} );

// Register debugger sidebar window.
registerPlugin( 'gpalab-feeder-debugger-sidebar', {
  icon: warning,
  render: DebuggerSidebar,
} );
