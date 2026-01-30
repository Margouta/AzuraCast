<template>
    <loading :loading="loading" lazy>
        <card-page :title="$gettext('OAuth Authentication')">
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-check form-switch">
                            <input
                                id="oauth_enabled_toggle"
                                type="checkbox"
                                class="form-check-input"
                                v-model="oauthEnabled"
                                @change="onToggleOAuth"
                            >
                            <label
                                for="oauth_enabled_toggle"
                                class="form-check-label"
                            >
                                {{ $gettext('Enable OAuth Authentication') }}
                            </label>
                        </div>
                        <small class="form-text text-muted d-block mt-2">
                            {{ $gettext('Allow users to sign in using OAuth 2.0 providers like Google, GitHub, etc.') }}
                        </small>
                    </div>
                </div>

                <hr>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <h5>{{ $gettext('OAuth Providers') }}</h5>
                        <p
                            v-if="providers.length === 0"
                            class="text-muted"
                        >
                            {{ $gettext('No OAuth providers configured yet.') }}
                        </p>

                        <div
                            v-for="provider in providers"
                            :key="provider.provider"
                            class="card mb-3"
                        >
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h6 class="mb-3">
                                            {{ provider.provider.toUpperCase() }}
                                        </h6>

                                        <div class="form-check form-switch mb-3">
                                            <input
                                                :id="`provider_enabled_${provider.provider}`"
                                                type="checkbox"
                                                class="form-check-input"
                                                :checked="provider.enabled"
                                                @change="(e) => onToggleProvider(provider, (e.target as HTMLInputElement).checked)"
                                            >
                                            <label
                                                :for="`provider_enabled_${provider.provider}`"
                                                class="form-check-label"
                                            >
                                                {{ $gettext('Enabled') }}
                                            </label>
                                        </div>

                                        <div class="form-group mb-3">
                                            <label :for="`client_id_${provider.provider}`">
                                                {{ $gettext('Client ID') }}
                                            </label>
                                            <input
                                                :id="`client_id_${provider.provider}`"
                                                type="text"
                                                class="form-control"
                                                :value="provider.client_id"
                                                @change="(e) => onUpdateProvider(provider, 'client_id', (e.target as HTMLInputElement).value)"
                                                :disabled="!oauthEnabled"
                                            >
                                        </div>

                                        <div class="form-group mb-3">
                                            <label :for="`client_secret_${provider.provider}`">
                                                {{ $gettext('Client Secret') }}
                                            </label>
                                            <input
                                                :id="`client_secret_${provider.provider}`"
                                                type="password"
                                                class="form-control"
                                                :value="provider.client_secret"
                                                @change="(e) => onUpdateProvider(provider, 'client_secret', (e.target as HTMLInputElement).value)"
                                                :disabled="!oauthEnabled"
                                            >
                                        </div>

                                        <div
                                            v-if="provider.authorization_endpoint"
                                            class="form-group mb-3"
                                        >
                                            <label :for="`auth_endpoint_${provider.provider}`">
                                                {{ $gettext('Authorization Endpoint') }}
                                            </label>
                                            <input
                                                :id="`auth_endpoint_${provider.provider}`"
                                                type="url"
                                                class="form-control"
                                                :value="provider.authorization_endpoint"
                                                @change="(e) => onUpdateProvider(provider, 'authorization_endpoint', (e.target as HTMLInputElement).value)"
                                                :disabled="!oauthEnabled"
                                            >
                                        </div>

                                        <div
                                            v-if="provider.token_endpoint"
                                            class="form-group mb-3"
                                        >
                                            <label :for="`token_endpoint_${provider.provider}`">
                                                {{ $gettext('Token Endpoint') }}
                                            </label>
                                            <input
                                                :id="`token_endpoint_${provider.provider}`"
                                                type="url"
                                                class="form-control"
                                                :value="provider.token_endpoint"
                                                @change="(e) => onUpdateProvider(provider, 'token_endpoint', (e.target as HTMLInputElement).value)"
                                                :disabled="!oauthEnabled"
                                            >
                                        </div>

                                        <div
                                            v-if="provider.userinfo_endpoint"
                                            class="form-group mb-3"
                                        >
                                            <label :for="`userinfo_endpoint_${provider.provider}`">
                                                {{ $gettext('User Info Endpoint') }}
                                            </label>
                                            <input
                                                :id="`userinfo_endpoint_${provider.provider}`"
                                                type="url"
                                                class="form-control"
                                                :value="provider.userinfo_endpoint"
                                                @change="(e) => onUpdateProvider(provider, 'userinfo_endpoint', (e.target as HTMLInputElement).value)"
                                                :disabled="!oauthEnabled"
                                            >
                                        </div>

                                        <div
                                            v-if="provider.scope"
                                            class="form-group mb-3"
                                        >
                                            <label :for="`scope_${provider.provider}`">
                                                {{ $gettext('Scopes') }}
                                            </label>
                                            <input
                                                :id="`scope_${provider.provider}`"
                                                type="text"
                                                class="form-control"
                                                :value="provider.scope"
                                                @change="(e) => onUpdateProvider(provider, 'scope', (e.target as HTMLInputElement).value)"
                                                :disabled="!oauthEnabled"
                                                :placeholder="$gettext('openid email profile')"
                                            >
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <button
                                            type="button"
                                            class="btn btn-danger w-100"
                                            @click="onRemoveProvider(provider.provider)"
                                            :disabled="!oauthEnabled"
                                        >
                                            {{ $gettext('Remove Provider') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="form-group mb-3">
                            <label for="new_provider">
                                {{ $gettext('Add New Provider') }}
                            </label>
                            <div class="input-group">
                                <input
                                    id="new_provider"
                                    v-model="newProviderName"
                                    type="text"
                                    class="form-control"
                                    :placeholder="$gettext('e.g., google, github, custom')"
                                    :disabled="!oauthEnabled"
                                >
                                <button
                                    type="button"
                                    class="btn btn-primary"
                                    @click="onAddProvider"
                                    :disabled="!oauthEnabled || !newProviderName"
                                >
                                    {{ $gettext('Add') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col-md-12">
                        <button
                            type="button"
                            class="btn btn-primary"
                            @click="onSave"
                            :disabled="loading"
                        >
                            {{ $gettext('Save Settings') }}
                        </button>
                        <button
                            v-if="hasChanges"
                            type="button"
                            class="btn btn-secondary ms-2"
                            @click="onReset"
                            :disabled="loading"
                        >
                            {{ $gettext('Cancel') }}
                        </button>
                    </div>
                </div>
            </div>
        </card-page>
    </loading>
</template>

<script setup lang="ts">
import {useAxios} from "~/vendor/axios";
import {useTranslate} from "~/vendor/gettext";
import {ref, watch} from "vue";
import CardPage from "~/components/Common/CardPage.vue";
import Loading from "~/components/Common/Loading.vue";

interface OAuthSettingResponse {
    provider: string;
    enabled: boolean;
    client_id: string;
    client_secret: string;
    authorization_endpoint: string | null;
    token_endpoint: string | null;
    userinfo_endpoint: string | null;
    scope: string | null;
}

const {axios} = useAxios();
const {$gettext} = useTranslate();

const loading = ref(false);
const oauthEnabled = ref(false);
const providers = ref<OAuthSettingResponse[]>([]);
const newProviderName = ref('');
const originalProviders = ref<OAuthSettingResponse[]>([]);

const hasChanges = ref(false);

watch([oauthEnabled, providers], () => {
    hasChanges.value = true;
}, {deep: true});

const loadProviders = async () => {
    loading.value = true;
    try {
        const response = await axios.get<OAuthSettingResponse[]>('/api/admin/oauth-settings');
        providers.value = response.data || [];
        originalProviders.value = JSON.parse(JSON.stringify(providers.value));
    } catch (error) {
        console.error('Failed to load OAuth providers:', error);
    } finally {
        loading.value = false;
    }
};

const onToggleOAuth = () => {
    hasChanges.value = true;
};

const onToggleProvider = (provider: OAuthSettingResponse, enabled: boolean) => {
    provider.enabled = enabled;
    hasChanges.value = true;
};

const onUpdateProvider = (provider: OAuthSettingResponse, field: string, value: string) => {
    (provider as any)[field] = value;
    hasChanges.value = true;
};

const onAddProvider = async () => {
    if (!newProviderName.value.trim()) {
        return;
    }

    const providerName = newProviderName.value.toLowerCase().trim();

    // Check if provider already exists
    if (providers.value.some((p: OAuthSettingResponse) => p.provider === providerName)) {
        alert($gettext('Provider already exists'));
        return;
    }

    // Set default endpoints based on provider type
    const newProvider: OAuthSettingResponse = {
        provider: providerName,
        enabled: false,
        client_id: '',
        client_secret: '',
        authorization_endpoint: getDefaultAuthEndpoint(providerName),
        token_endpoint: getDefaultTokenEndpoint(providerName),
        userinfo_endpoint: getDefaultUserinfoEndpoint(providerName),
        scope: 'openid email profile',
    };

    providers.value.push(newProvider);
    newProviderName.value = '';
    hasChanges.value = true;
};

const onRemoveProvider = async (providerName: string) => {
    if (confirm($gettext('Are you sure you want to remove this provider?'))) {
        providers.value = providers.value.filter((p: OAuthSettingResponse) => p.provider !== providerName);
        hasChanges.value = true;
    }
};

const onSave = async () => {
    loading.value = true;
    try {
        // Save each provider
        for (const provider of providers.value) {
            await axios.post('/api/admin/oauth-settings', provider);
        }

        // Remove providers that were deleted
        const currentProviderNames = providers.value.map((p: OAuthSettingResponse) => p.provider);
        for (const original of originalProviders.value) {
            if (!currentProviderNames.includes(original.provider)) {
                await axios.delete(`/api/admin/oauth-settings/${original.provider}`);
            }
        }

        originalProviders.value = JSON.parse(JSON.stringify(providers.value));
        hasChanges.value = false;

        // Show success message
        alert($gettext('OAuth settings saved successfully'));
    } catch (error) {
        console.error('Failed to save OAuth settings:', error);
        alert($gettext('Failed to save settings'));
    } finally {
        loading.value = false;
    }
};

const onReset = () => {
    if (confirm($gettext('Discard unsaved changes?'))) {
        providers.value = JSON.parse(JSON.stringify(originalProviders.value));
        hasChanges.value = false;
    }
};

const getDefaultAuthEndpoint = (provider: string): string | null => {
    const endpoints: Record<string, string> = {
        google: 'https://accounts.google.com/o/oauth2/auth',
        github: 'https://github.com/login/oauth/authorize',
        microsoft: 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
    };
    return endpoints[provider] || null;
};

const getDefaultTokenEndpoint = (provider: string): string | null => {
    const endpoints: Record<string, string> = {
        google: 'https://oauth2.googleapis.com/token',
        github: 'https://github.com/login/oauth/access_token',
        microsoft: 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
    };
    return endpoints[provider] || null;
};

const getDefaultUserinfoEndpoint = (provider: string): string | null => {
    const endpoints: Record<string, string> = {
        google: 'https://www.googleapis.com/oauth2/v2/userinfo',
        github: 'https://api.github.com/user',
        microsoft: 'https://graph.microsoft.com/v1.0/me',
    };
    return endpoints[provider] || null;
};

loadProviders();
</script>

<style scoped>
.form-control:disabled,
.form-check-input:disabled {
    cursor: not-allowed;
}
</style>
