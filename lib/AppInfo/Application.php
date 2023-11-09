<?php
/**
 * Nextcloud - DocuSign
 *
 *
 * @author Florian Klinger <florian.klinger@nextcloud.com>
 * @copyright Florian Klinger 2023
 */

namespace OCA\DocuSign\AppInfo;

use OCA\DocuSign\Notification\Notifier;

use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;

use OCP\SabrePluginEvent;
use OCP\SystemTag\MapperEvent;
use OCP\Util;

/**
 * Class Application
 *
 * @package OCA\DocuSign\AppInfo
 */
class Application extends App implements IBootstrap {
	public const APP_ID = 'integration_docusign';
	public const ADMIN_SETTINGS_SECTION = 'connected-accounts';
	// docusign
	public const DOCUSIGN_TOKEN_REQUEST_URL = 'https://account-d.docusign.com/oauth/token';
	public const DOCUSIGN_USER_INFO_REQUEST_URL = 'https://account-d.docusign.com/oauth/userinfo';

	/**
	 * Constructor
	 *
	 * @param array $urlParams
	 */
	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);

		$container = $this->getContainer();

		$eventDispatcher = $container->get(IEventDispatcher::class);
		// load files plugin script
		$eventDispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
			Util::addscript(self::APP_ID, self::APP_ID . '-filesplugin');
			Util::addStyle(self::APP_ID, 'files-style');
		});
	}

	public function register(IRegistrationContext $context): void {
	}

	public function boot(IBootContext $context): void {
	}
}
