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
import {
	registerFileAction, Permission, FileAction, FileType,
} from '@nextcloud/files'
import DocuSignIcon from '../img/app-dark.svg'

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
	displayName: (nodes) => {
		return t('integration_docusign', 'Request signature with DocuSign')
	},
	enabled(nodes, view) {
		return !OCA.DocuSign.ignoreLists.includes(view.id)
			&& nodes.length === 1
			&& !nodes.some(({ permissions }) => (permissions & Permission.READ) === 0)
			&& !nodes.some(({ type }) => type !== FileType.File)
			&& !nodes.some(({ mime }) => mime !== 'application/pdf')
	},
	iconSvgInline: () => DocuSignIcon,
	async exec(node) {
		OCA.DocuSign.DocuSignModalVue.$children[0].setFileId(node.fileid)
		OCA.DocuSign.DocuSignModalVue.$children[0].showModal()
		return null
	},
})
registerFileAction(requestSignatureAction)

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
