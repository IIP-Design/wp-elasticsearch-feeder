import { PluginPrePublishPanel } from '@wordpress/edit-post';
import { select } from '@wordpress/data';

import MetaRadioControl from './MetaRadioControl';

import { i18nize } from '../utils/i18n';

const PrePublishConfirm = () => {
  const indexPost = select( 'core/editor' ).getEditedPostAttribute( 'meta' )._iip_index_post_to_cdp_option;

  return (
    <PluginPrePublishPanel
      title={ `${i18nize( 'Publish to Commons' )}: ${indexPost}` }
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
    </PluginPrePublishPanel>
  );
};

export default PrePublishConfirm;
