<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Pascal Berger
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @var array $_ */
/** @var \OCP\IL10N $l */
?>
<div id="lineup-admin-settings" class="section"
	data-endpoint="<?php p($_['endpoint']); ?>"
	data-confirm-remove="<?php p($l->t('Remove the connection to Lineup? Users can no longer open Lineup in Nextcloud until a new code is entered.')); ?>"
	data-error="<?php p($l->t('Saving failed. Please try again.')); ?>">
	<h2>Lineup</h2>
	<p class="settings-hint">
		<?php p($l->t('Lineup plans trainings, events and attendance for clubs. To connect, sign in to Lineup, open your organization, create a Nextcloud connection and paste the connection code here.')); ?>
		<a href="<?php p($_['default_url']); ?>" target="_blank" rel="noopener noreferrer"><?php p($_['default_url']); ?> ↗</a>
	</p>

	<?php if ($_['configured']): ?>
		<p id="lineup-status">
			<?php if ($_['legacy']): ?>
				<?php p($l->t('Connected to %s with a shared secret set via occ (legacy).', [$_['frontend_url']])); ?>
			<?php else: ?>
				<?php p($l->t('Connected to %s.', [$_['frontend_url']])); ?>
			<?php endif; ?>
			<button type="button" id="lineup-remove"><?php p($l->t('Remove connection')); ?></button>
		</p>
	<?php endif; ?>

	<form id="lineup-connect-form">
		<label for="lineup-code">
			<?php p($_['configured'] ? $l->t('Replace with a new connection code') : $l->t('Connection code')); ?>
		</label>
		<div class="lineup-row">
			<input type="text" id="lineup-code" name="code" placeholder="lnc1.…"
				autocomplete="off" autocapitalize="off" spellcheck="false" required>
			<button type="submit" class="primary"><?php p($l->t('Connect')); ?></button>
		</div>
		<p id="lineup-message" role="alert" hidden></p>
	</form>
</div>
