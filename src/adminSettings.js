/**
 * Nextcloud - DocuSign
 *
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Florian Klinger <florian.klinger@nextcloud.com>
 *
 * @copyright Florian Klinger 2023
 *
 */

import { createApp } from 'vue'
import AdminSettings from './components/AdminSettings.vue'

import { linkTo } from '@nextcloud/router'
import { getCSPNonce } from '@nextcloud/auth'

__webpack_nonce__ = getCSPNonce() // eslint-disable-line
__webpack_public_path__ = linkTo('integration_docusign', 'js/') // eslint-disable-line

const app = createApp(AdminSettings)
app.mixin({ methods: { t, n } })
app.mount('#docusign_prefs')
