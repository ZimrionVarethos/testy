function chatRoom({ fetchUrl, postUrl, csrfToken, senderRole }) {
    return {
        messages: [], newMessage: '', sending: false, loading: true, errorMsg: '', pollTimer: null,
        init() { this.fetchMessages(); this.pollTimer = setInterval(() => this.fetchMessages(), 5000); },
        async fetchMessages() {
            try {
                const res = await fetch(fetchUrl, { headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, credentials: 'same-origin' });
                if (!res.ok) throw new Error();
                const data = await res.json();
                const atBottom = this.isAtBottom();
                this.messages = data.messages; this.loading = false; this.errorMsg = '';
                if (atBottom) this.$nextTick(() => this.scrollToBottom());
            } catch { this.loading = false; this.errorMsg = 'Gagal memuat pesan.'; }
        },
        async sendMessage() {
            const text = this.newMessage.trim();
            if (!text || this.sending) return;
            this.sending = true; this.errorMsg = '';
            try {
                const res = await fetch(postUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }, credentials: 'same-origin', body: JSON.stringify({ message: text }) });
                if (!res.ok) { const e = await res.json().catch(() => ({})); throw new Error(e.message || 'Gagal.'); }
                const data = await res.json();
                this.messages.push(data.message); this.newMessage = '';
                this.$nextTick(() => { this.scrollToBottom(); const ta = this.$el.querySelector('textarea'); if (ta) ta.style.height = '38px'; });
            } catch(e) { this.errorMsg = e.message || 'Gagal mengirim pesan.'; }
            finally { this.sending = false; }
        },
        scrollToBottom() { const b = this.$refs.messageBox; if (b) b.scrollTop = b.scrollHeight; },
        isAtBottom() { const b = this.$refs.messageBox; if (!b) return true; return b.scrollHeight - b.scrollTop - b.clientHeight < 60; },
        autoResize(el) { el.style.height = '38px'; el.style.height = Math.min(el.scrollHeight, 96) + 'px'; },
    };
}
