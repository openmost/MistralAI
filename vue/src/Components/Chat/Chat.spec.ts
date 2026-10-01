/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

import { flushPromises, mount, VueWrapper } from '@vue/test-utils';
import {
  afterEach, beforeEach, describe, expect, it, vi,
} from 'vitest';
import { AjaxHelper, MatomoUrl } from 'CoreHome';
import Chat from './Chat.vue';
import { AgentStatus, Recommendation } from '../../types';

// the markdown renderer dependencies (showdown) are installed in the plugin, they are not available
// to the Matomo client test runner: render the raw markdown instead
vi.mock('../Markdown.vue', async () => {
  const { defineComponent, h } = await import('vue');

  return {
    default: defineComponent({
      props: { markdown: { type: String, default: '' } },
      setup(props) {
        return () => h('div', { class: 'markdown-wrapper' }, props.markdown);
      },
    }),
  };
});

vi.mock('CoreHome', async () => {
  const { defineComponent, h } = await import('vue');

  return {
    AjaxHelper: { fetch: vi.fn(() => Promise.resolve({})) },
    MatomoUrl: { parsed: { value: { idSite: '1', period: 'day', date: 'yesterday' } } },
    translate: (key: string, ...values: unknown[]) => (values.length ? `${key}:${values.join(',')}` : key),
    Alert: defineComponent({
      props: { severity: { type: String, default: '' } },
      setup(props, { slots }) {
        return () => h('div', { class: `alert alert-${props.severity}` }, slots.default?.());
      },
    }),
  };
});

const AGENT_READY: AgentStatus = {
  mode: 'agent',
  keySource: 'system',
  mcp: 'ready',
  model: 'ministral-14b-latest',
  toolCount: 19,
  canPerformActions: true,
  recommendations: [],
};

const WRITE_MODE: Recommendation = {
  id: 'enableWriteMode',
  message: 'MistralAI_RecommendEnableWriteMode',
  action: 'MistralAI_RecommendEnableWriteModeAction',
  url: 'index.php?module=CoreAdminHome&action=generalSettings#/McpServer',
  askAdministrator: false,
};

const EMPTY_STREAM = {
  ok: true,
  body: { getReader: () => ({ read: async () => ({ done: true, value: undefined }) }) },
};

// Server-Sent Events body returning the given data lines in a single chunk
function streamResponse(dataLines: string[]) {
  const chunks = [new TextEncoder().encode(dataLines.map((line) => `data: ${line}\n\n`).join(''))];
  return {
    ok: true,
    body: {
      getReader: () => ({
        read: async () => {
          const value = chunks.shift();
          return value ? { done: false, value } : { done: true, value: undefined };
        },
      }),
    },
  };
}

type ChatInstance = {
  onSubmit: (message?: { role: string, content: string }) => Promise<void>;
  parseAgentData: (data: string) => void;
  messages: { role: string, content: string }[];
  agentSteps: { id: string, status: string, title: string }[];
  streamingContent: string;
  errored: boolean;
  errorMessage: string;
};

let fetchMock: ReturnType<typeof vi.fn>;
const wrappers: VueWrapper[] = [];

/**
 * @param status the agent status, null when the status request fails
 * @param other the response of the other requests (agent, streaming API)
 */
function mockAgentStatus(status: Partial<AgentStatus> | null, other: () => unknown = () => EMPTY_STREAM) {
  fetchMock.mockImplementation(async (url: string) => {
    if (url.includes('action=agentStatus')) {
      return status
        ? { ok: true, json: async () => ({ ...AGENT_READY, ...status }) }
        : { ok: false, json: async () => ({}) };
    }
    return other();
  });
}

async function mountChat(): Promise<VueWrapper> {
  const wrapper = mount(Chat, {
    props: {
      aiName: 'mistral-ai',
      aiLabel: 'MistralAI',
      apiMethod: 'MistralAI.getResponse',
    },
  });
  wrappers.push(wrapper);
  await flushPromises();
  return wrapper;
}

function vm(wrapper: VueWrapper): ChatInstance {
  return wrapper.vm as unknown as ChatInstance;
}

