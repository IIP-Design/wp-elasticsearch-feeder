import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

import { i18nize } from '../utils/i18n';

const IndexOptionsPanel = () => (
  <PluginDocumentSettingPanel
    title={ i18nize( 'Publish to Content Commons' ) }
  >
    Test
  </PluginDocumentSettingPanel>
);

export default IndexOptionsPanel;
