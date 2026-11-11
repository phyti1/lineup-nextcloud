/**
 * SPDX-FileCopyrightText: 2026 Pascal Berger
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Lineup embed bridge.
 *
 * Hands a short-lived token (minted from the Nextcloud session by
 * TokenController) to the embedded Lineup iframe via postMessage. Origin checks
 * are enforced on both ends; we only ever talk to the configured frontend origin.
 *
 * The message types (absencetool-*) are part of the protocol of the deployed
 * Lineup frontend and must not be renamed.
 */
(function () {
	'use strict'

	function requestToken() {
		// Kept up to date by Nextcloud when the CSRF token is refreshed.
		return document.head.dataset.requesttoken || (window.OC && window.OC.requestToken) || ''
	}

	function init() {
		var iframe = document.getElementById('lineup-frame')
		if (!iframe) {
			return
		}

		var frontendOrigin = iframe.getAttribute('data-frontend-origin')
		var tokenUrl = iframe.getAttribute('data-token-url')

		function sendToken() {
			fetch(tokenUrl, {
				method: 'GET',
				credentials: 'same-origin',
				headers: {
					'OCS-APIREQUEST': 'true',
					requesttoken: requestToken(),
				},
			}).then(function (response) {
				if (!response.ok) {
					throw new Error('Token request failed: ' + response.status)
				}
				return response.json()
			}).then(function (data) {
				if (data && data.token && iframe.contentWindow) {
					iframe.contentWindow.postMessage(
						{ type: 'absencetool-token', token: data.token },
						frontendOrigin,
					)
				}
			}).catch(function (err) {
				console.error('[lineup] Failed to obtain token', err)
			})
		}

		// The iframe asks for a token when it loads ('ready') and on refresh.
		window.addEventListener('message', function (event) {
			if (event.origin !== frontendOrigin || event.source !== iframe.contentWindow) {
				return // strict origin and source check
			}
			var data = event.data
			if (!data || typeof data !== 'object') {
				return
			}
			if (data.type === 'absencetool-ready' || data.type === 'absencetool-request-token') {
				sendToken()
			}
		})
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init)
	} else {
		init()
	}
})()