async function submit(content: string): Promise<VueWrapper> {
  const wrapper = await mountChat();
  await vm(wrapper).onSubmit({ role: 'user', content });
  await flushPromises();
  return wrapper;
}

function callTo(fragment: string) {
  return fetchMock.mock.calls.find((call) => String(call[0]).includes(fragment));
}

function requestedUrls(): string[] {
  return fetchMock.mock.calls.map((call) => String(call[0]));
}

const urlParams = MatomoUrl.parsed.value as Record<string, unknown>;
const DEFAULT_URL_PARAMS = { ...urlParams };

function requestUrl(fragment: string): URL {
  const call = fetchMock.mock.calls.find((c) => String(c[0]).includes(fragment));
  return new URL(String(call![0]), 'http://matomo.test/');
}

describe('Chat', () => {
  beforeEach(() => {
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
    vi.mocked(AjaxHelper.fetch).mockReset();
    vi.mocked(AjaxHelper.fetch).mockImplementation(() => Promise.resolve({}));
  });

  afterEach(() => {
    Object.keys(urlParams).forEach((key) => delete urlParams[key]);
    Object.assign(urlParams, DEFAULT_URL_PARAMS);
    wrappers.splice(0).forEach((wrapper) => wrapper.unmount());
    vi.unstubAllGlobals();
  });

  it('displays the first recommendation, with its link for super users', async () => {
    mockAgentStatus({
      canPerformActions: false,
      recommendations: [WRITE_MODE, { ...WRITE_MODE, id: 'other', message: 'MistralAI_Other' }],
    });

    const wrapper = await mountChat();

    const notice = wrapper.find('.ai-chat-notice');
    expect(notice.text()).toContain('MistralAI_RecommendEnableWriteMode');
    expect(notice.text()).not.toContain('MistralAI_Other');
    expect(notice.text()).not.toContain('MistralAI_AskAdministrator');
    const link = notice.find('a.ai-chat-notice-action');
    expect(link.attributes('href')).toBe(WRITE_MODE.url);
    expect(link.text()).toBe('MistralAI_RecommendEnableWriteModeAction');
  });

  it('asks the administrator, without link, for the other users', async () => {
    mockAgentStatus({
      mode: 'chat',
      mcp: 'not_installed',
      recommendations: [{
        id: 'installMcpServer',
        message: 'MistralAI_RecommendInstallMcpServer',
        action: '',
        url: '',
        askAdministrator: true,
      }],
    });

    const wrapper = await mountChat();

    const notice = wrapper.find('.ai-chat-notice');
    expect(notice.text()).toContain('MistralAI_RecommendInstallMcpServer');
    expect(notice.text()).toContain('MistralAI_AskAdministrator');
    expect(notice.find('a').exists()).toBe(false);
  });

  it('displays the text only when a super user cannot take the step from Matomo', async () => {
    mockAgentStatus({
      recommendations: [{ ...WRITE_MODE, action: '', url: '' }],
    });

    const wrapper = await mountChat();

    const notice = wrapper.find('.ai-chat-notice');
    expect(notice.text()).toBe('MistralAI_RecommendEnableWriteMode');
    expect(notice.find('a').exists()).toBe(false);
  });

  it('does not display a notice without recommendation', async () => {
    mockAgentStatus({});

    const wrapper = await mountChat();

    expect(wrapper.find('.ai-chat-notice').exists()).toBe(false);
  });

  it('streams the answer from the MistralAI API', async () => {
    mockAgentStatus(null, () => streamResponse([
      '{"choices":[{"delta":{"content":"Hello "}}]}',
      '{"choices":[{"delta":{"content":"world"}}]}',
      '[DONE]',
    ]));

    const wrapper = await submit('Hi');

    const call = fetchMock.mock.calls.find((c) => String(c[0]).includes('method=MistralAI.getStreamingResponse'));
    expect(String(call![0])).toContain('module=API');
    expect(JSON.parse((call![1].body as URLSearchParams).get('messages')!))
      .toEqual([{ role: 'user', content: 'Hi' }]);
    expect(vm(wrapper).messages).toEqual([
      { role: 'user', content: 'Hi' },
      { role: 'assistant', content: 'Hello world' },
    ]);
  });

  it('falls back to the non streaming API when the stream has no content', async () => {
    mockAgentStatus(null, () => streamResponse(['[DONE]']));
    vi.mocked(AjaxHelper.fetch).mockImplementation(() => Promise.resolve({
      choices: [{ message: { role: 'assistant', content: 'Fallback answer' } }],
    }));

    const wrapper = await submit('Hi');

    expect(AjaxHelper.fetch).toHaveBeenCalledWith(
      { method: 'MistralAI.getResponse' },
      expect.objectContaining({
        postParams: expect.objectContaining({ messages: [{ role: 'user', content: 'Hi' }] }),
      }),
    );
    expect(vm(wrapper).messages[1]).toEqual({ role: 'assistant', content: 'Fallback answer' });
  });

  it('displays the errors returned in the stream, without the fallback', async () => {
    mockAgentStatus(null, () => streamResponse([
      '{"error":{"message":"Invalid model"}}',
      '[DONE]',
    ]));

    const wrapper = await submit('Hi');

    expect(vm(wrapper).errored).toBe(true);
    expect(wrapper.find('.ai-chat-error__message').text()).toBe('Invalid model');
    expect(AjaxHelper.fetch).not.toHaveBeenCalled();
  });

  it('keeps the classic chat without notice when the agent status is unavailable', async () => {
    mockAgentStatus(null);

    const wrapper = await mountChat();
    await vm(wrapper).onSubmit({ role: 'user', content: 'Hello' });
    await flushPromises();

    expect(wrapper.find('.ai-chat-notice').exists()).toBe(false);
    expect(requestedUrls().some((url) => url.includes('module=API')
      && url.includes('method=MistralAI.getStreamingResponse'))).toBe(true);
    expect(requestedUrls().some((url) => url.includes('action=agent&'))).toBe(false);
  });

  it('uses the classic chat when the agent is not ready', async () => {
    mockAgentStatus({ mode: 'chat', mcp: 'not_installed', toolCount: 0 });

    const wrapper = await mountChat();
    await vm(wrapper).onSubmit({ role: 'user', content: 'Hello' });
    await flushPromises();

    expect(requestedUrls().some((url) => url.includes('method=MistralAI.getStreamingResponse'))).toBe(true);
    expect(requestedUrls().some((url) => url.includes('action=agent&'))).toBe(false);
  });

  it('sends the conversation to the agent when it is ready', async () => {
    mockAgentStatus({});

    const wrapper = await mountChat();
    await vm(wrapper).onSubmit({ role: 'user', content: 'How many visits?' });
    await flushPromises();

    const agentCall = fetchMock.mock.calls.find((call) => String(call[0]).includes('action=agent&'));
    expect(agentCall).toBeDefined();
    const body = agentCall![1].body as URLSearchParams;
    expect(JSON.parse(body.get('messages')!)).toEqual([{ role: 'user', content: 'How many visits?' }]);
    expect(body.get('force_api_session')).toBe('1');
    expect(String(agentCall![0])).toContain('module=MistralAI');
  });

  it('builds the tool steps and the answer from the agent events', async () => {
    mockAgentStatus({});
    const wrapper = await mountChat();
    const chat = vm(wrapper);

    chat.parseAgentData(JSON.stringify({
      type: 'tool_call', id: 'call_1', name: 'matomo_site_list', title: 'List sites',
    }));
    chat.parseAgentData(JSON.stringify({ type: 'tool_result', id: 'call_1', isError: false }));
    chat.parseAgentData(JSON.stringify({
      type: 'tool_call', id: 'call_2', name: 'matomo_goal_get', title: '',
    }));
    chat.parseAgentData(JSON.stringify({ type: 'tool_result', id: 'call_2', isError: true }));
    chat.parseAgentData(JSON.stringify({ type: 'text', content: 'First part' }));
    chat.parseAgentData(JSON.stringify({ type: 'text', content: 'Second part' }));
    chat.parseAgentData('not json');

    expect(chat.agentSteps.map(({ id, status, title }) => ({ id, status, title }))).toEqual([
      { id: 'call_1', status: 'done', title: 'List sites' },
      { id: 'call_2', status: 'error', title: 'matomo_goal_get' },
    ]);
    expect(chat.streamingContent).toBe('First part\n\nSecond part');
  });

  it('displays the agent errors', async () => {
    mockAgentStatus({});
    const wrapper = await mountChat();

    vm(wrapper).parseAgentData(JSON.stringify({ type: 'error', message: 'Rate limit exceeded' }));
    await flushPromises();

    expect(vm(wrapper).errored).toBe(true);
    expect(wrapper.find('.ai-chat-error[role="alert"]').text()).toBe('Rate limit exceeded');
  });

  it('displays the agent errors with the settings link, without retrying', async () => {
    mockAgentStatus({}, () => streamResponse([
      JSON.stringify({
        type: 'error',
        message: 'MistralAI rate limit',
        settingsUrl: 'index.php?module=MistralAI&action=manage',
        settingsLabel: 'Change the model',
      }),
      '[DONE]',
    ]));

    const wrapper = await submit('Hi');

    expect(vm(wrapper).errored).toBe(true);
    expect(wrapper.find('.ai-chat-error__message').text()).toBe('MistralAI rate limit');
    const link = wrapper.find('a.ai-chat-settings-link');
    expect(link.attributes('href')).toBe('index.php?module=MistralAI&action=manage');
    expect(link.text()).toBe('Change the model');
    expect(requestedUrls().filter((url) => url.includes('action=agent&'))).toHaveLength(1);
    expect(requestedUrls().some((url) => url.includes('getStreamingResponse'))).toBe(false);
    expect(AjaxHelper.fetch).not.toHaveBeenCalled();
  });

  it('sends a single request when the chat is closed during the answer', async () => {
    mockAgentStatus({}, async () => {
      const error = new Error('The user aborted a request.');
      error.name = 'AbortError';
      throw error;
    });

    const wrapper = await submit('Hi');

    expect(vm(wrapper).errored).toBe(false);
    expect(requestedUrls().filter((url) => url.includes('action=agent&'))).toHaveLength(1);
    expect(requestedUrls().some((url) => url.includes('getStreamingResponse'))).toBe(false);
    expect(AjaxHelper.fetch).not.toHaveBeenCalled();
  });
  it('announces each complete answer once, never the streamed chunks', async () => {
    const chunks = [
      {
        type: 'tool_call', id: 'call_1', name: 'matomo_visits', title: 'Visits',
      },
      { type: 'tool_result', id: 'call_1', isError: false },
      { type: 'text', content: '**257 visits**' },
      { type: 'text', content: 'Bounce rate | 58%' },
    ].map((event) => new TextEncoder().encode(`data: ${JSON.stringify(event)}
`));
    const seenWhileStreaming: string[] = [];
    let wrapper: VueWrapper | null = null;

    fetchMock.mockImplementation(async (url: string) => {
      if (url.includes('action=agentStatus')) {
        return { ok: true, json: async () => AGENT_READY };
      }
      return {
        ok: true,
        body: {
          getReader: () => ({
            read: async () => {
              await flushPromises();
              seenWhileStreaming.push(wrapper!.find('[aria-live="polite"]').text());
              const value = chunks.shift();
              return value ? { done: false, value } : { done: true, value: undefined };
            },
          }),
        },
      };
    });

    wrapper = await mountChat();
    await vm(wrapper).onSubmit({ role: 'user', content: 'KPIs?' });
    await flushPromises();

    await vi.waitFor(() => {
      expect(wrapper!.find('[aria-live="polite"]').text())
        .toBe('MistralAI_AnswerAnnouncement:MistralAI 257 visits Bounce rate 58%');
    });
    expect(seenWhileStreaming.length).toBeGreaterThan(3);
    expect(seenWhileStreaming.every((text) => text === '')).toBe(true);
  });

  it('copies the markdown of a complete answer', async () => {
    mockAgentStatus({});
    const writeText = vi.fn(async () => undefined);
    vi.stubGlobal('navigator', { clipboard: { writeText } });
    vi.stubGlobal('isSecureContext', true);
    const wrapper = await mountChat();
    const chat = wrapper.vm as unknown as { messages: { role: string, content: string }[] };
    chat.messages.push({ role: 'user', content: 'Q' }, { role: 'assistant', content: '**42** visits' });
    await flushPromises();

    const copy = wrapper.find('.ai-chat-message__actions button');
    expect(copy.attributes('aria-label')).toBe('MistralAI_CopyAnswer');
    await copy.trigger('click');
    await flushPromises();

    expect(writeText).toHaveBeenCalledWith('**42** visits');
    expect(copy.attributes('aria-label')).toBe('MistralAI_AnswerCopied');
  });

  it('suggests translated questions on an empty conversation, and sends the picked one', async () => {
    mockAgentStatus({});
    const wrapper = mount(Chat, {
      props: {
        aiName: 'mistral-ai',
        aiLabel: 'MistralAI',
        apiMethod: 'MistralAI.getResponse',
        showEmptyState: true,
      },
    });
    wrappers.push(wrapper);
    await flushPromises();

    const suggestions = wrapper.findAll('.ai-chat-empty__suggestion');
    expect(suggestions.map((suggestion) => suggestion.text())).toEqual([
      'MistralAI_SuggestionWeeklyKpis',
      'MistralAI_SuggestionTopPages',
      'MistralAI_SuggestionTrafficSources',
      'MistralAI_SuggestionGoals',
    ]);

    await suggestions[0].trigger('click');
    await flushPromises();

    const agentCall = fetchMock.mock.calls.find((call) => String(call[0]).includes('action=agent&'));
    const body = agentCall![1].body as URLSearchParams;
    expect(JSON.parse(body.get('messages')!)).toEqual([
      { role: 'user', content: 'MistralAI_SuggestionWeeklyKpis' },
    ]);
    expect(wrapper.find('.ai-chat-empty').exists()).toBe(false);
  });
  it('sends no segment nor comparison when the URL has none', async () => {
    mockAgentStatus({});

    await submit('Hi');

    [requestUrl('action=agentStatus'), requestUrl('action=agent&')].forEach((url) => {
      expect(url.searchParams.has('segment')).toBe(false);
      expect([...url.searchParams.keys()].some((key) => key.startsWith('compare'))).toBe(false);
    });
  });

  it('sends the segment and the comparisons of the URL in the query of every request', async () => {
    Object.assign(urlParams, {
      segment: 'browserCode==FF',
      comparePeriods: ['month'],
      compareDates: ['2026-08-01'],
      compareSegments: ['', 'deviceType==smartphone'],
    });
    mockAgentStatus({});

    await submit('Hi');

    [requestUrl('action=agentStatus'), requestUrl('action=agent&')].forEach((url) => {
      expect(url.searchParams.get('segment')).toBe('browserCode==FF');
      expect(url.searchParams.getAll('comparePeriods[]')).toEqual(['month']);
      expect(url.searchParams.getAll('compareDates[]')).toEqual(['2026-08-01']);
      expect(url.searchParams.getAll('compareSegments[]')).toEqual(['', 'deviceType==smartphone']);
    });
    const body = callTo('action=agent&')![1].body as URLSearchParams;
    expect(body.has('segment')).toBe(false);
  });

  it('sends the segment and the comparisons with the classic chat stream', async () => {
    Object.assign(urlParams, { segment: 'visitorType==new', compareDates: ['2026-09-01'] });
    mockAgentStatus({ mode: 'chat', mcp: 'not_installed', toolCount: 0 });

    await submit('Hi');

    const url = requestUrl('method=MistralAI.getStreamingResponse');
    expect(url.searchParams.get('segment')).toBe('visitorType==new');
    expect(url.searchParams.getAll('compareDates[]')).toEqual(['2026-09-01']);
    expect(url.searchParams.getAll('comparePeriods[]')).toEqual([]);
  });
});
