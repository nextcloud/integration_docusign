<template>
	<div id="docusign_prefs" class="section">
		<h2 class="section-title">
			<DocusignIcon />
			{{ t('integration_docusign', 'DocuSign integration') }}
		</h2>
		<p class="settings-hint">
			{{ t('integration_docusign', 'DocuSign is an electronic signature solution.') }}
			<a href="https://www.docusign.com/" class="external" target="_blank">
				https://www.docusign.com
				<span class="icon icon-external" />
			</a>
		</p>
		<p v-if="!connected"
			class="settings-hint">
			<br>
			{{ t('integration_docusign', 'If you want to use DocuSign, create an application in your DocuSign "My Apps & Keys" developer account settings and put the client ID (integration key) and secret below.') }}
			<br>
			{{ t('integration_docusign', 'Make sure you set this "Redirect URI":') }}
			<b> {{ redirect_uri }} </b>
			<br>
			{{ t('integration_docusign', 'If your DocuSign user is associated with multiple DocuSign accounts, the default one will be used.') }}
		</p>
		<div v-if="!connected"
			class="form">
			<label for="docusign-client-id">
				{{ t('integration_docusign', 'Client ID (aka integration key)') }}
			</label>
			<input id="docusign-client-id"
				v-model="state.docusign_client_id"
				type="password"
				:readonly="readonly"
				:placeholder="t('integration_docusign', 'Client ID of your application')"
				@focus="readonly = false"
				@input="onFieldInput">
			<label for="docusign-client-secret">
				{{ t('integration_docusign', 'Application secret key') }}
			</label>
			<input id="docusign-client-secret"
				v-model="state.docusign_client_secret"
				type="password"
				:readonly="readonly"
				:placeholder="t('integration_docusign', 'Secret key of your application')"
				@focus="readonly = false"
				@input="onFieldInput">
		</div>
		<NcButton v-if="oAuthConfigured && !connected"
			id="docusign-oauth-connect"
			:disabled="loading === true"
			:class="{ loading }"
			@click="onOAuthClick">
			{{ t('integration_docusign', 'Connect to DocuSign') }}
		</NcButton>
		<div v-if="connected">
			<p class="docusign-connected">
				<a class="icon icon-checkmark-color" />
				{{ t('integration_docusign', 'Connected as {user} ({email})', { user: state.docusign_user_name, email: state.docusign_user_email }) }}
			</p>
			<NcButton class="docusign-rm-cred" @click="onLogoutClick">
				<template #icon>
					<CloseIcon :size="20" />
				</template>
				{{ t('integration_docusign', 'Disconnect from DocuSign') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import CloseIcon from 'vue-material-design-icons/Close.vue'
import DocusignIcon from './icons/DocusignIcon.vue'

import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'

import { loadState } from '@nextcloud/initial-state'
import { generateUrl } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import { delay } from '../utils.js'
import { showSuccess, showError } from '@nextcloud/dialogs'
import { confirmPassword } from '@nextcloud/password-confirmation'

export default {
	name: 'AdminSettings',

	components: {
		DocusignIcon,
		NcButton,
		CloseIcon,
	},

	props: [],

	data() {
		return {
			state: loadState('integration_docusign', 'docusign-config'),
			// to prevent some browsers to fill fields with remembered passwords
			readonly: true,
			loading: false,
			redirect_uri: window.location.protocol + '//' + window.location.host + generateUrl('/apps/integration_docusign/docusign/oauth-redirect'),
		}
	},

	computed: {
		oAuthConfigured() {
			return this.state.docusign_client_id && this.state.docusign_client_secret
		},
		connected() {
			return this.state.docusign_token && this.state.docusign_token !== ''
		},
	},

	watch: {
	},

	mounted() {
		const paramString = window.location.search.slice(1)
		// eslint-disable-next-line
		const urlParams = new URLSearchParams(paramString)
		const dsToken = urlParams.get('docusignToken')
		if (dsToken === 'success') {
			showSuccess(t('integration_docusign', 'Successfully connected to DocuSign!'))
		} else if (dsToken === 'error') {
			showError(t('integration_docusign', 'OAuth access token could not be obtained:') + ' ' + urlParams.get('message'))
		}
	},

	methods: {
		onFieldInput() {
			this.loading = true
			delay(async () => {
				await confirmPassword()

				const values = {}
				if (this.state.docusign_client_id !== 'dummyClientNumber') {
					values.docusign_client_id = this.state.docusign_client_id
				}
				if (this.state.docusign_client_secret !== 'dummyClientSecret') {
					values.docusign_client_secret = this.state.docusign_client_secret
				}
				this.saveOptions(values)
			}, 2000)()
		},
		saveOptions(values) {
			const req = {
				values,
			}
			const url = generateUrl('/apps/integration_docusign/docusign-config')
			axios.put(url, req)
				.then((response) => {
					showSuccess(t('integration_docusign', 'DocuSign admin options saved'))
				})
				.catch((error) => {
					showError(
						t('integration_docusign', 'Failed to save DocuSign admin options')
						+ ': ' + error.response.request.responseText,
					)
				})
				.then(() => {
					this.loading = false
				})
		},
		onOAuthClick() {
			let dummyValueProvided = false
			if (this.state.docusign_client_id === 'dummyClientNumber') {
				this.state.docusign_client_id = ''
				dummyValueProvided = true
			}
			if (this.state.docusign_client_secret === 'dummyClientSecret') {
				this.state.docusign_client_secret = ''
				dummyValueProvided = true
			}
			if (dummyValueProvided) {
				showError(t('integration_docusign', 'For security reasons, please enter your client credentials again'))
				return
			}

			const oauthState = Math.random().toString(36).substring(3)
			const scopes = [
				'signature',
				'user_read',
				'account_read',
			]
			const requestUrl = 'https://account-d.docusign.com/oauth/auth'
				+ '?client_id=' + encodeURIComponent(this.state.docusign_client_id)
				+ '&redirect_uri=' + encodeURIComponent(this.redirect_uri)
				+ '&response_type=code'
				+ '&state=' + encodeURIComponent(oauthState)
				+ '&scope=' + scopes.join(',')

			const req = {
				values: {
					docusign_oauth_state: oauthState,
					docusign_redirect_uri: this.redirect_uri,
				},
			}
			const url = generateUrl('/apps/integration_docusign/docusign-config')
			axios.put(url, req)
				.then((response) => {
					window.location.replace(requestUrl)
				})
				.catch((error) => {
					showError(
						t('integration_docusign', 'Failed to save DocuSign OAuth state')
						+ ': ' + error.response.request.responseText,
					)
				})
				.then(() => {
				})
		},
		onLogoutClick() {
			this.state.docusign_token = ''
			this.saveOptions({
				docusign_token: this.state.docusign_token,
			})
		},
	},
}
</script>

<style scoped lang="scss">
#docusign_prefs {
	.docusign-rm-cred {
		height: 44px;
		min-height: 44px;
		margin-top: 12px;
	}

	p .icon {
		margin-bottom: -3px;
	}

	.icon {
		display: inline-block;
		width: 32px;
	}

	.section-title {
		display: flex;
		gap: 12px;
	}

	.settings-hint .icon {
		width: 16px;
	}

	button .icon {
		margin: 0 !important;
	}

	.icon-docusign {
		background-image: url('../../img/app-dark.svg');
		background-size: 23px 23px;
		height: 23px;
		margin-bottom: -4px;
	}

	.form {
		display: flex;
		flex-direction: column;
		label {
			line-height: 32px;
		}
		input {
			width: 250px;
		}
	}
}

body.theme--dark .icon-docusign {
	background-image: url('../../img/app.svg');
}

</style>
