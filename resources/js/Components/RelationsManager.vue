<template>
	<PkpTable>
		<template #label>
			<h3 class="text-4 font-semibold">{{ t('plugins.generic.relations.relations') }}</h3>
		</template>
		<PkpTableHeader>
			<PkpTableColumn>{{ t('plugins.generic.relations.column.relationType') }}</PkpTableColumn>
			<PkpTableColumn>{{ t('plugins.generic.relations.column.identifierType') }}</PkpTableColumn>
			<PkpTableColumn>{{ t('plugins.generic.relations.column.value') }}</PkpTableColumn>
			<PkpTableColumn class="w-[100px]">&nbsp;</PkpTableColumn>
		</PkpTableHeader>
		<PkpTableBody>
			<PkpTableRow
				v-for="(relation, index) in relations"
				:key="index"
			>
				<PkpTableCell>
					<select
						:value="relation.relationType"
						class="pkpFormField__input pkpFormField--select__input pkpFormField--select__input--sizelarge"
						@change="(e) => updateRelation(index, 'relationType', e.target.value)"
					>
						<option v-for="option in props.relationTypes" :key="option.value" :value="option.value">
							{{ option.label }}
						</option>
					</select>
				</PkpTableCell>
				<PkpTableCell>
					<select
						:value="relation.identifierType"
						class="pkpFormField__input pkpFormField--select__input pkpFormField--select__input--sizelarge"
						@change="(e) => updateRelation(index, 'identifierType', e.target.value)"
					>
						<option v-for="option in props.identifierTypes" :key="option.value" :value="option.value">
							{{ option.label }}
						</option>
					</select>
				</PkpTableCell>
				<PkpTableCell>
					<input
						:value="relation.value"
						type="text"
						:class="['pkpFormField__input', 'w-full', errors[index] ? 'pkpFormField__input--invalid' : '']"
						@input="(e) => updateRelation(index, 'value', e.target.value)"
					/>
					<div v-if="errors[index]" class="pkpFieldError" aria-live="polite">
						<div class="pkpFieldError__message">
							<PkpIcon icon="Error" class="me-1 h-4 w-4 text-on-dark" :inline="true" />
							<span>{{ errors[index] }}</span>
						</div>
					</div>
				</PkpTableCell>
				<PkpTableCell>
					<PkpButton :is-warnable="true" @click="removeRelation(index)">
						{{ t('plugins.generic.relations.button.remove') }}
					</PkpButton>
				</PkpTableCell>
			</PkpTableRow>
		</PkpTableBody>
		<template #bottom-controls>
			<PkpButton @click="addRelation">{{ t('plugins.generic.relations.button.addRelation') }}</PkpButton>
			<PkpButton :is-disabled="isSaving" @click="saveRelations">
				{{ isSaving ? t('plugins.generic.relations.button.saving') : t('plugins.generic.relations.button.save') }}
			</PkpButton>
		</template>
	</PkpTable>
</template>

<script setup>
import {computed, ref, watch} from 'vue';

const {useLocalize} = pkp.modules.useLocalize;
const {useUrl} = pkp.modules.useUrl;
const {useFetch} = pkp.modules.useFetch;
const {t} = useLocalize();

const props = defineProps({
	submission: {type: Object, required: true},
	publication: {type: Object, required: true},
	relationTypes: {type: Array, default: () => []},
	identifierTypes: {type: Array, default: () => []},
});

const relations = ref([]);
const errors = ref({});

watch(
	() => props.publication,
	(publication) => {
		if (publication) {
			relations.value = publication.relations ?? [];
			errors.value = {};
		}
	},
	{immediate: true},
);


const {apiUrl} = useUrl(
	computed(
		() => `submissions/${props.submission.id}/publications/${props.publication.id}`,
	),
);

const payload = ref({relations: []});

const {fetch: putPublication, isLoading: isSaving, validationError} = useFetch(apiUrl, {
	method: 'PUT',
	body: payload,
	expectValidationError: true,
});

async function saveRelations() {
	payload.value = {relations: JSON.parse(JSON.stringify(relations.value))};
	await putPublication();
	if (validationError.value) {
		Object.entries(validationError.value).forEach(([key, messages]) => {
			const match = key.match(/^relations\.(\d+)\./);
			if (match) {
				const index = parseInt(match[1]);
				errors.value = {...errors.value, [index]: messages[0]};
			}
		});
	}
}

function addRelation() {
	relations.value.push({
		relationType: props.relationTypes[0]?.value ?? '',
		identifierType: props.identifierTypes[0]?.value ?? '',
		value: '',
	});
}

function removeRelation(index) {
	relations.value.splice(index, 1);
	const newErrors = {...errors.value};
	delete newErrors[index];
	errors.value = newErrors;
}

function updateRelation(index, field, value) {
	relations.value[index] = {...relations.value[index], [field]: value};
	if (field === 'value' || field === 'identifierType') {
		const newErrors = {...errors.value};
		delete newErrors[index];
		errors.value = newErrors;
	}
}
</script>
