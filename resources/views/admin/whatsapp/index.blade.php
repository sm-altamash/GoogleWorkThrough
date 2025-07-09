@extends('admin.layouts.master')
@section('title', 'WhatsApp Messaging')

@push('styles')
<style>
/* bubble styling */
.bubble-me   {background:#d1e7dd;border-radius:1rem 1rem .25rem 1rem;}
.bubble-them {background:#f0f0f0;border-radius:1rem 1rem 1rem .25rem;}
.chat-scroll {max-height:70vh;overflow-y:auto;}
</style>
@endpush

@section('content')
<section>
    <h4 class="py-3 mb-4"><span class="text-muted fw-light">Messaging /</span> WhatsApp</h4>

    {{-- flash messages --}}
    @foreach (['success'=>'success','error'=>'danger'] as $k=>$v)
        @if (session($k)) <div class="alert alert-{{ $v }}">{{ session($k) }}</div> @endif
    @endforeach

    <div class="row col-12">
        <div class="mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <div class="input-group">
                        <input id="searchBox" class="form-control" placeholder="Search…" />
                        <button class="btn btn-primary" type="button">
                            <i class="ti ti-search"></i>
                        </button>
                        <button class="btn btn-success ms-2"
                                data-bs-toggle="modal"
                                data-bs-target="#sendModal">
                            <i class="ti ti-send"></i>
                        </button>
                    </div>
                </div>

                <ul id="convList" class="list-group list-group-flush">
                    @foreach ($conversations ?? [] as $num)
                        <li class="list-group-item conv-item"
                            data-number="{{ $num }}"
                            style="cursor:pointer">
                            <strong>{{ $num }}</strong>
                            <i class="bx bx-chevron-right float-end"></i>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="modal fade" id="sendModal" tabindex="-1">
            <div class="modal-dialog">
                {{-- NOTE: action+method ensure it works without JS --}}
                <form id="sendForm"
                    action="{{ route('whatsapp.send') }}"
                    method="POST"
                    class="modal-content">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Send WhatsApp Message</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <label class="form-label">To (e.g. +923001234567)</label>
                        <input id="toField"
                            name="to"
                            class="form-control mb-3"
                            required
                            value="{{ $number ?? '' }}">

                        <label class="form-label">Message</label>
                        <textarea name="message"
                                class="form-control"
                                rows="4"
                                required></textarea>
                    </div>

                    <div class="modal-footer">
                        <button id="sendBtn" class="btn btn-primary" type="submit">
                            <span id="sendSpin" class="spinner-border spinner-border-sm d-none"></span>
                            <span class="send-label">Send</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {

    /* ---------------- helpers ---------------- */
    const csrf     = document.querySelector('meta[name="csrf-token"]').content;
    const chatBody = document.getElementById('chatBody');
    const chatTit  = document.getElementById('chatTitle');
    const toField  = document.getElementById('toField');
    const sendForm = document.getElementById('sendForm');
    const sendBtn  = document.getElementById('sendBtn');
    const sendSpin = document.getElementById('sendSpin');
    const search   = document.getElementById('searchBox');

    const bubbleTpl = m => `
        <div class="d-flex flex-column ${m.direction==='outbound'?'align-items-end':''}">
            <div class="p-2 ${m.direction==='outbound'?'bubble-me':'bubble-them'}">
                ${m.body.replace(/\n/g,'<br>')}
            </div>
            <small class="text-muted">${new Date(m.created_at).toLocaleString()}</small>
        </div>`;

    /* ------------- live load conversation ------------- */
    document.querySelectorAll('.conv-item').forEach(item=>{
        item.addEventListener('click', async () => {
            const num = item.dataset.number;
            chatTit.textContent = 'Conversation with ' + num;
            toField.value       = num;

            const res = await fetch("{{ url('/whatsapp') }}/" + num, {
                headers:{'X-Requested-With':'XMLHttpRequest'}
            });
            const msgs = await res.json();
            chatBody.innerHTML = msgs.map(bubbleTpl).join('');
            chatBody.scrollTop = chatBody.scrollHeight;
        });
    });

    /* ------------- ajax send ------------- */
    sendForm.addEventListener('submit', async e => {
        // If JS missing, form will POST normally; this only runs when JS is enabled
        e.preventDefault();
        sendBtn.disabled = true; sendSpin.classList.remove('d-none');

        const fd = new FormData(sendForm);
        const cleanTo = fd.get('to').replace(/^whatsapp:/, ''); // strip if user pasted
        fd.set('to', cleanTo);

        const res = await fetch("{{ route('whatsapp.send') }}", {
            method : 'POST',
            headers: {'X-CSRF-TOKEN': csrf, 'X-Requested-With':'XMLHttpRequest'},
            body   : fd
        });

        sendBtn.disabled = false; sendSpin.classList.add('d-none');

        if (res.ok) {
            /* optimistic display */
            chatBody.insertAdjacentHTML('beforeend', bubbleTpl({
                body      : fd.get('message'),
                created_at: (new Date()).toISOString(),
                direction : 'outbound'
            }));
            chatBody.scrollTop = chatBody.scrollHeight;
            sendForm.reset();
            bootstrap.Modal.getInstance(document.getElementById('sendModal')).hide();
        } else {
            const data = await res.json().catch(()=>({message:'Unknown error'}));
            alert('Failed: ' + data.message);
        }
    });

    /* ------------- conversation search ------------- */
    search.addEventListener('input', e=>{
        const q = e.target.value.toLowerCase();
        document.querySelectorAll('.conv-item').forEach(li=>{
            li.classList.toggle('d-none', !li.dataset.number.toLowerCase().includes(q));
        });
    });
});
</script>
@endpush
