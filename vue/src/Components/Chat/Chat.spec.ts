/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

/* eslint-disable @typescript-eslint/no-var-requires, global-require */

// provided by the Matomo client test runner
// eslint-disable-next-line import/no-extraneous-dependencies
import { flushPromises, mount } from '@vue/test-utils';
import { AjaxHelper, MatomoUrl } from 'CoreHome';
import Chat from './Chat.vue';
import { AgentStatus, Recommendation } from '../../types';

// the jsdom of the Matomo 5 test runner has no TextDecoder nor TextEncoder, used by the streams
const testWindow = window as unknown as { TextDecoder?: unknown, TextEncoder?: unknown };
if (!testWindow.TextDecoder) {
  testWindow.TextDecoder = require('util').TextDecoder;
}
if (!testWindow.TextEncoder) {
  testWindow.TextEncoder = require('util').TextEncoder;
}

// the markdown renderer dependencies (showdown) are installed in the plugin, they are not available
// to the Matomo client test runner: render the raw markdown instead
jest.mock('../Markdown.vue', () => {
  const { defineComponent, h } = require('vue');

  return {
    __esModule: true,
    default: defineComponent({
      props: { markdown: { type: String, default: '' } },
      setup(props: { markdown: string }) {
        return () => h('div', { class: 'markdown-wrapper' }, props.markdown);
      },
    }),
  };
});

jest.mock('CoreHome', () => {
  const { defineComponent, h } = require('vue');

  return {
    AjaxHelper: { fetch: jest.fn(() => Promise.resolve({})) },
    MatomoUrl: { parsed: { value: { idSite: '1', period: 'day', date: 'yesterday' } } },
    translate: (key: string, ...values: unknown[]) => (
      values.length ? `${key}:${values.join(',')}` : key
    ),
    Alert: defineComponent({
      props: { severity: { type: String, default: '' } },
      setup(
        props: { severity: string },
        { slots }: { slots: { default?: () => unknown } },
      ) {
        return () => h(
          'div',
          { class: `alert alert-${props.severity}` },
          slots.default ? slots.default() : undefined,
        );
      },
    }),
  };
}, { virtual: true });

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

type ChatInstance = {
  onSubmit: (message?: { role: string, content: string }) => Promise<void>;
  parseAgentData: (data: string) => void;
  messages: { role: string, content: string }[];
  agentSteps: { id: string, status: string, title: string }[];
  streamingContent: string;
  errored: boolean;
};

type ChatWrapper = ReturnType<typeof mount>;
type FetchResponse = Record<string, unknown>;

let fetchMock: jest.Mock;
const originalFetch = window.fetch;
const wrappers: ChatWrapper[] = [];
const ajaxFetch = AjaxHelper.fetch as unknown as jest.Mock;

// jsdom globals replaced by a test, restored after it
const restoreStubs: Array<() => void> = [];

function stubProperty(target: unknown, name: string, value: unknown): void {
  const descriptor = Object.getOwnPropertyDescriptor(target, name);
  Object.defineProperty(target, name, { configurable: true, writable: true, value });
  restoreStubs.push(() => {
    if (descriptor) {
      Object.defineProperty(target, name, descriptor);
    } else {
      delete (target as Record<string, unknown>)[name];
    }
  });
}

// polls an assertion until it passes, the streams resolve over several ticks
async function waitFor(assertion: () => void, timeout = 1000): Promise<void> {
  const start = Date.now();
  for (;;) {
    try {
      assertion();
      return;
    } catch (error) {
      if (Date.now() - start > timeout) {
        throw error;
      }
    }
    // eslint-disable-next-line no-await-in-loop
    await new Promise((resolve) => { setTimeout(resolve, 10); });
  }
}

