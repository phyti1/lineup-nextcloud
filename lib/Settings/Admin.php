<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Pascal Berger
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Lineup\Settings;

use OCA\Lineup\AppInfo\Application;
use OCA\Lineup\Service\ConnectionService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IURLGenerator;
use OCP\Settings\ISettings;
use OCP\Util;

class Admin implements ISettings {
	public function __construct(
		private ConnectionService $connection,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function getForm(): TemplateResponse {
		Util::addStyle(Application::APP_ID, 'admin-settings');
		Util::addScript(Application::APP_ID, 'admin-settings');

		// The secret is deliberately not passed to the template.
		return new TemplateResponse(Application::APP_ID, 'settings/admin', [
			'configured' => $this->connection->isConfigured(),
			'legacy' => $this->connection->isLegacy(),
			'frontend_url' => $this->connection->getFrontendUrl(),
			'default_url' => ConnectionService::DEFAULT_FRONTEND_URL,
			'endpoint' => $this->urlGenerator->linkToRoute(Application::APP_ID . '.settings.saveConnection'),
		], TemplateResponse::RENDER_AS_BLANK);
	}

	public function getSection(): string {
		return Application::APP_ID;
	}

	public function getPriority(): int {
		return 10;
	}
}
