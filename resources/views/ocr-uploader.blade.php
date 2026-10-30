@extends('app')

@section('title', 'Batch ANPR Image Uploader')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/ocr.css') }}">
<style>
    /* ---------- Dropzone ---------- */
    .ocr-dropzone {
        border: 2px dashed var(--bs-gray-300);
        border-radius: .75rem;
        background: var(--bs-gray-100);
        padding: 2.5rem 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: border-color .15s ease, background-color .15s ease;
    }

    .ocr-dropzone:hover,
    .ocr-dropzone.dragover {
        border-color: var(--bs-primary);
        background: rgba(var(--bs-primary-rgb), 0.04);
    }

    /* ---------- Section label ---------- */
    .ocr-label {
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--bs-gray-500);
    }

    /* ---------- Queue ---------- */
    .queue-list-container {
        max-height: 380px;
        overflow-y: auto;
        padding-right: 4px;
    }

    .queue-item {
        border: 1px solid var(--bs-gray-200);
        border-radius: .475rem;
        padding: .75rem .9rem;
        cursor: pointer;
        background: var(--bs-body-bg);
        transition: background-color .15s ease, border-color .15s ease;
    }

    .queue-item:hover {
        background: var(--bs-gray-100);
    }

    .queue-item.active {
        border-color: var(--bs-primary);
    }

    .queue-thumb {
        width: 60px;
        height: 44px;
        object-fit: cover;
        border-radius: .375rem;
        background: var(--bs-gray-100);
        flex-shrink: 0;
    }

    /* ---------- Plate badge ---------- */
    .ocr-plate {
        display: inline-block;
        border: 1px solid var(--bs-gray-300);
        border-radius: .375rem;
        background: var(--bs-body-bg);
        padding: .25rem .6rem;
        font-family: var(--bs-font-monospace);
        font-weight: 700;
        font-size: .95rem;
        letter-spacing: .06em;
        color: var(--bs-gray-900);
    }

    .ocr-plate-sm {
        font-size: .8rem;
        padding: .15rem .5rem;
    }

    /* ---------- Inspector ---------- */
    .saved-preview-stage {
        position: relative;
        height: 280px;
        border-radius: .625rem;
        background: var(--bs-gray-100);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        cursor: zoom-in;
    }

    .saved-preview-stage img {
        max-width: 100%;
        max-height: 276px;
        object-fit: contain;
    }

    .magnifier-glass {
        position: absolute;
        width: 150px;
        height: 150px;
        border-radius: 50%;
        border: 2px solid var(--bs-white);
        box-shadow: 0 4px 18px rgba(0, 0, 0, .25);
        background-repeat: no-repeat;
        pointer-events: none;
        z-index: 10;
    }

    .magnifier-hint {
        position: absolute;
        right: 8px;
        bottom: 8px;
        font-size: .7rem;
        padding: 2px 8px;
        border-radius: 20px;
        background: rgba(0, 0, 0, .55);
        color: #fff;
        pointer-events: none;
    }

    .best-summary {
        height: 100%;
        border: 1px solid var(--bs-gray-200);
        border-radius: .625rem;
        padding: 1.25rem;
    }

    .best-char-item,
    .best-attr-chip {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        padding: .25rem .55rem;
        border-radius: .375rem;
        border: 1px solid var(--bs-gray-200);
        background: var(--bs-gray-100);
        font-size: .75rem;
    }

    .best-char-item {
        font-family: var(--bs-font-monospace);
    }

    .best-col-cell {
        background: rgba(var(--bs-warning-rgb), .04);
    }

    .currentlogic-col-cell {
        background: rgba(var(--bs-primary-rgb), .04);
    }

    /* ---------- Character cards ---------- */
    .char-engine-card {
        height: 100%;
        border: 1px solid var(--bs-gray-200);
        border-radius: .625rem;
        padding: 1rem;
    }

    .char-keycap-list {
        display: flex;
        flex-wrap: wrap;
        gap: .4rem;
    }

    .char-keycap {
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        min-width: 44px;
        padding: .3rem .45rem;
        border-radius: .375rem;
        border: 1px solid var(--bs-gray-200);
    }

    .char-keycap-pos {
        font-size: .6rem;
        color: var(--bs-gray-500);
    }

    .char-keycap-letter {
        font-family: var(--bs-font-monospace);
        font-size: 1.05rem;
        font-weight: 700;
        color: var(--bs-gray-900);
        line-height: 1.2;
    }

    .char-keycap-conf {
        font-family: var(--bs-font-monospace);
        font-size: .65rem;
        font-weight: 600;
    }

    /* ---------- Tables ---------- */
    .ocr-table {
        table-layout: fixed;
        width: 100%;
    }

    .ocr-table th,
    .ocr-table td {
        white-space: normal;
        overflow-wrap: anywhere;
        word-break: break-word;
        vertical-align: top;
    }

    .ocr-table thead th {
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--bs-gray-500);
        vertical-align: middle;
    }

    .ocr-table td .badge,
    .ocr-table td code {
        white-space: normal;
        overflow-wrap: anywhere;
        text-align: left;
        line-height: 1.35;
        max-width: 100%;
    }

    #comparison-table {
        min-width: 1190px;
    }

    #comparison-table th,
    #comparison-table td {
        width: 14.2857%;
    }

    #char-breakdown-table {
        min-width: 840px;
    }

    .ocr-code {
        max-height: 520px;
        overflow: auto;
        font-size: .8rem;
    }
</style>
@endsection

