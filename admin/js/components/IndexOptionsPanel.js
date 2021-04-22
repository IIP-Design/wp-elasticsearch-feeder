import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

import MetaSelectInput from './MetaSelectInput';
import MetaRadioControl from './MetaRadioControl';
import StatusIndicator from './StatusIndicator';

import { i18nize } from '../utils/i18n';

const { languages, owners, syncStatus } = window.gpalabFeederAdmin;

const IndexOptionsPanel = () => (
  <PluginDocumentSettingPanel
    title={ i18nize( 'Publish to Content Commons' ) }
  >
    <MetaRadioControl
      fallback="yes"
      label={ i18nize( 'Include this post in Commons?' ) }
      metaKey="_iip_index_post_to_cdp_option"
      options={ [
        { label: i18nize( 'Yes' ), value: 'yes' },
        { label: i18nize( 'No' ), value: 'no' },
      ] }
    />

    <MetaSelectInput
      fallback="en-us"
      label={ i18nize( 'Language' ) }
      metaKey="_iip_language"
      options={ languages }
    />

    <MetaSelectInput
      label={ i18nize( 'Owner' ) }
      metaKey="_iip_owner"
      options={ owners }
    />

    <StatusIndicator color={ syncStatus.color } title={ syncStatus.title } />
  </PluginDocumentSettingPanel>
);

export default IndexOptionsPanel;
