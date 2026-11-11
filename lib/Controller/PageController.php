<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Pascal Berger
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Lineup\Controller;

use OCA\Lineup\AppInfo\Application;
use OCA\Lineup\Service\ConnectionService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\Util;

class PageController extends Controller {
	public function __construct(
		IRequest $request,
		private ConnectionService $connection,
		private IURLGenerator $urlGenerator,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * Render the iframe wrapper. The embedded Lineup app authenticates via the
	 * token minted by {@see TokenController::get()} and handed over by embed.js.
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/')]
	public function index(): TemplateResponse {
		Util::addStyle(Application::APP_ID, 'style');

		if (!$this->connection->isConfigured()) {
			$user = $this->userSession->getUser();
			return new TemplateResponse(Application::APP_ID, 'not-configured', [
				'is_admin' => $user !== null && $this->groupManager->isAdmin($user->getUID()),
				'settings_url' => $this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => Application::APP_ID]),
			]);
		}

		Util::addScript(Application::APP_ID, 'embed');

		$frontendOrigin = $this->connection->getFrontendOrigin();
		$response = new TemplateResponse(Application::APP_ID, 'index', [
			'iframe_src' => $this->connection->getFrontendUrl() . '/?embed=nc',
			'frontend_origin' => $frontendOrigin,
			'token_url' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.token.get'),
		]);

		// Allow the Lineup frontend inside the iframe.
		$csp = new ContentSecurityPolicy();
		$csp->addAllowedFrameDomain($frontendOrigin);
		$response->setContentSecurityPolicy($csp);

		return $response;
	}
}
