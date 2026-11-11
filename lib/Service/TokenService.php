<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Pascal Berger
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Lineup\Service;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IURLGenerator;
use OCP\IUser;

/**
 * Mints the short-lived HS256 JWT that signs a Nextcloud user into Lineup.
 */
class TokenService {
	public function __construct(
		private ConnectionService $connection,
		private IURLGenerator $urlGenerator,
		private ITimeFactory $timeFactory,
	) {
	}

	/**
	 * @return array{token: string, expires_in: int}
	 */
	public function createToken(IUser $user, string $email): array {
		$secret = $this->connection->getSecret();
		$ttl = $this->connection->getTokenTtl();
		$now = $this->timeFactory->getTime();
		$connectionId = $this->connection->getConnectionId();

		if ($connectionId === '') {
			// Legacy mode (shared secret set via occ): token format of 1.0.0, unchanged.
			$header = ['alg' => 'HS256', 'typ' => 'JWT'];
			$payload = [
				'iss' => 'nextcloud-absencetool',
				'iat' => $now,
				'exp' => $now + $ttl,
				'email' => $email,
				'name' => $user->getDisplayName(),
				'nextcloud_uid' => $user->getUID(),
			];
		} else {
			// Connection mode: the key id tells Lineup which connection (and
			// therefore which secret and organization) signed the token.
			$header = ['alg' => 'HS256', 'typ' => 'JWT', 'kid' => $connectionId];
			$payload = [
				'iss' => 'nextcloud-lineup',
				'iat' => $now,
				'exp' => $now + $ttl,
				'email' => $email,
				'name' => $user->getDisplayName(),
				'nextcloud_uid' => $user->getUID(),
				'nextcloud_url' => rtrim($this->urlGenerator->getAbsoluteURL('/'), '/'),
			];
		}

		return [
			'token' => self::signJwt($header, $payload, $secret),
			'expires_in' => $ttl,
		];
	}

	/** Produce a compact HS256 JWT (RFC 7519) without external dependencies. */
	public static function signJwt(array $header, array $payload, string $secret): string {
		$signingInput = self::b64url((string)json_encode($header, JSON_UNESCAPED_SLASHES))
			. '.' . self::b64url((string)json_encode($payload, JSON_UNESCAPED_SLASHES));
		return $signingInput . '.' . self::b64url(hash_hmac('sha256', $signingInput, $secret, true));
	}

	/** Base64url-encode without padding (RFC 7515). */
	private static function b64url(string $data): string {
		return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
	}
}
