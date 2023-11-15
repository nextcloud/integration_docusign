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

return [
	'routes' => [
		/**
		 * DocuSign
		 */
		['name' => 'Docusign#setDocusignConfig', 'url' => '/docusign-config', 'verb' => 'PUT'],
		['name' => 'Docusign#getDocusignInfo', 'url' => '/docusign/info', 'verb' => 'GET'],
		['name' => 'Docusign#oauthRedirect', 'url' => '/docusign/oauth-redirect', 'verb' => 'GET'],
		['name' => 'Docusign#signStandalone', 'url' => '/docusign/standalone-sign/{fileId}', 'verb' => 'PUT'],

	],

];
