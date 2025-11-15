<?php

/**
 * Nextcloud - DocuSign
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Florian Klinger <florian.klinger@nextcloud.com>
 * @copyright Florian Klinger 2023
 */

declare(strict_types=1);

namespace OCA\DocuSign\Controller;

use DateTime;
use OCA\DocuSign\AppInfo\Application;
use OCA\DocuSign\Service\DocusignAPIService;
use OCA\DocuSign\Service\UtilsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\IAppConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;

class DocusignController extends Controller {
	private $userId;
	private $appConfig;
	/**
	 * @var IL10N
	 */
	private $l;
	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;
	/**
	 * @var DocusignAPIService
	 */
	private $docusignAPIService;
	/**
	 * @var UtilsService
	 */
	private $utilsService;

	public function __construct($AppName,
		IRequest $request,
		IAppConfig $appConfig,
		IL10N $l,
		IURLGenerator $urlGenerator,
		DocusignAPIService $docusignAPIService,
		UtilsService $utilsService,
		?string $userId) {
		parent::__construct($AppName, $request);
		$this->appConfig = $appConfig;
		$this->l = $l;
		$this->urlGenerator = $urlGenerator;
		$this->docusignAPIService = $docusignAPIService;
		$this->utilsService = $utilsService;
		$this->userId = $userId;
	}

	/**
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'GET', url: '/docusign/info')]
	public function getDocusignInfo(): DataResponse {
		$token = $this->utilsService->getEncryptedAppValue('docusign_token');
		$isConnected = ($token !== '');
		return new DataResponse([
			'connected' => $isConnected,
		]);
	}

	/**
	 * @param int $fileId
	 * @param array $targetEmails
	 * @param array $targetUserIds
	 * @return DataResponse
	 */
	#[NoAdminRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/docusign/standalone-sign/{fileId}')]
	public function signStandalone(int $fileId, array $targetEmails = [], array $targetUserIds = []): DataResponse {
		$token = $this->utilsService->getEncryptedAppValue('docusign_token');
		$clientID = $this->utilsService->getEncryptedAppValue('docusign_client_id');
		$clientSecret = $this->utilsService->getEncryptedAppValue('docusign_client_secret');
		$isConnected = ($token !== '' && $clientID !== '' && $clientSecret !== '');
		if (!$isConnected) {
			return new DataResponse(['error' => 'DocuSign admin connected account is not configured'], 401);
		}
		if (!$this->utilsService->userHasAccessTo($fileId, $this->userId)) {
			return new DataResponse(['error' => 'You don\'t have access to this file'], 401);
		}
		$signResult = $this->docusignAPIService->emailSignStandalone($fileId, $this->userId, $targetEmails, $targetUserIds);
		if (isset($signResult['error'])) {
			return new DataResponse($signResult, 401);
		} else {
			return new DataResponse($signResult);
		}
	}

	/**
	 * set admin config values
	 *
	 * @param array $values
	 * @return DataResponse
	 */
	#[PasswordConfirmationRequired]
	#[FrontpageRoute(verb: 'PUT', url: '/docusign-config')]
	public function setDocusignConfig(array $values): DataResponse {
		foreach ($values as $key => $value) {
			if (in_array($key, ['docusign_client_id', 'docusign_client_secret', 'docusign_token', 'docusign_refresh_token'], true)) {
				$this->utilsService->setEncryptedAppValue($key, $value);
			} else {
				$this->appConfig->setValueString(Application::APP_ID, $key, $value, lazy: true);
			}
		}
		$result = [];

		if (isset($values['docusign_token'])) {
			if ($values['docusign_token'] && $values['docusign_token'] !== '') {
				// $result = $this->storeUserInfo($values['token']);
			} else {
				$this->appConfig->deleteKey(Application::APP_ID, 'docusign_user_email');
				$this->appConfig->deleteKey(Application::APP_ID, 'docusign_user_name');
				$this->appConfig->deleteKey(Application::APP_ID, 'docusign_user_account_id');
				$this->appConfig->deleteKey(Application::APP_ID, 'docusign_token');
				$this->appConfig->deleteKey(Application::APP_ID, 'docusign_refresh_token');
			}
		}

		if (isset($result['error'])) {
			return new DataResponse($result, 401);
		} else {
			return new DataResponse($result);
		}
	}

