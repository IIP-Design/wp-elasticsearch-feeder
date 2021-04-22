import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

import MetaSelectInput from './MetaSelectInput';

import { i18nize } from '../utils/i18n';

const { languages } = window.gpalabFeederAdmin;

const IndexOptionsPanel = () => (
  <PluginDocumentSettingPanel
    title={ i18nize( 'Publish to Content Commons' ) }
  >
    <MetaSelectInput
      fallback="en-us"
      label={ i18nize( 'Language' ) }
      metaKey="_iip_language"
      options={ languages }
    />
  </PluginDocumentSettingPanel>
);

export default IndexOptionsPanel;
