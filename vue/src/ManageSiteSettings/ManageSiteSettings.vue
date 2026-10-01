<!--
  Matomo - free/libre analytics platform

  @link    https://matomo.org
  @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
-->

<template>
  <div>
    <ContentBlock :content-title="translate('MistralAI_SettingsConnectionTitle')">
      <p>{{ translate('MistralAI_SiteSettingsIntro') }}</p>

      <div
        v-for="field in connectionFields"
        :key="field.name"
      >
        <Field
          :uicontrol="field.uicontrol"
          :name="`mistralAi_${field.name}`"
          :title="field.title"
          :inline-help="field.description"
          :options="field.options"
          v-model="values[field.name]"
        />
      </div>

      <div id="mistralAiSiteNotice_connection" />
      <div class="mistralAiActions">
        <SaveButton
          class="mistralAiSaveConnection"
          :saving="isSaving.connection"
          @confirm="save('connection')"
        />
        <button
          v-if="hasApiKey"
          type="button"
          class="btn btn-outline mistralAiDeleteApiKey"
          :disabled="isDeletingApiKey"
          @click="confirmDeleteApiKey()"
        >
          {{ translate('MistralAI_DeleteApiKey') }}
        </button>
      </div>
    </ContentBlock>

    <ContentBlock :content-title="translate('MistralAI_SettingsPromptsTitle')">
      <p>{{ translate('MistralAI_SiteSettingsPromptsIntro') }}</p>

      <div
        v-for="field in promptFields"
        :key="field.name"
        class="mistralAiPromptField"
      >
        <Field
          :uicontrol="field.uicontrol"
          :name="`mistralAi_${field.name}`"
          :title="field.title"
          :inline-help="field.description"
          v-model="values[field.name]"
        />
      </div>

      <div id="mistralAiSiteNotice_prompts" />
      <div class="mistralAiActions">
        <SaveButton
          class="mistralAiSavePrompts"
          :saving="isSaving.prompts"
          @confirm="save('prompts')"
        />
        <button
          v-if="hasCustomPrompt"
          type="button"
          class="btn btn-outline mistralAiResetPrompts"
          :title="translate('MistralAI_UseGeneralPromptHelp')"
          @click="resetPrompts()"
        >
          {{ translate('MistralAI_UseGeneralPrompt') }}
        </button>
      </div>

      <div
        v-if="generalSettingsUrl"
        class="mistralAiGeneralSettingsLink"
      >
        {{ translate('MistralAI_SiteSettingsGeneralSettings') }}
        <a :href="generalSettingsUrl">{{ translate('MistralAI_SystemSettingsLink') }}</a>
      </div>
    </ContentBlock>

    <div
      class="ui-confirm"
      ref="confirmDeleteApiKeyModal"
    >
      <h2>{{ translate('MistralAI_DeleteApiKeyConfirmTitle') }}</h2>
      <p>{{ translate('MistralAI_DeleteSiteApiKeyConfirmText') }}</p>
      <input
        role="yes"
        type="button"
        :value="translate('General_Yes')"
      />
      <input
        role="no"
        type="button"
        :value="translate('General_No')"
      />
    </div>
  </div>
</template>

<script lang="ts">
import {
  computed,
  defineComponent,
  PropType,
  reactive,
  ref,
} from 'vue';
import {
  AjaxHelper,
  ContentBlock,
  Matomo,
  NotificationsStore,
  translate,
} from 'CoreHome';
import { Field, SaveButton } from 'CorePluginsAdmin';

const PROMPT_FIELDS = ['chatBasePrompt', 'insightBasePrompt'];

const API_KEY_PLACEHOLDER = '******';

type Card = 'connection' | 'prompts';

export interface SiteSettingOption {
  key: string;
  value: string;
}

export interface SiteSettingField {
  name: string;
  uicontrol: string;
  title: string;
  description: string;
  options?: SiteSettingOption[];
}

/**
 * Mistral AI settings of a site, rendered on the Mistral AI page of the Websites administration.
 * Empty values use the general settings, the site is chosen with the site selector of the page.
 * Each card saves only its own fields, the unsaved edits of the other card are kept.
 */
