/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';
import { createRoot, render, StrictMode, createInterpolateElement } from '@wordpress/element';
import { Button, TextControl } from '@wordpress/components';
import { useState,useEffect } from 'react';

import "./scss/style.scss"


const domElement = document.getElementById( window.wpmudevPluginTest.dom_element_id );

const WPMUDEV_PluginTest = () => {

	const [clientId, setClientId ] = useState('');
	const [clientSecret, setClientSecret ] = useState('');
	const [alertMessage, setAlertMessage ] = useState('');
	const [borderColor, setBorderColor ] = useState('green');

	useEffect(() => {
		const fetchData = async () => {
			try {
				// Make a GET request to the WordPress REST API
				const response = await fetch( window.wpmudevPluginTest.auth_endpoint , {
					method: 'GET',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce' : window.wpmudevPluginTest.nonce
					}
				});
				const resultData = await response.json();
				// Update state with the fetched data

				if( resultData.data.code ){
					console.log( resultData.data.message );
				}else{
					setClientId(resultData.data.client_id)
					setClientSecret(resultData.data.client_secret)
				}

			} catch (error) {
				console.error('Error:', error);
			}
		};

		fetchData(); // Call the function to fetch data when the component mounts
	}, []); // Empty dependency array ensures the effect runs once after initial render


	const handleClick = (event) => {
		event.preventDefault();

		// Fetch POST request to WordPress REST API
		const data = {
			client_id: clientId,
			client_secret: clientSecret
		}
		fetch( window.wpmudevPluginTest.auth_endpoint, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce' : window.wpmudevPluginTest.nonce
			},
			body: JSON.stringify(data),
		})
			.then((response) => response.json())
			.then((responseData) => {
				setAlertMessage( responseData.data.message );
				responseData.data.code === 200 ? setBorderColor("green") : setBorderColor("red");

			})
			.catch((error) => {
				console.error('Error:', error);
			});
    }

    return (
    <>
		<div class="sui-header">
            <h1 class="sui-header-title">{ __('Settings', 'wpmudev-plugin-test') }</h1>
      	</div>

		<div className="sui-box">
			<div className="sui-box-header">
				<h2 className="sui-box-title">{__('Set Google credentials', 'wpmudev-plugin-test')}</h2>
			</div>

			<div className="sui-box-body">
					<div className="sui-box-settings-row">
						<TextControl
							help={createInterpolateElement(
								__('You can get Client ID from <a>here</a>.', 'wpmudev-plugin-test'),
								{
									a: <a
										href="https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid"/>,
								}
							)}
							label="Client ID"
							onChange={ (e) => setClientId( e ) }
							//onChange={handleChange}
							className="client_id"
							value={clientId}
						/>
					</div>

					<div className="sui-box-settings-row">
						<TextControl
							help={createInterpolateElement(
								__('You can get Client Secret from <a>here</a>.', 'wpmudev-plugin-test'),
								{
									a: <a
										href="https://developers.google.com/identity/gsi/web/guides/get-google-api-clientid"/>,
								}
							)}
							label="Client Secret"
							onChange={ (e) => setClientSecret( e ) }
							type="password"
							className="client_secret"
							value={clientSecret}

						/>
					</div>


				<div className="sui-box-settings-row">
					 <span>
						{__('Please use this URL', 'wpmudev-plugin-test')}{' '}
						 <em>{window.wpmudevPluginTest.returnUrl}</em>{' '}
						 {__('in your Google API\'s', 'wpmudev-plugin-test')}{' '}
						 <strong>{__('Authorized redirect URIs', 'wpmudev-plugin-test')}</strong>{' '}
						 {__('field', 'wpmudev-plugin-test')}
					</span>
				</div>
				{/* Display the alert message */}
				{alertMessage && <div className="sui-box-settings-row wpmu-alert-box" style={{backgroundColor: borderColor}}>{alertMessage}</div>}
			</div>

			<div className="sui-box-footer">
				<div className="sui-actions-right">
					<Button variant="primary" onClick={handleClick}>
						{__('Save', 'wpmudev-plugin-test')}
					</Button>
				</div>
			</div>
		</div>
	</>
	);
}

if (createRoot) {
	createRoot(domElement).render(<StrictMode><WPMUDEV_PluginTest/></StrictMode>);
} else {
    render( <StrictMode><WPMUDEV_PluginTest/></StrictMode>, domElement );
}
