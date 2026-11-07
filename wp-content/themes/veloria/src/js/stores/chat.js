/**
 * Chat store. Same localStorage format as the headless Zustand store
 * ("veloria-chat"), so open conversations survive the migration.
 */
import { storage } from "../lib/api.js";

const KEY = "veloria-chat";
const genId = () => `${Date.now()}-${Math.random().toString(36).slice(2, 9)}`;
const genSession = () => `session-${Date.now()}-${Math.random().toString(36).slice(2, 11)}`;

export default {
  messages: [],
  isOpen: false,
  isLoading: false,
  sessionId: "",
  hasGreeted: false,

  init() {
    const saved = storage.get(KEY);
    const state = saved && saved.state ? saved.state : {};
    this.messages = Array.isArray(state.messages) ? state.messages.filter((m) => !m.isTyping) : [];
    this.sessionId = state.sessionId || genSession();
    this.hasGreeted = Boolean(state.hasGreeted);
  },

  persist() {
    storage.set(KEY, {
      state: { messages: this.messages.filter((m) => !m.isTyping).slice(-50), sessionId: this.sessionId, hasGreeted: this.hasGreeted },
      version: 0,
    });
  },

  addMessage(message) {
    this.messages.push({ ...message, id: genId(), timestamp: Date.now() });
    this.persist();
  },

  removeTyping() {
    this.messages = this.messages.filter((m) => !m.isTyping);
  },
};
