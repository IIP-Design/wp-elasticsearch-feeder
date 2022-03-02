import { registerPlugin } from '@wordpress/plugins';
import { warning } from '@wordpress/icons';

import DebuggerSidebar from './components/DebuggerSidebar';
import IndexOptionsPanel from './components/IndexOptionsPanel';
import PrePublishConfirm from './components/PrePublishConfirm';

const { visibleMeta } = window.gpalabFeederAdmin;

// Register CDP indexing metabox.
registerPlugin( 'gpalab-feeder-panel', {
  icon: null,
  render: IndexOptionsPanel,
} );

// Add CDP indexing metabox check on pre-publication slide-out.
registerPlugin( 'gpalab-feeder-publish-confirm', {
  icon: null,
  render: PrePublishConfirm,
} );

if ( visibleMeta?.debugger ) {
  // Register debugger sidebar window.
  registerPlugin( 'gpalab-feeder-debugger-sidebar', {
    icon: warning,
    render: DebuggerSidebar,
  } );
}
