/*
 * Copyright (c) 2021 Julien Veyssier <eneiluj@posteo.net>
 *
 * This file is licensed under the Affero General Public License version 3
 * or later.
 *
 * See the COPYING-README file.
 *
 */

import DocuSignModal from './components/DocuSignModal.vue'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

import Vue from 'vue'
import './bootstrap.js'

(function() {
	if (!OCA.DocuSign) {
		/**
		 * @namespace
		 */
		OCA.DocuSign = {
			requestOnFileChange: false,
		}
	}

	/**
	 * @namespace
	 */
	OCA.DocuSign.FilesPlugin = {
		ignoreLists: [
			'trashbin',
			'files.public',
		],

		attach(fileList) {
			if (this.ignoreLists.indexOf(fileList.id) >= 0) {
				return
			}

			fileList.fileActions.registerAction({
				name: 'approval-sign-docusign',
				displayName: (context) => {
					if (context && context.$file && OCA.DocuSign.docusignConnected) {
						return t('approval', 'Request signature')
					}
					return ''
				},
				mime: 'application/pdf',
				order: -139,
				iconClass: (fileName, context) => {
					if (context && context.$file && OCA.DocuSign.docusignConnected) {
						return 'icon-rename'
					}
				},
				permissions: OC.PERMISSION_READ,
				actionHandler: this.signDocuSign,
			})
		},

		signDocuSign: (fileName, context) => {
			const fileId = context.$file.data('id')
			OCA.DocuSign.DocuSignModalVue.$children[0].setFileId(fileId)
			OCA.DocuSign.DocuSignModalVue.$children[0].showModal()
		},

	}

})()

OC.Plugins.register('OCA.Files.FileList', OCA.DocuSign.FilesPlugin)

// signature modal
const modalId = 'docusignModal'
const modalElement = document.createElement('div')
modalElement.id = modalId
document.body.append(modalElement)

OCA.DocuSign.DocuSignModalVue = new Vue({
	el: modalElement,
	render: h => {
		return h(DocuSignModal)
	},
})

// is DocuSign configured?
const urlDs = generateUrl('/apps/integration_docusign/docusign/info')
axios.get(urlDs).then((response) => {
	OCA.DocuSign.docusignConnected = response.data.connected
}).catch((error) => {
	console.error(error)
})