export default defineComponent({
  name: 'ManageSiteSettings',
  components: {
    ContentBlock,
    Field,
    SaveButton,
  },
  props: {
    idSite: {
      type: [Number, String],
      required: true,
    },
    fields: {
      type: Array as PropType<SiteSettingField[]>,
      required: true,
    },
    settings: {
      type: Object as PropType<Record<string, string>>,
      required: true,
    },
    generalSettingsUrl: {
      type: String,
      default: '',
    },
    // prompts of the general settings, used by the website when its own prompt is empty
    generalPrompts: {
      type: Object as PropType<Record<string, string>>,
      default: () => ({}),
    },
  },
  setup(props) {
    const values = reactive<Record<string, string>>({ ...props.settings });
    const isSaving = reactive<Record<Card, boolean>>({ connection: false, prompts: false });
    const hasApiKey = ref(!!props.settings.apiKey);
    const isDeletingApiKey = ref(false);
    const confirmDeleteApiKeyModal = ref<HTMLElement | null>(null);

    const connectionFields = computed(
      () => props.fields.filter((field) => !PROMPT_FIELDS.includes(field.name)),
    );
    const promptFields = computed(
      () => props.fields.filter((field) => PROMPT_FIELDS.includes(field.name)),
    );

    const hasCustomPrompt = computed(
      () => PROMPT_FIELDS.some((name) => !!(values[name] || '').trim()),
    );

    // the website goes back to the general prompts, the fields are left empty rather than copied
    const resetPrompts = () => {
      PROMPT_FIELDS.forEach((name) => {
        values[name] = '';
      });
    };

    const notify = (card: Card, context: 'success' | 'error', message: string) => {
      NotificationsStore.show({
        message,
        context,
        type: 'transient',
        id: `mistralAiSiteSettingsNotice_${card}`,
        placeat: `#mistralAiSiteNotice_${card}`,
      });
    };

    const save = (card: Card) => {
      isSaving[card] = true;

      // the fields of the other card are left out, they keep their saved values
      const cardFields = card === 'prompts' ? promptFields.value : connectionFields.value;
      const postParams: Record<string, string | number> = { idSite: props.idSite };
      cardFields.forEach((field) => {
        postParams[field.name] = values[field.name] || '';
      });

      AjaxHelper.post(
        { method: 'MistralAI.setSiteSettings' },
        postParams,
        { createErrorNotification: false },
      ).then(() => {
        if (card === 'connection') {
          // an empty key keeps the saved key
          hasApiKey.value = hasApiKey.value || !!values.apiKey;
          values.apiKey = hasApiKey.value ? API_KEY_PLACEHOLDER : '';
        }
        if (card === 'prompts') {
          // a prompt equal to the general prompt is saved empty
          PROMPT_FIELDS.forEach((name) => {
            const general = (props.generalPrompts[name] || '').trim();
            if (general && (values[name] || '').trim() === general) {
              values[name] = '';
            }
          });
        }
        notify(card, 'success', translate('General_YourChangesHaveBeenSaved'));
      }).catch((error: Error) => {
        notify(card, 'error', (error && error.message) || translate('MistralAI_AnErrorOccurred'));
      }).finally(() => {
        isSaving[card] = false;
      });
    };

    // an empty key keeps the saved key: only this explicit request removes it
    const deleteApiKey = () => {
      isDeletingApiKey.value = true;
      AjaxHelper.post(
        { method: 'MistralAI.setSiteSettings' },
        { idSite: props.idSite, deleteApiKey: 1 },
        { createErrorNotification: false },
      ).then(() => {
        hasApiKey.value = false;
        values.apiKey = '';
        notify('connection', 'success', translate('MistralAI_DeleteApiKeyDone'));
      }).catch((error: Error) => {
        const message = (error && error.message) || translate('MistralAI_AnErrorOccurred');
        notify('connection', 'error', message);
      }).finally(() => {
        isDeletingApiKey.value = false;
      });
    };

    const confirmDeleteApiKey = () => {
      Matomo.helper.modalConfirm(confirmDeleteApiKeyModal.value as HTMLElement, {
        yes: deleteApiKey,
      });
    };

    return {
      translate,
      values,
      isSaving,
      connectionFields,
      promptFields,
      hasCustomPrompt,
      resetPrompts,
      hasApiKey,
      isDeletingApiKey,
      confirmDeleteApiKeyModal,
      confirmDeleteApiKey,
      save,
    };
  },
});
</script>

<style lang="less" scoped>
// the secondary actions sit on the line of the Save button, and wrap below it on narrow screens
.mistralAiActions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
}

// a div: the Matomo card resets the margin of its paragraphs
.mistralAiGeneralSettingsLink {
  margin-top: 2rem;
}
</style>
