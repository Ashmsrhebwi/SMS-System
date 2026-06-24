import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.data('globalSearch', () => ({
    query: '',
    results: [],
    open: false,
    loading: false,
    async search() {
        if (this.query.length < 2) { this.results = []; this.open = false; return; }
        this.loading = true;
        try {
            const res = await fetch(`/search?q=${encodeURIComponent(this.query)}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            const data = await res.json();
            this.results = data.results || [];
            this.open = true;
        } catch (_) {}
        this.loading = false;
    },
    close() { this.open = false; },
}));

Alpine.start();
