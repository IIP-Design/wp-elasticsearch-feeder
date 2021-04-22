import { PluginDocumentSettingPanel } from '@wordpress/edit-post';

import MetaSelectInput from './MetaSelectInput';
import MetaRadioControl from './MetaRadioControl';
import Spacer from './Spacer';
import StatusIndicator from './StatusIndicator';

import { i18nize } from '../utils/i18n';

const { languages, owners, syncStatus, visibleMeta } = window.gpalabFeederAdmin;

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

    <Spacer />

    { visibleMeta?.language && (
      <MetaSelectInput
        fallback="en-us"
        label={ i18nize( 'Set Language' ) }
        metaKey="_iip_language"
        options={ languages }
      />
    ) }

    { visibleMeta?.owner && (
      <MetaSelectInput
        label={ i18nize( 'Set Owner' ) }
        metaKey="_iip_owner"
        options={ owners }
      />
    ) }

    <Spacer />

    <StatusIndicator color={ syncStatus.color } title={ syncStatus.title } />
  </PluginDocumentSettingPanel>
);

export default IndexOptionsPanel;
