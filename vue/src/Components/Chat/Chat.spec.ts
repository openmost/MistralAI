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
import { AjaxHelper } from 'CoreHome';
import Chat from './Chat.vue';

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
    AjaxHelper: { fetch: vi.fn() },
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

type ChatInstance = {
  onSubmit: (message?: { role: string, content: string }) => void;
  messages: { role: string, content: string }[];
  errored: boolean;
};

let fetchMock: ReturnType<typeof vi.fn>;
const wrappers: VueWrapper[] = [];

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

async function submit(content: string): Promise<VueWrapper> {
  const wrapper = mount(Chat, {
    props: {
      aiName: 'mistral-ai',
      aiLabel: 'MistralAI',
      apiMethod: 'MistralAI.getResponse',
    },
  });
  wrappers.push(wrapper);

  (wrapper.vm as unknown as ChatInstance).onSubmit({ role: 'user', content });
  await flushPromises();

  return wrapper;
}

function vm(wrapper: VueWrapper): ChatInstance {
  return wrapper.vm as unknown as ChatInstance;
}

describe('Chat', () => {
  beforeEach(() => {
    fetchMock = vi.fn();
    vi.stubGlobal('fetch', fetchMock);
    vi.mocked(AjaxHelper.fetch).mockReset();
  });

  afterEach(() => {
    wrappers.splice(0).forEach((wrapper) => wrapper.unmount());
    vi.unstubAllGlobals();
  });

  it('streams the answer from the MistralAI API', async () => {
    fetchMock.mockResolvedValue(streamResponse([
      '{"choices":[{"delta":{"content":"Hello "}}]}',
      '{"choices":[{"delta":{"content":"world"}}]}',
      '[DONE]',
    ]));

    const wrapper = await submit('Hi');

    const [url, options] = fetchMock.mock.calls[0];
    expect(String(url)).toContain('module=API');
    expect(String(url)).toContain('method=MistralAI.getStreamingResponse');
    expect(JSON.parse((options.body as URLSearchParams).get('messages')!)).toEqual([{ role: 'user', content: 'Hi' }]);
    expect(vm(wrapper).messages).toEqual([
      { role: 'user', content: 'Hi' },
      { role: 'assistant', content: 'Hello world' },
    ]);
  });

  it('falls back to the non streaming API when the stream has no content', async () => {
    fetchMock.mockResolvedValue(streamResponse(['[DONE]']));
    vi.mocked(AjaxHelper.fetch).mockResolvedValue({
      choices: [{ message: { role: 'assistant', content: 'Fallback answer' } }],
    });

    const wrapper = await submit('Hi');

    expect(AjaxHelper.fetch).toHaveBeenCalledWith(
      { method: 'MistralAI.getResponse' },
      expect.objectContaining({ postParams: expect.objectContaining({ messages: [{ role: 'user', content: 'Hi' }] }) }),
    );
    expect(vm(wrapper).messages[1]).toEqual({ role: 'assistant', content: 'Fallback answer' });
  });

  it('falls back to the non streaming API when streaming fails', async () => {
    fetchMock.mockRejectedValue(new Error('Network error'));
    vi.mocked(AjaxHelper.fetch).mockResolvedValue({
      choices: [{ message: { role: 'assistant', content: 'Fallback answer' } }],
    });

    const wrapper = await submit('Hi');

    expect(vm(wrapper).messages[1]).toEqual({ role: 'assistant', content: 'Fallback answer' });
  });

  it('displays the errors returned in the stream', async () => {
    fetchMock.mockResolvedValue(streamResponse(['{"error":{"message":"Invalid model"}}', '[DONE]']));

    const wrapper = await submit('Hi');

    expect(vm(wrapper).errored).toBe(true);
    expect(wrapper.find('.alert-danger').text()).toBe('Invalid model');
    expect(AjaxHelper.fetch).not.toHaveBeenCalled();
  });
});