@section('content')
<!--begin::Header-->
<div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-4 mb-8">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge badge-light-success fs-8 fw-semibold">Authenticated</span>
            <h1 class="text-gray-900 fw-bold fs-2 mb-0">Batch ANPR Image Uploader</h1>
        </div>
        <div class="text-gray-500 fs-7">Drop license plate images to process 4 ANPR engines concurrently and persist records.</div>
    </div>

    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('ocr.batch') }}" class="btn btn-sm btn-light-primary d-inline-flex align-items-center gap-2">
            <i class="ki-outline ki-tablet-text-down fs-5"></i> View Records Log
        </a>
        <form method="POST" action="{{ route('ocr.batch.uploader.lock') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-light-danger d-inline-flex align-items-center gap-2" title="Lock access to this page">
                <i class="ki-outline ki-lock fs-5"></i> Lock
            </button>
        </form>
    </div>
</div>
<!--end::Header-->

<!--begin::Uploader Card-->
<div class="row g-5 mb-8">
    <div class="col-12" id="batch-control-column">
        <div class="card card-flush shadow-sm">
            <div class="card-header pt-6">
                <div class="card-title">
                    <h3 class="fw-bold text-gray-900 fs-5 mb-0">Upload &amp; Queue Control</h3>
                </div>
                <div class="card-toolbar d-flex align-items-center gap-4">
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input h-20px w-35px" type="checkbox" id="auto-process-toggle" checked />
                        <label class="form-check-label fs-7 fw-semibold text-gray-600 ms-2" for="auto-process-toggle">Auto-run</label>
                    </div>
                </div>
            </div>

            <div class="card-body pt-2">
                <div class="row g-8">
                    <!--Left: Engine Selector & Dropzone-->
                    <div class="col-lg-5 d-flex flex-column">
                        <div class="ocr-label mb-2">Engine Selection</div>
                        <div class="btn-group w-100 mb-5" role="group" aria-label="Engine selector">
                            <input type="radio" class="btn-check" name="batch_engine" id="engine-all" value="all" checked>
                            <label class="btn btn-sm btn-light btn-color-gray-700 btn-active-light-primary" for="engine-all">All</label>

                            <input type="radio" class="btn-check" name="batch_engine" id="engine-candid" value="candid">
                            <label class="btn btn-sm btn-light btn-color-gray-700 btn-active-light-primary" for="engine-candid">Candid</label>

                            <input type="radio" class="btn-check" name="batch_engine" id="engine-eydea" value="eydea">
                            <label class="btn btn-sm btn-light btn-color-gray-700 btn-active-light-primary" for="engine-eydea">Eydea</label>

                            <input type="radio" class="btn-check" name="batch_engine" id="engine-parkonic" value="parkonic">
                            <label class="btn btn-sm btn-light btn-color-gray-700 btn-active-light-primary" for="engine-parkonic">ARH</label>

                            <input type="radio" class="btn-check" name="batch_engine" id="engine-anpr2" value="anpr2">
                            <label class="btn btn-sm btn-light btn-color-gray-700 btn-active-light-primary" for="engine-anpr2">Parkonic</label>
                        </div>

                        <div class="ocr-dropzone flex-grow-1 d-flex flex-column align-items-center justify-content-center" id="batch-upload-zone" style="min-height: 200px;">
                            <input type="file" id="batch-file-input" class="d-none" accept="image/*" multiple>
                            <i class="ki-outline ki-file-up fs-3x text-primary mb-3"></i>
                            <div class="text-gray-900 fw-bold fs-6 mb-1">Drop images here</div>
                            <div class="text-gray-500 fs-7 mb-4">or click anywhere to browse from disk</div>
                            <span class="btn btn-sm btn-light-primary pe-none">Select files</span>
                        </div>
                    </div>

                    <!--Right: Queue List & Controls-->
                    <div class="col-lg-7 d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="d-flex align-items-center gap-2">
                                <span class="ocr-label">Queue</span>
                                <span class="badge badge-light fs-8 fw-semibold" id="queue-counter-badge">0 / 0</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-primary" id="start-queue-btn" disabled>
                                    <span id="start-queue-btn-text">Start queue</span>
                                </button>
                                <button class="btn btn-sm btn-light" id="clear-queue-btn" disabled>Clear</button>
                            </div>
                        </div>

                        <div class="progress h-4px bg-light mb-4">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: 0%; transition: width .3s ease;" id="queue-progress-bar"></div>
                        </div>

                        <div class="queue-list-container d-flex flex-column gap-2 flex-grow-1" id="queue-list">
                            <div class="text-center py-12 border border-dashed border-gray-300 rounded my-auto" id="queue-empty-state">
                                <i class="ki-outline ki-file-sheet fs-3x text-gray-400 mb-2"></i>
                                <div class="fw-semibold text-gray-700 fs-6 mb-1">Queue is empty</div>
                                <div class="text-gray-500 fs-7">Drop plate images on the left or click to browse.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Uploader Card-->

@include('partials.record-inspector-modal')

@endsection

