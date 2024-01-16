<template>
	<div>
		<div id="approval_prefs" class="section">
			<h2>
				<span class="icon icon-approval" />
				{{ t('approval', 'Sign with DocuSign') }}
			</h2>
			<br>
			<DocuSignSettings />
		</div>
	</div>
</template>

<script>
import DocuSignSettings from './DocuSignSettings.vue'

export default {
	name: 'AdminSettings',

	components: {
		DocuSignSettings,
	},

	props: [],

	computed: {
	},

	watch: {
	},

	mounted() {
	},

	methods: {
	},
}
</script>

<style scoped lang="scss">
// nothing yet
</style>
