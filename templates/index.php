<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Pascal Berger
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @var array $_ */
?>
<div id="lineup-wrapper">
	<!-- No sandbox attribute on purpose: the embedded app opens popups (account linking). -->
	<iframe id="lineup-frame"
		src="<?php p($_['iframe_src']); ?>"
		data-frontend-origin="<?php p($_['frontend_origin']); ?>"
		data-token-url="<?php p($_['token_url']); ?>"
		title="Lineup"
		allow="clipboard-read; clipboard-write">
	</iframe>
</div>
