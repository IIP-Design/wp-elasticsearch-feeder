import { compose } from '@wordpress/compose';
import { RadioControl } from '@wordpress/components';
import { withSelect, withDispatch } from '@wordpress/data';

const MetaRadioControl = compose(
  withSelect( ( select, { fallback = '', metaKey } ) => ( {
    selected: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[metaKey] || fallback,
  } ) ),
  withDispatch( ( dispatch, { metaKey } ) => ( {
    setValue( val ) {
      dispatch( 'core/editor' ).editPost(
        { meta: { [metaKey]: val } },
      );
    },
  } ) ),
)( ( { help = '', label, options, selected, setValue } ) => (
  <RadioControl
    help={ help }
    label={ label }
    options={ options }
    selected={ selected }
    onChange={ option => setValue( option ) }
  />
) );

export default MetaRadioControl;
