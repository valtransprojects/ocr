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
                    <button type="button" class="btn btn-sm btn-light-info" id="modal-btn-accept-both" data-logic="both_acceptable">Both Acceptable</button>
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

