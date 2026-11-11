<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Pascal Berger
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Lineup\Controller;

use OCA\Lineup\AppInfo\Application;
use OCA\Lineup\Service\ConnectionService;
use OCA\Lineup\Service\TokenService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class TokenController extends Controller {
	public function __construct(
		IRequest $request,
		private IUserSession $userSession,
		private ConnectionService $connection,
		private TokenService $tokenService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Mint a short-lived HS256 JWT from the current Nextcloud session.
	 *
	 * The token carries the user's email (the linking key in Lineup), display
	 * name and Nextcloud uid. CSRF-protected: embed.js sends the request token.
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/token')]
	public function get(): JSONResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return new JSONResponse(['error' => 'not_authenticated'], Http::STATUS_UNAUTHORIZED);
		}
		if (!$this->connection->isConfigured()) {
			return new JSONResponse(['error' => 'not_configured'], Http::STATUS_SERVICE_UNAVAILABLE);
		}

		$email = $user->getEMailAddress();
		if ($email === null || $email === '') {
			return new JSONResponse(['error' => 'no_email'], Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse($this->tokenService->createToken($user, $email));
	}
}
