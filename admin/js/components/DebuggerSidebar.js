import { Button } from '@wordpress/components';
import { PluginSidebar } from '@wordpress/edit-post';
import { withState } from '@wordpress/compose';

import { fetchDebugData } from '../utils/debugger';
import { i18nize } from '../utils/i18n';

const { apiVars: { endpoint, token } } = window.gpalabFeederAdmin;

const DebuggerSidebar = withState( { data: '' } )(
  ( { data, setState } ) => {
    const fetchData = async () => {
      const fetched = await fetchDebugData( endpoint, token );

      setState( { data: JSON.stringify( fetched, null, 2 ) } );
    };

    return (
      <PluginSidebar
        name="gpalab-feeder-debugger-sidebar"
        title={ i18nize( 'CDP API Debugger' ) }
      >
        <div style={ { display: 'flex', flexDirection: 'column', padding: '1rem' } }>
          <p>{ i18nize( 'Use this tool to check the post data returned from the CDP API.' ) }</p>
          <Button
            isSecondary
            onClick={ () => fetchData() }
            style={ { alignSelf: 'flex-start' } }
            text={ i18nize( 'Retrieve Data' ) }
          />
          <pre id="es_response">
            { data }
          </pre>
        </div>
      </PluginSidebar>
    );
  },
);

export default DebuggerSidebar;

