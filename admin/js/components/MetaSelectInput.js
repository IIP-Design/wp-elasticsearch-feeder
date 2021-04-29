import { compose } from '@wordpress/compose';
import { SelectControl } from '@wordpress/components';
import { withSelect, withDispatch } from '@wordpress/data';

const MetaSelectInput = compose(
  withSelect( ( select, { fallback = '', label, metaKey, options } ) => ( {
    label,
    options,
    selected: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[metaKey] || fallback,
  } ) ),
  withDispatch( ( dispatch, { metaKey } ) => ( {
    setValue( val ) {
      dispatch( 'core/editor' ).editPost(
        { meta: { [metaKey]: val } },
      );
    },
  } ) ),
)( ( { label, options, selected, setValue } ) => (
  <SelectControl
    label={ label }
    options={ options }
    value={ selected }
    onBlur={ e => setValue( e.target.value ) }
    onChange={ setValue }
  />
) );

export default MetaSelectInput;
