<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Pascal Berger
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Lineup\Service;

use InvalidArgumentException;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IL10N;

/**
 * Reads and writes the connection to a Lineup instance.
 *
 * Two modes are supported:
 *  - Connection mode: an admin pasted a connection code ("lnc1.<base64url JSON>")
 *    created in Lineup. Stored as frontend_url, connection_id and jwt_secret.
 *  - Legacy mode: jwt_secret (and usually frontend_url) were set via occ, without
 *    a connection_id. Tokens are then signed exactly as in version 1.0.0.
 */
class ConnectionService {
	public const DEFAULT_FRONTEND_URL = 'https://lineup.insidertips.ch';
	public const CODE_PREFIX = 'lnc1.';

	public const KEY_FRONTEND_URL = 'frontend_url';
	public const KEY_CONNECTION_ID = 'connection_id';
	public const KEY_SECRET = 'jwt_secret';
	public const KEY_TOKEN_TTL = 'token_ttl';

	private const DEFAULT_TOKEN_TTL = 600;
	private const MAX_CODE_LENGTH = 4096;
	private const MIN_SECRET_LENGTH = 16;
	private const MAX_SECRET_LENGTH = 1024;

	public function __construct(
		private IAppConfig $appConfig,
		private IL10N $l,
	) {
	}

	public function isConfigured(): bool {
		return $this->getSecret() !== '';
	}

	/** True when configured via occ with a shared secret but without a connection id. */
	public function isLegacy(): bool {
		return $this->isConfigured() && $this->getConnectionId() === '';
	}

	public function getFrontendUrl(): string {
		$url = rtrim($this->appConfig->getAppValueString(self::KEY_FRONTEND_URL, ''), '/');
		return $url !== '' ? $url : self::DEFAULT_FRONTEND_URL;
	}

	public function getFrontendOrigin(): string {
		return self::originOf($this->getFrontendUrl());
	}

	public function getConnectionId(): string {
		return $this->appConfig->getAppValueString(self::KEY_CONNECTION_ID, '');
	}

	public function getSecret(): string {
		return $this->appConfig->getAppValueString(self::KEY_SECRET, '');
	}

	public function getTokenTtl(): int {
		// Read as string: values set with `occ config:app:set` carry no type.
		$ttl = (int)$this->appConfig->getAppValueString(self::KEY_TOKEN_TTL, (string)self::DEFAULT_TOKEN_TTL);
		return $ttl > 0 ? $ttl : self::DEFAULT_TOKEN_TTL;
	}

	/**
	 * Validate a connection code and store it, replacing any previous connection
	 * (including a legacy occ configuration).
	 *
	 * @return array{url: string, id: string}
	 * @throws InvalidArgumentException with a translated, user-facing message
	 */
	public function saveConnectionCode(string $code): array {
		$connection = $this->parseConnectionCode($code);

		$this->appConfig->setAppValueString(self::KEY_FRONTEND_URL, $connection['url']);
		$this->appConfig->setAppValueString(self::KEY_CONNECTION_ID, $connection['id']);
		// Drop a possibly non-sensitive legacy value first so the new one is
		// stored with the sensitive flag (hidden in occ output and admin APIs).
		$this->appConfig->deleteAppValue(self::KEY_SECRET);
		$this->appConfig->setAppValueString(self::KEY_SECRET, $connection['secret'], false, true);

		return ['url' => $connection['url'], 'id' => $connection['id']];
	}

	public function removeConnection(): void {
		$this->appConfig->deleteAppValue(self::KEY_FRONTEND_URL);
		$this->appConfig->deleteAppValue(self::KEY_CONNECTION_ID);
		$this->appConfig->deleteAppValue(self::KEY_SECRET);
	}

	/**
	 * Decode "lnc1." + base64url(JSON {"u": url, "i": connectionId, "s": secret}).
	 *
	 * @return array{url: string, id: string, secret: string}
	 * @throws InvalidArgumentException with a translated, user-facing message
	 */
	public function parseConnectionCode(string $code): array {
		// Tolerate line breaks or spaces picked up while copying.
		$code = (string)preg_replace('/\s+/', '', $code);
		if ($code === '') {
			throw new InvalidArgumentException($this->l->t('Please paste the connection code from Lineup.'));
		}
		if (strlen($code) > self::MAX_CODE_LENGTH
			|| !str_starts_with($code, self::CODE_PREFIX)) {
			throw new InvalidArgumentException($this->l->t('This is not a Lineup connection code.'));
		}

		$payload = substr($code, strlen(self::CODE_PREFIX));
		if ($payload === '' || preg_match('/^[A-Za-z0-9_-]+$/', $payload) !== 1) {
			throw new InvalidArgumentException($this->l->t('The connection code is damaged. Please copy it again.'));
		}
		$json = base64_decode(strtr($payload, '-_', '+/') . str_repeat('=', (4 - strlen($payload) % 4) % 4), true);
		$data = $json === false ? null : json_decode($json, true);
		if (!is_array($data)) {
			throw new InvalidArgumentException($this->l->t('The connection code is damaged. Please copy it again.'));
		}

		$url = is_string($data['u'] ?? null) ? $this->normalizeUrl($data['u']) : null;
		if ($url === null) {
			throw new InvalidArgumentException($this->l->t('The connection code contains an invalid Lineup address.'));
		}

		$id = $data['i'] ?? null;
		if (is_int($id)) {
			$id = (string)$id;
		}
		if (!is_string($id) || preg_match('/^[A-Za-z0-9._:-]{1,128}$/', $id) !== 1) {
			throw new InvalidArgumentException($this->l->t('The connection code is damaged. Please copy it again.'));
		}

		$secret = $data['s'] ?? null;
		if (!is_string($secret)
			|| strlen($secret) < self::MIN_SECRET_LENGTH
			|| strlen($secret) > self::MAX_SECRET_LENGTH) {
			throw new InvalidArgumentException($this->l->t('The connection code is damaged. Please copy it again.'));
		}

		return ['url' => $url, 'id' => $id, 'secret' => $secret];
	}

	/**
	 * Accept https URLs (http only for local development hosts) without
	 * credentials, query or fragment. Returns the URL without trailing slash.
	 */
	private function normalizeUrl(string $url): ?string {
		$parts = parse_url(trim($url));
		if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])
			|| isset($parts['user']) || isset($parts['pass'])
			|| isset($parts['query']) || isset($parts['fragment'])) {
			return null;
		}
		$scheme = strtolower($parts['scheme']);
		$host = strtolower($parts['host']);
		$isLocal = in_array($host, ['localhost', '127.0.0.1', '[::1]'], true);
		if ($scheme !== 'https' && !($scheme === 'http' && $isLocal)) {
			return null;
		}
		$normalized = $scheme . '://' . $host;
		if (isset($parts['port'])) {
			$normalized .= ':' . $parts['port'];
		}
		return $normalized . rtrim($parts['path'] ?? '', '/');
	}

	/** Build the scheme://host[:port] origin from a full URL. */
	public static function originOf(string $url): string {
		$parts = parse_url($url);
		if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
			return $url;
		}
		$origin = $parts['scheme'] . '://' . $parts['host'];
		if (isset($parts['port'])) {
			$origin .= ':' . $parts['port'];
		}
		return $origin;
	}
}
