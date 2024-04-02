<?php
/**
 * Nextcloud - integration_docusign
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Florian Klinger <florian.klinger@nextcloud.com>
 * @copyright Florian Klinger 2023
 */

require_once __DIR__.'/../../../lib/base.php';
require_once __DIR__.'/../vendor/autoload.php';

\OC::$loader->addValidRoot(OC::$SERVERROOT . '/tests');
\OC_App::loadApp(\OCA\DocuSign\AppInfo\Application::APP_ID);
