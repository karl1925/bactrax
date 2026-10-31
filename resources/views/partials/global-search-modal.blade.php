<div id="global-search-modal" class="hidden fixed inset-0 z-[180] bg-slate-900/60 backdrop-blur-sm flex items-start justify-center pt-20 px-4">
    <div class="bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-2xl border border-slate-200 dark:border-slate-700 overflow-hidden">
        <div class="p-4 border-b border-slate-200 dark:border-slate-700 flex items-center gap-3">
            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            <input type="text" id="global-search-input" autocomplete="off" placeholder="Search by tracking number or project title..." class="w-full bg-transparent text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none text-base">
            <kbd class="px-2 py-0.5 text-xs bg-slate-100 dark:bg-slate-700 text-slate-500 rounded border border-slate-300 dark:border-slate-600">ESC</kbd>
        </div>
        <div id="search-results-container" class="max-h-80 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-700 p-2">
            <p class="text-xs text-slate-400 p-4 text-center">Start typing to query procurements across DICT Region 2...</p>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let timer;

        $('#global-search-input').on('keyup', function() {
            clearTimeout(timer);
            let query = $(this).val();

            if (query.length < 2) {
                $('#search-results-container').html('<p class="text-xs text-slate-400 p-4 text-center">Start typing to query procurements...</p>');
                return;
            }

            timer = setTimeout(function() {
                $.ajax({
                    url: "{{ route('api.search.global') }}",
                    type: "GET",
                    data: { q: query },
                    success: function(data) {
                        let html = '';
                        if (data.length === 0) {
                            html = '<p class="text-xs text-slate-400 p-4 text-center">No matching procurement records found.</p>';
                        } else {
                            data.forEach(function(item) {
                                html += `
                                    <a href="${item.url}" class="block p-3 hover:bg-blue-50 dark:hover:bg-slate-700/50 rounded-lg transition">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="font-mono text-xs font-bold text-blue-600 dark:text-blue-400">${item.pr_number}</span>
                                            <span class="text-xs px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 font-semibold">${item.office}</span>
                                        </div>
                                        <p class="text-sm font-medium text-slate-800 dark:text-slate-200 truncate">${item.title}</p>
                                        <div class="flex justify-between text-xs text-slate-500 dark:text-slate-400 mt-1">
                                            <span>ABC: ${item.abc}</span>
                                            <span class="font-semibold text-slate-700 dark:text-slate-300">${item.status}</span>
                                        </div>
                                    </a>`;
                            });
                        }
                        $('#search-results-container').html(html);
                    }
                });
            }, 300);
        });
    });
</script>