// Server-Sent Events body returning the given data lines in a single chunk
function streamResponse(dataLines: string[]): FetchResponse {
  const text = dataLines.map((line) => `data: ${line}\n\n`).join('');
  const chunks = [new TextEncoder().encode(text)];
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

/**
 * @param status the agent status, null when the status request fails
 * @param other the response of the other requests (agent, streaming API)
 */
function mockFetch(status: Partial<AgentStatus> | null, other: () => Promise<FetchResponse>) {
  fetchMock.mockImplementation(async (url: string) => {
    if (url.includes('action=agentStatus')) {
      return status
        ? { ok: true, json: async () => ({ ...AGENT_READY, ...status }) }
        : { ok: false, json: async () => ({}) };
    }
    return other();
  });
}

async function mountChat(): Promise<ChatWrapper> {
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

function vm(wrapper: ChatWrapper): ChatInstance {
  return wrapper.vm as unknown as ChatInstance;
}

async function submit(content: string): Promise<ChatWrapper> {
  const wrapper = await mountChat();
  await vm(wrapper).onSubmit({ role: 'user', content });
  await flushPromises();
  return wrapper;
}

function requestedUrls(): string[] {
  return fetchMock.mock.calls.map((call) => String(call[0]));
}

function callTo(fragment: string) {
  return fetchMock.mock.calls.find((call) => String(call[0]).includes(fragment));
}

describe('Chat', () => {
  beforeEach(() => {
    fetchMock = jest.fn();
    window.fetch = fetchMock as unknown as typeof window.fetch;
    ajaxFetch.mockReset();
    ajaxFetch.mockImplementation(() => Promise.resolve({}));
  });

  afterEach(() => {
    wrappers.splice(0).forEach((wrapper) => wrapper.unmount());
    window.fetch = originalFetch;
    restoreStubs.splice(0).reverse().forEach((restore) => restore());
  });

  describe('classic chat', () => {
    it('streams the answer from the MistralAI API', async () => {
      mockFetch(null, async () => streamResponse([
        '{"choices":[{"delta":{"content":"Hello "}}]}',
        '{"choices":[{"delta":{"content":"world"}}]}',
        '[DONE]',
      ]));

      const wrapper = await submit('Hi');

      const [url, options] = callTo('method=MistralAI.getStreamingResponse')!;
      expect(String(url)).toContain('module=API');
      expect(JSON.parse((options.body as URLSearchParams).get('messages')!))
        .toEqual([{ role: 'user', content: 'Hi' }]);
      expect(vm(wrapper).messages).toEqual([
        { role: 'user', content: 'Hi' },
        { role: 'assistant', content: 'Hello world' },
      ]);
    });

    it('falls back to the non streaming API when the stream has no content', async () => {
      mockFetch(null, async () => streamResponse(['[DONE]']));
      ajaxFetch.mockImplementation(() => Promise.resolve({
        choices: [{ message: { role: 'assistant', content: 'Fallback answer' } }],
      }));

      const wrapper = await submit('Hi');

      expect(ajaxFetch).toHaveBeenCalledWith(
        { method: 'MistralAI.getResponse' },
        expect.objectContaining({
          postParams: expect.objectContaining({ messages: [{ role: 'user', content: 'Hi' }] }),
        }),
      );
      expect(vm(wrapper).messages[1]).toEqual({ role: 'assistant', content: 'Fallback answer' });
    });

    it('displays the errors returned in the stream', async () => {
      mockFetch(null, async () => streamResponse([
        '{"error":{"message":"Invalid model"}}',
        '[DONE]',
      ]));

      const wrapper = await submit('Hi');

      expect(vm(wrapper).errored).toBe(true);
      expect(wrapper.find('.ai-chat-error__message').text()).toBe('Invalid model');
      expect(ajaxFetch).not.toHaveBeenCalled();
    });

    it('is used when McpServer is not ready', async () => {
      mockFetch(
        { mode: 'chat', mcp: 'not_installed', toolCount: 0 },
        async () => streamResponse(['[DONE]']),
      );

      await submit('Hello');

      expect(requestedUrls().some((url) => url.includes('getStreamingResponse'))).toBe(true);
      expect(requestedUrls().some((url) => url.includes('action=agent&'))).toBe(false);
    });
  });

  describe('recommendations', () => {
    it('displays the first recommendation, with its link for super users', async () => {
      mockFetch({
        canPerformActions: false,
        recommendations: [WRITE_MODE, { ...WRITE_MODE, id: 'other', message: 'MistralAI_Other' }],
      }, async () => streamResponse([]));

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
      mockFetch({
        mode: 'chat',
        mcp: 'not_installed',
        recommendations: [{
          id: 'installMcpServer',
          message: 'MistralAI_RecommendInstallMcpServer',
          action: '',
          url: '',
          askAdministrator: true,
        }],
      }, async () => streamResponse([]));

      const wrapper = await mountChat();

      const notice = wrapper.find('.ai-chat-notice');
      expect(notice.text()).toContain('MistralAI_RecommendInstallMcpServer');
      expect(notice.text()).toContain('MistralAI_AskAdministrator');
      expect(notice.find('a').exists()).toBe(false);
    });

    it('displays no notice without recommendation, nor when the status fails', async () => {
      mockFetch({}, async () => streamResponse([]));
      const ready = await mountChat();
      expect(ready.find('.ai-chat-notice').exists()).toBe(false);

      mockFetch(null, async () => streamResponse([]));
      const unavailable = await mountChat();
      expect(unavailable.find('.ai-chat-notice').exists()).toBe(false);
    });
  });

  describe('agent mode', () => {
    it('sends the conversation to the agent when it is ready', async () => {
      mockFetch({}, async () => streamResponse([
        '{"type":"text","content":"106 visits"}',
        '[DONE]',
      ]));

      const wrapper = await submit('How many visits?');

      const agentCall = callTo('action=agent&');
      expect(agentCall).toBeDefined();
      expect(String(agentCall![0])).toContain('module=MistralAI');
      const body = agentCall![1].body as URLSearchParams;
      expect(JSON.parse(body.get('messages')!))
        .toEqual([{ role: 'user', content: 'How many visits?' }]);
      expect(body.get('force_api_session')).toBe('1');
      expect(requestedUrls().some((url) => url.includes('getStreamingResponse'))).toBe(false);
      expect(vm(wrapper).messages[1]).toMatchObject({ role: 'assistant', content: '106 visits' });
    });

    it('builds the tool steps and the answer from the agent events', async () => {
      mockFetch({}, async () => streamResponse([]));
      const wrapper = await mountChat();
      const chat = vm(wrapper);

      chat.parseAgentData(JSON.stringify({
        type: 'tool_call', id: 'a1b2c3d4e', name: 'matomo_site_list', title: 'List sites',
      }));
      chat.parseAgentData(JSON.stringify({ type: 'tool_result', id: 'a1b2c3d4e', isError: false }));
      chat.parseAgentData(JSON.stringify({
        type: 'tool_call', id: 'f5g6h7i8j', name: 'matomo_goal_get', title: '',
      }));
      chat.parseAgentData(JSON.stringify({ type: 'tool_result', id: 'f5g6h7i8j', isError: true }));
      chat.parseAgentData(JSON.stringify({ type: 'text', content: 'First part' }));
      chat.parseAgentData(JSON.stringify({ type: 'text', content: 'Second part' }));
      chat.parseAgentData('not json');

      expect(chat.agentSteps.map(({ id, status, title }) => ({ id, status, title }))).toEqual([
        { id: 'a1b2c3d4e', status: 'done', title: 'List sites' },
        { id: 'f5g6h7i8j', status: 'error', title: 'matomo_goal_get' },
      ]);
      expect(chat.streamingContent).toBe('First part\n\nSecond part');
    });

    it('displays the agent errors with the settings link, without retrying', async () => {
      mockFetch({}, async () => streamResponse([
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
      expect(ajaxFetch).not.toHaveBeenCalled();
    });

    it('sends a single request when the chat is closed during the answer', async () => {
      mockFetch({}, async () => {
        const error = new Error('The user aborted a request.');
        error.name = 'AbortError';
        throw error;
      });

      const wrapper = await submit('Hi');

      expect(vm(wrapper).errored).toBe(false);
      expect(requestedUrls().filter((url) => url.includes('action=agent&'))).toHaveLength(1);
      expect(requestedUrls().some((url) => url.includes('getStreamingResponse'))).toBe(false);
      expect(ajaxFetch).not.toHaveBeenCalled();
    });
  });

  describe('redesign', () => {
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
      let wrapper: ChatWrapper | null = null;

      mockFetch({}, async () => ({
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
      }));

      wrapper = await mountChat();
      await vm(wrapper).onSubmit({ role: 'user', content: 'KPIs?' });
      await flushPromises();

      await waitFor(() => {
        expect(wrapper!.find('[aria-live="polite"]').text())
          .toBe('MistralAI_AnswerAnnouncement:MistralAI 257 visits Bounce rate 58%');
      });
      expect(seenWhileStreaming.length).toBeGreaterThan(3);
      expect(seenWhileStreaming.every((text) => text === '')).toBe(true);
    });

    it('copies the markdown of a complete answer', async () => {
      mockFetch({}, async () => streamResponse([]));
      const writeText = jest.fn(async () => undefined);
      stubProperty(window.navigator, 'clipboard', { writeText });
      stubProperty(window, 'isSecureContext', true);
      const wrapper = await mountChat();
      vm(wrapper).messages.push(
        { role: 'user', content: 'Q' },
        { role: 'assistant', content: '**42** visits' },
      );
      await flushPromises();

      const copy = wrapper.find('.ai-chat-message__actions button');
      expect(copy.attributes('aria-label')).toBe('MistralAI_CopyAnswer');
      await copy.trigger('click');
      await flushPromises();

      expect(writeText).toHaveBeenCalledWith('**42** visits');
      expect(copy.attributes('aria-label')).toBe('MistralAI_AnswerCopied');
    });

    it('suggests translated questions on an empty conversation, and sends the picked one', async () => {
      mockFetch({}, async () => streamResponse([]));
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

      const body = callTo('action=agent&')![1].body as URLSearchParams;
      expect(JSON.parse(body.get('messages')!)).toEqual([
        { role: 'user', content: 'MistralAI_SuggestionWeeklyKpis' },
      ]);
      expect(wrapper.find('.ai-chat-empty').exists()).toBe(false);
    });
  });
  describe('report context', () => {
    const context = MatomoUrl.parsed.value as Record<string, unknown>;

    function queryOf(fragment: string): URLSearchParams {
      const call = fetchMock.mock.calls.find((c) => String(c[0]).includes(fragment));
      return new URL(String(call![0]), 'http://matomo.test/').searchParams;
    }

    function bodyOf(fragment: string): URLSearchParams {
      const call = fetchMock.mock.calls.find((c) => String(c[0]).includes(fragment));
      return call![1].body as URLSearchParams;
    }

    async function ask(): Promise<void> {
      const wrapper = await mountChat();
      await vm(wrapper).onSubmit({ role: 'user', content: 'Hi' });
      await flushPromises();
    }

    afterEach(() => {
      ['segment', 'comparePeriods', 'compareDates', 'compareSegments'].forEach((key) => {
        delete context[key];
      });
    });

    it('sends the URL segment in the query of the agent requests, not in the body', async () => {
      context.segment = 'browserCode==FF';
      mockFetch({}, async () => streamResponse(['[DONE]']));

      await ask();

      expect(queryOf('action=agentStatus').get('segment')).toBe('browserCode==FF');
      expect(queryOf('action=agent&').get('segment')).toBe('browserCode==FF');
      expect(bodyOf('action=agent&').has('segment')).toBe(false);
    });

    it('sends the segment in the query of the streaming request', async () => {
      context.segment = 'browserCode==FF';
      mockFetch(null, async () => streamResponse(['[DONE]']));

      await ask();

      const query = queryOf('getStreamingResponse');
      expect(query.get('segment')).toBe('browserCode==FF');
      expect(query.get('idSite')).toBe('1');
      expect(bodyOf('getStreamingResponse').has('segment')).toBe(false);
    });

    it('sends no segment nor comparison without them in the URL', async () => {
      mockFetch({}, async () => streamResponse(['[DONE]']));

      await ask();

      const query = queryOf('action=agent&');
      expect(query.has('segment')).toBe(false);
      expect(Array.from(query.keys()).some((key) => key.startsWith('compare'))).toBe(false);
    });

    it('sends the comparison arrays of the URL', async () => {
      context.comparePeriods = ['day', 'week'];
      context.compareDates = ['2026-09-29', '2026-09-22'];
      context.compareSegments = ['', 'browserCode==FF'];
      mockFetch({}, async () => streamResponse(['[DONE]']));

      await ask();

      ['action=agentStatus', 'action=agent&'].forEach((fragment) => {
        const query = queryOf(fragment);
        expect(query.getAll('comparePeriods[]')).toEqual(['day', 'week']);
        expect(query.getAll('compareDates[]')).toEqual(['2026-09-29', '2026-09-22']);
        expect(query.getAll('compareSegments[]')).toEqual(['', 'browserCode==FF']);
      });
    });
  });
});
