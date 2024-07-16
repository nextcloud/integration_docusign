<template>
	<div class="docusign-modal-container">
		<NcModal v-if="show"
			size="normal"
			label-id="docusign-modal-title"
			@close="closeRequestModal">
			<div class="docusign-modal-content">
				<h2 id="docusign-modal-title" class="modal-title">
					{{ t('integration_docusign', 'Request a signature via DocuSign') }}
				</h2>
				<span class="field-label">
					{{ t('integration_docusign', 'Users or email addresses') }}
				</span>
				<MultiselectWho
					ref="multiselect"
					class="userInput"
					:value="selectedItems"
					:types="[0]"
					:enable-emails="true"
					:placeholder="t('integration_docusign', 'Nextcloud users or email addresses')"
					:label="t('integration_docusign', 'Users or email addresses')"
					@update:value="updateSelectedItems($event)" />
				<NcEmptyContent
					:name="t('integration_docusign', 'DocuSign workflow')"
					:description="t('integration_docusign', 'Recipients will receive an email from DocuSign with a link to sign the document. You will be informed by email when the document has been signed by all recipients.')">
					<template #icon>
						<DocusignIcon />
					</template>
				</NcEmptyContent>
				<div class="docusign-footer">
					<NcButton
						@click="closeRequestModal">
						{{ t('integration_docusign', 'Cancel') }}
					</NcButton>
					<NcButton type="primary"
						:disabled="!canValidate"
						@click="onSignClick">
						{{ t('integration_docusign', 'Request signature') }}
						<template v-if="loading" #icon>
							<NcLoadingIcon />
						</template>
					</NcButton>
				</div>
			</div>
		</NcModal>
	</div>
</template>

<script>
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcLoadingIcon from '@nextcloud/vue/dist/Components/NcLoadingIcon.js'
import NcEmptyContent from '@nextcloud/vue/dist/Components/NcEmptyContent.js'

import MultiselectWho from './MultiselectWho.vue'
import DocusignIcon from './icons/DocusignIcon.vue'

import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'
import { showSuccess, showError } from '@nextcloud/dialogs'

export default {
	name: 'DocuSignModal',

	components: {
		DocusignIcon,
		NcModal,
		MultiselectWho,
		NcButton,
		NcLoadingIcon,
		NcEmptyContent,
	},

	props: [],

	data() {
		return {
			show: false,
			loading: false,
			fileId: 0,
			selectedItems: [],
		}
	},

	computed: {
		canValidate() {
			return this.selectedItems.length > 0
		},
	},

	watch: {
	},

	mounted() {
	},

	methods: {
		showModal() {
			this.show = true
			// once the modal is opened, focus on the multiselect input
			this.$nextTick(() => {
				this.$refs.multiselect.$el.querySelector('input').focus()
			})
		},
		closeRequestModal() {
			this.selectedItems = []
			this.show = false
		},
		setFileId(fileId) {
			this.fileId = fileId
		},
		updateSelectedItems(newValue) {
			this.selectedItems = newValue
			console.debug(this.selectedItems)
		},
		onSignClick() {
			this.loading = true

			const targetUserIds = this.selectedItems.filter((i) => { return i.type === 'user' }).map((i) => { return i.entityId })
			const targetEmails = this.selectedItems.filter((i) => { return i.type === 'email' }).map((i) => { return i.email })
			const req = {
				targetUserIds,
				targetEmails,
			}
			const url = generateUrl('/apps/integration_docusign/docusign/standalone-sign/' + this.fileId)
			axios.put(url, req).then((response) => {
				showSuccess(t('integration_docusign', 'Recipients will receive an email from DocuSign to sign the document'))
				this.closeRequestModal()
			}).catch((error) => {
				console.debug(error.response)
				showError(
					t('integration_docusign', 'Failed to request signature with DocuSign')
					+ ': ' + (error.response?.data?.response?.message ?? error.response?.data?.error ?? error.response?.request?.responseText ?? ''),
				)
			}).then(() => {
				this.loading = false
			})
		},
	},
}
</script>

<style scoped lang="scss">
.docusign-modal-content {
	padding: 16px;
	// min-height: 400px;
	display: flex;
	flex-direction: column;

	input[type='text'] {
		width: 100%;
	}

	.userInput {
		width: 100%;
		margin: 0 0 28px 0;
		--vs-dropdown-max-height: 300px;
	}

	.settings-hint {
		color: var(--color-text-maxcontrast);
		margin: 52px 0 52px 0;
	}
}

.docusign-footer {
	margin-top: 16px;
	display: flex;
	gap: 8px;
	justify-content: end;
}

.field-label {
	display: flex;
	align-items: center;
	height: 36px;
	margin: 8px 0 0 0;
	.icon {
		width: 32px;
	}
}
</style>