@section('scripts')
<script id="ocr-uploader-script">
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Named Route templates with placeholders for dynamic IDs
        const acceptLogicRouteTemplate = "{{ route('ocr.batch.acceptLogic', ['id' => ':id']) }}";
        const deleteRecordRouteTemplate = "{{ route('ocr.batch.deleteRecord', ['id' => ':id']) }}";
        const imageRouteTemplate = "{{ route('ocr.image', ['filename' => ':filename']) }}";
        const processRoute = "{{ route('ocr.batch.process') }}";

        // State
        const currentBatchId = 'batch_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
        let uploadQueue = [];
        let isProcessingQueue = false;
        let activeItemKey = null;
        let activeInspectedRecord = null;
        let activeInspectedRecordId = null;

        // Elements
        const $ = (id) => document.getElementById(id);
        const fileInput = $('batch-file-input');
        const uploadZone = $('batch-upload-zone');
        const autoProcessToggle = $('auto-process-toggle');
        const startQueueBtn = $('start-queue-btn');
        const startQueueBtnText = $('start-queue-btn-text');
        const clearQueueBtn = $('clear-queue-btn');
        const queueCounterBadge = $('queue-counter-badge');
        const queueProgressBar = $('queue-progress-bar');
        const queueListEl = $('queue-list');
        const queueEmptyState = $('queue-empty-state');

        // Inspector Modal Elements
        const recordInspectorModalEl = $('recordInspectorModal');
        let bsRecordInspectorModal = null;
        const getModal = () => {
            if (!bsRecordInspectorModal && recordInspectorModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bsRecordInspectorModal = bootstrap.Modal.getOrCreateInstance(recordInspectorModalEl);
            }
            return bsRecordInspectorModal;
        };
        getModal();

        const inspectorPlaceholder = $('inspector-placeholder');
        const inspectorContent = $('inspector-content');
        const activeRecordSubtitle = $('active-record-subtitle');
        const openSavedImageBtn = $('open-saved-image-btn');
        const imagePreview = $('image-preview');
        const imageFrameWrapper = $('image-frame-wrapper');
        const imageMagnifier = $('image-magnifier');
        const metaDbIdBadge = $('meta-db-id-badge');
        const bestHeroPlateWinner = $('best-hero-plate-winner');
        const bestHeroPlateDisplay = $('best-hero-plate-display');
        const bestHeroRegionLine = $('best-hero-region-line');
        const bestHeroCharList = $('best-hero-char-list');
        const bestHeroExtraBadges = $('best-hero-extra-badges');

        const modalBtnAcceptCurrentLogic = $('modal-btn-accept-current-logic');
        const modalBtnAcceptBestPick = $('modal-btn-accept-best-pick');
        const modalBtnAcceptBoth = $('modal-btn-accept-both');
        const modalBtnRejectAll = $('modal-btn-reject-all');
        const modalBtnDeleteRecord = $('modal-btn-delete-record');

        const partialWarning = $('engine-partial-warning');
        const partialWarningText = $('partial-warning-text');
        const charTotalCountBadge = $('char-total-count-badge');
        const charEngineCardsGrid = $('char-engine-cards-grid');

        const tabCandid = $('raw-candid-tab');
        const tabEydea = $('raw-eydea-tab');
        const tabParkonic = $('raw-parkonic-tab');
        const tabAnpr2 = $('raw-anpr2-tab');
        const thCandid = $('th-candid');
        const thEydea = $('th-eydea');
        const thParkonic = $('th-parkonic');
        const thAnpr2 = $('th-anpr2');

        const FIELDS = ['plate', 'local', 'region', 'platecolor', 'conf', 'chars', 'make', 'model', 'generation', 'vehicle', 'category', 'color', 'viewport', 'quality', 'bbox', 'tags', 'time'];
        const buildCells = (col) => FIELDS.reduce((acc, f) => {
            acc[f] = $(`comp-${col}-${f}`);
            return acc;
        }, {});
        const C = {
            candid: buildCells('candid'),
            eydea: buildCells('eydea'),
            arh: buildCells('parkonic'),
            parkonic: buildCells('anpr2'),
            best: buildCells('best'),
            current: buildCells('currentlogic'),
        };

        const candidJsonCode = $('candid-json-code');
        const eydeaJsonCode = $('eydea-json-code');
        const parkonicJsonCode = $('parkonic-json-code');
        const anpr2JsonCode = $('anpr2-json-code');

        // Helpers
        const getSelectedEngine = () => {
            const checked = document.querySelector('input[name="batch_engine"]:checked');
            return checked ? checked.value : 'all';
        };

        const formatBytes = (bytes) => {
            const b = Number(bytes) || 0;
            if (b >= 1048576) return (b / 1048576).toFixed(2) + ' MB';
            if (b >= 1024) return (b / 1024).toFixed(1) + ' KB';
            return b + ' B';
        };

        const escapeHtml = (str) => {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        // Magnifier
        const moveMagnifier = (e) => {
            if (!imagePreview.src || imageMagnifier.classList.contains('d-none')) return;
            const rect = imagePreview.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            if (x < 0 || y < 0 || x > rect.width || y > rect.height) {
                imageMagnifier.classList.add('d-none');
                return;
            }
            const zoom = 2.5;
            const bw = 75;
            imageMagnifier.style.left = (x - bw) + 'px';
            imageMagnifier.style.top = (y - bw) + 'px';
            imageMagnifier.style.backgroundImage = `url('${imagePreview.src}')`;
            imageMagnifier.style.backgroundSize = `${rect.width * zoom}px ${rect.height * zoom}px`;
            imageMagnifier.style.backgroundPosition = `-${x * zoom - bw}px -${y * zoom - bw}px`;
        };

        if (imagePreview) {
            imagePreview.addEventListener('mouseenter', () => {
                if (imagePreview.src) imageMagnifier.classList.remove('d-none');
            });
            imagePreview.addEventListener('mousemove', moveMagnifier);
            imagePreview.addEventListener('mouseleave', () => imageMagnifier.classList.add('d-none'));
        }

        if (imageFrameWrapper) {
            imageFrameWrapper.addEventListener('click', () => {
                if (imagePreview.src) window.open(imagePreview.src, '_blank');
            });
        }

        // Dropzone & Queue
        if (uploadZone) {
            uploadZone.addEventListener('click', () => fileInput && fileInput.click());
            ['dragenter', 'dragover'].forEach(n => uploadZone.addEventListener(n, (e) => {
                e.preventDefault();
                uploadZone.classList.add('dragover');
            }));
            ['dragleave', 'drop'].forEach(n => uploadZone.addEventListener(n, (e) => {
                e.preventDefault();
                uploadZone.classList.remove('dragover');
            }));
            uploadZone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files && files.length) enqueueFiles(Array.from(files));
            });
        }

        if (fileInput) {
            fileInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files.length) {
                    enqueueFiles(Array.from(e.target.files));
                    fileInput.value = '';
                }
            });
        }

        function readFileAsDataURL(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = (e) => resolve(e.target.result);
                reader.onerror = reject;
                reader.readAsDataURL(file);
            });
        }

        async function enqueueFiles(files) {
            const validFiles = files.filter(f => f.type && f.type.startsWith('image/'));
            if (validFiles.length === 0) {
                alert('Please select valid image files (JPG, PNG, WEBP, etc.).');
                return;
            }
            for (const file of validFiles) {
                const base64 = await readFileAsDataURL(file);
                uploadQueue.push({
                    key: 'q_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7),
                    file,
                    originalName: file.name,
                    fileSize: file.size,
                    mimeType: file.type,
                    base64,
                    status: 'queued',
                    errorMsg: null,
                    record: null,
                    results: null,
                    engine: getSelectedEngine(),
                });
            }
            renderQueueList();
            updateQueueProgress();
            if (autoProcessToggle && autoProcessToggle.checked) processNextInQueue();
        }

        if (startQueueBtn) {
            startQueueBtn.addEventListener('click', () => processNextInQueue());
        }

        if (clearQueueBtn) {
            clearQueueBtn.addEventListener('click', () => {
                uploadQueue = isProcessingQueue ? uploadQueue.filter(i => i.status === 'processing') : [];
                renderQueueList();
                updateQueueProgress();
            });
        }

        function updateQueueProgress() {
            const total = uploadQueue.length;
            const completed = uploadQueue.filter(i => i.status === 'done' || i.status === 'error').length;
            const queuedCount = uploadQueue.filter(i => i.status === 'queued').length;

            if (queueCounterBadge) queueCounterBadge.textContent = `${completed} / ${total}`;
            if (queueProgressBar) queueProgressBar.style.width = `${total > 0 ? Math.round((completed / total) * 100) : 0}%`;

            if (startQueueBtn) startQueueBtn.disabled = isProcessingQueue || queuedCount === 0;
            if (clearQueueBtn) clearQueueBtn.disabled = total === 0;
            if (startQueueBtnText) startQueueBtnText.textContent = isProcessingQueue ? 'Running...' : `Run (${queuedCount})`;
        }

        function renderQueueList() {
            if (!queueListEl) return;
            queueListEl.innerHTML = '';
            if (uploadQueue.length === 0) {
                if (queueEmptyState) {
                    queueListEl.appendChild(queueEmptyState);
                    queueEmptyState.classList.remove('d-none');
                }
                return;
            }
            if (queueEmptyState) queueEmptyState.classList.add('d-none');

            uploadQueue.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = `queue-item status-${item.status} d-flex align-items-center justify-content-between gap-3 ${activeItemKey === item.key ? 'active' : ''}`;

                let statusBadge = '';
                if (item.status === 'queued') {
                    statusBadge = `<span class="badge badge-light fs-9">Queued #${index + 1}</span>`;
                } else if (item.status === 'processing') {
                    statusBadge = `<span class="badge badge-light-primary fs-9"><span class="spinner-border spinner-border-sm me-1"></span> Processing</span>`;
                } else if (item.status === 'done' && item.record) {
                    statusBadge = `<span class="badge badge-light-success fs-9">DB #${item.record.id}</span>`;
                } else if (item.status === 'error') {
                    statusBadge = `<span class="badge badge-light-danger fs-9" title="${escapeHtml(item.errorMsg)}">Failed</span>`;
                }

                let platesPreview = '';
                if (item.record) {
                    const detected = [
                        ['Candid', item.record.candid_plate],
                        ['Eydea', item.record.eydea_plate],
                        ['ARH', item.record.arh_plate],
                        ['Parkonic', item.record.parkonic_plate],
                    ].filter(([, p]) => p).map(([l, p]) => `<span class="badge badge-light fs-9 font-monospace" title="${l}">${escapeHtml(p)}</span>`);
                    if (detected.length) platesPreview = `<div class="mt-1 d-flex flex-wrap gap-1">${detected.join('')}</div>`;
                }

                const thumbSrc = (item.record && item.record.image_url) ? item.record.image_url : item.base64;
                const dimInfo = (item.record && item.record.image_width && item.record.image_height) ?
                    ` · ${item.record.image_width}×${item.record.image_height}` : '';

                div.innerHTML = `
                    <div class="d-flex align-items-center gap-3 overflow-hidden">
                        <img src="${thumbSrc}" class="queue-thumb" alt="">
                        <div class="overflow-hidden">
                            <div class="fw-semibold text-gray-900 fs-7 text-truncate" style="max-width: 260px;" title="${escapeHtml(item.originalName)}">${escapeHtml(item.originalName)}</div>
                            <div class="text-gray-500 fs-8">${formatBytes(item.fileSize)}${dimInfo}</div>
                            ${platesPreview}
                        </div>
                    </div>
                    <div class="flex-shrink-0 d-flex align-items-center gap-2">
                        ${statusBadge}
                        ${item.record ? '<i class="ki-outline ki-eye fs-4 text-gray-500" title="Click to inspect"></i>' : ''}
                    </div>
                `;

                div.addEventListener('click', () => {
                    activeItemKey = item.key;
                    renderQueueList();
                    inspectQueueItem(item, true);
                });

                queueListEl.appendChild(div);
            });
        }

        async function processNextInQueue() {
            if (isProcessingQueue) return;
            const nextItem = uploadQueue.find(i => i.status === 'queued');
            if (!nextItem) {
                updateQueueProgress();
                return;
            }

            isProcessingQueue = true;
            nextItem.status = 'processing';
            nextItem.engine = getSelectedEngine();
            renderQueueList();
            updateQueueProgress();

            const isModalOpen = recordInspectorModalEl && recordInspectorModalEl.classList.contains('show');
            if (activeItemKey === nextItem.key && isModalOpen) {
                inspectQueueItem(nextItem, false);
            }

            try {
                const response = await fetch(processRoute, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        base64: nextItem.base64,
                        engine: nextItem.engine,
                        original_filename: nextItem.originalName,
                        batch_id: currentBatchId
                    })
                });
                const data = await response.json();

                if (!response.ok || !data.status) {
                    nextItem.status = 'error';
                    nextItem.errorMsg = data.error || 'Server processing failed';
                } else {
                    nextItem.status = 'done';
                    nextItem.record = data.record;
                    nextItem.results = data.results;

                    const modalStillOpen = recordInspectorModalEl && recordInspectorModalEl.classList.contains('show');
                    if (activeItemKey === nextItem.key && modalStillOpen) {
                        inspectQueueItem(nextItem, false);
                    }
                }
            } catch (err) {
                console.error('Batch item error:', err);
                nextItem.status = 'error';
                nextItem.errorMsg = err.message || 'Network error';
            } finally {
                isProcessingQueue = false;
                renderQueueList();
                updateQueueProgress();
                if (uploadQueue.some(i => i.status === 'queued') && autoProcessToggle && autoProcessToggle.checked) {
                    processNextInQueue();
                }
            }
        }

        function inspectQueueItem(item, openModal = false) {
            if (!item) return;
            if (inspectorPlaceholder) inspectorPlaceholder.classList.add('d-none');
            if (inspectorContent) inspectorContent.classList.remove('d-none');

            if (item.record) {
                populateInspectorFromRecord(item.record, item.results, openModal);
                return;
            }

            activeInspectedRecord = null;
            activeInspectedRecordId = null;
            updateComparisonTableAcceptedHeaders(null);
            updateModalAcceptLogicButtons(null);

            imagePreview.src = item.base64;
            openSavedImageBtn.classList.add('d-none');
            metaDbIdBadge.textContent = item.status === 'processing' ? 'Processing' : 'Queued';
            metaDbIdBadge.className = 'badge badge-light-warning fw-semibold fs-8';
            activeRecordSubtitle.textContent = item.status === 'processing' ?
                `Running engines on ${item.originalName}...` :
                `Queued: ${item.originalName}`;

            bestHeroPlateWinner.textContent = item.status === 'processing' ? 'Running engines...' : 'Queued';
            bestHeroPlateDisplay.innerHTML = `<span class="text-gray-500 fs-7">Computing best values...</span>`;
            bestHeroRegionLine.textContent = '-';
            bestHeroCharList.innerHTML = '';
            bestHeroExtraBadges.innerHTML = '';

            clearInspectorValues();

            if (openModal) {
                const m = getModal();
                if (m) m.show();
            }
        }

        function populateInspectorFromRecord(record, liveResults = null, openModal = false) {
            if (!record) return;
            if (inspectorPlaceholder) inspectorPlaceholder.classList.add('d-none');
            if (inspectorContent) inspectorContent.classList.remove('d-none');
            activeInspectedRecord = record;
            activeInspectedRecordId = record.id;

            const imageUrl = record.image_url || (record.stored_filename ? imageRouteTemplate.replace(':filename', encodeURIComponent(record.stored_filename)) : '');
            imagePreview.src = imageUrl;
            openSavedImageBtn.href = imageUrl;
            openSavedImageBtn.classList.remove('d-none');

            activeRecordSubtitle.textContent = `Saved in ${record.image_path || 'plateImages/'}`;
            metaDbIdBadge.textContent = `DB #${record.id}`;
            metaDbIdBadge.className = 'badge badge-light-success fw-semibold fs-8';

            updateComparisonTableAcceptedHeaders(record.accepted_logic);
            updateModalAcceptLogicButtons(record.accepted_logic);

            const results = liveResults || {
                candid: record.candid_raw,
                eydea: record.eydea_raw,
                parkonic: record.arh_raw,
                anpr2: record.parkonic_raw,
            };

            displayComparativeResults(results, record.selected_engine || 'all', record);
            renderBestPicks(record);
            renderCurrentLogic(record);
            renderCharacterBreakdown(record);

            if (openModal) {
                const m = getModal();
                if (m) m.show();
            }
        }

        // Formatting Helpers
        const toExactPct = (val) => {
            if (val === null || val === undefined || val === '') return '';
            const str = String(val).trim();
            const n = Number(str);
            if (isNaN(n)) return str;
            if (n > 0.0 && n <= 1.0) return Number((n * 100).toPrecision(12)).toString();
            return str;
        };

        const getPctForColor = (val) => {
            const n = parseFloat(toExactPct(val));
            return isNaN(n) ? 0 : n;
        };

        const confTone = (val) => {
            const pct = getPctForColor(val);
            return pct >= 95 ? 'success' : (pct >= 90 ? 'warning' : 'danger');
        };

        const formatWithConf = (val, confVal) => {
            if (!val || val === 'N/A') return 'N/A';
            if (confVal === undefined || confVal === null || confVal === '') return val;
            const exact = toExactPct(confVal);
            return `${val} <span class="fs-9 fw-semibold text-${confTone(exact)} ms-1">${exact}%</span>`;
        };

        const plateBadge = (text) => `<span class="ocr-plate">${escapeHtml(text || '-')}</span>`;

        const renderPlate = (text, code) => {
            if (!text && !code) return '-';
            let cleanText = text ? String(text).trim() : '';
            let cleanCode = code ? String(code).trim() : '';

            if (cleanCode && cleanText) {
                const escapedCode = cleanCode.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                const prefixWithSeparator = new RegExp('^' + escapedCode + '[-\\s]+', 'i');
                if (prefixWithSeparator.test(cleanText)) {
                    cleanText = cleanText.replace(prefixWithSeparator, '');
                } else {
                    const prefixDirect = new RegExp('^' + escapedCode, 'i');
                    if (prefixDirect.test(cleanText) && cleanText.length > cleanCode.length) {
                        cleanText = cleanText.replace(prefixDirect, '').trim();
                    }
                }
            }

            if (cleanCode && cleanText) {
                return `
                    <div class="d-inline-flex align-items-center gap-1 border border-gray-300 rounded px-2 py-1 bg-body">
                        <span class="badge badge-light-primary fw-bold fs-8 font-monospace">${escapeHtml(cleanCode)}</span>
                        <span class="fw-bold fs-7 font-monospace text-gray-900">${escapeHtml(cleanText)}</span>
                    </div>
                `;
            }
            return plateBadge(cleanText || cleanCode);
        };

        const renderBbox = (b) => {
            if (!b || !Array.isArray(b) || b.length < 4) return '-';
            return `[${b.map(n => Math.round(Number(n) || 0)).join(', ')}]`;
        };

        const renderTags = (tags) => {
            if (!tags || !Array.isArray(tags) || tags.length === 0) return '-';
            return tags.map(t => `<span class="badge badge-light fs-9 me-1 mb-1">${escapeHtml(t)}</span>`).join('');
        };

        function clearInspectorValues() {
            Object.values(C).forEach(col => {
                Object.values(col).forEach(el => {
                    if (el) el.innerHTML = '-';
                });
            });
            candidJsonCode.textContent = '{}';
            eydeaJsonCode.textContent = '{}';
            parkonicJsonCode.textContent = '{}';
            anpr2JsonCode.textContent = '{}';
        }

        // Logic Decisions
        function updateComparisonTableAcceptedHeaders(accepted) {
            if (C.current.plate) {
                C.current.plate.closest('table').querySelectorAll('.currentlogic-col-cell').forEach(el => {
                    el.style.outline = (accepted === 'current_logic' || accepted === 'both_acceptable') ? '2px solid var(--bs-primary)' : 'none';
                });
            }
            if (C.best.plate) {
                C.best.plate.closest('table').querySelectorAll('.best-col-cell').forEach(el => {
                    el.style.outline = (accepted === 'best_pick' || accepted === 'both_acceptable') ? '2px solid var(--bs-warning)' : 'none';
                });
            }
        }

        function updateModalAcceptLogicButtons(accepted) {
            [modalBtnAcceptCurrentLogic, modalBtnAcceptBestPick, modalBtnAcceptBoth, modalBtnRejectAll].forEach(btn => {
                if (btn) btn.classList.remove('btn-primary', 'btn-warning', 'btn-info', 'btn-danger');
            });
            if (modalBtnAcceptCurrentLogic) modalBtnAcceptCurrentLogic.className = 'btn btn-sm btn-light-primary';
            if (modalBtnAcceptBestPick) modalBtnAcceptBestPick.className = 'btn btn-sm btn-light-warning';
            if (modalBtnAcceptBoth) modalBtnAcceptBoth.className = 'btn btn-sm btn-light-info';
            if (modalBtnRejectAll) modalBtnRejectAll.className = 'btn btn-sm btn-light-danger';

            if (accepted === 'current_logic' && modalBtnAcceptCurrentLogic) {
                modalBtnAcceptCurrentLogic.className = 'btn btn-sm btn-primary';
            } else if (accepted === 'best_pick' && modalBtnAcceptBestPick) {
                modalBtnAcceptBestPick.className = 'btn btn-sm btn-warning text-dark';
            } else if (accepted === 'both_acceptable' && modalBtnAcceptBoth) {
                modalBtnAcceptBoth.className = 'btn btn-sm btn-info text-white';
            } else if (accepted === 'reject_all' && modalBtnRejectAll) {
                modalBtnRejectAll.className = 'btn btn-sm btn-danger';
            }
        }

        async function submitLogicDecision(acceptedLogic) {
            if (!activeInspectedRecordId) {
                alert('No saved database record is currently active.');
                return;
            }
            const recordId = activeInspectedRecordId;
            const targetUrl = acceptLogicRouteTemplate.replace(':id', encodeURIComponent(recordId));

            try {
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        accepted_logic: acceptedLogic
                    })
                });
                const data = await response.json();
                if (!response.ok || !data.status) {
                    alert('Error saving decision: ' + (data.error || 'Request failed'));
                    return;
                }

                if (activeInspectedRecord && activeInspectedRecord.id === recordId) {
                    activeInspectedRecord.accepted_logic = data.accepted_logic;
                }
                const queueMatch = uploadQueue.find(i => i.record && i.record.id === recordId);
                if (queueMatch && queueMatch.record) {
                    queueMatch.record.accepted_logic = data.accepted_logic;
                }
                updateModalAcceptLogicButtons(data.accepted_logic);
                updateComparisonTableAcceptedHeaders(data.accepted_logic);
            } catch (err) {
                console.error('Decision save failed:', err);
                alert('Network error saving decision.');
            }
        }

        [modalBtnAcceptCurrentLogic, modalBtnAcceptBestPick, modalBtnAcceptBoth, modalBtnRejectAll].forEach(btn => {
            if (btn) {
                btn.addEventListener('click', () => {
                    const logic = btn.getAttribute('data-logic');
                    submitLogicDecision(logic);
                });
            }
        });

        // Delete record
        if (modalBtnDeleteRecord) {
            modalBtnDeleteRecord.addEventListener('click', async () => {
                if (!activeInspectedRecordId) return;
                const recId = activeInspectedRecordId;
                if (!confirm(`Delete record #${recId}? This can be restored if needed.`)) return;

                const deleteUrl = deleteRecordRouteTemplate.replace(':id', encodeURIComponent(recId));
                try {
                    const response = await fetch(deleteUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (!response.ok || !data.status) {
                        alert(data.error || 'Failed to delete record.');
                        return;
                    }
                    const m = getModal();
                    if (m) m.hide();
                    uploadQueue = uploadQueue.filter(i => !i.record || i.record.id !== recId);
                    renderQueueList();
                    updateQueueProgress();
                } catch (err) {
                    alert('Network error deleting record.');
                }
            });
        }

        // Render functions for Inspector
        function displayComparativeResults(results, selectedEngine, record) {
            candidJsonCode.textContent = JSON.stringify(results.candid || {}, null, 2);
            eydeaJsonCode.textContent = JSON.stringify(results.eydea || {}, null, 2);
            parkonicJsonCode.textContent = JSON.stringify(results.parkonic || {}, null, 2);
            anpr2JsonCode.textContent = JSON.stringify(results.anpr2 || {}, null, 2);

            const fillCol = (col, r) => {
                if (!r || !r.success) {
                    C[col].plate.innerHTML = `<span class="badge badge-light-danger fs-9">${escapeHtml((r && r.error) ? r.error : 'No data')}</span>`;
                    return;
                }
                const p = r.parsed || {};
                C[col].plate.innerHTML = renderPlate(p.plate_text, p.plate_code);
                C[col].local.innerHTML = p.local_text ? escapeHtml(p.local_text) : '-';
                C[col].region.innerHTML = p.region ? escapeHtml(p.region) : '-';
                C[col].platecolor.innerHTML = p.plate_color ? escapeHtml(p.plate_color) : '-';
                C[col].conf.innerHTML = p.plate_confidence ? `<span class="badge badge-light-${confTone(p.plate_confidence)}">${toExactPct(p.plate_confidence)}%</span>` : '-';
                C[col].chars.innerHTML = p.characters_count ? `${p.characters_count} chars` : '-';
                C[col].make.innerHTML = formatWithConf(p.vehicle_make, p.vehicle_make_confidence);
                C[col].model.innerHTML = formatWithConf(p.vehicle_model, p.vehicle_model_confidence);
                C[col].generation.innerHTML = formatWithConf(p.vehicle_generation, p.vehicle_generation_confidence);
                C[col].vehicle.innerHTML = formatWithConf(p.vehicle_body_type, p.vehicle_body_type_confidence);
                C[col].category.innerHTML = p.vehicle_category ? escapeHtml(p.vehicle_category) : '-';
                C[col].color.innerHTML = formatWithConf(p.vehicle_color, p.vehicle_color_confidence);
                C[col].viewport.innerHTML = p.vehicle_viewpoint ? escapeHtml(p.vehicle_viewpoint) : '-';
                C[col].quality.innerHTML = p.image_quality ? escapeHtml(p.image_quality) : '-';
                C[col].bbox.innerHTML = renderBbox(p.vehicle_bbox);
                C[col].tags.innerHTML = renderTags(p.tags);
                C[col].time.innerHTML = r.execution_time_ms ? `${Math.round(r.execution_time_ms)} ms` : '-';
            };

            fillCol('candid', results.candid);
            fillCol('eydea', results.eydea);
            fillCol('arh', results.parkonic);
            fillCol('parkonic', results.anpr2);
        }

        function renderBestPicks(record) {
            if (!record) return;
            bestHeroPlateWinner.textContent = record.best_plate_source ? `From ${record.best_plate_source.toUpperCase()}` : '-';
            bestHeroPlateDisplay.innerHTML = renderPlate(record.best_plate_text, record.best_plate_code);
            bestHeroRegionLine.textContent = [record.best_plate_region, record.best_plate_color].filter(Boolean).join(' · ') || '-';

            C.best.plate.innerHTML = renderPlate(record.best_plate_text, record.best_plate_code);
            C.best.local.innerHTML = record.best_plate_local ? escapeHtml(record.best_plate_local) : '-';
            C.best.region.innerHTML = record.best_plate_region ? escapeHtml(record.best_plate_region) : '-';
            C.best.platecolor.innerHTML = record.best_plate_color ? escapeHtml(record.best_plate_color) : '-';
            C.best.conf.innerHTML = record.best_plate_confidence ? `<span class="badge badge-light-${confTone(record.best_plate_confidence)}">${toExactPct(record.best_plate_confidence)}%</span>` : '-';
            C.best.chars.innerHTML = record.characters_count ? `${record.characters_count} chars` : '-';
            C.best.make.innerHTML = formatWithConf(record.best_vehicle_make, record.best_vehicle_make_confidence);
            C.best.model.innerHTML = formatWithConf(record.best_vehicle_model, record.best_vehicle_model_confidence);
            C.best.generation.innerHTML = formatWithConf(record.best_vehicle_generation, record.best_vehicle_generation_confidence);
            C.best.vehicle.innerHTML = formatWithConf(record.best_vehicle_body_type, record.best_vehicle_body_type_confidence);
            C.best.category.innerHTML = record.best_vehicle_category ? escapeHtml(record.best_vehicle_category) : '-';
            C.best.color.innerHTML = formatWithConf(record.best_vehicle_color, record.best_vehicle_color_confidence);
            C.best.viewport.innerHTML = record.best_vehicle_viewpoint ? escapeHtml(record.best_vehicle_viewpoint) : '-';
            C.best.quality.innerHTML = record.best_image_quality ? escapeHtml(record.best_image_quality) : '-';
            C.best.bbox.innerHTML = renderBbox(record.best_vehicle_bbox);
            C.best.tags.innerHTML = renderTags(record.best_tags);
            C.best.time.innerHTML = record.best_plate_source ? `Best pick` : '-';
        }

        function renderCurrentLogic(record) {
            if (!record) return;
            C.current.plate.innerHTML = renderPlate(record.current_plate_text, record.current_plate_code);
            C.current.local.innerHTML = record.current_plate_local ? escapeHtml(record.current_plate_local) : '-';
            C.current.region.innerHTML = record.current_plate_region ? escapeHtml(record.current_plate_region) : '-';
            C.current.platecolor.innerHTML = record.current_plate_color ? escapeHtml(record.current_plate_color) : '-';
            C.current.conf.innerHTML = record.current_plate_confidence ? `<span class="badge badge-light-${confTone(record.current_plate_confidence)}">${toExactPct(record.current_plate_confidence)}%</span>` : '-';
            C.current.chars.innerHTML = record.current_characters_count ? `${record.current_characters_count} chars` : '-';
            C.current.make.innerHTML = formatWithConf(record.current_vehicle_make, record.current_vehicle_make_confidence);
            C.current.model.innerHTML = formatWithConf(record.current_vehicle_model, record.current_vehicle_model_confidence);
            C.current.generation.innerHTML = formatWithConf(record.current_vehicle_generation, record.current_vehicle_generation_confidence);
            C.current.vehicle.innerHTML = formatWithConf(record.current_vehicle_body_type, record.current_vehicle_body_type_confidence);
            C.current.category.innerHTML = record.current_vehicle_category ? escapeHtml(record.current_vehicle_category) : '-';
            C.current.color.innerHTML = formatWithConf(record.current_vehicle_color, record.current_vehicle_color_confidence);
            C.current.viewport.innerHTML = record.current_vehicle_viewpoint ? escapeHtml(record.current_vehicle_viewpoint) : '-';
            C.current.quality.innerHTML = record.current_image_quality ? escapeHtml(record.current_image_quality) : '-';
            C.current.bbox.innerHTML = renderBbox(record.current_vehicle_bbox);
            C.current.tags.innerHTML = renderTags(record.current_tags);
            C.current.time.innerHTML = record.current_source ? `From ${record.current_source.toUpperCase()}` : '-';
        }

        function renderCharacterBreakdown(record) {
            const tbody = $('char-breakdown-tbody');
            if (!tbody) return;
            const chars = record.characters || [];
            charTotalCountBadge.textContent = chars.length;

            if (chars.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-500 py-6">No individual character data available.</td></tr>`;
                charEngineCardsGrid.innerHTML = '';
                bestHeroCharList.innerHTML = '';
                return;
            }

            bestHeroCharList.innerHTML = chars
                .filter(c => c.engine === (record.best_plate_source || 'candid'))
                .map(c => `
                    <span class="best-char-item" title="Conf: ${toExactPct(c.confidence)}%">
                        <strong>${escapeHtml(c.character)}</strong>
                        <span class="text-${confTone(c.confidence)}">${Math.round(getPctForColor(c.confidence))}%</span>
                    </span>
                `).join('');

            tbody.innerHTML = chars.map(c => `
                <tr>
                    <td><span class="badge badge-light fw-bold">${escapeHtml(c.engine.toUpperCase())}</span></td>
                    <td class="font-monospace">${escapeHtml(c.plate_text || '-')}</td>
                    <td><span class="badge badge-light-dark">#${c.character_index + 1}</span></td>
                    <td class="fw-bold font-monospace fs-6">${escapeHtml(c.character)}</td>
                    <td><span class="badge badge-light-${confTone(c.confidence)}">${toExactPct(c.confidence)}%</span></td>
                    <td class="font-monospace fs-9 text-gray-600">${escapeHtml(JSON.stringify(c.raw_data || {}))}</td>
                </tr>
            `).join('');

            const groupedByEngine = {};
            chars.forEach(c => {
                if (!groupedByEngine[c.engine]) groupedByEngine[c.engine] = [];
                groupedByEngine[c.engine].push(c);
            });

            charEngineCardsGrid.innerHTML = Object.entries(groupedByEngine).map(([engine, list]) => `
                <div class="col-md-6 col-xl-3">
                    <div class="char-engine-card bg-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold text-gray-900">${engine.toUpperCase()}</span>
                            <span class="badge badge-light fs-9">${list.length} chars</span>
                        </div>
                        <div class="char-keycap-list">
                            ${list.map(c => `
                                <div class="char-keycap bg-light">
                                    <span class="char-keycap-pos">${c.character_index + 1}</span>
                                    <span class="char-keycap-letter">${escapeHtml(c.character)}</span>
                                    <span class="char-keycap-conf text-${confTone(c.confidence)}">${Math.round(getPctForColor(c.confidence))}%</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `).join('');
        }
    });
</script>
@endsection

