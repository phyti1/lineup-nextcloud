<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Pascal Berger
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Lineup\Controller;

use InvalidArgumentException;
use OCA\Lineup\AppInfo\Application;
use OCA\Lineup\Service\ConnectionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

/**
 * Admin-only endpoints of the "Administration settings > Lineup" page.
 * No #[NoAdminRequired] / #[NoCSRFRequired]: both checks stay active.
 */
class SettingsController extends Controller {
	public function __construct(
		IRequest $request,
		private ConnectionService $connection,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	#[FrontpageRoute(verb: 'POST', url: '/settings/connection')]
	public function saveConnection(string $code = ''): JSONResponse {
		try {
			$saved = $this->connection->saveConnectionCode($code);
		} catch (InvalidArgumentException $e) {
			return new JSONResponse(['message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
		// Never echo the secret back.
		return new JSONResponse(['frontend_url' => $saved['url']]);
	}

	#[FrontpageRoute(verb: 'DELETE', url: '/settings/connection')]
	public function removeConnection(): JSONResponse {
		$this->connection->removeConnection();
		return new JSONResponse([]);
	}
}
