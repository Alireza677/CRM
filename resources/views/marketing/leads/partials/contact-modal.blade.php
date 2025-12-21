{{-- مودال انتخاب مخاطب --}}
@php $contacts = $contacts ?? collect(); @endphp
<div id="leadContactModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white w-3/4 max-h-[80vh] overflow-y-auto p-4 rounded shadow">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">انتخاب مخاطب</h3>
            <button type="button" onclick="closeLeadContactModal()" class="text-gray-500 hover:text-red-500 text-lg">&times;</button>
        </div>

        <div class="mb-3">
            <input id="leadContactSearchInput" type="text" placeholder="جستجوی نام یا موبایل…"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                   autocomplete="off">
            <div class="mt-1 text-xs text-gray-500">با تایپ، فهرست فیلتر می‌شود.</div>
        </div>

        <div class="border border-gray-200 rounded overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="bg-gray-100 text-gray-700 sticky top-0">
                <tr>
                    <th class="px-4 py-2 border-b border-gray-300">نام مخاطب</th>
                    <th class="px-4 py-2 border-b border-gray-300">شماره موبایل</th>
                </tr>
                </thead>
                <tbody id="leadContactTableBody">
                @foreach($contacts as $c)
                    <tr class="cursor-pointer hover:bg-gray-50"
                        data-name="{{ $c->full_name }}"
                        data-phone="{{ preg_replace('/\D+/', '', (string)($c->mobile ?? '')) }}"
                        onclick="selectLeadContact({{ $c->id }}, @js($c->full_name))">
                        <td class="px-4 py-2 border-b border-gray-200">{{ $c->full_name }}</td>
                        <td class="px-4 py-2 border-b border-gray-200 text-gray-500">{{ $c->mobile ?? '—' }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div id="leadContactNoResults" class="hidden p-4 text-center text-sm text-gray-500">موردی یافت نشد.</div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openLeadContactModal() {
  const modal = document.getElementById('leadContactModal');
  if (!modal) return;
  modal.classList.remove('hidden');
  modal.classList.add('flex');
  const input = document.getElementById('leadContactSearchInput');
  if (input) {
    input.value = '';
    input.dispatchEvent(new Event('input'));
    setTimeout(() => input.focus(), 10);
  }
}

function closeLeadContactModal() {
  const modal = document.getElementById('leadContactModal');
  if (!modal) return;
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}

function selectLeadContact(id, name) {
  const idInput = document.getElementById('lead_contact_id');
  const nameInput = document.getElementById('lead_contact_display');
  if (idInput) idInput.value = id || '';
  if (nameInput) nameInput.value = name || '';
  closeLeadContactModal();
}

(function () {
  const modal = document.getElementById('leadContactModal');
  const searchInput = document.getElementById('leadContactSearchInput');
  const rows = Array.from(document.querySelectorAll('#leadContactTableBody tr'));
  const emptyState = document.getElementById('leadContactNoResults');

  function applyFilter() {
    if (!searchInput) return;
    const term = (searchInput.value || '').trim().toLowerCase();
    let visible = 0;
    rows.forEach((row) => {
      const name = (row.dataset.name || '').toLowerCase();
      const phone = (row.dataset.phone || '').toLowerCase();
      const match = !term || name.includes(term) || phone.includes(term);
      row.classList.toggle('hidden', !match);
      if (match) visible += 1;
    });
    if (emptyState) emptyState.classList.toggle('hidden', visible !== 0);
  }

  if (searchInput) {
    searchInput.addEventListener('input', applyFilter);
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeLeadContactModal();
  });

  if (modal) {
    modal.addEventListener('click', function (e) {
      if (e.target === modal) closeLeadContactModal();
    });
  }
})();
</script>
@endpush
