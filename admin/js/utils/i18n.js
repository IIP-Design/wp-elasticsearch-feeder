import { __ } from '@wordpress/i18n';

/**
 * Wraps the provided text with the WordPress internationalization function.
 *
 * @param {string} string   A string to translate.
 * @returns {string}        Translated text.
 */
export const i18nize = string => {
  if ( typeof string !== 'string' ) {
    return string;
  }

  return __( string, 'gpalab-feeder' );
};
