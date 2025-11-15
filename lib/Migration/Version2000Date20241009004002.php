<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DocuSign\Migration;

use Closure;
use OCA\DocuSign\AppInfo\Application;
use OCP\IAppConfig;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use OCP\Security\ICrypto;

class Version2000Date20241009004002 extends SimpleMigrationStep {

	public function __construct(
		private ICrypto $crypto,
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		foreach (['docusign_client_id', 'docusign_token', 'docusign_refresh_token'] as $key) {
			$value = $this->appConfig->getValueString(Application::APP_ID, $key, lazy: true);
			if ($value !== '') {
				$encryptedValue = $this->crypto->encrypt($value);
				$this->appConfig->setValueString(Application::APP_ID, $key, $encryptedValue, lazy: true);
			}
		}
	}
}
