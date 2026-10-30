@extends('app')

@section('title', 'Batch OCR Upload')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/ocr.css') }}">
<style>
    /* ---------- Stats strip ---------- */
    .ocr-stats {
        display: flex;
        flex-wrap: wrap;
        row-gap: 1rem;
    }

    .ocr-stat {
        padding: 0 1.75rem;
        border-left: 1px solid var(--bs-gray-200);
    }

    .ocr-stat:first-child {
        padding-left: 0;
        border-left: 0;
    }

    .ocr-stat-label {
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--bs-gray-500);
        margin-bottom: .25rem;
    }

    .ocr-stat-value {
        font-size: 1.35rem;
        font-weight: 700;
        line-height: 1.2;
        color: var(--bs-gray-900);
    }

    .ocr-stat-sub {
        font-size: .75rem;
        color: var(--bs-gray-500);
    }

    /* ---------- Section label ---------- */
    .ocr-label {
        font-size: .7rem;
        font-weight: 600;
        letter-spacing: .05em;
        text-transform: uppercase;
        color: var(--bs-gray-500);
    }

    /* ---------- Dropzone ---------- */
    .ocr-dropzone {
        border: 1px dashed var(--bs-gray-300);
        border-radius: .625rem;
        background: var(--bs-gray-100);
        padding: 2rem 1.5rem;
        text-align: center;
        cursor: pointer;
        transition: border-color .15s ease, background-color .15s ease;
    }

    .ocr-dropzone:hover,
    .ocr-dropzone.dragover {
        border-color: var(--bs-primary);
    }

    /* ---------- Queue ---------- */
    .queue-list-container {
        max-height: 260px;
        overflow-y: auto;
        padding-right: 2px;
    }

    .queue-item {
        border: 1px solid var(--bs-gray-200);
        border-radius: .475rem;
        padding: .6rem .75rem;
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
        width: 52px;
        height: 38px;
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

    #db-history-table {
        min-width: 1720px;
    }

    #db-history-table th:nth-child(1),
    #db-history-table td:nth-child(1) {
        width: 60px;
    }

    #db-history-table th:nth-child(2),
    #db-history-table td:nth-child(2) {
        width: 110px;
    }

    #db-history-table th:nth-child(3),
    #db-history-table td:nth-child(3) {
        width: 160px;
    }

    #db-history-table th:nth-child(n+4),
    #db-history-table td:nth-child(n+4) {
        width: 230px;
    }

    #db-history-table tbody tr {
        cursor: pointer;
        transition: background-color .15s ease;
    }

    #db-history-table tbody tr:hover {
        background: var(--bs-gray-100);
    }

    #db-history-table tbody tr.active-db-row {
        background-color: rgba(255, 244, 214, 0.03);
    }

    .db-thumb-wrap {
        display: inline-block;
        width: 84px;
        height: 56px;
        border-radius: .375rem;
        overflow: hidden;
        background: var(--bs-gray-100);
    }

    .db-thumb {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .db-char-pill {
        display: inline-block;
        font-family: var(--bs-font-monospace);
        font-size: .7rem;
        padding: 1px 5px;
        margin: 1px;
        border-radius: 4px;
        background: var(--bs-gray-100);
    }

    .table-responsive-region {
        overflow-x: auto;
        overflow-y: hidden;
        scrollbar-width: thin;
        padding-bottom: 6px;
    }

    .ocr-code {
        max-height: 520px;
        overflow: auto;
        font-size: .8rem;
    }

    #batch-results-column,
    .card,
    .card-body {
        min-width: 0;
    }
</style>
@endsection

@section('content')
<!--begin::Header-->
<div class="d-flex flex-column flex-xl-row align-items-xl-end justify-content-between gap-6 mb-8">
    <div>
        <h1 class="text-gray-900 fw-bold fs-2 mb-1">Batch OCR Upload</h1>
        <div class="text-gray-500 fs-7">Upload plate images, compare engines and confirm the best logic.</div>
    </div>

    <div class="ocr-stats">
        <div class="ocr-stat">
            <div class="ocr-stat-label">Session queue</div>
            <div class="ocr-stat-value" id="stat-session-count">0 Files</div>
        </div>
        <div class="ocr-stat">
            <div class="ocr-stat-label">Processed</div>
            <div class="ocr-stat-value" id="stat-processed-count">0 / 0</div>
        </div>
        <div class="ocr-stat">
            <div class="ocr-stat-label">DB records</div>
            <div class="ocr-stat-value" id="stat-db-total">{{ $stats['total'] ?? '--' }}</div>
        </div>
        <div class="ocr-stat" title="Records where Current Logic was accepted">
            <div class="ocr-stat-label">Current logic</div>
            <div class="ocr-stat-value">
                <span id="stat-current-logic-count">{{ $stats['current_logic_count'] ?? 0 }}</span>
                <span class="fs-7 fw-semibold text-primary ms-1" id="stat-current-logic-pct">{{ $stats['current_logic_pct'] ?? 0 }}%</span>
            </div>
            <div class="ocr-stat-sub" id="stat-current-logic-sub">{{ $stats['current_logic_count'] ?? 0 }} of {{ $stats['decided_picks'] ?? 0 }} picks</div>
        </div>
        <div class="ocr-stat" title="Records where New Logic was accepted">
            <div class="ocr-stat-label">New Logic</div>
            <div class="ocr-stat-value">
                <span id="stat-best-pick-count">{{ $stats['best_pick_count'] ?? 0 }}</span>
                <span class="fs-7 fw-semibold text-warning ms-1" id="stat-best-pick-pct">{{ $stats['best_pick_pct'] ?? 0 }}%</span>
            </div>
            <div class="ocr-stat-sub" id="stat-best-pick-sub">{{ $stats['best_pick_count'] ?? 0 }} of {{ $stats['decided_picks'] ?? 0 }} picks</div>
        </div>
    </div>
</div>
<!--end::Header-->

<!--begin::Uploader-->
<div class="row g-5 mb-8">
    <div class="col-12" id="batch-control-column">
        <div class="card card-flush">
            <div class="card-header pt-6">
                <div class="card-title">
                    <h3 class="fw-bold text-gray-900 fs-5 mb-0">Upload &amp; Queue</h3>
                </div>
                <div class="card-toolbar">
                    <div class="form-check form-switch form-check-custom form-check-solid">
                        <input class="form-check-input h-20px w-35px" type="checkbox" id="auto-process-toggle" checked />
                        <label class="form-check-label fs-7 fw-semibold text-gray-600 ms-2" for="auto-process-toggle">Auto-run</label>
                    </div>
                </div>
            </div>

            <div class="card-body pt-2">
                <div class="row g-8">
                    <!--Left: engine + dropzone-->
                    <div class="col-lg-5 d-flex flex-column">
                        <div class="ocr-label mb-2">Engine</div>
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

                        <div class="ocr-dropzone flex-grow-1 d-flex flex-column align-items-center justify-content-center" id="batch-upload-zone" style="min-height: 170px;">
                            <input type="file" id="batch-file-input" class="d-none" accept="image/*" multiple>
                            <div class="text-gray-900 fw-semibold fs-6 mb-1">Drop images here</div>
                            <div class="text-gray-500 fs-7 mb-4">or click to browse — you can add more while the queue runs</div>
                            <span class="btn btn-sm btn-light-primary pe-none">Select images</span>
                        </div>
                    </div>

                    <!--Right: queue-->
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
                            <div class="text-center py-10 border border-dashed border-gray-300 rounded my-auto" id="queue-empty-state">
                                <div class="fw-semibold text-gray-700 fs-6 mb-1">Queue is empty</div>
                                <div class="text-gray-500 fs-7">Add one or more plate images to begin.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Uploader-->

<!--begin::Inspector Modal-->
<div class="modal fade" id="recordInspectorModal" tabindex="-1" aria-labelledby="recordInspectorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable" style="max-width: 95vw;">
        <div class="modal-content">
            <div class="modal-header py-5 px-7 flex-wrap gap-4">
                <div class="d-flex align-items-center gap-3">
                    <span class="badge badge-light-success fw-semibold fs-8" id="meta-db-id-badge">DB #--</span>
                    <div>
                        <h4 class="modal-title fw-bold text-gray-900 fs-5 mb-0" id="recordInspectorModalLabel">Record Inspector</h4>
                        <div class="text-gray-500 fs-8" id="active-record-subtitle">Select an item from the queue or database log</div>
                    </div>
                </div>

                <div class="d-flex align-items-center flex-wrap gap-2 ms-auto" id="modal-choose-logic-bar">
                    <span class="ocr-label me-1 d-none d-lg-inline">Decision</span>
                    <button type="button" class="btn btn-sm btn-light-primary" id="modal-btn-accept-current-logic" data-logic="current_logic">Current Logic</button>
                    <button type="button" class="btn btn-sm btn-light-warning" id="modal-btn-accept-best-pick" data-logic="best_pick">New Logic</button>
                    <button type="button" class="btn btn-sm btn-light-danger" id="modal-btn-reject-all" data-logic="reject_all">Reject All</button>
                    <div class="vr mx-1"></div>
                    <button type="button" class="btn btn-sm btn-light btn-color-danger" id="modal-btn-delete-record">Delete</button>
                    <a href="#" target="_blank" class="btn btn-sm btn-light d-none" id="open-saved-image-btn">Open image</a>
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>

            <div class="modal-body px-7 py-6">
                <div class="text-center py-15 d-none" id="inspector-placeholder">
                    <div class="text-gray-800 fw-bold fs-5 mb-2">No record selected</div>
                    <div class="text-gray-500 fs-7">Click a saved record or queue item to inspect it.</div>
                </div>

                <div id="inspector-content">
                    <!--Image + Best summary-->
                    <div class="row g-5 mb-8">
                        <div class="col-md-5">
                            <div class="saved-preview-stage" id="image-frame-wrapper" title="Click to open full image">
                                <img src="" id="image-preview" alt="Plate image">
                                <span class="magnifier-hint">Hover to zoom · click to open</span>
                                <div id="image-magnifier" class="magnifier-glass d-none"></div>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <div class="best-summary d-flex flex-column justify-content-between" id="best-pick-hero-box">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                        <span class="ocr-label">New Logic across 4 engines</span>
                                        <span class="text-gray-600 fs-8" id="best-hero-plate-winner">-</span>
                                    </div>
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-2" id="best-hero-plate-display">
                                        <span class="text-gray-500 fs-7">Awaiting OCR results...</span>
                                    </div>
                                    <div class="fs-7 text-gray-700 mb-3" id="best-hero-region-line">-</div>
                                    <div class="d-flex flex-wrap gap-2" id="best-hero-char-list"></div>
                                </div>
                                <div class="d-flex flex-wrap gap-2 pt-4 mt-4 border-top border-gray-200" id="best-hero-extra-badges"></div>
                            </div>
                        </div>
                    </div>

                    <!--Tabs-->
                    <ul class="nav nav-line-tabs nav-line-tabs-2x border-gray-200 fs-7 fw-semibold mb-6" id="results-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-active-primary active" id="table-tab" data-bs-toggle="tab" data-bs-target="#table-panel" type="button" role="tab">Comparison</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-active-primary" id="char-breakdown-tab" data-bs-toggle="tab" data-bs-target="#char-breakdown-panel" type="button" role="tab">
                                Characters <span class="badge badge-light fs-9 ms-1" id="char-total-count-badge">0</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-active-primary" id="raw-candid-tab" data-bs-toggle="tab" data-bs-target="#raw-candid-panel" type="button" role="tab">Candid JSON</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-active-primary" id="raw-eydea-tab" data-bs-toggle="tab" data-bs-target="#raw-eydea-panel" type="button" role="tab">Eydea JSON</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-active-primary" id="raw-parkonic-tab" data-bs-toggle="tab" data-bs-target="#raw-parkonic-panel" type="button" role="tab">ARH JSON</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link text-active-primary" id="raw-anpr2-tab" data-bs-toggle="tab" data-bs-target="#raw-anpr2-panel" type="button" role="tab">Parkonic JSON</button>
                        </li>
                    </ul>

                    <div class="tab-content" id="results-tabs-content">
                        <!--Comparison-->
                        <div class="tab-pane fade show active" id="table-panel" role="tabpanel">
                            <div class="alert bg-light-warning border border-warning border-dashed p-4 mb-5 d-none" id="engine-partial-warning" role="alert">
                                <span id="partial-warning-text" class="text-gray-700 fs-7">One of the requested engines failed. Displaying partial results.</span>
                            </div>

                            <div class="table-responsive table-responsive-region" tabindex="0" role="region" aria-label="Engine comparison">
                                <table class="table table-row-dashed table-row-gray-200 gy-3 gx-3 fs-7 mb-0 ocr-table" id="comparison-table">
                                    <thead>
                                        <tr>
                                            <th>Parameter</th>
                                            <th id="th-candid">Candid</th>
                                            <th id="th-eydea">Eydea</th>
                                            <th id="th-parkonic">ARH</th>
                                            <th id="th-anpr2">Parkonic</th>
                                            <th id="th-best" class="best-col-cell text-gray-800">New Logic</th>
                                            <th id="th-currentlogic" class="currentlogic-col-cell text-gray-800">Current Logic</th>
                                        </tr>
                                    </thead>
                                    <tbody class="text-gray-800">
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Detected plate</th>
                                            <td id="comp-candid-plate">-</td>
                                            <td id="comp-eydea-plate">-</td>
                                            <td id="comp-parkonic-plate">-</td>
                                            <td id="comp-anpr2-plate">-</td>
                                            <td id="comp-best-plate" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-plate" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Local / Arabic text</th>
                                            <td id="comp-candid-local">-</td>
                                            <td id="comp-eydea-local">-</td>
                                            <td id="comp-parkonic-local">-</td>
                                            <td id="comp-anpr2-local">-</td>
                                            <td id="comp-best-local" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-local" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Region / type</th>
                                            <td id="comp-candid-region">-</td>
                                            <td id="comp-eydea-region">-</td>
                                            <td id="comp-parkonic-region">-</td>
                                            <td id="comp-anpr2-region">-</td>
                                            <td id="comp-best-region" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-region" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Plate color / size</th>
                                            <td id="comp-candid-platecolor">-</td>
                                            <td id="comp-eydea-platecolor">-</td>
                                            <td id="comp-parkonic-platecolor">-</td>
                                            <td id="comp-anpr2-platecolor">-</td>
                                            <td id="comp-best-platecolor" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-platecolor" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">OCR confidence</th>
                                            <td id="comp-candid-conf">-</td>
                                            <td id="comp-eydea-conf">-</td>
                                            <td id="comp-parkonic-conf">-</td>
                                            <td id="comp-anpr2-conf">-</td>
                                            <td id="comp-best-conf" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-conf" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Characters</th>
                                            <td id="comp-candid-chars">-</td>
                                            <td id="comp-eydea-chars">-</td>
                                            <td id="comp-parkonic-chars">-</td>
                                            <td id="comp-anpr2-chars">-</td>
                                            <td id="comp-best-chars" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-chars" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Make</th>
                                            <td id="comp-candid-make">-</td>
                                            <td id="comp-eydea-make">-</td>
                                            <td id="comp-parkonic-make">-</td>
                                            <td id="comp-anpr2-make">-</td>
                                            <td id="comp-best-make" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-make" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Model</th>
                                            <td id="comp-candid-model">-</td>
                                            <td id="comp-eydea-model">-</td>
                                            <td id="comp-parkonic-model">-</td>
                                            <td id="comp-anpr2-model">-</td>
                                            <td id="comp-best-model" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-model" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Generation / year</th>
                                            <td id="comp-candid-generation">-</td>
                                            <td id="comp-eydea-generation">-</td>
                                            <td id="comp-parkonic-generation">-</td>
                                            <td id="comp-anpr2-generation">-</td>
                                            <td id="comp-best-generation" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-generation" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Vehicle (MMR)</th>
                                            <td id="comp-candid-vehicle">-</td>
                                            <td id="comp-eydea-vehicle">-</td>
                                            <td id="comp-parkonic-vehicle">-</td>
                                            <td id="comp-anpr2-vehicle">-</td>
                                            <td id="comp-best-vehicle" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-vehicle" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Body type</th>
                                            <td id="comp-candid-category">-</td>
                                            <td id="comp-eydea-category">-</td>
                                            <td id="comp-parkonic-category">-</td>
                                            <td id="comp-anpr2-category">-</td>
                                            <td id="comp-best-category" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-category" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Vehicle color</th>
                                            <td id="comp-candid-color">-</td>
                                            <td id="comp-eydea-color">-</td>
                                            <td id="comp-parkonic-color">-</td>
                                            <td id="comp-anpr2-color">-</td>
                                            <td id="comp-best-color" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-color" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Viewpoint</th>
                                            <td id="comp-candid-viewport">-</td>
                                            <td id="comp-eydea-viewport">-</td>
                                            <td id="comp-parkonic-viewport">-</td>
                                            <td id="comp-anpr2-viewport">-</td>
                                            <td id="comp-best-viewport" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-viewport" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Quality</th>
                                            <td id="comp-candid-quality">-</td>
                                            <td id="comp-eydea-quality">-</td>
                                            <td id="comp-parkonic-quality">-</td>
                                            <td id="comp-anpr2-quality">-</td>
                                            <td id="comp-best-quality" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-quality" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Bounding box</th>
                                            <td id="comp-candid-bbox" class="font-monospace fs-8">-</td>
                                            <td id="comp-eydea-bbox" class="font-monospace fs-8">-</td>
                                            <td id="comp-parkonic-bbox" class="font-monospace fs-8">-</td>
                                            <td id="comp-anpr2-bbox" class="font-monospace fs-8">-</td>
                                            <td id="comp-best-bbox" class="best-col-cell font-monospace fs-8">-</td>
                                            <td id="comp-currentlogic-bbox" class="currentlogic-col-cell font-monospace fs-8">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Tags</th>
                                            <td id="comp-candid-tags">-</td>
                                            <td id="comp-eydea-tags">-</td>
                                            <td id="comp-parkonic-tags">-</td>
                                            <td id="comp-anpr2-tags">-</td>
                                            <td id="comp-best-tags" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-tags" class="currentlogic-col-cell">-</td>
                                        </tr>
                                        <tr>
                                            <th scope="row" class="text-gray-600 fw-semibold">Latency</th>
                                            <td id="comp-candid-time">-</td>
                                            <td id="comp-eydea-time">-</td>
                                            <td id="comp-parkonic-time">-</td>
                                            <td id="comp-anpr2-time">-</td>
                                            <td id="comp-best-time" class="best-col-cell">-</td>
                                            <td id="comp-currentlogic-time" class="currentlogic-col-cell">-</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!--Characters-->
                        <div class="tab-pane fade" id="char-breakdown-panel" role="tabpanel">
                            <div class="row g-4 mb-8" id="char-engine-cards-grid"></div>

                            <div class="ocr-label mb-3">Character rows</div>
                            <div class="table-responsive table-responsive-region" tabindex="0" role="region" aria-label="Character breakdown">
                                <table class="table table-row-dashed table-row-gray-200 gy-3 gx-3 fs-7 mb-0 ocr-table" id="char-breakdown-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 12%;">Engine</th>
                                            <th style="width: 10%;">Plate</th>
                                            <th style="width: 10%;">Position</th>
                                            <th style="width: 12%;">Character</th>
                                            <th style="width: 16%;">Confidence</th>
                                            <th style="width: 40%;">Raw data</th>
                                        </tr>
                                    </thead>
                                    <tbody id="char-breakdown-tbody" class="text-gray-800">
                                        <tr>
                                            <td colspan="6" class="text-center text-gray-500 py-6">Select a record to view characters.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!--JSON panels-->
                        <div class="tab-pane fade" id="raw-candid-panel" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="ocr-label">Candid response</span>
                                <button class="btn btn-sm btn-light" id="copy-candid-json-btn">Copy</button>
                            </div>
                            <pre class="ocr-code rounded bg-light p-4 mb-0"><code id="candid-json-code">{}</code></pre>
                        </div>
                        <div class="tab-pane fade" id="raw-eydea-panel" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="ocr-label">Eydea response</span>
                                <button class="btn btn-sm btn-light" id="copy-eydea-json-btn">Copy</button>
                            </div>
                            <pre class="ocr-code rounded bg-light p-4 mb-0"><code id="eydea-json-code">{}</code></pre>
                        </div>
                        <div class="tab-pane fade" id="raw-parkonic-panel" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="ocr-label">ARH response</span>
                                <button class="btn btn-sm btn-light" id="copy-parkonic-json-btn">Copy</button>
                            </div>
                            <pre class="ocr-code rounded bg-light p-4 mb-0"><code id="parkonic-json-code">{}</code></pre>
                        </div>
                        <div class="tab-pane fade" id="raw-anpr2-panel" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="ocr-label">Parkonic response</span>
                                <button class="btn btn-sm btn-light" id="copy-anpr2-json-btn">Copy</button>
                            </div>
                            <pre class="ocr-code rounded bg-light p-4 mb-0"><code id="anpr2-json-code">{}</code></pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!--end::Inspector Modal-->

<!--begin::Database Log-->
<div class="card card-flush">
    <div class="card-header pt-6 flex-wrap gap-3">
        <div class="card-title">
            <div class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-4"></i>
                <input type="text" id="db-search-input" class="form-control form-control-solid w-250px ps-12" placeholder="Search...">
            </div>
            <div class="text-gray-500 fs-8 fw-normal ms-6" id="db-total-subtitle">Compare Current Logic and New Logic, then confirm one or reject both.</div>
        </div>
        <div class="card-toolbar flex-row-fluid justify-content-end gap-5">
            <div class="w-200 mw-250px">
                <select id="db-decision-filter" class="form-select form-select-solid w-180px" title="Filter database log by decision status">
                    <option value="all" selected>All (Pending First)</option>
                    <option value="pending">Pending Decisions Only</option>
                    <option value="decided">Decided Decisions Only</option>
                    <option value="current_logic">Accepted Current Logic</option>
                    <option value="best_pick">Accepted New Logic</option>
                    <option value="reject_all">Rejected All</option>
                </select>
            </div>

            <button class="btn btn-light-info" id="refresh-db-btn">Refresh</button>
        </div>
    </div>

    <div class="card-body pt-2">
        <div class="table-responsive table-responsive-region" tabindex="0" role="region" aria-label="Database log">
            <table class="table table-row-dashed table-row-gray-200 gy-4 gx-3 fs-7 mb-0 ocr-table" id="db-history-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Decision</th>
                        <th class="best-col-cell text-gray-800">New Logic</th>
                        <th class="currentlogic-col-cell text-gray-800">Current Logic</th>
                        <th>Candid</th>
                        <th>Eydea</th>
                        <th>ARH</th>
                        <th>Parkonic</th>
                    </tr>
                </thead>
                <tbody id="db-history-tbody" class="text-gray-800">
                    <tr>
                        <td colspan="9" class="text-center py-10 text-gray-500">Loading records...</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center pt-5 mt-2 border-top border-gray-200" id="db-pagination-bar">
            <span class="text-gray-500 fs-7" id="db-page-info">Page 1 of 1</span>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-light" id="db-prev-page-btn" disabled>Previous</button>
                <button class="btn btn-sm btn-light" id="db-next-page-btn" disabled>Next</button>
            </div>
        </div>
    </div>
</div>
<!--end::Database Log-->

<!--begin::Hover preview-->
<div id="image-hover-popover" class="card shadow border border-gray-200 position-fixed d-none bg-body" style="z-index: 9999; pointer-events: none; width: 560px; max-width: 94vw;">
    <div class="card-header min-h-auto py-3 px-4 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2 overflow-hidden">
            <span class="badge badge-light fs-8" id="popover-db-id">DB #--</span>
            <span class="fs-8 text-gray-600 font-monospace text-truncate" style="max-width: 320px;" id="popover-filename">image.jpg</span>
        </div>
        <span class="text-gray-400 fs-8">Click row to inspect</span>
    </div>
    <div class="card-body p-2 d-flex align-items-center justify-content-center bg-gray-100" style="min-height: 300px; max-height: 460px;">
        <img id="popover-img" src="" class="img-fluid rounded" style="max-height: 440px; object-fit: contain;" alt="Plate preview">
    </div>
    <div class="card-footer py-3 px-4 d-flex flex-wrap justify-content-between gap-2" id="popover-footer-meta">
        <span class="fs-8 text-gray-700" id="popover-best-plate">Best: -</span>
        <span class="fs-8 text-gray-700" id="popover-current-plate">Current: -</span>
    </div>
</div>
<!--end::Hover preview-->
@endsection

@section('scripts')
<script id="ocr-batch-script">
    document.addEventListener('DOMContentLoaded', () => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Named Route templates with placeholders for dynamic IDs
        const acceptLogicRouteTemplate = "{{ route('ocr.batch.acceptLogic', ['id' => ':id']) }}";
        const deleteRecordRouteTemplate = "{{ route('ocr.batch.deleteRecord', ['id' => ':id']) }}";
        const imageRouteTemplate = "{{ route('ocr.image', ['filename' => ':filename']) }}";

        // State
        const currentBatchId = 'batch_' + Date.now() + '_' + Math.random().toString(36).substring(2, 7);
        let uploadQueue = [];
        let isProcessingQueue = false;
        let activeItemKey = null;
        let activeInspectedRecordId = null;
        let activeInspectedRecord = null;
        let dbRecordsCache = [];
        let currentDbPage = 1;
        let lastDbPage = 1;

        const $ = (id) => document.getElementById(id);

        // Stats
        const statSessionCount = $('stat-session-count');
        const statProcessedCount = $('stat-processed-count');
        const statDbTotal = $('stat-db-total');
        const statCurrentLogicCount = $('stat-current-logic-count');
        const statCurrentLogicPct = $('stat-current-logic-pct');
        const statCurrentLogicSub = $('stat-current-logic-sub');
        const statBestPickCount = $('stat-best-pick-count');
        const statBestPickPct = $('stat-best-pick-pct');
        const statBestPickSub = $('stat-best-pick-sub');

        // Queue
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

        // Inspector
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

        const recordInspectorModalEl = $('recordInspectorModal');
        let bsRecordInspectorModal = null;
        const getModal = () => {
            if (!bsRecordInspectorModal && recordInspectorModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bsRecordInspectorModal = bootstrap.Modal.getOrCreateInstance(recordInspectorModalEl);
            }
            return bsRecordInspectorModal;
        };
        getModal();

        const hoverPopoverEl = $('image-hover-popover');
        const popoverImg = $('popover-img');
        const popoverDbId = $('popover-db-id');
        const popoverFilename = $('popover-filename');
        const popoverBestPlate = $('popover-best-plate');
        const popoverCurrentPlate = $('popover-current-plate');

        const modalBtnAcceptCurrentLogic = $('modal-btn-accept-current-logic');
        const modalBtnAcceptBestPick = $('modal-btn-accept-best-pick');
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

        // Comparison cells: cells.<col>.<field>
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

        // DB table
        const dbDecisionFilter = $('db-decision-filter');
        const dbSearchInput = $('db-search-input');
        const refreshDbBtn = $('refresh-db-btn');
        const dbHistoryTbody = $('db-history-tbody');
        const dbTotalSubtitle = $('db-total-subtitle');
        const dbPageInfo = $('db-page-info');
        const dbPrevPageBtn = $('db-prev-page-btn');
        const dbNextPageBtn = $('db-next-page-btn');

        // ---------- Helpers ----------
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
                .replace(/"/g, '&quot;');
        };

        const isEmptyVal = (v) => v === null || v === undefined || v === '' || v === 'N/A' || v === '-' || v === 'None';

        // Copy buttons (text feedback)
        function bindCopy(button, getText) {
            button.addEventListener('click', () => {
                const text = getText();
                if (!text) return;
                navigator.clipboard.writeText(text).then(() => {
                    button.textContent = 'Copied';
                    setTimeout(() => button.textContent = 'Copy', 1600);
                });
            });
        }
        bindCopy($('copy-candid-json-btn'), () => candidJsonCode.textContent);
        bindCopy($('copy-eydea-json-btn'), () => eydeaJsonCode.textContent);
        bindCopy($('copy-parkonic-json-btn'), () => parkonicJsonCode.textContent);
        bindCopy($('copy-anpr2-json-btn'), () => anpr2JsonCode.textContent);

        // ---------- Magnifier ----------
        const magnifierZoom = 2.8;

        function moveMagnifier(e) {
            if (!imagePreview.src) return;
            const rect = imagePreview.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            if (x < 0 || x > rect.width || y < 0 || y > rect.height) {
                imageMagnifier.classList.add('d-none');
                return;
            }
            imageMagnifier.classList.remove('d-none');
            const frameRect = imageFrameWrapper.getBoundingClientRect();
            imageMagnifier.style.left = `${e.clientX - frameRect.left - imageMagnifier.offsetWidth / 2}px`;
            imageMagnifier.style.top = `${e.clientY - frameRect.top - imageMagnifier.offsetHeight / 2}px`;
            imageMagnifier.style.backgroundImage = `url('${imagePreview.src}')`;
            imageMagnifier.style.backgroundSize = `${rect.width * magnifierZoom}px ${rect.height * magnifierZoom}px`;
            const rx = x * magnifierZoom - imageMagnifier.offsetWidth / 2;
            const ry = y * magnifierZoom - imageMagnifier.offsetHeight / 2;
            imageMagnifier.style.backgroundPosition = `-${rx}px -${ry}px`;
        }
        imagePreview.addEventListener('mousemove', moveMagnifier);
        imagePreview.addEventListener('mouseleave', () => imageMagnifier.classList.add('d-none'));

        // ---------- Dropzone ----------
        uploadZone.addEventListener('click', () => fileInput.click());
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
        fileInput.addEventListener('change', (e) => {
            if (e.target.files && e.target.files.length) {
                enqueueFiles(Array.from(e.target.files));
                fileInput.value = '';
            }
        });

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
            if (autoProcessToggle.checked) processNextInQueue();
        }

        startQueueBtn.addEventListener('click', () => processNextInQueue());

        clearQueueBtn.addEventListener('click', () => {
            uploadQueue = isProcessingQueue ? uploadQueue.filter(i => i.status === 'processing') : [];
            renderQueueList();
            updateQueueProgress();
        });

        function updateQueueProgress() {
            const total = uploadQueue.length;
            const completed = uploadQueue.filter(i => i.status === 'done' || i.status === 'error').length;
            const queuedCount = uploadQueue.filter(i => i.status === 'queued').length;

            queueCounterBadge.textContent = `${completed} / ${total}`;
            statSessionCount.textContent = `${total} ${total === 1 ? 'File' : 'Files'}`;
            statProcessedCount.textContent = `${completed} / ${total}`;
            queueProgressBar.style.width = `${total > 0 ? Math.round((completed / total) * 100) : 0}%`;

            startQueueBtn.disabled = isProcessingQueue || queuedCount === 0;
            clearQueueBtn.disabled = total === 0;
            startQueueBtnText.textContent = isProcessingQueue ? 'Running...' : `Run (${queuedCount})`;
        }

        function renderQueueList() {
            queueListEl.innerHTML = '';
            if (uploadQueue.length === 0) {
                queueListEl.appendChild(queueEmptyState);
                queueEmptyState.classList.remove('d-none');
                return;
            }
            queueEmptyState.classList.add('d-none');

            uploadQueue.forEach((item, index) => {
                const div = document.createElement('div');
                div.className = `queue-item status-${item.status} d-flex align-items-center justify-content-between gap-3 ${activeItemKey === item.key ? 'active' : ''}`;

                let statusBadge = '';
                if (item.status === 'queued') {
                    statusBadge = `<span class="badge badge-light fs-9">Queued #${index + 1}</span>`;
                } else if (item.status === 'processing') {
                    statusBadge = `<span class="badge badge-light-primary fs-9">Processing</span>`;
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
                            <div class="fw-semibold text-gray-900 fs-7 text-truncate" style="max-width: 220px;" title="${escapeHtml(item.originalName)}">${escapeHtml(item.originalName)}</div>
                            <div class="text-gray-500 fs-8">${formatBytes(item.fileSize)}${dimInfo}</div>
                            ${platesPreview}
                        </div>
                    </div>
                    <div class="flex-shrink-0">${statusBadge}</div>
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

            // If the user already opened the inspector modal specifically for this item, update its preview without forcing open
            const isModalOpen = recordInspectorModalEl && recordInspectorModalEl.classList.contains('show');
            if (activeItemKey === nextItem.key && isModalOpen) {
                inspectQueueItem(nextItem, false);
            }

            try {
                const response = await fetch('{{ route("ocr.batch.process") }}', {
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
                    // If user is currently viewing this item in the modal, update content without forcing open
                    const modalStillOpen = recordInspectorModalEl && recordInspectorModalEl.classList.contains('show');
                    if (activeItemKey === nextItem.key && modalStillOpen) {
                        inspectQueueItem(nextItem, false);
                    }
                    loadDatabaseRecords(1);
                }
            } catch (err) {
                console.error('Batch item error:', err);
                nextItem.status = 'error';
                nextItem.errorMsg = err.message || 'Network error';
            } finally {
                isProcessingQueue = false;
                renderQueueList();
                updateQueueProgress();
                if (uploadQueue.some(i => i.status === 'queued') && autoProcessToggle.checked) {
                    processNextInQueue();
                }
            }
        }

        function inspectQueueItem(item, openModal = false) {
            if (!item) return;
            inspectorPlaceholder.classList.add('d-none');
            inspectorContent.classList.remove('d-none');

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

            bestHeroPlateWinner.textContent = item.status === 'processing' ? 'Running 4 engines...' : 'Queued';
            bestHeroPlateDisplay.innerHTML = `<span class="text-gray-500 fs-7">Computing best values...</span>`;
            bestHeroRegionLine.textContent = '-';
            bestHeroCharList.innerHTML = '';
            bestHeroExtraBadges.innerHTML = '';

            if (openModal) {
                const m = getModal();
                if (m) m.show();
            }
        }

        function populateInspectorFromRecord(record, liveResults = null, openModal = false) {
            if (!record) return;
            inspectorPlaceholder.classList.add('d-none');
            inspectorContent.classList.remove('d-none');
            activeInspectedRecord = record;
            activeInspectedRecordId = record.id;

            document.querySelectorAll('#db-history-tbody tr').forEach(tr => {
                tr.classList.toggle('active-db-row', Number(tr.dataset.recordId) === Number(record.id));
            });

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

        // ---------- Formatting ----------
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
                } else if (cleanText.toLowerCase().startsWith(cleanCode.toLowerCase())) {
                    if (/[A-Z]/i.test(cleanCode)) {
                        cleanText = cleanText.substring(cleanCode.length);
                    } else if (/^\d+$/.test(cleanText) && cleanText.length > 5) {
                        cleanText = cleanText.substring(cleanCode.length);
                    }
                }
            }

            if (!cleanCode && cleanText) {
                const separatorMatch = cleanText.match(/^(.*?)[-\s]+([A-Z0-9]+)$/i);
                if (separatorMatch) {
                    cleanCode = separatorMatch[1].trim();
                    cleanText = separatorMatch[2].trim();
                } else if (/^\d+$/.test(cleanText) && cleanText.length > 5) {
                    const n = cleanText.length;
                    cleanCode = cleanText.substring(0, n - 5);
                    cleanText = cleanText.substring(n - 5);
                } else {
                    const m = cleanText.match(/^([A-Z]+)([0-9]+)$/i);
                    if (m) {
                        cleanCode = m[1].toUpperCase();
                        cleanText = m[2];
                    }
                }
            }

            if (cleanCode && cleanText) return plateBadge(cleanCode) + ' ' + plateBadge(cleanText);
            if (cleanCode) return plateBadge(cleanCode);
            if (cleanText) return plateBadge(cleanText);
            return '-';
        };

        const confidenceBadge = (c) => {
            const exact = toExactPct(c);
            return `<span class="badge badge-light-${confTone(exact)} fs-8 fw-semibold">${exact}%</span>`;
        };
        const charChip = (label, conf) => {
            const exact = toExactPct(conf);
            return `<span class="badge badge-light-${confTone(exact)} fw-semibold m-1 font-monospace">${escapeHtml(label)} ${exact}%</span>`;
        };
        const latencyCell = (ms) => `<span class="text-gray-700">${ms} ms</span>`;
        const failedCell = () => `<span class="text-danger fs-7 fw-semibold">Failed</span>`;
        const noPlateCell = () => `<span class="text-warning fs-7 fw-semibold">No plate</span>`;
        const mutedDash = () => `<span class="text-gray-400">-</span>`;

        function winnerBadgeHtml(engineLabel) {
            if (!engineLabel) return '';
            return `<span class="text-gray-500 fs-9 fw-semibold">via ${escapeHtml(engineLabel)}</span>`;
        }

        const ENGINE_DEFS = [{
            key: 'candid',
            label: 'Candid'
        }, {
            key: 'eydea',
            label: 'Eydea'
        }, {
            key: 'arh',
            label: 'ARH'
        }, {
            key: 'parkonic',
            label: 'Parkonic'
        }];

        // ---------- New Logic ----------
        function getOrComputeBestSummary(rec) {
            if (rec && rec.best_summary_json && rec.best_summary_json.plate) {
                if (!Array.isArray(rec.best_summary_json.characters) || rec.best_summary_json.characters.length === 0) {
                    if (Array.isArray(rec.best_characters_json) && rec.best_characters_json.length > 0) {
                        rec.best_summary_json.characters = rec.best_characters_json;
                    }
                }
                if (Array.isArray(rec.best_summary_json.characters) && rec.best_summary_json.characters.length > 0) {
                    return rec.best_summary_json;
                }
            }

            const engineDefs = ENGINE_DEFS;

            const parseEmbeddedValueAndConf = (str, fallbackConf) => {
                if (!str || str === 'N/A' || str === 'None' || str === '-') return null;
                const matches = [...String(str).matchAll(/\(([0-9.]+)%\)/g)];
                if (matches.length > 0) {
                    const nums = matches.map(m => parseFloat(toExactPct(m[1]))).filter(n => !isNaN(n));
                    const avgScore = nums.reduce((a, b) => a + b, 0) / nums.length;
                    return {
                        value: String(str).replace(/\s*\([0-9.]+%\)/g, '').trim(),
                        full_text: str,
                        conf: toExactPct(matches[0][1]),
                        score: avgScore + 1000
                    };
                }
                const fbExact = toExactPct(fallbackConf);
                return {
                    value: String(str).trim(),
                    full_text: String(str).trim(),
                    conf: fbExact,
                    score: parseFloat(fbExact) || 0
                };
            };

            const pickBestAttr = (field) => {
                let best = null;
                engineDefs.forEach(eng => {
                    const parsed = parseEmbeddedValueAndConf(rec[`${eng.key}_${field}`], rec[`${eng.key}_confidence`]);
                    if (!parsed) return;
                    if (!best || parsed.score > best.score) best = {
                        ...parsed,
                        engine: eng.key,
                        engine_label: eng.label
                    };
                });
                return best;
            };

            // Plate
            let bestPlatePick = null;
            engineDefs.forEach(eng => {
                const plate = rec[`${eng.key}_plate`];
                if (!plate || plate === 'N/A' || plate === '-') return;
                const conf = toExactPct(rec[`${eng.key}_confidence`]);
                const score = parseFloat(conf) || 0;
                if (!bestPlatePick || score > bestPlatePick.score) {
                    bestPlatePick = {
                        value: plate,
                        conf,
                        score,
                        engine: eng.key,
                        engine_label: eng.label
                    };
                }
            });

            // Characters per position
            const filteredCharMap = {};
            let maxChars = 0;
            engineDefs.forEach(eng => {
                const rawArr = rec[`${eng.key}_characters_json`] || [];
                if (Array.isArray(rawArr)) {
                    let primaryOnly = rawArr.filter(c => Number(c.plate_index || 0) === 0);
                    if (primaryOnly.length === 0) primaryOnly = rawArr;
                    let asciiOnly = primaryOnly.filter(c => c && c.character && /^[A-Za-z0-9]$/.test(String(c.character).trim()));
                    if (asciiOnly.length === 0) asciiOnly = primaryOnly;
                    filteredCharMap[eng.key] = asciiOnly;
                    if (asciiOnly.length > maxChars) maxChars = asciiOnly.length;
                } else {
                    filteredCharMap[eng.key] = [];
                }
            });

            const bestCharsList = [];
            let assembledChars = '';
            for (let idx = 0; idx < maxChars; idx++) {
                let posWinner = null;
                engineDefs.forEach(eng => {
                    const arr = filteredCharMap[eng.key] || [];
                    const offset = (arr.length < maxChars && arr.length > 0 && /^\d$/.test(String(arr[0].character))) ? (maxChars - arr.length) : 0;
                    const ch = arr[idx - offset];
                    if (idx - offset < 0 || !ch) return;
                    const exactConf = toExactPct(ch.confidence);
                    const score = parseFloat(exactConf) || 0;
                    if (!posWinner || score > posWinner.score) {
                        posWinner = {
                            position: idx + 1,
                            character: ch.character,
                            confidence: exactConf,
                            score,
                            engine: eng.key,
                            engine_label: eng.label
                        };
                    }
                });
                if (posWinner) {
                    bestCharsList.push(posWinner);
                    assembledChars += posWinner.character;
                }
            }

            // Region
            let bestRegionPick = null;
            engineDefs.forEach(eng => {
                const pConf = eng.key === 'candid' ? '100' : rec[`${eng.key}_confidence`];
                const parsed = parseEmbeddedValueAndConf(rec[`${eng.key}_region`], pConf);
                if (!parsed) return;
                if (!bestRegionPick || parsed.score > bestRegionPick.score) bestRegionPick = {
                    ...parsed,
                    engine: eng.key,
                    engine_label: eng.label
                };
            });

            // Generic composite builder for pipe-separated attributes
            const compositeFrom = (field, classify) => {
                const buckets = {};
                engineDefs.forEach(eng => {
                    const raw = rec[`${eng.key}_${field}`];
                    if (isEmptyVal(raw)) return;
                    String(raw).split('|').forEach(p => {
                        const trimmed = p.trim();
                        if (!trimmed) return;
                        const parsed = parseEmbeddedValueAndConf(trimmed, rec[`${eng.key}_confidence`]);
                        if (!parsed) return;
                        const item = classify(parsed, eng);
                        if (!item) return;
                        const cur = buckets[item.bucket];
                        if (!cur || item.score > cur.score) buckets[item.bucket] = item;
                    });
                });
                return buckets;
            };

            const finalizeComposite = (items, fallbackField) => {
                items = items.filter(Boolean);
                if (items.length === 0) return pickBestAttr(fallbackField);
                return {
                    value: items.map(it => it.value).join(' | '),
                    conf: items[0].conf,
                    score: items[0].score,
                    engine_label: items[0].engine_label,
                    items
                };
            };

            // Plate attributes
            const plateBuckets = compositeFrom('plate_color', (parsed, eng) => {
                const clean = parsed.value.replace(/^(Type|Color|Size|Dim):\s*/i, '').trim();
                const base = {
                    clean_value: clean,
                    conf: parsed.conf,
                    score: parsed.score,
                    engine_label: eng.label
                };
                if (/taxi|commercial|police|diplomatic|type:/i.test(parsed.value)) {
                    return {
                        ...base,
                        bucket: 'type',
                        label: 'Plate Type',
                        value: 'Type: ' + clean,
                        score: parsed.score + (/taxi|commercial|police/i.test(clean) ? 500 : 0)
                    };
                }
                if (/dim:|×|x|\d+\s*mm/i.test(parsed.value)) return {
                    ...base,
                    bucket: 'dim',
                    label: 'Plate Dim',
                    value: 'Dim: ' + clean
                };
                if (/size:|long|short|square/i.test(parsed.value)) return {
                    ...base,
                    bucket: 'size',
                    label: 'Plate Size',
                    value: 'Size: ' + clean
                };
                return {
                    ...base,
                    bucket: 'color',
                    label: 'Plate Color',
                    value: 'Color: ' + clean
                };
            });
            const bestPlateColorPick = finalizeComposite([plateBuckets.type, plateBuckets.color, plateBuckets.size, plateBuckets.dim], 'plate_color');

            // Quality
            const qualityBuckets = compositeFrom('quality', (parsed, eng) => ({
                bucket: /cropped/i.test(parsed.value) ? 'cropped' : 'det',
                label: /cropped/i.test(parsed.value) ? 'Cropped Quality' : 'Detection Quality',
                value: parsed.value,
                clean_value: parsed.value,
                conf: parsed.conf,
                score: parsed.score,
                engine_label: eng.label
            }));
            const bestQualityPick = finalizeComposite([qualityBuckets.cropped, qualityBuckets.det], 'quality');

            // Local text
            const localBuckets = compositeFrom('local_text', (parsed, eng) => {
                let bucket = 'script',
                    label = 'Local Script';
                if (/code:/i.test(parsed.value)) {
                    bucket = 'code';
                    label = 'Plate Code';
                } else if (/groups:/i.test(parsed.value)) {
                    bucket = 'groups';
                    label = 'Text Groups';
                } else if (/lines/i.test(parsed.value)) {
                    bucket = 'lines';
                    label = 'Line Breakdown';
                }
                return {
                    bucket,
                    label,
                    value: parsed.value,
                    clean_value: parsed.value,
                    conf: parsed.conf,
                    score: parsed.score,
                    engine_label: eng.label
                };
            });
            const bestLocalTextPick = finalizeComposite([localBuckets.script, localBuckets.code, localBuckets.groups, localBuckets.lines], 'local_text');

            // Bounding box (vehicle box: last engine wins; plate box: highest conf)
            let plateBb = null,
                vehicleBb = null;
            engineDefs.forEach(eng => {
                const raw = rec[`${eng.key}_bbox`];
                if (isEmptyVal(raw)) return;
                String(raw).split('|').forEach(p => {
                    const trimmed = p.trim();
                    if (!trimmed) return;
                    const conf = rec[`${eng.key}_confidence`] || '100';
                    const score = parseFloat(toExactPct(conf)) || 0;
                    const item = {
                        value: trimmed,
                        clean_value: trimmed,
                        conf,
                        score,
                        engine_label: eng.label
                    };
                    if (/vehicle:/i.test(trimmed)) {
                        vehicleBb = {
                            ...item,
                            label: 'Vehicle BBox'
                        };
                    } else if (!plateBb || score > plateBb.score) {
                        plateBb = {
                            ...item,
                            label: 'Plate BBox'
                        };
                    }
                });
            });
            const bestBboxPick = finalizeComposite([plateBb, vehicleBb], 'bbox');

            const bestViewportPick = pickBestAttr('viewport');

            let fastestPick = null;
            engineDefs.forEach(eng => {
                const ms = Number(rec[`${eng.key}_time_ms`]);
                if (rec[`${eng.key}_success`] && ms > 0 && (!fastestPick || ms < fastestPick.time_ms)) {
                    fastestPick = {
                        time_ms: ms,
                        engine: eng.key,
                        engine_label: eng.label
                    };
                }
            });

            let cityPrefix = '';
            if (bestRegionPick && bestRegionPick.value) {
                cityPrefix = String(bestRegionPick.value).split(/[\/\(\|]/)[0].replace(/UAE/gi, '').replace(/^[-_\s]+|[-_\s]+$/g, '').toUpperCase();
            }
            const plateCore = assembledChars !== '' ? assembledChars : (bestPlatePick ? bestPlatePick.value : '');
            let finalBestPlate = plateCore;
            if (cityPrefix && plateCore && !plateCore.toUpperCase().includes(cityPrefix)) {
                finalBestPlate = `${cityPrefix} ${plateCore}`;
            }

            return {
                final_plate: finalBestPlate || (bestPlatePick ? bestPlatePick.value : null),
                plate: bestPlatePick,
                region: bestRegionPick,
                characters: bestCharsList,
                make: pickBestAttr('make'),
                model: pickBestAttr('model'),
                generation: pickBestAttr('generation'),
                vehicle: pickBestAttr('vehicle'),
                category: pickBestAttr('category'),
                color: pickBestAttr('color'),
                plate_color: bestPlateColorPick,
                local_text: bestLocalTextPick,
                bbox: bestBboxPick,
                viewport: bestViewportPick,
                orientation: bestViewportPick,
                quality: bestQualityPick,
                tags: pickBestAttr('tags'),
                fastest: fastestPick,
            };
        }

        function renderBestPicks(record) {
            const best = getOrComputeBestSummary(record);

            // Summary card
            if (best && (best.final_plate || best.plate)) {
                bestHeroPlateDisplay.innerHTML = renderPlate(best.final_plate || (best.plate ? best.plate.value : '-'));

                bestHeroPlateWinner.innerHTML = best.plate ?
                    `${escapeHtml(best.plate.value)} · ${escapeHtml(toExactPct(best.plate.conf))}% ${winnerBadgeHtml(best.plate.engine_label)}` :
                    '-';

                bestHeroRegionLine.innerHTML = best.region ?
                    `${escapeHtml(best.region.value)} <span class="text-success fw-semibold ms-1">${escapeHtml(toExactPct(best.region.conf))}%</span> ${winnerBadgeHtml(best.region.engine_label)}` :
                    '';

                bestHeroCharList.innerHTML = (Array.isArray(best.characters) && best.characters.length > 0) ?
                    best.characters.map(c => {
                        const exactC = toExactPct(c.confidence);
                        return `<div class="best-char-item">
                            <span class="fw-bold fs-6 text-gray-900">${escapeHtml(c.character)}</span>
                            <span class="fw-semibold text-${confTone(exactC)}">${escapeHtml(exactC)}</span>
                            <span class="text-gray-500 fs-9">${escapeHtml(c.engine_label)}</span>
                        </div>`;
                    }).join('') :
                    `<span class="text-gray-500 fs-8">No character-level splits</span>`;

                const buildChip = (label, attr) => {
                    if (!attr || !attr.value) return '';
                    const items = (Array.isArray(attr.items) && attr.items.length) ? attr.items : [{
                        ...attr,
                        label
                    }];
                    return items.map(it => {
                        const exactC = toExactPct(it.conf);
                        return `<div class="best-attr-chip">
                            <span class="text-gray-500">${escapeHtml(it.label || label)}</span>
                            <span class="fw-semibold text-gray-900">${escapeHtml(it.clean_value || it.value)}</span>
                            ${exactC ? `<span class="text-success fw-semibold">${escapeHtml(exactC)}%</span>` : ''}
                            <span class="text-gray-500 fs-9">${escapeHtml(it.engine_label)}</span>
                        </div>`;
                    }).join('');
                };

                const chips = [
                    buildChip('Make', best.make),
                    buildChip('Model', best.model),
                    buildChip('Gen/Year', best.generation),
                    buildChip('Body', best.category),
                    buildChip('Color', best.color),
                    buildChip('View', best.orientation || best.viewport),
                    buildChip('Plate', best.plate_color),
                    buildChip('Local', best.local_text),
                    buildChip('Quality', best.quality),
                    buildChip('BBox', best.bbox),
                ].filter(Boolean);

                if (best.fastest) {
                    chips.push(`<div class="best-attr-chip">
                        <span class="text-gray-500">Fastest</span>
                        <span class="fw-semibold text-gray-900">${escapeHtml(best.fastest.time_ms)} ms</span>
                        <span class="text-gray-500 fs-9">${escapeHtml(best.fastest.engine_label)}</span>
                    </div>`);
                }
                bestHeroExtraBadges.innerHTML = chips.join('');
            } else {
                bestHeroPlateDisplay.innerHTML = `<span class="text-danger fs-7 fw-semibold">No plate detected by any engine</span>`;
                bestHeroPlateWinner.textContent = '-';
                bestHeroRegionLine.textContent = '-';
                bestHeroCharList.innerHTML = '';
                bestHeroExtraBadges.innerHTML = '';
            }

            // Comparison table column
            const formatAttrBestCell = (attr) => {
                if (!attr || !attr.value) return mutedDash();
                if (Array.isArray(attr.items) && attr.items.length > 0) {
                    return `<div class="d-flex flex-column gap-2">` + attr.items.map(it => {
                        const exactC = toExactPct(it.conf);
                        return `<div>
                            <div class="fw-semibold text-gray-900 fs-8">${escapeHtml(it.value || it.clean_value)}${exactC ? ` <span class="text-success fs-9">${escapeHtml(exactC)}%</span>` : ''}</div>
                            <div>${winnerBadgeHtml(it.engine_label)}</div>
                        </div>`;
                    }).join('') + `</div>`;
                }
                const exactC = toExactPct(attr.conf);
                return `<div class="fw-semibold text-gray-900">${escapeHtml(attr.value)}${exactC ? ` <span class="text-success fs-8">${escapeHtml(exactC)}%</span>` : ''}</div><div>${winnerBadgeHtml(attr.engine_label)}</div>`;
            };

            const B = C.best;
            B.plate.innerHTML = (best && (best.final_plate || best.plate)) ?
                `<div>${renderPlate(best.final_plate || best.plate.value)}</div>` + (best.plate ? `<div class="mt-1">${winnerBadgeHtml(best.plate.engine_label)}</div>` : '') :
                mutedDash();
            B.local.innerHTML = formatAttrBestCell(best ? best.local_text : null);
            B.region.innerHTML = formatAttrBestCell(best ? best.region : null);
            B.platecolor.innerHTML = formatAttrBestCell(best ? best.plate_color : null);
            B.conf.innerHTML = (best && best.plate) ?
                `<div>${confidenceBadge(best.plate.conf)}</div><div class="mt-1">${winnerBadgeHtml(best.plate.engine_label)}</div>` :
                mutedDash();

            let bestCharList = [];
            if (best && Array.isArray(best.characters) && best.characters.length > 0) {
                bestCharList = best.characters;
            } else if (record && Array.isArray(record.best_characters_json) && record.best_characters_json.length > 0) {
                bestCharList = record.best_characters_json;
            }
            B.chars.innerHTML = bestCharList.length > 0 ?
                bestCharList.map(c => {
                    const exactC = toExactPct(c.confidence);
                    const eng = c.engine_label || c.engine || (c.engine_key ? c.engine_key.toUpperCase() : 'Best');
                    return `<span class="badge badge-light-${confTone(exactC)} fw-semibold m-1 font-monospace">${escapeHtml(c.character)} ${escapeHtml(exactC)}% · ${escapeHtml(eng)}</span>`;
                }).join('') :
                mutedDash();

            B.make.innerHTML = formatAttrBestCell(best ? best.make : null);
            B.model.innerHTML = formatAttrBestCell(best ? best.model : null);
            B.generation.innerHTML = formatAttrBestCell(best ? best.generation : null);
            B.vehicle.innerHTML = formatAttrBestCell(best ? best.vehicle : null);
            B.category.innerHTML = formatAttrBestCell(best ? best.category : null);
            B.color.innerHTML = formatAttrBestCell(best ? best.color : null);
            B.viewport.innerHTML = formatAttrBestCell(best ? (best.orientation || best.viewport) : null);
            B.quality.innerHTML = formatAttrBestCell(best ? best.quality : null);
            B.bbox.innerHTML = formatAttrBestCell(best ? best.bbox : null);
            B.tags.innerHTML = formatAttrBestCell(best ? best.tags : null);
            B.time.innerHTML = (best && best.fastest) ?
                `<div>${latencyCell(best.fastest.time_ms)}</div><div class="mt-1">${winnerBadgeHtml(best.fastest.engine_label)}</div>` :
                mutedDash();
        }

        // ---------- Current logic (95% threshold, mismatch check, min 2 engines) ----------
        function getOrComputeCurrentLogic(rec) {
            if (rec && rec.current_logic_json && rec.current_logic_json.status) {
                return rec.current_logic_json;
            }

            const engineDefs = ENGINE_DEFS;
            const evaluations = {};
            const qualifiedList = {};

            engineDefs.forEach(eng => {
                const plate = rec ? rec[`${eng.key}_plate`] : null;
                const isSuccess = rec && rec[`${eng.key}_success`] && plate && plate !== 'N/A' && plate !== '-';

                if (!isSuccess) {
                    evaluations[eng.key] = {
                        engine_key: eng.key,
                        engine_label: eng.label,
                        plate: null,
                        overall_conf: null,
                        avg_conf: null,
                        avg_score: 0.0,
                        is_qualified: false,
                        status: 'rejected',
                        reason: 'No Plate / Failed',
                        failing_items: ['No Plate / Failed'],
                        characters: [],
                    };
                    return;
                }

                const overallConfStr = toExactPct(rec[`${eng.key}_confidence`]);
                const overallConfNum = parseFloat(overallConfStr) || 0.0;
                const rawChars = rec[`${eng.key}_characters_json`] || [];
                const failingItems = [];
                let charConfSum = 0.0;
                let charCount = 0;
                const cleanChars = [];

                if (overallConfNum < 95.0) failingItems.push(`Overall OCR Conf ${overallConfStr}% < 95%`);

                if (Array.isArray(rawChars)) {
                    rawChars.forEach(c => {
                        if (!c) return;
                        const chVal = String(c.character || '').trim();
                        if (!chVal) return;
                        const chConfStr = toExactPct(c.confidence ?? c.conf ?? 0);
                        const chConfNum = parseFloat(chConfStr) || 0.0;
                        charConfSum += chConfNum;
                        charCount++;
                        cleanChars.push({
                            character: chVal,
                            confidence: chConfStr,
                            score: chConfNum
                        });
                        if (chConfNum < 95.0) failingItems.push(`'${chVal}' (${chConfStr}% < 95%)`);
                    });
                }

                const avgScore = charCount > 0 ? (charConfSum / charCount) : overallConfNum;
                const isQualified = failingItems.length === 0;
                const evalObj = {
                    engine_key: eng.key,
                    engine_label: eng.label,
                    plate,
                    overall_conf: overallConfStr,
                    avg_conf: toExactPct(avgScore),
                    avg_score: avgScore,
                    is_qualified: isQualified,
                    status: isQualified ? 'qualified' : 'rejected',
                    reason: isQualified ? 'All values >= 95%' : failingItems.join(', '),
                    failing_items: failingItems,
                    characters: cleanChars,
                };
                evaluations[eng.key] = evalObj;
                if (isQualified) qualifiedList[eng.key] = evalObj;
            });

            let candidate = null;
            const qualifiedKeys = Object.keys(qualifiedList);
            if (qualifiedKeys.length > 0) {
                const sorted = [...qualifiedKeys].sort((a, b) => (qualifiedList[b].avg_score || 0) - (qualifiedList[a].avg_score || 0));
                candidate = qualifiedList[sorted[0]];
            } else {
                const detected = Object.values(evaluations).filter(e => e.plate && e.reason !== 'No Plate / Failed');
                if (detected.length > 0) {
                    detected.sort((a, b) => (b.avg_score || 0) - (a.avg_score || 0));
                    candidate = detected[0];
                } else {
                    candidate = Object.values(evaluations)[0] || null;
                }
            }

            const base = {
                selected_engine: null,
                selected_engine_label: null,
                selected_plate: null,
                selected_confidence: null,
                winner: null,
                is_fallback: false,
                evaluations,
            };

            // 1. Mismatch between any detected plates
            const normalizedPlates = {};
            const mismatchDetails = [];
            engineDefs.forEach(eng => {
                const plate = rec ? rec[`${eng.key}_plate`] : null;
                const isSuccess = rec && rec[`${eng.key}_success`] && plate && plate !== 'N/A' && plate !== '-';
                if (isSuccess) {
                    normalizedPlates[eng.key] = String(plate).replace(/[^A-Za-z0-9\u0600-\u06FF]/g, '').toUpperCase().trim();
                    mismatchDetails.push(`${eng.label}: '${plate}'`);
                }
            });

            const candidateFields = (c) => ({
                candidate_engine: c ? c.engine_key : null,
                candidate_engine_label: c ? c.engine_label : null,
                candidate_plate: c ? c.plate : null,
                candidate_confidence: c ? c.avg_conf : null,
                candidate: c,
            });

            if (new Set(Object.values(normalizedPlates)).size > 1) {
                return {
                    ...base,
                    ...candidateFields(candidate),
                    status: 'mismatch',
                    mismatch_reason: 'Character mismatch: ' + mismatchDetails.join(' vs '),
                    qualified_engines: qualifiedKeys,
                };
            }

            // 2. Minimum 2 qualified engines
            if (qualifiedKeys.length === 1) {
                const single = qualifiedList[qualifiedKeys[0]];
                return {
                    ...base,
                    ...candidateFields(single),
                    status: 'mismatch',
                    mismatch_reason: `Minimum 2 OCR engines required (only ${single.engine_label} qualified >= 95%)`,
                    qualified_engines: qualifiedKeys,
                };
            }

            // 3. Winner = highest average
            if (qualifiedKeys.length >= 2) {
                qualifiedKeys.sort((a, b) => qualifiedList[b].avg_score - qualifiedList[a].avg_score);
                const winner = qualifiedList[qualifiedKeys[0]];
                return {
                    ...base,
                    ...candidateFields(winner),
                    status: 'qualified',
                    selected_engine: winner.engine_key,
                    selected_engine_label: winner.engine_label,
                    selected_plate: winner.plate,
                    selected_confidence: winner.avg_conf,
                    winner,
                    qualified_engines: qualifiedKeys,
                };
            }

            // 4. None qualified
            return {
                ...base,
                ...candidateFields(candidate),
                status: 'none_qualified',
                qualified_engines: [],
            };
        }

        const clTargetOf = (cl) => cl ? (cl.winner || cl.candidate || (cl.evaluations ? (Object.values(cl.evaluations).find(e => e.plate) || Object.values(cl.evaluations)[0]) : null)) : null;

        // Engine status list (text only)
        function enginePillsHtml(cl) {
            if (!cl || !cl.evaluations) return '';
            return Object.values(cl.evaluations).map(ev => {
                if (ev.status === 'excluded') {
                    return `<span class="badge badge-light text-gray-500 fs-9">${escapeHtml(ev.engine_label)} · excluded</span>`;
                }
                if (cl.winner && ev.engine_key === cl.winner.engine_key) {
                    return `<span class="badge badge-success fs-9">${escapeHtml(ev.engine_label)} · selected ${escapeHtml(ev.avg_conf)}%</span>`;
                }
                if (ev.is_qualified) {
                    return `<span class="badge badge-light-success fs-9">${escapeHtml(ev.engine_label)} · ${escapeHtml(ev.avg_conf)}%</span>`;
                }
                if (ev.reason === 'No Plate / Failed' || !ev.plate) {
                    return `<span class="badge badge-light text-gray-500 fs-9">${escapeHtml(ev.engine_label)} · failed</span>`;
                }
                return `<span class="badge badge-light-danger fs-9" title="${escapeHtml(ev.reason)}">${escapeHtml(ev.engine_label)} · below 95%</span>`;
            }).join(' ');
        }

        function renderCurrentLogic(record) {
            const cl = getOrComputeCurrentLogic(record);
            if (!cl) return;

            const target = cl.winner || cl.candidate || (cl.evaluations ? Object.values(cl.evaluations).find(e => e.plate) : null);
            const engKey = target ? target.engine_key : null;
            const srcCells = engKey ? C[engKey] : null;
            const T = C.current;
            const labelBadge = target ? (cl.winner ? winnerBadgeHtml(target.engine_label) : `<span class="text-gray-500 fs-9">ref ${escapeHtml(target.engine_label)}</span>`) : '';

            // Plate
            if (cl.winner) {
                T.plate.innerHTML = `
                    <div class="d-flex flex-column gap-2">
                        <div>${renderPlate(target.plate)}</div>
                        <div class="d-flex flex-wrap gap-1">
                            <span class="badge badge-light-success fs-9">${cl.is_fallback ? 'Selected (below 95%)' : 'Selected'}: ${escapeHtml(target.engine_label)}</span>
                            <span class="badge badge-light fs-9">${escapeHtml(target.avg_conf)}% avg</span>
                        </div>
                        <div class="d-flex flex-wrap gap-1">${enginePillsHtml(cl)}</div>
                    </div>`;
            } else {
                const isMismatch = cl.status === 'mismatch';
                const title = isMismatch ? 'No decision — mismatch' : 'No decision — all below 95%';
                const reason = isMismatch ? (cl.mismatch_reason || 'Qualified engines disagree on characters') : 'All engines rejected (confidence or character below 95%)';
                T.plate.innerHTML = `
                    <div class="d-flex flex-column gap-2">
                        <span class="badge badge-light-danger fs-8 fw-semibold">${title}</span>
                        <span class="text-gray-600 fs-9">${escapeHtml(reason)}</span>
                        ${target && target.plate ? `<div>${renderPlate(target.plate)}</div>
                        <div><span class="badge badge-light fs-9">Ref ${escapeHtml(target.engine_label)} · ${escapeHtml(target.avg_conf)}% avg</span></div>` : ''}
                        <div class="d-flex flex-wrap gap-1">${enginePillsHtml(cl)}</div>
                    </div>`;
            }

            // Confidence
            if (target) {
                const confVal = target.overall_conf || (record && engKey && record[`${engKey}_confidence`]);
                T.conf.innerHTML = `<div class="d-flex flex-wrap gap-1">${confidenceBadge(confVal)}<span class="badge badge-light fs-9">${escapeHtml(target.avg_conf)}% avg</span></div><div class="mt-1">${labelBadge}</div>`;
            } else {
                T.conf.innerHTML = mutedDash();
            }

            // Characters
            if (target) {
                const srcHtml = srcCells && srcCells.chars ? srcCells.chars.innerHTML.trim() : '';
                if (srcHtml && srcHtml !== 'N/A' && srcHtml !== '-') {
                    T.chars.innerHTML = `<div>${srcHtml}</div><div class="mt-1">${labelBadge}</div>`;
                } else {
                    let tChars = [];
                    if (Array.isArray(target.characters) && target.characters.length > 0) {
                        tChars = target.characters;
                    } else if (record && engKey && Array.isArray(record[`${engKey}_characters_json`])) {
                        tChars = record[`${engKey}_characters_json`];
                    }
                    T.chars.innerHTML = tChars.length > 0 ?
                        `<div>${tChars.map(c => charChip(c.character, c.confidence ?? c.conf ?? 0)).join('')}</div><div class="mt-1">${labelBadge}</div>` :
                        `<span class="text-gray-400">N/A</span><div class="mt-1">${labelBadge}</div>`;
                }
            } else {
                T.chars.innerHTML = mutedDash();
            }

            // Mirror other fields from the target engine column
            const mirror = (slot, rawField) => {
                const cell = T[slot];
                if (!cell) return;
                if (!target || !engKey) {
                    cell.innerHTML = mutedDash();
                    return;
                }
                const srcHtml = srcCells && srcCells[slot] ? srcCells[slot].innerHTML.trim() : '';
                if (srcHtml && srcHtml !== '-' && srcHtml !== 'N/A') {
                    cell.innerHTML = `<div>${srcHtml}</div><div class="mt-1">${labelBadge}</div>`;
                    return;
                }
                const fallbackVal = (record && record[`${engKey}_${rawField}`]) || (target.summary && target.summary[rawField]);
                cell.innerHTML = !isEmptyVal(fallbackVal) ?
                    `<div class="fw-semibold text-gray-900">${escapeHtml(fallbackVal)}</div><div class="mt-1">${labelBadge}</div>` :
                    `${mutedDash()}<div class="mt-1">${labelBadge}</div>`;
            };

            [
                ['local', 'local_text'],
                ['region', 'region'],
                ['platecolor', 'plate_color'],
                ['make', 'make'],
                ['model', 'model'],
                ['generation', 'generation'],
                ['vehicle', 'vehicle'],
                ['category', 'category'],
                ['color', 'color'],
                ['viewport', 'viewport'],
                ['quality', 'quality'],
                ['bbox', 'bbox'],
                ['tags', 'tags'],
                ['time', 'time_ms'],
            ].forEach(([slot, field]) => mirror(slot, field));
        }

        // ---------- Character breakdown ----------
        function renderCharacterBreakdown(record) {
            const tbody = $('char-breakdown-tbody');
            if (!tbody) return;

            const best = getOrComputeBestSummary(record);
            const cl = getOrComputeCurrentLogic(record);
            const clTarget = clTargetOf(cl);

            const engineConfigs = [{
                    key: 'best',
                    title: 'New Logic',
                    plate: best ? (best.final_plate || (best.plate ? best.plate.value : 'No plate')) : 'No plate',
                    list: best && Array.isArray(best.characters) ? best.characters : []
                },
                {
                    key: 'current_logic',
                    title: 'Current Logic' + (cl && cl.status === 'mismatch' ? ' · mismatch' : (cl && cl.winner ? ' · winner' : '')),
                    plate: clTarget ? (clTarget.plate || 'No plate') : 'No plate',
                    list: clTarget && Array.isArray(clTarget.characters) ? clTarget.characters : []
                },
                {
                    key: 'candid',
                    title: 'Candid',
                    plate: record.candid_plate,
                    list: record.candid_characters_json || []
                },
                {
                    key: 'eydea',
                    title: 'Eydea',
                    plate: record.eydea_plate,
                    list: record.eydea_characters_json || []
                },
                {
                    key: 'arh',
                    title: 'ARH',
                    plate: record.arh_plate,
                    list: record.arh_characters_json || []
                },
                {
                    key: 'parkonic',
                    title: 'Parkonic',
                    plate: record.parkonic_plate,
                    list: record.parkonic_characters_json || []
                },
            ];

            charEngineCardsGrid.innerHTML = engineConfigs.map(cfg => {
                const chars = Array.isArray(cfg.list) ? cfg.list : [];
                const keycaps = chars.length > 0 ?
                    `<div class="char-keycap-list mt-3">` + chars.map(c => {
                        const cVal = (c.confidence !== null && c.confidence !== undefined && c.confidence !== '') ? toExactPct(c.confidence) : '-';
                        return `<div class="char-keycap">
                            <span class="char-keycap-pos">${c.position || 1}</span>
                            <span class="char-keycap-letter">${escapeHtml(c.character)}</span>
                            <span class="char-keycap-conf text-${confTone(cVal)}">${escapeHtml(cVal)}%</span>
                        </div>`;
                    }).join('') + `</div>` :
                    `<div class="text-gray-500 fs-8 mt-3">No character splits</div>`;

                return `<div class="col-md-6">
                    <div class="char-engine-card">
                        <div class="d-flex justify-content-between align-items-center gap-2">
                            <span class="fw-semibold text-gray-800 fs-7">${cfg.title}</span>
                            <span class="text-gray-600 fs-7 font-monospace">${escapeHtml(cfg.plate || 'No plate')}</span>
                        </div>
                        ${keycaps}
                    </div>
                </div>`;
            }).join('');

            let charRows = [];
            if (record && Array.isArray(record.characters) && record.characters.length > 0) {
                charRows = record.characters;
            } else {
                engineConfigs.forEach(cfg => {
                    (Array.isArray(cfg.list) ? cfg.list : []).forEach(item => {
                        charRows.push({
                            engine: cfg.key,
                            plate_index: item.plate_index || 0,
                            char_position: item.position || 1,
                            character: item.character,
                            confidence: item.confidence,
                            raw_char_data: item.raw || item,
                        });
                    });
                });
            }

            charTotalCountBadge.textContent = charRows.length;

            if (charRows.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center text-gray-500 py-6">No character-level splits for this image.</td></tr>`;
                return;
            }

            const engineLabels = {
                candid: 'Candid',
                eydea: 'Eydea',
                arh: 'ARH',
                parkonic: 'Parkonic',
                best: 'New Logic',
                current_logic: 'Current Logic'
            };

            tbody.innerHTML = charRows.map(row => {
                const hasConf = row.confidence !== null && row.confidence !== undefined && row.confidence !== '';
                const rawStr = row.raw_char_data ? JSON.stringify(row.raw_char_data) : '-';
                return `<tr>
                    <td class="fw-semibold">${escapeHtml(engineLabels[row.engine] || row.engine)}</td>
                    <td>${(Number(row.plate_index) || 0) + 1}</td>
                    <td>${row.char_position}</td>
                    <td><span class="ocr-plate ocr-plate-sm">${escapeHtml(row.character)}</span></td>
                    <td>${hasConf ? confidenceBadge(row.confidence) : '-'}</td>
                    <td><code class="fs-9 text-gray-600 bg-transparent p-0">${escapeHtml(rawStr)}</code></td>
                </tr>`;
            }).join('');
        }

        // ---------- Engine columns ----------
        function displayComparativeResults(results, engine, record = null) {
            const partialFailures = [];
            const isAll = engine === 'all' || engine === 'both';
            const showCandid = engine === 'candid' || isAll;
            const showEydea = engine === 'eydea' || isAll;
            const showParkonic = engine === 'parkonic' || isAll;
            const showAnpr2 = engine === 'anpr2' || isAll;

            thCandid.classList.toggle('d-none', !showCandid);
            thEydea.classList.toggle('d-none', !showEydea);
            thParkonic.classList.toggle('d-none', !showParkonic);
            thAnpr2.classList.toggle('d-none', !showAnpr2);
            tabCandid.classList.toggle('d-none', !showCandid);
            tabEydea.classList.toggle('d-none', !showEydea);
            tabParkonic.classList.toggle('d-none', !showParkonic);
            tabAnpr2.classList.toggle('d-none', !showAnpr2);

            document.querySelectorAll('#comparison-table tr').forEach(tr => {
                if (tr.cells[1]) tr.cells[1].classList.toggle('d-none', !showCandid);
                if (tr.cells[2]) tr.cells[2].classList.toggle('d-none', !showEydea);
                if (tr.cells[3]) tr.cells[3].classList.toggle('d-none', !showParkonic);
                if (tr.cells[4]) tr.cells[4].classList.toggle('d-none', !showAnpr2);
            });

            const clearCells = (cells, except = []) => {
                Object.entries(cells).forEach(([k, c]) => {
                    if (c && !except.includes(k)) c.textContent = '-';
                });
            };

            const formatCorners = (corners) => {
                if (!Array.isArray(corners) || corners.length === 0) return null;
                const xs = [],
                    ys = [];
                corners.forEach(pt => {
                    if (pt && pt.x !== undefined && pt.y !== undefined) {
                        xs.push(Number(pt.x));
                        ys.push(Number(pt.y));
                    } else if (Array.isArray(pt) && pt.length >= 2) {
                        xs.push(Number(pt[0]));
                        ys.push(Number(pt[1]));
                    }
                });
                if (!xs.length) return null;
                const minX = Math.min(...xs),
                    maxX = Math.max(...xs),
                    minY = Math.min(...ys),
                    maxY = Math.max(...ys);
                return `[${minX},${minY} → ${maxX},${maxY}] (${maxX - minX}×${maxY - minY}px)`;
            };

            const activeTagsHtml = (tags) => {
                if (!tags) return '';
                const active = [];
                for (const [tagKey, tagData] of Object.entries(tags)) {
                    if (tagData && (tagData.value === 'yes' || tagData.value === true)) {
                        const label = tagKey.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                        active.push(`<span class="badge badge-light m-1 fs-8">${formatWithConf(label, tagData.confidence ?? tagData.conf)}</span>`);
                    }
                }
                return active.join('');
            };

            const noneTag = '<span class="text-gray-400 fs-8">None</span>';
            const divider = '<div class="vr mx-2 align-self-stretch"></div>';
            const row = (htmls) => `<div class="d-flex flex-row align-items-center flex-wrap gap-2">${htmls.map(h => `<div>${h}</div>`).join(divider)}</div>`;
            const titleCase = (s) => s.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');

            // 1. Candid
            if (results && results.candid) {
                const candid = results.candid;
                const X = C.candid;
                candidJsonCode.textContent = JSON.stringify(candid.data || candid, null, 2);

                if (candid.success && candid.data && candid.data.plates && candid.data.plates.length > 0) {
                    const plate = candid.data.plates[0];
                    X.plate.innerHTML = renderPlate(plate.text);

                    if (record && record.candid_local_text) {
                        X.local.textContent = record.candid_local_text;
                    } else {
                        const parts = [];
                        if (plate.local_text) parts.push(plate.local_text);
                        if (Array.isArray(plate.text_groups) && plate.text_groups.length) parts.push(`Groups: [${plate.text_groups.join(' | ')}]`);
                        X.local.textContent = parts.length ? parts.join(' | ') : 'N/A';
                    }

                    let regionText = '';
                    if (plate.category) {
                        const cCity = (plate.category.city || '').trim();
                        const cCountry = (plate.category.country || '').trim();
                        regionText += (cCity && cCity.toLowerCase() === cCountry.toLowerCase()) ? cCity : `${cCity} ${cCountry}`.trim();
                        if (plate.category.description) regionText += ` (${plate.category.description})`;
                    }
                    const catConf = plate.category_confidence ?? (plate.category ? plate.category.confidence : null);
                    X.region.innerHTML = regionText.trim() ? formatWithConf(regionText.trim(), catConf) : (record && record.candid_region ? escapeHtml(record.candid_region) : 'N/A');

                    if (record && record.candid_plate_color) {
                        X.platecolor.textContent = record.candid_plate_color;
                    } else {
                        const parts = [];
                        if (plate.category && (plate.category.colour || plate.category.color)) parts.push(plate.category.colour || plate.category.color);
                        if (plate.category && plate.category.size) parts.push(`Size: ${plate.category.size}`);
                        X.platecolor.textContent = parts.length ? parts.join(' | ') : 'N/A';
                    }

                    X.conf.innerHTML = confidenceBadge(plate.confidence ?? plate.confidance ?? 0);

                    const charsList = plate.characters || plate.plate_detail || [];
                    X.chars.innerHTML = charsList.length ? charsList.map(c => charChip(c.character, c.confidence ?? c.conf ?? 0)).join('') : 'N/A';

                    if (plate.vehicle) {
                        const v = plate.vehicle;
                        const makerStr = formatWithConf(v.maker || v.make, v.maker_confidence ?? v.make_confidence ?? v.make_conf ?? v.maker_conf ?? v.mmr_confidence);
                        const modelStr = formatWithConf(v.model, v.model_confidence ?? v.model_conf ?? v.mmr_confidence);
                        X.make.innerHTML = makerStr !== 'N/A' ? makerStr : (record && record.candid_make ? escapeHtml(record.candid_make) : 'N/A');
                        X.model.innerHTML = modelStr !== 'N/A' ? modelStr : (record && record.candid_model ? escapeHtml(record.candid_model) : 'N/A');
                        X.generation.innerHTML = formatWithConf(v.generation || v.sub_model || v.year, v.generation_confidence ?? v.year_confidence);
                        X.vehicle.innerHTML = [makerStr, modelStr].filter(s => s && s !== 'N/A').join(' ') || 'N/A';
                        X.category.innerHTML = formatWithConf(v.type, v.type_confidence ?? v.type_conf);
                        X.color.innerHTML = formatWithConf(v.color, v.color_confidence ?? v.color_conf);
                    } else {
                        X.make.textContent = (record && record.candid_make) || 'N/A';
                        X.model.textContent = (record && record.candid_model) || 'N/A';
                        X.generation.textContent = (record && record.candid_generation) || 'N/A';
                        X.vehicle.textContent = (record && record.candid_vehicle) || 'N/A';
                        X.category.textContent = (record && record.candid_category) || 'N/A';
                        X.color.textContent = (record && record.candid_color) || 'N/A';
                    }

                    if (plate.vehicle && (plate.vehicle.viewpoint || plate.vehicle.view || plate.vehicle.orientation)) {
                        const v = plate.vehicle;
                        const viewConf = v.viewpoint_confidence ?? v.view_confidence ?? v.viewpoint_conf ?? v.view_conf ?? v.orientation_confidence ?? v.orientation_conf;
                        X.viewport.innerHTML = formatWithConf(String(v.viewpoint || v.view || v.orientation).toUpperCase(), viewConf);
                    } else if (plate.viewpoint) {
                        X.viewport.innerHTML = formatWithConf(plate.viewpoint.toUpperCase(), plate.viewpoint_conf ?? plate.viewpoint_confidence);
                    } else {
                        X.viewport.textContent = (record && record.candid_viewport) || 'N/A';
                    }

                    if (record && record.candid_bbox) {
                        X.bbox.textContent = record.candid_bbox;
                    } else {
                        const parts = [];
                        const pBox = formatCorners(plate.corners);
                        if (pBox) parts.push(`Plate: ${pBox}`);
                        if (plate.vehicle && plate.vehicle.corners) {
                            const vBox = formatCorners(plate.vehicle.corners);
                            if (vBox) parts.push(`Vehicle: ${vBox}`);
                        }
                        X.bbox.textContent = parts.length ? parts.join(' | ') : 'N/A';
                    }

                    X.tags.innerHTML = (plate.vehicle && activeTagsHtml(plate.vehicle.tags)) || noneTag;
                    X.quality.textContent = (record && record.candid_quality) || 'N/A';
                    X.time.innerHTML = latencyCell(candid.time_ms);
                } else if (candid.success) {
                    clearCells(X, ['plate', 'time']);
                    X.plate.innerHTML = noPlateCell();
                    X.time.innerHTML = candid.time_ms ? latencyCell(candid.time_ms) : '-';
                } else {
                    clearCells(X, ['plate']);
                    X.plate.innerHTML = failedCell();
                    partialFailures.push(`Candid: ${candid.message || 'Plate detection returned 0 results'}`);
                }
            }

            // 2. Eydea
            if (results && results.eydea) {
                const eydea = results.eydea;
                const X = C.eydea;
                eydeaJsonCode.textContent = JSON.stringify(eydea.data || eydea, null, 2);

                if (eydea.success && eydea.data) {
                    if (eydea.data.anpr && eydea.data.anpr.length > 0) {
                        const H = {};
                        FIELDS.forEach(f => H[f] = []);

                        eydea.data.anpr.forEach((anpr, idx) => {
                            const mmr = (Array.isArray(eydea.data.mmr_classification) ?
                                eydea.data.mmr_classification[idx] :
                                (idx === 0 ? eydea.data.mmr_classification : null)) || {};

                            const compiledText = (anpr.lines && anpr.lines.length > 0) ? anpr.lines.map(l => l.text).join(' ') : 'N/A';
                            H.plate.push(renderPlate(compiledText));

                            const linesCount = anpr.num_lines ?? (anpr.lines ? anpr.lines.length : 0);
                            H.local.push(linesCount > 1 ? `Lines (${linesCount}): ${escapeHtml(compiledText)}` : 'N/A');

                            H.region.push(formatWithConf(String(anpr.plate_type || 'N/A').replace(/^UAE-/i, ''), anpr.plate_type_confidence));

                            const pCol = [];
                            if (anpr.dimensions_mm && anpr.dimensions_mm.width && anpr.dimensions_mm.height) {
                                pCol.push(formatWithConf(`Dim: ${anpr.dimensions_mm.width}×${anpr.dimensions_mm.height} mm`, anpr.dimensions_mm.confidence));
                            }
                            if (anpr.num_lines) pCol.push(`${anpr.num_lines} Line${Number(anpr.num_lines) > 1 ? 's' : ''}`);
                            H.platecolor.push(pCol.length ? pCol.join(' | ') : 'N/A');

                            H.conf.push(confidenceBadge(anpr.confidence ?? 0));

                            const allChars = [];
                            (anpr.lines || []).forEach(l => {
                                if (l.characters_confidences && l.characters_confidences.length > 0) {
                                    l.characters_confidences.forEach(cc => allChars.push({
                                        character: cc.character,
                                        confidence: cc.confidence ?? 0
                                    }));
                                } else {
                                    for (const ch of (l.text || '')) {
                                        if (ch.trim() !== '') allChars.push({
                                            character: ch,
                                            confidence: l.confidence ?? 0
                                        });
                                    }
                                }
                            });
                            H.chars.push(allChars.length ? allChars.map(c => charChip(c.character, c.confidence)).join('') : 'N/A');

                            const makeStr = formatWithConf(mmr.make, mmr.make_confidence);
                            const modelStr = formatWithConf(mmr.model, mmr.model_confidence);
                            H.make.push(makeStr || 'N/A');
                            H.model.push(modelStr || 'N/A');

                            const genText = [mmr.generation, mmr.variation].filter(Boolean).join(' ').trim();
                            H.generation.push(genText ? formatWithConf(genText, mmr.generation_confidence ?? mmr.variation_confidence) : 'N/A');
                            H.vehicle.push([makeStr, modelStr].filter(s => s && s !== 'N/A').join(' ') || 'N/A');
                            H.category.push(formatWithConf(mmr.category, mmr.category_confidence));
                            H.color.push(formatWithConf(mmr.color, mmr.color_confidence));
                            H.viewport.push(mmr.view ? formatWithConf(mmr.view.toUpperCase(), mmr.view_confidence) : 'N/A');

                            const firstLineConf = (anpr.lines && anpr.lines[0] && anpr.lines[0].confidence !== undefined) ? anpr.lines[0].confidence : null;
                            H.quality.push(firstLineConf !== null ? `Line OCR: ${toExactPct(firstLineConf)}%` : 'N/A');

                            if (anpr.bbox && anpr.bbox.x !== undefined) {
                                const bx = anpr.bbox;
                                H.bbox.push(`Plate: [${bx.x},${bx.y} → ${Number(bx.x) + Number(bx.width || 0)},${Number(bx.y) + Number(bx.height || 0)}] (${bx.width || 0}×${bx.height || 0}px)`);
                            } else {
                                H.bbox.push('N/A');
                            }

                            H.tags.push(activeTagsHtml(mmr.tags) || noneTag);
                        });

                        X.plate.innerHTML = `<div class="d-flex flex-row align-items-center flex-wrap gap-2">${H.plate.join(divider)}</div>`;
                        X.chars.innerHTML = H.chars.join('<div class="separator separator-dashed my-2"></div>');
                        ['local', 'region', 'platecolor', 'conf', 'make', 'model', 'generation', 'vehicle', 'category', 'color', 'viewport', 'quality', 'bbox', 'tags']
                        .forEach(f => {
                            if (X[f]) X[f].innerHTML = row(H[f]);
                        });
                        X.time.innerHTML = latencyCell(eydea.time_ms);
                    } else {
                        clearCells(X, ['plate']);
                        X.plate.innerHTML = noPlateCell();
                    }
                } else {
                    clearCells(X, ['plate']);
                    X.plate.innerHTML = failedCell();
                    partialFailures.push(`Eydea: ${eydea.message || 'Service request returned an error'}`);
                }
            }

            // 3. ARH (parkonic key)
            if (results && results.parkonic) {
                const parkonic = results.parkonic;
                const X = C.arh;
                let data = parkonic.data;
                if (typeof data === 'string') {
                    try {
                        data = JSON.parse(data);
                    } catch (e) {}
                }
                parkonicJsonCode.textContent = JSON.stringify(data || parkonic, null, 2);

                if (parkonic.success && data && (data.text || (data.plate_ && data.plate_.length > 0))) {
                    const plate = (data.plate_ && data.plate_.length > 0) ? data.plate_[0] : data;
                    X.plate.innerHTML = renderPlate(plate.text, plate.category || '');

                    let regionText = plate.cityName || '';
                    if (plate.platetype) regionText += ` (${plate.platetype})`;
                    X.region.textContent = regionText.trim() || 'N/A';

                    if (plate.backcolor && plate.textcolor) {
                        X.platecolor.textContent = `Color: ${plate.textcolor} on ${plate.backcolor}`;
                    } else if (plate.backcolor) {
                        X.platecolor.textContent = `Color: ${plate.backcolor}`;
                    } else {
                        X.platecolor.textContent = (record && record.arh_plate_color) || 'N/A';
                    }

                    X.conf.innerHTML = confidenceBadge(plate.confidance ?? plate.confidence ?? 0);

                    const charsList = plate.plate_detail || plate.characters || [];
                    X.chars.innerHTML = charsList.length ? charsList.map(c => charChip(c.character, c.conf ?? c.confidence ?? 0)).join('') : 'N/A';

                    const nonAscii = charsList.map(c => c.character || '').filter(ch => /[^\x00-\x7F]/.test(ch));
                    X.local.textContent = nonAscii.length ? nonAscii.join(' ') : ((record && record.arh_local_text) || 'N/A');

                    const makeStr = formatWithConf(plate.make || plate.maker, plate.mmr_make_conf);
                    const modelStr = formatWithConf(plate.model, plate.mmr_model_conf);
                    X.make.innerHTML = makeStr !== 'N/A' ? makeStr : ((record && escapeHtml(record.arh_make)) || 'N/A');
                    X.model.innerHTML = modelStr !== 'N/A' ? modelStr : ((record && escapeHtml(record.arh_model)) || 'N/A');
                    X.generation.innerHTML = formatWithConf(plate.sub_model, plate.mmr_submodel_conf);
                    X.vehicle.innerHTML = [makeStr, modelStr].filter(s => s && s !== 'N/A').join(' ') || 'N/A';
                    X.category.innerHTML = formatWithConf(plate.mmr_category || plate.bodytype, plate.mmr_category_conf);

                    let colorHtml = formatWithConf(plate.mmr_color || plate.backcolor || '', plate.mmr_color_conf);
                    if (plate.backcolor && plate.textcolor) {
                        colorHtml += `<div class="text-gray-500 fs-8">Plate: ${escapeHtml(plate.textcolor)} on ${escapeHtml(plate.backcolor)}</div>`;
                    }
                    X.color.innerHTML = colorHtml || 'N/A';
                    X.viewport.innerHTML = plate.viewpoint ? formatWithConf(plate.viewpoint.toUpperCase(), plate.viewpoint_conf) : 'N/A';
                    X.tags.innerHTML = activeTagsHtml(plate.tags) || noneTag;
                    X.quality.textContent = (plate.plate_conf !== undefined && plate.plate_conf !== null && plate.plate_conf !== '') ?
                        `Plate Conf: ${toExactPct(plate.plate_conf)}%` :
                        ((record && record.arh_quality) || 'N/A');

                    if (plate.x1 !== undefined && plate.y1 !== undefined) {
                        const xs = [plate.x1, plate.x2, plate.x3, plate.x4].map(v => Number(v || 0));
                        const ys = [plate.y1, plate.y2, plate.y3, plate.y4].map(v => Number(v || 0));
                        const minX = Math.min(...xs),
                            maxX = Math.max(...xs),
                            minY = Math.min(...ys),
                            maxY = Math.max(...ys);
                        X.bbox.textContent = `Plate: [${minX},${minY} → ${maxX},${maxY}] (${maxX - minX}×${maxY - minY}px)`;
                    } else {
                        X.bbox.textContent = (record && record.arh_bbox) || 'N/A';
                    }

                    X.time.innerHTML = latencyCell(data.Time_ms ?? data.time_ms ?? parkonic.time_ms);
                } else {
                    clearCells(X, ['plate']);
                    X.plate.innerHTML = failedCell();
                    partialFailures.push(`ARH: ${parkonic.message || 'Plate detection returned 0 results'}`);
                }
            }

            // 4. Parkonic (anpr2 key)
            if (results && results.anpr2) {
                const anpr2 = results.anpr2;
                const X = C.parkonic;
                let data = anpr2.data;
                if (typeof data === 'string') {
                    try {
                        data = JSON.parse(data);
                    } catch (e) {}
                }
                anpr2JsonCode.textContent = JSON.stringify(data || anpr2, null, 2);

                const hasVehicles = data && data.vehicles && data.vehicles.length > 0;
                const hasPlates = data && (data.text || (data.plate_ && data.plate_.length > 0));

                if (anpr2.success && data && (hasVehicles || hasPlates)) {
                    if (hasVehicles) {
                        const H = {};
                        FIELDS.forEach(f => H[f] = []);

                        data.vehicles.forEach(vehicle => {
                            const ocr = vehicle.ocr || {};
                            H.plate.push(renderPlate(ocr.number, ocr.category || ''));
                            H.local.push(ocr.category ? `Code: ${escapeHtml(ocr.category)}` : 'N/A');

                            const regionParts = [];
                            if (vehicle.city && vehicle.city.type) {
                                regionParts.push(formatWithConf(titleCase(vehicle.city.type), vehicle.city.confidence));
                            }
                            if (vehicle.plate_type && vehicle.plate_type.type) {
                                const plateType = titleCase(vehicle.plate_type.type);
                                regionParts.push(formatWithConf(plateType, vehicle.plate_type.confidence));
                                H.platecolor.push(formatWithConf('Type: ' + plateType, vehicle.plate_type.confidence));
                            } else {
                                H.platecolor.push('N/A');
                            }
                            H.region.push(regionParts.length ? regionParts.join(' / ') : 'N/A');

                            H.conf.push(confidenceBadge(ocr.confidence ?? 0));
                            const charsList = ocr.characters || [];
                            H.chars.push(charsList.length ? charsList.map(c => charChip(c.character, c.confidence ?? c.conf ?? 0)).join('') : 'N/A');

                            if (vehicle.mmr) {
                                const makeStr = formatWithConf(vehicle.mmr.make, vehicle.mmr.make_confidence);
                                const modelStr = formatWithConf(vehicle.mmr.model, vehicle.mmr.model_confidence);
                                H.make.push(makeStr || 'N/A');
                                H.model.push(modelStr || 'N/A');
                                H.generation.push(formatWithConf(vehicle.mmr.generation || vehicle.mmr.sub_model || vehicle.mmr.year, vehicle.mmr.generation_confidence));
                                H.vehicle.push([makeStr, modelStr].filter(s => s && s !== 'N/A').join(' ') || 'N/A');
                            } else {
                                H.make.push('N/A');
                                H.model.push('N/A');
                                H.generation.push('N/A');
                                H.vehicle.push('N/A');
                            }

                            H.category.push(vehicle.vehicle_type ? formatWithConf(vehicle.vehicle_type.type, vehicle.vehicle_type.confidence) : 'N/A');
                            H.color.push((vehicle.mmr && vehicle.mmr.color) ? formatWithConf(vehicle.mmr.color, vehicle.mmr.color_confidence) : 'N/A');
                            H.viewport.push(vehicle.viewpoint ? formatWithConf(vehicle.viewpoint.type.toUpperCase(), vehicle.viewpoint.confidence) : 'N/A');
                            H.quality.push(vehicle.cropped_Quality ? formatWithConf('Cropped: ' + vehicle.cropped_Quality.type, vehicle.cropped_Quality.confidence) : 'N/A');

                            if (vehicle.coordinates && Array.isArray(vehicle.coordinates.points)) {
                                const pBox = formatCorners(vehicle.coordinates.points);
                                H.bbox.push(pBox ? `Plate: ${escapeHtml(pBox)}` : 'N/A');
                            } else {
                                H.bbox.push('N/A');
                            }
                        });

                        X.plate.innerHTML = `<div class="d-flex flex-row align-items-center flex-wrap gap-2">${H.plate.join(divider)}</div>`;
                        X.chars.innerHTML = H.chars.join('<div class="separator separator-dashed my-2"></div>');
                        ['local', 'region', 'platecolor', 'conf', 'make', 'model', 'generation', 'vehicle', 'category', 'color', 'viewport', 'quality', 'bbox']
                        .forEach(f => {
                            if (X[f]) X[f].innerHTML = row(H[f]);
                        });
                        X.tags.innerHTML = noneTag;
                        X.time.innerHTML = latencyCell(data.processing_time_ms ?? anpr2.time_ms);
                    }
                } else {
                    clearCells(X, ['plate']);
                    X.plate.innerHTML = failedCell();
                    partialFailures.push(`Parkonic: ${anpr2.message || 'Plate detection returned 0 results'}`);
                }
            }

            if (isAll && partialFailures.length > 0) {
                partialWarningText.innerHTML = `<span class="fw-semibold">Partial results:</span> ${escapeHtml(partialFailures.join(' | '))}`;
                partialWarning.classList.remove('d-none');
            } else {
                partialWarning.classList.add('d-none');
            }
        }

        // ---------- Hover preview ----------
        function showImageHoverPopover(e, rec) {
            if (!hoverPopoverEl || !rec || !rec.image_url) return;
            popoverImg.src = rec.image_url;
            popoverDbId.textContent = `DB #${rec.id}`;
            const fname = rec.stored_filename || rec.original_filename || '';
            popoverFilename.textContent = fname;
            popoverFilename.title = fname;

            const best = getOrComputeBestSummary(rec);
            const cl = getOrComputeCurrentLogic(rec);
            const bestPlate = (best && (best.final_plate || (best.plate && best.plate.value))) || '-';
            const clPlate = (cl && (cl.winner ? cl.winner.plate : (cl.candidate ? cl.candidate.plate : null))) || '-';
            popoverBestPlate.innerHTML = `Best <span class="fw-bold text-gray-900 ms-1">${escapeHtml(bestPlate)}</span>`;
            popoverCurrentPlate.innerHTML = `Current <span class="fw-bold text-gray-900 ms-1">${escapeHtml(clPlate)}</span>`;

            hoverPopoverEl.classList.remove('d-none');
            updateHoverPopoverPosition(e);
        }

        function updateHoverPopoverPosition(e) {
            if (!hoverPopoverEl || hoverPopoverEl.classList.contains('d-none')) return;
            const w = hoverPopoverEl.offsetWidth || 560;
            const h = hoverPopoverEl.offsetHeight || 420;
            const pad = 16;
            let left = e.clientX + 24;
            if (left + w > window.innerWidth - pad) left = e.clientX - w - 24;
            if (left < pad) left = pad;
            let top = e.clientY - h / 2;
            if (top + h > window.innerHeight - pad) top = window.innerHeight - h - pad;
            if (top < pad) top = pad;
            hoverPopoverEl.style.left = `${left}px`;
            hoverPopoverEl.style.top = `${top}px`;
        }

        function hideImageHoverPopover() {
            if (hoverPopoverEl) hoverPopoverEl.classList.add('d-none');
        }

        window.openRecordInspectorModal = function(recId) {
            hideImageHoverPopover();
            const rec = dbRecordsCache.find(r => Number(r.id) === Number(recId)) ||
                (activeInspectedRecord && Number(activeInspectedRecord.id) === Number(recId) ? activeInspectedRecord : null);
            if (rec) populateInspectorFromRecord(rec, null, true);
        };
        window.openDbImageModal = window.openRecordInspectorModal;

        imageFrameWrapper.addEventListener('click', () => {
            if (!activeInspectedRecord) return;
            const imgUrl = activeInspectedRecord.image_url || (activeInspectedRecord.stored_filename ? imageRouteTemplate.replace(':filename', encodeURIComponent(activeInspectedRecord.stored_filename)) : '');
            if (imgUrl) window.open(imgUrl, '_blank');
        });

        // ---------- Decisions ----------
        const isRejectVal = (v) => v === 'reject_all' || v === 'rejected';

        function updateModalAcceptLogicButtons(acceptedLogic) {
            const set = (btn, base, label, acceptedCls, acceptedLabel, isOn, anyOn) => {
                btn.className = `btn btn-sm ${isOn ? acceptedCls : base}${anyOn && !isOn ? ' opacity-50' : ''}`;
                btn.textContent = isOn ? acceptedLabel : label;
            };
            const anyOn = !!acceptedLogic && acceptedLogic !== 'none';
            set(modalBtnAcceptCurrentLogic, 'btn-light-primary', 'Current Logic', 'btn-success', 'Current Logic · Accepted', acceptedLogic === 'current_logic', anyOn);
            set(modalBtnAcceptBestPick, 'btn-light-warning', 'New Logic', 'btn-success', 'New Logic · Accepted', acceptedLogic === 'best_pick', anyOn);
            set(modalBtnRejectAll, 'btn-light-danger', 'Reject All', 'btn-danger', 'Rejected', isRejectVal(acceptedLogic), anyOn);
        }

        modalBtnAcceptCurrentLogic.addEventListener('click', (e) => {
            e.preventDefault();
            if (activeInspectedRecordId) window.setAcceptedLogic(activeInspectedRecordId, 'current_logic');
        });
        modalBtnAcceptBestPick.addEventListener('click', (e) => {
            e.preventDefault();
            if (activeInspectedRecordId) window.setAcceptedLogic(activeInspectedRecordId, 'best_pick');
        });
        modalBtnRejectAll.addEventListener('click', (e) => {
            e.preventDefault();
            if (activeInspectedRecordId) window.setAcceptedLogic(activeInspectedRecordId, 'reject_all');
        });
        modalBtnDeleteRecord.addEventListener('click', (e) => {
            e.preventDefault();
            if (activeInspectedRecordId) window.confirmSoftDeleteRecord(activeInspectedRecordId);
        });

        function updateLogicKpiStats(stats) {
            if (!stats) return;
            if (stats.total !== undefined) statDbTotal.textContent = stats.total;

            const currentCount = Number(stats.current_logic_count || 0);
            const bestCount = Number(stats.best_pick_count || 0);
            const fmt = (v) => v !== undefined ? Number(v).toFixed(1) : '0.0';
            const decided = stats.decided_picks !== undefined ? stats.decided_picks : (currentCount + bestCount);

            statCurrentLogicCount.textContent = currentCount;
            statCurrentLogicPct.textContent = `${fmt(stats.current_logic_pct)}%`;
            statCurrentLogicSub.textContent = `${currentCount} of ${decided} picks · ${fmt(stats.current_logic_pct_total)}% total`;

            statBestPickCount.textContent = bestCount;
            statBestPickPct.textContent = `${fmt(stats.best_pick_pct)}%`;
            statBestPickSub.textContent = `${bestCount} of ${decided} picks · ${fmt(stats.best_pick_pct_total)}% total`;
        }

        function updateComparisonTableAcceptedHeaders(acceptedLogic) {
            const thBest = $('th-best');
            const thCurrent = $('th-currentlogic');
            const tag = (txt, tone) => ` <span class="badge badge-light-${tone} fs-9 ms-1">${txt}</span>`;

            thBest.innerHTML = 'New Logic' + (acceptedLogic === 'best_pick' ? tag('Accepted', 'success') : (isRejectVal(acceptedLogic) ? tag('Rejected', 'danger') : ''));
            thCurrent.innerHTML = 'Current Logic' + (acceptedLogic === 'current_logic' ? tag('Accepted', 'success') : (isRejectVal(acceptedLogic) ? tag('Rejected', 'danger') : ''));
        }

        window.setAcceptedLogic = async function(recordId, logicKey) {
            if (!recordId) return;

            let targetRec = dbRecordsCache.find(r => Number(r.id) === Number(recordId));
            if (!targetRec && activeInspectedRecord && Number(activeInspectedRecord.id) === Number(recordId)) {
                targetRec = activeInspectedRecord;
            }

            const currentVal = targetRec ? targetRec.accepted_logic : null;
            const newVal = currentVal === logicKey ? 'none' : logicKey;
            const nextAccepted = newVal === 'none' ? null : newVal;

            const applyUi = (val) => {
                if (targetRec) targetRec.accepted_logic = val;
                if (activeInspectedRecord && Number(activeInspectedRecord.id) === Number(recordId)) {
                    activeInspectedRecord.accepted_logic = val;
                    updateComparisonTableAcceptedHeaders(val);
                    updateModalAcceptLogicButtons(val);
                }
                updateDbRowAcceptedUI(recordId, val);
            };
            applyUi(nextAccepted);

            try {
                const targetUrl = acceptLogicRouteTemplate.replace(':id', encodeURIComponent(recordId));
                const response = await fetch(targetUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        logic: newVal
                    })
                });
                const data = await response.json();
                if (!response.ok || !data.status) {
                    console.error('Failed to save logic decision:', data.error);
                    applyUi(currentVal);
                } else {
                    applyUi(data.accepted_logic);
                    if (data.stats) updateLogicKpiStats(data.stats);
                }
            } catch (err) {
                console.error('Error sending accept logic:', err);
                applyUi(currentVal);
            }
        };

        window.confirmSoftDeleteRecord = async function(recordId) {
            if (!recordId) return;

            const executeDelete = async () => {
                try {
                    const targetUrl = deleteRecordRouteTemplate.replace(':id', encodeURIComponent(recordId));
                    const response = await fetch(targetUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await response.json();
                    if (!response.ok || !data.status) {
                        const msg = data.error || 'Could not delete record.';
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Delete failed',
                                text: msg,
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                }
                            });
                        } else {
                            alert('Delete failed: ' + msg);
                        }
                        return;
                    }

                    if (activeInspectedRecordId && Number(activeInspectedRecordId) === Number(recordId)) {
                        activeInspectedRecord = null;
                        activeInspectedRecordId = null;
                        const m = getModal();
                        if (m) m.hide();
                    }

                    dbRecordsCache = dbRecordsCache.filter(r => Number(r.id) !== Number(recordId));
                    const row = document.querySelector(`#db-history-tbody tr[data-record-id="${recordId}"]`);
                    if (row) row.remove();

                    if (data.stats) updateLogicKpiStats(data.stats);
                    if (dbRecordsCache.length === 0) loadDatabaseRecords(currentDbPage > 1 ? currentDbPage - 1 : 1);

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted',
                            text: `Record #${recordId} was soft deleted.`,
                            timer: 1600,
                            showConfirmButton: false
                        });
                    }
                } catch (err) {
                    console.error('Delete error:', err);
                }
            };

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: `Delete record #${recordId}?`,
                    text: 'The record will be soft deleted and can be recovered later.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-light'
                    }
                }).then((r) => {
                    if (r.isConfirmed) executeDelete();
                });
            } else if (confirm(`Soft delete record #${recordId}?`)) {
                executeDelete();
            }
        };

        function updateDbRowAcceptedUI(recordId, acceptedLogic) {
            const row = document.querySelector(`#db-history-tbody tr[data-record-id="${recordId}"]`);
            const rec = dbRecordsCache.find(r => Number(r.id) === Number(recordId));
            if (!row || !rec) return;
            rec.accepted_logic = acceptedLogic;
            const d = row.querySelector('.decision-col-cell');
            const b = row.querySelector('td.best-col-cell');
            const c = row.querySelector('td.currentlogic-col-cell');
            if (d) d.innerHTML = formatDbDecisionCell(rec);
            if (b) b.innerHTML = formatDbBestPickCell(rec);
            if (c) c.innerHTML = formatDbCurrentLogicCell(rec);
        }

        // ---------- DB table cells ----------
        const formatDbDecisionCell = (rec) => {
            const accepted = rec.accepted_logic;
            const isBest = accepted === 'best_pick';
            const isCurrent = accepted === 'current_logic';
            const isReject = isRejectVal(accepted);
            const anyOn = isBest || isCurrent || isReject;

            const btn = (on, onCls, offCls, label, action, title) => `
                <button type="button" class="btn btn-sm py-1 px-3 fs-8 text-start ${on ? onCls : offCls}${anyOn && !on ? ' opacity-50' : ''}"
                    onclick="window.setAcceptedLogic(${rec.id}, '${action}')" title="${title}">${label}</button>`;

            let status = '<span class="badge badge-light-warning text-dark fs-9 fw-bold mt-1 px-2 py-1"><i class="ki-outline ki-time fs-9 me-1 text-warning"></i>Decision Pending</span>';
            if (isBest) status = '<span class="badge badge-light-success text-success fs-9 fw-semibold mt-1 px-2 py-1"><i class="ki-outline ki-check-circle fs-9 me-1"></i>Accepted New Logic</span>';
            if (isCurrent) status = '<span class="badge badge-light-success text-success fs-9 fw-semibold mt-1 px-2 py-1"><i class="ki-outline ki-check-circle fs-9 me-1"></i>Accepted Current Logic</span>';
            if (isReject) status = '<span class="badge badge-light-danger text-danger fs-9 fw-semibold mt-1 px-2 py-1"><i class="ki-outline ki-cross-circle fs-9 me-1"></i>Rejected Both</span>';

            return `
                <div class="d-flex flex-column gap-1" onclick="event.stopPropagation();">
                    ${btn(isCurrent, 'btn-success', 'btn-light-primary', 'Current Logic', 'current_logic', 'Accept Current Logic')}
                    ${btn(isBest, 'btn-success', 'btn-light-warning', 'New Logic', 'best_pick', 'Accept New Logic')}
                    ${btn(isReject, 'btn-danger', 'btn-light-danger', isReject ? 'Rejected' : 'Reject All', 'reject_all', 'Neither logic is correct')}
                    <button type="button" class="btn btn-sm btn-light btn-color-gray-600 py-1 px-3 fs-8 text-start" onclick="window.confirmSoftDeleteRecord(${rec.id})">Delete</button>
                    ${status}
                </div>`;
        };

        const formatDbBestPickCell = (rec) => {
            const best = getOrComputeBestSummary(rec);
            if (!best || (!best.final_plate && !best.plate)) {
                return `<span class="badge badge-light-danger fs-9">No plate</span>`;
            }

            const finalPlate = best.final_plate || (best.plate ? best.plate.value : '-');
            const regionHtml = (best.region && best.region.value) ?
                `<div class="fs-8 text-gray-700 mt-1">${escapeHtml(best.region.value)} <span class="text-success">${escapeHtml(toExactPct(best.region.conf))}%</span> <span class="text-gray-500">· ${escapeHtml(best.region.engine_label)}</span></div>` : '';

            const charsHtml = (Array.isArray(best.characters) && best.characters.length > 0) ?
                `<div class="d-flex flex-column gap-1 mt-2">` + best.characters.map(c => {
                    const exactC = toExactPct(c.confidence);
                    return `<div class="font-monospace fs-9"><span class="fw-bold text-gray-900">${escapeHtml(c.character)}</span> <span class="text-${confTone(exactC)}">${escapeHtml(exactC)}</span> <span class="text-gray-500">${escapeHtml(c.engine_label)}</span></div>`;
                }).join('') + `</div>` : '';

            const tag = (label, attr) => {
                if (!attr || !attr.value) return '';
                const items = (Array.isArray(attr.items) && attr.items.length) ? attr.items : [{
                    ...attr,
                    label
                }];
                return items.map(it => {
                    const exactC = toExactPct(it.conf);
                    return `<span class="badge badge-light fs-9 fw-normal text-gray-700"><span class="fw-semibold me-1">${escapeHtml(it.label || label)}</span>${escapeHtml(it.clean_value || it.value)}${exactC ? ` <span class="text-success ms-1">${escapeHtml(exactC)}%</span>` : ''}</span>`;
                }).join('');
            };

            const attrs = [
                tag('Make', best.make),
                tag('Model', best.model),
                tag('Gen', best.generation),
                tag('Type', best.category),
                tag('Color', best.color),
                tag('View', best.orientation || best.viewport),
                tag('Plate', best.plate_color),
                tag('Local', best.local_text),
                tag('Quality', best.quality),
            ].filter(Boolean);

            let statusHtml = '';
            if (rec.accepted_logic === 'best_pick') statusHtml = `<span class="badge badge-light-success fs-9 mt-2">Accepted</span>`;
            else if (isRejectVal(rec.accepted_logic)) statusHtml = `<span class="badge badge-light-danger fs-9 mt-2">Rejected</span>`;

            return `
                <div class="d-flex flex-column align-items-start">
                    <span class="ocr-plate ocr-plate-sm">${escapeHtml(finalPlate)}</span>
                    ${regionHtml}
                    ${charsHtml}
                    ${attrs.length ? `<div class="d-flex flex-wrap gap-1 mt-2">${attrs.join('')}</div>` : ''}
                    ${statusHtml}
                </div>`;
        };

        const metaBadgesFor = (get) => {
            const out = [];
            const add = (label, v, mono = false) => {
                if (isEmptyVal(v)) return;
                out.push(`<span class="badge badge-light fs-9 fw-normal text-gray-700${mono ? ' font-monospace' : ''}">${label ? `<span class="fw-semibold me-1">${label}</span>` : ''}${escapeHtml(v)}</span>`);
            };
            const make = get('make'),
                model = get('model');
            add('Local', get('local_text'));
            add('', get('region'));
            add('Plate', get('plate_color'));
            add('Make', make);
            add('Model', model);
            add('Gen', get('generation'));
            if (!make && !model) add('', get('vehicle'));
            add('Type', get('category'));
            add('Color', get('color'));
            add('View', get('viewport'));
            add('Q', get('quality'));
            add('', get('bbox'), true);
            add('Tags', get('tags'));
            return out.length ? `<div class="d-flex flex-wrap gap-1 mt-2">${out.join('')}</div>` : '';
        };

        const charPillsHtml = (list) => (Array.isArray(list) && list.length > 0) ?
            `<div class="mt-2">` + list.map(c => {
                const cVal = (c.confidence !== null && c.confidence !== undefined && c.confidence !== '') ? toExactPct(c.confidence) : '-';
                return `<span class="db-char-pill"><span class="fw-bold text-gray-900">${escapeHtml(c.character ?? '')}</span> <span class="text-${confTone(cVal)}">${escapeHtml(cVal)}%</span></span>`;
            }).join('') + `</div>` : '';

        const headLine = (plate, conf, timeMs) => {
            const confHtml = !isEmptyVal(conf) ? `<span class="badge badge-light-${confTone(conf)} fs-9">${escapeHtml(conf)}%</span>` : '';
            const timeHtml = !isEmptyVal(timeMs) ? `<span class="text-gray-500 fs-9">${escapeHtml(timeMs)} ms</span>` : '';
            return `<div class="d-flex align-items-center flex-wrap gap-2"><span class="ocr-plate ocr-plate-sm">${escapeHtml(plate || '-')}</span>${confHtml}${timeHtml}</div>`;
        };

        const formatDbCurrentLogicCell = (rec) => {
            const cl = getOrComputeCurrentLogic(rec);
            if (!cl) return '-';

            const w = clTargetOf(cl);
            const prefix = w ? w.engine_key : null;
            const getAttr = (f) => prefix ? ((rec && rec[`${prefix}_${f}`]) ?? (w && w.summary ? w.summary[f] : null)) : null;

            const plate = w ? (w.plate || getAttr('plate') || '-') : '-';
            const conf = w ? (w.overall_conf || toExactPct(getAttr('confidence'))) : null;
            const avgConf = w ? (w.avg_conf || toExactPct(w.avg_score)) : null;
            const charsList = (w && Array.isArray(w.characters) && w.characters.length > 0) ? w.characters : (prefix ? (rec[`${prefix}_characters_json`] || []) : []);

            let status = '';
            if (cl.winner) {
                status = `<div class="d-flex flex-wrap gap-1 mt-2">
                    <span class="badge badge-success">${cl.is_fallback ? 'Selected (below 95%)' : 'Selected'}: ${escapeHtml(cl.winner.engine_label)}</span>
                    <span class="badge badge-light fs-9">${escapeHtml(avgConf)}% avg</span>
                </div>`;
            } else if (cl.status === 'mismatch') {
                status = `<div class="d-flex flex-wrap gap-1 mt-2">
                    <span class="badge badge-danger">No decision · mismatch</span>
                    ${w ? `<span class="badge badge-light fs-9">${escapeHtml(avgConf)}% avg · ${escapeHtml(w.engine_label)}</span>` : ''}
                </div>
                <div class="text-gray-600 fs-9 mt-1">${escapeHtml(cl.mismatch_reason || 'Qualified engines disagree on characters')}</div>`;
            } else {
                status = `<div class="d-flex flex-wrap gap-1 mt-2">
                    <span class="badge badge-light-danger fs-9">No decision · all below 95%</span>
                    ${w ? `<span class="badge badge-light fs-9">${escapeHtml(avgConf)}% avg · ${escapeHtml(w.engine_label)}</span>` : ''}
                </div>`;
            }

            let acceptedHtml = '';
            if (rec.accepted_logic === 'current_logic') acceptedHtml = `<span class="badge badge-light-success fs-9 mt-2">Accepted</span>`;
            else if (isRejectVal(rec.accepted_logic)) acceptedHtml = `<span class="badge badge-light-danger fs-9 mt-2">Rejected</span>`;

            return `
                <div class="d-flex flex-column align-items-start">
                    ${headLine(plate, conf, w ? getAttr('time_ms') : null)}
                    ${status}
                    ${charPillsHtml(charsList)}
                    ${metaBadgesFor(getAttr)}
                    <div class="d-flex flex-wrap gap-1 mt-2">${enginePillsHtml(cl)}</div>
                    ${acceptedHtml}
                </div>`;
        };

        const formatDbEngineFullCell = (rec, prefix) => {
            const plate = rec[`${prefix}_plate`];
            if (!rec[`${prefix}_success`] && !plate) {
                return `<span class="text-gray-400 fs-8">No plate / failed</span>`;
            }
            const charsJson = rec[`${prefix}_characters_json`];
            const chars = rec[`${prefix}_characters`];
            const charsHtml = (Array.isArray(charsJson) && charsJson.length > 0) ?
                charPillsHtml(charsJson) :
                (chars ? `<div class="text-gray-600 font-monospace fs-9 mt-2">${escapeHtml(chars)}</div>` : '');

            return `
                <div class="d-flex flex-column align-items-start">
                    ${headLine(plate, toExactPct(rec[`${prefix}_confidence`]), rec[`${prefix}_time_ms`])}
                    ${charsHtml}
                    ${metaBadgesFor((f) => rec[`${prefix}_${f}`])}
                </div>`;
        };

        // ---------- Load DB ----------
        async function loadDatabaseRecords(page = 1) {
            const search = dbSearchInput.value.trim();
            const decisionFilter = dbDecisionFilter ? dbDecisionFilter.value : 'all';
            try {
                const url = new URL('{{ route("ocr.batch.records") }}', window.location.origin);
                url.searchParams.set('page', page);
                url.searchParams.set('per_page', 15);
                if (search) url.searchParams.set('search', search);
                if (decisionFilter && decisionFilter !== 'all') {
                    url.searchParams.set('status', decisionFilter);
                }

                const res = await fetch(url.toString(), {
                    headers: {
                        'Accept': 'application/json'
                    }
                });
                const data = await res.json();

                if (!res.ok || !data.status) {
                    dbHistoryTbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-6">Failed to load records: ${escapeHtml(data.error || 'Unknown error')}</td></tr>`;
                    return;
                }

                dbRecordsCache = data.records || [];
                currentDbPage = data.pagination.current_page;
                lastDbPage = data.pagination.last_page;

                statDbTotal.textContent = data.pagination.total;
                if (data.stats) updateLogicKpiStats(data.stats);
                const filterLabel = (decisionFilter && decisionFilter !== 'all') ? ` [Filter: ${decisionFilter}]` : ' · Decision Pending shown first';
                dbTotalSubtitle.innerHTML = `Showing <strong>${dbRecordsCache.length}</strong> of <strong>${data.pagination.total}</strong> records<span class="text-gray-500">${filterLabel}</span>`;
                dbPageInfo.textContent = `Page ${currentDbPage} of ${lastDbPage}`;
                dbPrevPageBtn.disabled = currentDbPage <= 1;
                dbNextPageBtn.disabled = currentDbPage >= lastDbPage;

                if (dbRecordsCache.length === 0) {
                    dbHistoryTbody.innerHTML = `<tr><td colspan="9" class="text-center text-gray-500 py-10">No records found matching current criteria.</td></tr>`;
                    return;
                }

                dbHistoryTbody.innerHTML = '';
                dbRecordsCache.forEach(rec => {
                    const tr = document.createElement('tr');
                    tr.dataset.recordId = rec.id;
                    if (Number(activeInspectedRecordId) === Number(rec.id)) tr.classList.add('active-db-row');

                    const fname = rec.stored_filename || rec.original_filename;
                    tr.innerHTML = `
                        <td class="fw-semibold text-gray-600">${rec.id}</td>
                        <td>
                            <a href="javascript:void(0)" class="db-thumb-wrap" onclick="event.stopPropagation(); window.openRecordInspectorModal(${rec.id});">
                                <img src="${rec.image_url}" class="db-thumb" alt="${escapeHtml(rec.original_filename)}">
                            </a>
                            <div class="fs-9 text-gray-500 font-monospace mt-1" style="word-break: break-all; line-height: 1.2;" title="${escapeHtml(fname)}">${escapeHtml(fname)}</div>
                        </td>
                        <td class="decision-col-cell">${formatDbDecisionCell(rec)}</td>
                        <td class="best-col-cell">${formatDbBestPickCell(rec)}</td>
                        <td class="currentlogic-col-cell">${formatDbCurrentLogicCell(rec)}</td>
                        <td>${formatDbEngineFullCell(rec, 'candid')}</td>
                        <td>${formatDbEngineFullCell(rec, 'eydea')}</td>
                        <td>${formatDbEngineFullCell(rec, 'arh')}</td>
                        <td>${formatDbEngineFullCell(rec, 'parkonic')}</td>
                    `;

                    const thumb = tr.querySelector('.db-thumb-wrap');
                    thumb.addEventListener('mouseenter', (e) => showImageHoverPopover(e, rec));
                    thumb.addEventListener('mousemove', updateHoverPopoverPosition);
                    thumb.addEventListener('mouseleave', hideImageHoverPopover);

                    tr.addEventListener('click', () => window.openRecordInspectorModal(rec.id));
                    dbHistoryTbody.appendChild(tr);
                });

                if (!activeItemKey && dbRecordsCache.length > 0) {
                    populateInspectorFromRecord(dbRecordsCache[0], null, false);
                }
            } catch (err) {
                console.error('Error loading DB records:', err);
                dbHistoryTbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-6">Could not reach the records endpoint.</td></tr>`;
            }
        }

        let searchDebounce = null;
        if (dbDecisionFilter) {
            dbDecisionFilter.addEventListener('change', () => loadDatabaseRecords(1));
        }
        dbSearchInput.addEventListener('input', () => {
            clearTimeout(searchDebounce);
            searchDebounce = setTimeout(() => loadDatabaseRecords(1), 300);
        });
        refreshDbBtn.addEventListener('click', () => loadDatabaseRecords(currentDbPage));
        dbPrevPageBtn.addEventListener('click', () => {
            if (currentDbPage > 1) loadDatabaseRecords(currentDbPage - 1);
        });
        dbNextPageBtn.addEventListener('click', () => {
            if (currentDbPage < lastDbPage) loadDatabaseRecords(currentDbPage + 1);
        });

        loadDatabaseRecords(1);
    });
</script>
@endsection