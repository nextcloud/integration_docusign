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

import Vue from 'vue'
import './bootstrap.js'
import AdminSettings from './components/AdminSettings.vue'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip.js'
Vue.directive('tooltip', Tooltip)

const View = Vue.extend(AdminSettings)
new View().$mount('#docusign_prefs')
