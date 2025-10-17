    </main>
    <footer class="mt-12 py-6 text-center text-sm text-gray-400 glass-footer">
        <div class="max-w-7xl mx-auto px-4">© <?php echo date('Y'); ?> CRM App</div>
    </footer>

    <!-- Floating Chatbot Widget (Alpine.js) -->
    <div x-data="chatbot()" class="chatbot-root">
        <button class="chatbot-toggle glass-card rounded-full px-4 py-3 text-sm flex items-center space-x-2"
                @click="open = !open" aria-label="Open chat">
            <span x-text="open ? 'Close Chat' : 'Chatbot'"></span>
        </button>

        <div class="chatbot-panel" x-show="open" x-transition>
            <div class="chatbot-surface p-3">
                <div class="flex items-center justify-between mb-2">
                    <div class="text-sm font-semibold">Assistant</div>
                    <button class="text-xs text-gray-300 hover:text-white" @click="open=false">✕</button>
                </div>
                <div class="chatbot-messages space-y-2 mb-2 text-sm" x-ref="messages">
                    <template x-for="m in messages" :key="m.id">
                        <div :class="m.role==='user' ? 'chatbot-msg-user p-2 rounded' : 'chatbot-msg-bot p-2 rounded'" x-text="m.text"></div>
                    </template>
                </div>
                <form class="flex space-x-2" @submit.prevent="send()">
                    <input type="text" x-model="input" class="flex-1 glass-input rounded px-3 py-2 text-sm" placeholder="Type your message..." />
                    <button type="submit" class="glass-card rounded px-3 py-2 text-sm">Send</button>
                </form>
            </div>
        </div>
    </div>

    <script>
      function chatbot() {
        return {
          open: false,
          input: '',
          messages: [
            { id: 1, role: 'bot', text: 'Hi! How can I help you today?' }
          ],
          async send() {
            const text = this.input.trim();
            if (!text) return;
            const id = Date.now();
            this.messages.push({ id, role: 'user', text });
            this.input = '';
            this.$nextTick(() => this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight);
            const reply = await window.postChatbot(text);
            this.messages.push({ id: id + 1, role: 'bot', text: reply });
            this.$nextTick(() => this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight);
          }
        }
      }
    </script>
</body>
</html>

