<?php

declare(strict_types=1);

namespace OCA\DocuSign\Settings;

use OCA\DocuSign\AppInfo\Application;
use OCA\DocuSign\Service\DocusignAPIService;
use OCA\DocuSign\Service\UtilsService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IAppConfig;
use OCP\Settings\ISettings;

class Admin implements ISettings {
	private $appConfig;
	/**
	 * @var IInitialState
	 */
	private $initialStateService;
	/**
	 * @var DocusignAPIService
	 */
	private $docusignAPIService;
	/**
	 * @var utilsService
	 */
	private $utilsService;
	/**
	 * @var string|null
	 */
	private $userId;

	public function __construct(string $appName,
		IAppConfig $appConfig,
		IInitialState $initialStateService,
		DocusignAPIService $docusignAPIService,
		UtilsService $utilsService,
		?string $userId) {
		$this->appConfig = $appConfig;
		$this->initialStateService = $initialStateService;
		$this->docusignAPIService = $docusignAPIService;
		$this->utilsService = $utilsService;
		$this->userId = $userId;
	}

	/**
	 * @return TemplateResponse
	 */
	public function getForm(): TemplateResponse {
		$clientID = $this->utilsService->getEncryptedAppValue('docusign_client_id');
		$clientSecret = $this->utilsService->getEncryptedAppValue('docusign_client_secret');
		$token = $this->utilsService->getEncryptedAppValue('docusign_token');
		$refreshToken = $this->utilsService->getEncryptedAppValue('docusign_refresh_token');

		$accounts = [];
		// get and update user info
		if ($clientID && $clientSecret && $token && $refreshToken) {
			$url = Application::DOCUSIGN_USER_INFO_REQUEST_URL;
			$info = $this->docusignAPIService->apiRequest($url, $token, $refreshToken, $clientID, $clientSecret);
			if (isset($info['name'], $info['email'], $info['accounts']) && is_array($info['accounts']) && count($info['accounts']) > 0) {
				$this->appConfig->setValueString(Application::APP_ID, 'docusign_user_name', $info['name'], lazy: true);
				$this->appConfig->setValueString(Application::APP_ID, 'docusign_user_email', $info['email'], lazy: true);
				$accounts = $info['accounts'];
				$accountId = '';
				$baseURI = '';
				foreach ($accounts as $account) {
					if ($account['is_default']) {
						$accountId = $account['account_id'];
						$baseURI = $account['base_uri'];
					}
				}
				$this->appConfig->setValueString(Application::APP_ID, 'docusign_user_account_id', $accountId, lazy: true);
				$this->appConfig->setValueString(Application::APP_ID, 'docusign_user_base_uri', $baseURI, lazy: true);
			} else {
				$this->appConfig->deleteKey(Application::APP_ID, 'docusign_user_name');
				$this->appConfig->deleteKey(Application::APP_ID, 'docusign_user_email');
				$this->appConfig->deleteKey(Application::APP_ID, 'docusign_user_account_id');
				$this->appConfig->deleteKey(Application::APP_ID, 'docusign_user_base_uri');
			}
		}

		$userName = $this->appConfig->getValueString(Application::APP_ID, 'docusign_user_name', lazy: true);
		$userEmail = $this->appConfig->getValueString(Application::APP_ID, 'docusign_user_email', lazy: true);

		$adminConfig = [
			'docusign_client_id' => $clientID ? 'dummyClientNumber' : '',
			'docusign_client_secret' => $clientSecret ? 'dummyClientSecret' : '',
			'docusign_token' => $token !== '',
			'docusign_user_name' => $userName,
			'docusign_user_email' => $userEmail,
			'docusign_user_accounts' => $accounts,
		];
		$this->initialStateService->provideInitialState('docusign-config', $adminConfig);
		return new TemplateResponse(Application::APP_ID, 'adminSettings');
	}

	public function getSection(): string {
		return Application::ADMIN_SETTINGS_SECTION;
	}

	public function getPriority(): int {
		return 1;
	}
}
