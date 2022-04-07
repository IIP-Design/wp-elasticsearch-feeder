import { useState } from 'react';
import { Button } from '@wordpress/components';
import { PluginSidebar } from '@wordpress/edit-post';

import Spinner from './Spinner';

import { fetchDebugData } from '../utils/debugger';
import { i18nize } from '../utils/i18n';

const { apiVars: { endpoint, token } } = window.gpalabFeederAdmin;

const DebuggerSidebar = () => {
  const [data, setData] = useState( '' );
  const [showSpinner, setShowSpinner] = useState( false );

  const fetchData = async () => {
    setShowSpinner( true );

    const fetched = await fetchDebugData( endpoint, token );

    setData( fetched );
    setShowSpinner( false );
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
        { showSpinner && (
          <Spinner msg="Fetching post data from the API..." />
        ) }
        <pre id="es-response">
          { data }
        </pre>
      </div>
    </PluginSidebar>
  );
};

export default DebuggerSidebar;