	/**
	 * receive oauth code and get oauth access token
	 *
	 * @param string $code
	 * @param string $state
	 * @return RedirectResponse
	 */
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'GET', url: '/docusign/oauth-redirect')]
	public function oauthRedirect(string $code = '', string $state = ''): RedirectResponse {
		$configState = $this->appConfig->getValueString(Application::APP_ID, 'docusign_oauth_state', lazy: true);
		$clientID = $this->utilsService->getEncryptedAppValue('docusign_client_id');
		$clientSecret = $this->utilsService->getEncryptedAppValue('docusign_client_secret');

		// anyway, reset state
		$this->appConfig->deleteKey(Application::APP_ID, 'docusign_oauth_state');

		if ($clientID && $clientSecret && $configState !== '' && $configState === $state) {
			// $redirect_uri = $this->appConfig->getValueString(Application::APP_ID, 'docusign_redirect_uri', lazy: true);
			$docusignTokenUrl = Application::DOCUSIGN_TOKEN_REQUEST_URL;
			$result = $this->docusignAPIService->requestOAuthAccessToken($docusignTokenUrl, $clientID, $clientSecret, [
				'code' => $code,
				// 'redirect_uri' => $redirect_uri,
				'grant_type' => 'authorization_code'
			], 'POST');
			if (isset($result['access_token'])) {
				$accessToken = $result['access_token'];
				$this->utilsService->setEncryptedAppValue('docusign_token', $accessToken);
				$this->utilsService->setEncryptedAppValue('token_type', 'oauth');

				$refreshToken = $result['refresh_token'];
				$this->utilsService->setEncryptedAppValue('docusign_refresh_token', $refreshToken);
				if (isset($result['expires_in']) && is_numeric($result['expires_in'])) {
					$nowTs = (new DateTime())->getTimestamp();
					$expiresIn = (int)$result['expires_in'];
					$this->appConfig->setValueString(Application::APP_ID, 'docusign_token_expires_at', (string)($nowTs + $expiresIn), lazy: true);
				}

				// get user info
				$this->storeUserInfo($accessToken);
				return new RedirectResponse(
					$this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => Application::ADMIN_SETTINGS_SECTION])
					. '?docusignToken=success'
				);
			}
			$result = $this->l->t('Error getting OAuth access token') . ' ' . $result['error'];
		} else {
			$result = $this->l->t('Error during OAuth exchanges');
		}
		return new RedirectResponse(
			$this->urlGenerator->linkToRoute('settings.AdminSettings.index', ['section' => Application::ADMIN_SETTINGS_SECTION])
			. '?docusignToken=error&message=' . urlencode($result)
		);
	}

	/**
	 * @param string $accessToken
	 * @return array
	 */
	private function storeUserInfo(string $accessToken): array {
		$refreshToken = $this->utilsService->getEncryptedAppValue('docusign_refresh_token');
		$clientID = $this->utilsService->getEncryptedAppValue('docusign_client_id');
		$clientSecret = $this->utilsService->getEncryptedAppValue('docusign_client_secret');

		$url = Application::DOCUSIGN_USER_INFO_REQUEST_URL;

		$info = $this->docusignAPIService->apiRequest($url, $accessToken, $refreshToken, $clientID, $clientSecret);
		if (isset($info['name'], $info['email'], $info['accounts']) && is_array($info['accounts']) && count($info['accounts']) > 0) {
			$this->appConfig->setValueString(Application::APP_ID, 'docusign_user_name', $info['name'], lazy: true);
			$this->appConfig->setValueString(Application::APP_ID, 'docusign_user_email', $info['email'], lazy: true);
			$accountId = '';
			$baseURI = '';
			foreach ($info['accounts'] as $account) {
				if ($account['is_default']) {
					$accountId = $account['account_id'];
					$baseURI = $account['base_uri'];
				}
			}
			$this->appConfig->setValueString(Application::APP_ID, 'docusign_user_account_id', $accountId, lazy: true);
			$this->appConfig->setValueString(Application::APP_ID, 'docusign_user_base_uri', $baseURI, lazy: true);
			return ['docusign_user_name' => $info['name']];
		} else {
			$this->appConfig->deleteKey(Application::APP_ID, 'docusign_user_name');
			$this->appConfig->deleteKey(Application::APP_ID, 'docusign_user_email');
			$this->appConfig->deleteKey(Application::APP_ID, 'docusign_user_account_id');
			$this->appConfig->deleteKey(Application::APP_ID, 'docusign_user_base_uri');
			return $info;
		}
	}
}
