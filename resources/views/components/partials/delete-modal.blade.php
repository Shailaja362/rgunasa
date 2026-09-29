@props(['label' => 'record'])

{{-- Confirmation modal for any button with class "deleteRecord" and a data-url --}}
<div id="recordDeleteModal" class="fixed inset-0 bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Confirm Delete</h2>
        <p id="recordDeleteMessage" class="text-gray-600 mb-5">Are you sure you want to delete this {{ $label }}? This cannot be undone.</p>
        <div class="flex justify-end gap-3">
            <button type="button" id="cancelRecordDelete" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Close</button>
            <button type="button" id="confirmRecordDelete" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Delete</button>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('recordDeleteModal');
        const messageEl = document.getElementById('recordDeleteMessage');
        const confirmBtn = document.getElementById('confirmRecordDelete');
        const cancelBtn = document.getElementById('cancelRecordDelete');
        const defaultMessage = 'Are you sure you want to delete this {{ $label }}? This cannot be undone.';
        let deleteUrl = null;

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            deleteUrl = null;
        }

        function postJson(url, confirmed) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ confirmed: confirmed }),
            }).then(res => res.json());
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.deleteRecord');
            if (!btn) return;
            deleteUrl = btn.dataset.url;

            messageEl.textContent = 'Checking related data…';
            confirmBtn.classList.add('hidden');
            openModal();

            postJson(deleteUrl, false)
                .then(data => {
                    messageEl.textContent = data.message || defaultMessage;
                    confirmBtn.classList.toggle('hidden', !!data.blocking);
                })
                .catch(() => {
                    messageEl.textContent = 'Could not check this {{ $label }}. Please try again.';
                    confirmBtn.classList.add('hidden');
                });
        });

        cancelBtn.addEventListener('click', closeModal);

        confirmBtn.addEventListener('click', function () {
            if (!deleteUrl) return;
            const url = deleteUrl;

            postJson(url, true)
                .then(data => {
                    closeModal();
                    if (data.success) {
                        showToast(data.message, 'success', 2000);
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        showToast(data.message || 'Failed to delete {{ $label }}', 'error', 3000);
                    }
                })
                .catch(() => {
                    closeModal();
                    showToast('Something went wrong', 'error', 2000);
                });
        });
    })();
</script>
