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
import { generateUrl, linkTo } from '@nextcloud/router'
import { getCSPNonce } from '@nextcloud/auth'

import { createApp } from 'vue'
import {
	registerFileAction, Permission, FileAction, FileType,
} from '@nextcloud/files'
import DocuSignIcon from '../img/app-dark.svg'

__webpack_nonce__ = getCSPNonce() // eslint-disable-line
__webpack_public_path__ = linkTo('integration_docusign', 'js/') // eslint-disable-line

if (!OCA.DocuSign) {
	/**
	 * @namespace
	 */
	OCA.DocuSign = {
		requestOnFileChange: false,
		ignoreLists: [
			'trashbin',
			'files.public',
		],
	}
}

const requestSignatureAction = new FileAction({
	id: 'docusign-sign',
	displayName: () => {
		return t('integration_docusign', 'Request signature with DocuSign')
	},
	enabled({ nodes, view }) {
		return !OCA.DocuSign.ignoreLists.includes(view.id)
			&& nodes.length === 1
			&& !nodes.some(({ permissions }) => (permissions & Permission.READ) === 0)
			&& !nodes.some(({ type }) => type !== FileType.File)
			&& !nodes.some(({ mime }) => mime !== 'application/pdf')
	},
	iconSvgInline: () => DocuSignIcon,
	async exec({ nodes }) {
		OCA.DocuSign.DocuSignModalVue.setFileId(nodes[0].fileid)
		OCA.DocuSign.DocuSignModalVue.showModal()
		return null
	},
})
registerFileAction(requestSignatureAction)

// signature modal
const modalId = 'docusignModal'
const modalElement = document.createElement('div')
modalElement.id = modalId
document.body.append(modalElement)

const app = createApp(DocuSignModal)
app.mixin({ methods: { t, n } })
OCA.DocuSign.DocuSignModalVue = app.mount(modalElement)

// is DocuSign configured?
const urlDs = generateUrl('/apps/integration_docusign/docusign/info')
axios.get(urlDs).then((response) => {
	OCA.DocuSign.docusignConnected = response.data.connected
}).catch((error) => {
	console.error(error)
})
