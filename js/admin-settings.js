/**
 * SPDX-FileCopyrightText: 2026 Pascal Berger
 * SPDX-License-Identifier: AGPL-3.0-or-later
 *
 * Administration settings > Lineup: save or remove the connection code.
 * Requests carry the Nextcloud CSRF token; the endpoints are admin-only.
 */
(function () {
	'use strict'

	function requestToken() {
		return document.head.dataset.requesttoken || (window.OC && window.OC.requestToken) || ''
	}

	function init() {
		var root = document.getElementById('lineup-admin-settings')
		if (!root) {
			return
		}
		var endpoint = root.getAttribute('data-endpoint')
		var form = document.getElementById('lineup-connect-form')
		var input = document.getElementById('lineup-code')
		var message = document.getElementById('lineup-message')
		var removeButton = document.getElementById('lineup-remove')

		function showError(text) {
			message.textContent = text || root.getAttribute('data-error')
			message.hidden = false
		}

		function send(method, body) {
			return fetch(endpoint, {
				method: method,
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					requesttoken: requestToken(),
				},
				body: body ? JSON.stringify(body) : undefined,
			}).then(function (response) {
				return response.json().catch(function () {
					return {}
				}).then(function (data) {
					if (!response.ok) {
						throw new Error(data.message || '')
					}
					return data
				})
			})
		}

		form.addEventListener('submit', function (event) {
			event.preventDefault()
			message.hidden = true
			var buttons = form.querySelectorAll('button')
			buttons.forEach(function (b) { b.disabled = true })
			send('POST', { code: input.value }).then(function () {
				input.value = ''
				window.location.reload()
			}).catch(function (err) {
				buttons.forEach(function (b) { b.disabled = false })
				showError(err.message)
			})
		})

		if (removeButton) {
			removeButton.addEventListener('click', function () {
				if (!window.confirm(root.getAttribute('data-confirm-remove'))) {
					return
				}
				removeButton.disabled = true
				send('DELETE').then(function () {
					window.location.reload()
				}).catch(function (err) {
					removeButton.disabled = false
					showError(err.message)
				})
			})
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init)
	} else {
		init()
	}
})()
