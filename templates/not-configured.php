<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Pascal Berger
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<div id="lineup-not-configured">
	<h2><?php p($l->t('Lineup is not connected yet')); ?></h2>
	<?php if ($_['is_admin']): ?>
		<p><?php p($l->t('Connect this Nextcloud to your Lineup organization with a connection code.')); ?></p>
		<p><a class="button primary" href="<?php p($_['settings_url']); ?>"><?php p($l->t('Open Lineup settings')); ?></a></p>
	<?php else: ?>
		<p><?php p($l->t('Please ask your Nextcloud administrator to set up Lineup.')); ?></p>
	<?php endif; ?>
</div>
