@extends('app')

@section('title', 'ANPR OCR Comparison Portal')

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/ocr.css') }}">
@endsection

@section('content')
    <!--begin::Toolbar / Page Heading-->
    <div class="d-flex flex-column align-items-center text-center mb-8">
        <h1 class="text-gray-900 fw-bold fs-2 mb-2" id="page-title">
            OCR ANPR Results
        </h1>
        <p class="text-gray-500 fs-6 mb-0" id="page-subtitle" style="max-width: 650px;">
            Upload a license plate photo to compare the Candid, Eydea, ARH, and Parkonic Engine side by side, and inspect each engine's raw payload schema.
        </p>
    </div>
    <!--end::Toolbar-->

    <div class="row g-5 g-xl-8">
        <!--begin::Left Column - Control Center-->
        <div class="col-lg-5" id="control-center-column">
            <div class="card card-flush h-100 animate-fade-in" id="uploader-card">
                <!--begin::Card header-->
                <div class="card-header pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold text-gray-900 fs-3 d-flex align-items-center">
                            Control Center
                        </h2>
                    </div>
                    <div class="card-toolbar">
                        <button class="btn btn-sm btn-icon btn-active-color-primary" id="close-control-center-btn" title="Hide Control Center">
                            <i class="ki-outline ki-cross fs-2"></i>
                        </button>
                    </div>
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-2">
                    <!--begin::Engine selector-->
                    <div class="mb-7" id="engine-selector-wrapper">
                        <label class="form-label fs-7 text-gray-700 fw-bold mb-3" id="engine-label">
                            
                            Select ANPR Engine Target
                        </label>
                        <div class="btn-group w-100 gap-2" role="group" aria-label="Engine Selector" id="engine-selector-group">
                            <input type="radio" class="btn-check" name="engine" id="engine-all" value="all" checked>
                            <label class="btn btn-sm btn-outline btn-outline-dashed btn-active-primary w-100" for="engine-all">All</label>

                            <input type="radio" class="btn-check" name="engine" id="engine-candid" value="candid">
                            <label class="btn btn-sm btn-outline btn-outline-dashed btn-active-primary w-100" for="engine-candid">Candid</label>

                            <input type="radio" class="btn-check" name="engine" id="engine-eydea" value="eydea">
                            <label class="btn btn-sm btn-outline btn-outline-dashed btn-active-primary w-100" for="engine-eydea">Eydea</label>

                            <input type="radio" class="btn-check" name="engine" id="engine-parkonic" value="parkonic">
                            <label class="btn btn-sm btn-outline btn-outline-dashed btn-active-primary w-100" for="engine-parkonic">ARH</label>

                            <input type="radio" class="btn-check" name="engine" id="engine-anpr2" value="anpr2">
                            <label class="btn btn-sm btn-outline btn-outline-dashed btn-active-primary w-100" for="engine-anpr2">Parkonic Engine</label>
                        </div>
                    </div>
                    <!--end::Engine selector-->

                    <!--begin::Dropzone-->
                    <div class="d-flex flex-column flex-center p-10 border border-dashed border-gray-300 rounded-3 mb-7 cursor-pointer upload-zone" id="upload-zone">
                        <input type="file" id="file-input" class="d-none" accept="image/*">
                        <i class="ki-outline ki-file-up text-primary fs-3x mb-3 upload-icon" id="cloud-icon"></i>
                        <h3 class="text-gray-900 fw-bold fs-5 mb-1" id="upload-prompt">Drag &amp; Drop Image Here</h3>
                        <p class="text-gray-500 fs-7 mb-0" id="upload-subprompt">or click to browse from device</p>
                    </div>
                    <!--end::Dropzone-->

                    <!--begin::Preview-->
                    <div class="mb-7 d-none" id="preview-wrapper">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fs-7 text-gray-700 fw-bold">
                                <i class="ki-outline ki-picture fs-6 me-1 text-gray-500"></i> Image Preview
                            </span>
                            <button class="btn btn-sm btn-light-danger px-3 py-2" id="clear-file-btn">
                                <i class="ki-outline ki-cross-circle fs-6 me-1"></i> Clear Image
                            </button>
                        </div>
                        <div class="position-relative" id="image-frame-wrapper">
                            <div class="border border-gray-300 rounded-3 bg-light overflow-hidden d-flex flex-center preview-frame" id="image-frame">
                                <img src="" id="image-preview" class="preview-img" alt="Uploaded Car Plate Preview">
                            </div>
                            <div id="image-magnifier" class="magnifier-glass d-none"></div>
                        </div>
                    </div>
                    <!--end::Preview-->

                    <!--begin::Process button-->
                    <button class="btn btn-primary w-100 d-flex flex-center gap-2" id="process-btn" disabled>
                        <i class="ki-outline ki-play fs-3"></i>
                        <span class="fw-bold">Process Plate OCR</span>
                    </button>
                    <!--end::Process button-->
                </div>
                <!--end::Card body-->
            </div>
        </div>
        <!--end::Left Column-->

        <!--begin::Right Column - Results-->
        <div class="col-lg-7" id="results-column">
            <div class="card card-flush h-100 animate-fade-in" id="results-card">
                <!--begin::Card header-->
                <div class="card-header pt-6">
                    <div class="card-title">
                        <h2 class="fw-bold text-gray-900 fs-3 d-flex align-items-center">
                            OCR Analysis Results
                        </h2>
                    </div>
                    <div class="card-toolbar">
                        <button class="btn btn-sm btn-primary d-none" id="open-control-center-btn">
                            <i class="ki-outline ki-show fs-6 me-1"></i> Control Center
                        </button>
                    </div>
                </div>
                <!--end::Card header-->

                <!--begin::Card body-->
                <div class="card-body pt-2 d-flex flex-column">
                    <!--begin::Placeholder state-->
                    <div class="d-flex flex-column flex-center text-center my-auto py-10" id="results-placeholder">
                        <i class="ki-outline ki-element-11 text-gray-400 fs-3x mb-4"></i>
                        <h3 class="text-gray-800 fw-bold fs-4 mb-2">Awaiting input data</h3>
                        <p class="text-gray-500 fs-6 mb-0">Select an engine, upload an image, then click process to view the comparison.</p>
                    </div>
                    <!--end::Placeholder state-->

                    <!--begin::Loading state-->
                    <div class="d-flex flex-column flex-center text-center my-auto py-10 d-none" id="loading-spinner">
                        <div class="spinner-border text-primary mb-4" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <h3 class="text-gray-900 fw-bold fs-4 mb-2" id="loading-title">Processing OCR Modules...</h3>
                        <p class="text-gray-500 fs-6 mb-0" id="loading-subtitle">Submitting payload to localized neural engines</p>
                    </div>
                    <!--end::Loading state-->

                    <!--begin::Results content-->
                    <div class="d-none" id="results-content">
                        <!--begin::Nav tabs-->
                        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-6 fw-bold mb-6" id="results-tabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link text-active-primary active" id="table-tab" data-bs-toggle="tab" data-bs-target="#table-panel" type="button" role="tab" aria-controls="table-panel" aria-selected="true">
                                    <i class="ki-outline ki-element-11 fs-5 me-1"></i> Parametric Comparison
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link text-active-primary" id="raw-candid-tab" data-bs-toggle="tab" data-bs-target="#raw-candid-panel" type="button" role="tab" aria-controls="raw-candid-panel" aria-selected="false">
                                    <i class="ki-outline ki-code fs-5 me-1"></i> Candid JSON
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link text-active-primary" id="raw-eydea-tab" data-bs-toggle="tab" data-bs-target="#raw-eydea-panel" type="button" role="tab" aria-controls="raw-eydea-panel" aria-selected="false">
                                    <i class="ki-outline ki-code fs-5 me-1"></i> Eydea JSON
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link text-active-primary" id="raw-parkonic-tab" data-bs-toggle="tab" data-bs-target="#raw-parkonic-panel" type="button" role="tab" aria-controls="raw-parkonic-panel" aria-selected="false">
                                    <i class="ki-outline ki-code fs-5 me-1"></i> ARH JSON
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link text-active-primary" id="raw-anpr2-tab" data-bs-toggle="tab" data-bs-target="#raw-anpr2-panel" type="button" role="tab" aria-controls="raw-anpr2-panel" aria-selected="false">
                                    <i class="ki-outline ki-code fs-5 me-1"></i> Parkonic JSON
                                </button>
                            </li>
                        </ul>
                        <!--end::Nav tabs-->

                        <!--begin::Tab content-->
                        <div class="tab-content" id="results-tabs-content">

                            <!--begin::Comparison table panel-->
                            <div class="tab-pane fade show active" id="table-panel" role="tabpanel" aria-labelledby="table-tab">
                                <!--begin::Partial warning-->
                                <div class="alert alert-dismissible bg-light-warning border border-warning border-dashed d-flex flex-column flex-sm-row align-items-sm-center p-5 mb-6 d-none" id="engine-partial-warning" role="alert">
                                    <i class="ki-outline ki-information-2 text-warning fs-2x me-4 mb-3 mb-sm-0"></i>
                                    <div class="d-flex flex-column pe-0 pe-sm-10">
                                        <h5 class="mb-1 text-warning fw-bold fs-6">Partial Results</h5>
                                        <span id="partial-warning-text" class="text-gray-700 fs-7">One of the requested engines failed. Displaying partial results.</span>
                                    </div>
                                </div>
                                <!--end::Partial warning-->

                                <div class="table-responsive">
                                    <table class="table align-middle table-row-dashed comparison-table fs-7 gy-4 mb-0" id="comparison-table">
                                        <thead>
                                            <tr class="text-start text-gray-500 fw-bold fs-7 text-uppercase align-middle">
                                                <th style="width: 16%">Parameter</th>
                                                <th id="th-candid" style="width: 21%">Candid Engine</th>
                                                <th id="th-eydea" style="width: 21%">Eydea Engine</th>
                                                <th id="th-parkonic" style="width: 21%">ARH Engine</th>
                                                <th id="th-anpr2" style="width: 21%">Parkonic Engine</th>
                                            </tr>
                                        </thead>
                                        <tbody class="text-gray-800">
                                            <tr>
                                                <td class="fw-semibold text-muted"><i class="ki-outline ki-car fs-6 me-1 text-gray-400"></i> Detected Plate</td>
                                                <td id="comp-candid-plate" class="fw-bold fs-5">-</td>
                                                <td id="comp-eydea-plate" class="fw-bold fs-5">-</td>
                                                <td id="comp-parkonic-plate" class="fw-bold fs-5">-</td>
                                                <td id="comp-anpr2-plate" class="fw-bold fs-5">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted"><i class="ki-outline ki-geolocation fs-6 me-1 text-gray-400"></i> Plate Region / Type</td>
                                                <td id="comp-candid-region">-</td>
                                                <td id="comp-eydea-region">-</td>
                                                <td id="comp-parkonic-region">-</td>
                                                <td id="comp-anpr2-region">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted"><i class="ki-outline ki-verify fs-6 me-1 text-gray-400"></i> OCR Confidence</td>
                                                <td id="comp-candid-conf">-</td>
                                                <td id="comp-eydea-conf">-</td>
                                                <td id="comp-parkonic-conf">-</td>
                                                <td id="comp-anpr2-conf">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted"><i class="ki-outline ki-abstract-26 fs-6 me-1 text-gray-400"></i> Character Splits</td>
                                                <td id="comp-candid-chars">-</td>
                                                <td id="comp-eydea-chars">-</td>
                                                <td id="comp-parkonic-chars">-</td>
                                                <td id="comp-anpr2-chars">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted"><i class="ki-outline ki-car-3 fs-6 me-1 text-gray-400"></i> Make &amp; Model</td>
                                                <td id="comp-candid-vehicle">-</td>
                                                <td id="comp-eydea-vehicle">-</td>
                                                <td id="comp-parkonic-vehicle">-</td>
                                                <td id="comp-anpr2-vehicle">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted"><i class="ki-outline ki-category fs-6 me-1 text-gray-400"></i> Body Type / Category</td>
                                                <td id="comp-candid-category">-</td>
                                                <td id="comp-eydea-category">-</td>
                                                <td id="comp-parkonic-category">-</td>
                                                <td id="comp-anpr2-category">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted"><i class="ki-outline ki-color-swatch fs-6 me-1 text-gray-400"></i> Vehicle Color</td>
                                                <td id="comp-candid-color">-</td>
                                                <td id="comp-eydea-color">-</td>
                                                <td id="comp-parkonic-color">-</td>
                                                <td id="comp-anpr2-color">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted"><i class="ki-outline ki-eye fs-6 me-1 text-gray-400"></i> View Port</td>
                                                <td id="comp-candid-viewport">-</td>
                                                <td id="comp-eydea-viewport">-</td>
                                                <td id="comp-parkonic-viewport">-</td>
                                                <td id="comp-anpr2-viewport">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted"><i class="ki-outline ki-scan fs-6 me-1 text-gray-400"></i> Cropped Quality</td>
                                                <td id="comp-candid-quality">-</td>
                                                <td id="comp-eydea-quality">-</td>
                                                <td id="comp-parkonic-quality">-</td>
                                                <td id="comp-anpr2-quality">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted"><i class="ki-outline ki-price-tag fs-6 me-1 text-gray-400"></i> Vehicle Tags</td>
                                                <td id="comp-candid-tags">-</td>
                                                <td id="comp-eydea-tags">-</td>
                                                <td id="comp-parkonic-tags">-</td>
                                                <td id="comp-anpr2-tags">-</td>
                                            </tr>
                                            <tr>
                                                <td class="fw-semibold text-muted"><i class="ki-outline ki-timer fs-6 me-1 text-gray-400"></i> Latency</td>
                                                <td id="comp-candid-time" class="fw-medium text-primary">-</td>
                                                <td id="comp-eydea-time" class="fw-medium text-primary">-</td>
                                                <td id="comp-parkonic-time" class="fw-medium text-primary">-</td>
                                                <td id="comp-anpr2-time" class="fw-medium text-primary">-</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <!--end::Comparison table panel-->

                            <!--begin::Candid JSON panel-->
                            <div class="tab-pane fade" id="raw-candid-panel" role="tabpanel" aria-labelledby="raw-candid-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fs-7 text-gray-600 fw-semibold"><i class="ki-outline ki-code fs-6 me-1"></i> Candid JSON Output</span>
                                    <button class="btn btn-sm btn-light-primary px-3 py-2" id="copy-candid-json-btn">
                                        <i class="ki-outline ki-copy fs-6 me-1"></i> Copy JSON
                                    </button>
                                </div>
                                <pre class="rounded-3 p-4 bg-light-dark"><code id="candid-json-code">{}</code></pre>
                            </div>
                            <!--end::Candid JSON panel-->

                            <!--begin::Eydea JSON panel-->
                            <div class="tab-pane fade" id="raw-eydea-panel" role="tabpanel" aria-labelledby="raw-eydea-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fs-7 text-gray-600 fw-semibold"><i class="ki-outline ki-code fs-6 me-1"></i> Eydea JSON Output</span>
                                    <button class="btn btn-sm btn-light-primary px-3 py-2" id="copy-eydea-json-btn">
                                        <i class="ki-outline ki-copy fs-6 me-1"></i> Copy JSON
                                    </button>
                                </div>
                                <pre class="rounded-3 p-4 bg-light-dark"><code id="eydea-json-code">{}</code></pre>
                            </div>
                            <!--end::Eydea JSON panel-->

                            <!--begin::Parkonic JSON panel-->
                            <div class="tab-pane fade" id="raw-parkonic-panel" role="tabpanel" aria-labelledby="raw-parkonic-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fs-7 text-gray-600 fw-semibold"><i class="ki-outline ki-code fs-6 me-1"></i> ARH JSON Output</span>
                                    <button class="btn btn-sm btn-light-primary px-3 py-2" id="copy-parkonic-json-btn">
                                        <i class="ki-outline ki-copy fs-6 me-1"></i> Copy JSON
                                    </button>
                                </div>
                                <pre class="rounded-3 p-4 bg-light-dark"><code id="parkonic-json-code">{}</code></pre>
                            </div>
                            <!--end::Parkonic JSON panel-->

                            <!--begin::ANPR 2 JSON panel-->
                            <div class="tab-pane fade" id="raw-anpr2-panel" role="tabpanel" aria-labelledby="raw-anpr2-tab">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="fs-7 text-gray-600 fw-semibold"><i class="ki-outline ki-code fs-6 me-1"></i> Parkonic JSON Output</span>
                                    <button class="btn btn-sm btn-light-primary px-3 py-2" id="copy-anpr2-json-btn">
                                        <i class="ki-outline ki-copy fs-6 me-1"></i> Copy JSON
                                    </button>
                                </div>
                                <pre class="rounded-3 p-4 bg-light-dark"><code id="anpr2-json-code">{}</code></pre>
                            </div>
                            <!--end::ANPR 2 JSON panel-->

                        </div>
                        <!--end::Tab content-->
                    </div>
                    <!--end::Results content-->
                </div>
                <!--end::Card body-->
            </div>
        </div>
        <!--end::Right Column-->
    </div>
@endsection

@section('scripts')
    <!-- OCR Controller AJAX logic & DOM Manipulation -->
    <script id="ocr-processing-script">
        document.addEventListener('DOMContentLoaded', () => {
            const fileInput = document.getElementById('file-input');
            const uploadZone = document.getElementById('upload-zone');
            const previewWrapper = document.getElementById('preview-wrapper');
            const imagePreview = document.getElementById('image-preview');
            const imageFrame = document.getElementById('image-frame');
            const imageFrameWrapper = document.getElementById('image-frame-wrapper');
            const imageMagnifier = document.getElementById('image-magnifier');
            const clearFileBtn = document.getElementById('clear-file-btn');
            const processBtn = document.getElementById('process-btn');

            const closeControlCenterBtn = document.getElementById('close-control-center-btn');
            const openControlCenterBtn = document.getElementById('open-control-center-btn');
            const controlCenterColumn = document.getElementById('control-center-column');
            const resultsColumn = document.getElementById('results-column');

            const resultsPlaceholder = document.getElementById('results-placeholder');
            const loadingSpinner = document.getElementById('loading-spinner');
            const resultsContent = document.getElementById('results-content');
            const partialWarning = document.getElementById('engine-partial-warning');
            const partialWarningText = document.getElementById('partial-warning-text');

            const tabCandid = document.getElementById('raw-candid-tab');
            const tabEydea = document.getElementById('raw-eydea-tab');
            const tabParkonic = document.getElementById('raw-parkonic-tab');
            const tabAnpr2 = document.getElementById('raw-anpr2-tab');

            const thCandid = document.getElementById('th-candid');
            const thEydea = document.getElementById('th-eydea');
            const thParkonic = document.getElementById('th-parkonic');
            const thAnpr2 = document.getElementById('th-anpr2');

                        const cellCandidPlate = document.getElementById('comp-candid-plate');
            const cellCandidRegion = document.getElementById('comp-candid-region');
            const cellCandidConf = document.getElementById('comp-candid-conf');
            const cellCandidChars = document.getElementById('comp-candid-chars');
            const cellCandidVehicle = document.getElementById('comp-candid-vehicle');
            const cellCandidCategory = document.getElementById('comp-candid-category');
            const cellCandidColor = document.getElementById('comp-candid-color');
            const cellCandidViewport = document.getElementById('comp-candid-viewport');
            const cellCandidTags = document.getElementById('comp-candid-tags');
            const cellCandidTime = document.getElementById('comp-candid-time');
            const cellCandidQuality = document.getElementById('comp-candid-quality');
            const candidJsonCode = document.getElementById('candid-json-code');
            const copyCandidJsonBtn = document.getElementById('copy-candid-json-btn');

            const cellEydeaPlate = document.getElementById('comp-eydea-plate');
            const cellEydeaRegion = document.getElementById('comp-eydea-region');
            const cellEydeaConf = document.getElementById('comp-eydea-conf');
            const cellEydeaChars = document.getElementById('comp-eydea-chars');
            const cellEydeaVehicle = document.getElementById('comp-eydea-vehicle');
            const cellEydeaCategory = document.getElementById('comp-eydea-category');
            const cellEydeaColor = document.getElementById('comp-eydea-color');
            const cellEydeaViewport = document.getElementById('comp-eydea-viewport');
            const cellEydeaTags = document.getElementById('comp-eydea-tags');
            const cellEydeaTime = document.getElementById('comp-eydea-time');
            const cellEydeaQuality = document.getElementById('comp-eydea-quality');
            const eydeaJsonCode = document.getElementById('eydea-json-code');
            const copyEydeaJsonBtn = document.getElementById('copy-eydea-json-btn');

            const cellParkonicPlate = document.getElementById('comp-parkonic-plate');
            const cellParkonicRegion = document.getElementById('comp-parkonic-region');
            const cellParkonicConf = document.getElementById('comp-parkonic-conf');
            const cellParkonicChars = document.getElementById('comp-parkonic-chars');
            const cellParkonicVehicle = document.getElementById('comp-parkonic-vehicle');
            const cellParkonicCategory = document.getElementById('comp-parkonic-category');
            const cellParkonicColor = document.getElementById('comp-parkonic-color');
            const cellParkonicViewport = document.getElementById('comp-parkonic-viewport');
            const cellParkonicTags = document.getElementById('comp-parkonic-tags');
            const cellParkonicTime = document.getElementById('comp-parkonic-time');
            const cellParkonicQuality = document.getElementById('comp-parkonic-quality');
            const parkonicJsonCode = document.getElementById('parkonic-json-code');
            const copyParkonicJsonBtn = document.getElementById('copy-parkonic-json-btn');

            const cellAnpr2Plate = document.getElementById('comp-anpr2-plate');
            const cellAnpr2Region = document.getElementById('comp-anpr2-region');
            const cellAnpr2Conf = document.getElementById('comp-anpr2-conf');
            const cellAnpr2Chars = document.getElementById('comp-anpr2-chars');
            const cellAnpr2Vehicle = document.getElementById('comp-anpr2-vehicle');
            const cellAnpr2Category = document.getElementById('comp-anpr2-category');
            const cellAnpr2Color = document.getElementById('comp-anpr2-color');
            const cellAnpr2Viewport = document.getElementById('comp-anpr2-viewport');
            const cellAnpr2Tags = document.getElementById('comp-anpr2-tags');
            const cellAnpr2Time = document.getElementById('comp-anpr2-time');
            const cellAnpr2Quality = document.getElementById('comp-anpr2-quality');
            const anpr2JsonCode = document.getElementById('anpr2-json-code');
            const copyAnpr2JsonBtn = document.getElementById('copy-anpr2-json-btn');

            let base64Data = '';

            const getSelectedEngine = () => {
                const checkedRadio = document.querySelector('input[name="engine"]:checked');
                return checkedRadio ? checkedRadio.value : 'all';
            };

            const formatColorVal = (val) => {
                if (typeof val === 'number') {
                    let hex = val.toString(16);
                    while (hex.length < 6) hex = '0' + hex;
                    return '#' + hex.toUpperCase();
                }
                return val || 'N/A';
            };

            const formatWithConf = (val, confVal) => {
                if (!val || val === 'N/A') return 'N/A';
                if (confVal === undefined || confVal === null) return val;
                
                // convert conf to percentage if it is 0..1
                let conf = parseFloat(confVal);
                if (isNaN(conf)) return val;
                if (conf <= 1.0 && conf > 0.0) {
                    conf = Math.round(conf * 100);
                } else {
                    conf = Math.round(conf);
                }
                
                const badgeColor = conf >= 95 ? 'text-success' : (conf >= 90 ? 'text-warning' : 'text-danger');
                return `${val} <span class="fs-9 fw-semibold ${badgeColor} ms-1">(${conf}%)</span>`;
            };

            // Styled plate helpers
            const plateBadge = (text) =>
                `<span class="badge bg-body-tertiary text-body border px-3 py-2 fs-6 font-display border-2 border-secondary border-opacity-50 plate-badge" style="letter-spacing: 1px">${text || '-'}</span>`;

            const renderPlate = (text, code) => {
                if (!text && !code) return '-';

                let cleanText = text ? text.trim() : '';
                let cleanCode = code ? code.trim() : '';

                // If both are provided, cleanText might contain the code prefix
                if (cleanCode && cleanText) {
                    // Case 1: starts with code followed by space or hyphen
                    const escapedCode = cleanCode.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
                    const prefixWithSeparator = new RegExp('^' + escapedCode + '[-\\s]+', 'i');
                    if (prefixWithSeparator.test(cleanText)) {
                        cleanText = cleanText.replace(prefixWithSeparator, '');
                    }
                    // Case 2: starts with code directly
                    else if (cleanText.toLowerCase().startsWith(cleanCode.toLowerCase())) {
                        // If code is alphabetic, strip it
                        if (/[A-Z]/i.test(cleanCode)) {
                            cleanText = cleanText.substring(cleanCode.length);
                        }
                        // If code is numeric, only strip if the full text is longer than a standard plate number (5 digits)
                        else if (/^\d+$/.test(cleanText) && cleanText.length > 5) {
                            cleanText = cleanText.substring(cleanCode.length);
                        }
                    }
                }

                // If code is not explicitly provided, try to extract it from the text
                if (!cleanCode && cleanText) {
                    // Scenario A: Text has a space or hyphen separating parts (e.g., "22 75679", "DXB-12345")
                    const separatorMatch = cleanText.match(/^(.*?)[-\s]+([A-Z0-9]+)$/i);
                    if (separatorMatch) {
                        cleanCode = separatorMatch[1].trim();
                        cleanText = separatorMatch[2].trim();
                    } 
                    // Scenario B: Text is all digits (e.g., "2275679") and length > 5
                    else if (/^\d+$/.test(cleanText) && cleanText.length > 5) {
                        const numDigits = cleanText.length;
                        // Assume the plate number is the last 5 digits
                        cleanCode = cleanText.substring(0, numDigits - 5);
                        cleanText = cleanText.substring(numDigits - 5);
                    }
                    // Scenario C: Text is Alphanumeric without space (e.g., "A12345", "DXB12345")
                    else {
                        const alphaNumMatch = cleanText.match(/^([A-Z]+)([0-9]+)$/i);
                        if (alphaNumMatch) {
                            cleanCode = alphaNumMatch[1].toUpperCase();
                            cleanText = alphaNumMatch[2];
                        }
                    }
                }

                // Render badges
                if (cleanCode && cleanText) {
                    return plateBadge(cleanCode) + ' ' + plateBadge(cleanText);
                } else if (cleanCode) {
                    return plateBadge(cleanCode);
                } else if (cleanText) {
                    return plateBadge(cleanText);
                }

                return '-';
            };

            const confBadgeClass = (c) => (c >= 95 ? 'badge-success' : (c >= 90 ? 'badge-warning' : 'badge-danger'));

            const confidenceBadge = (c) =>
                `<span class="badge ${confBadgeClass(c)} fs-7 fw-bold">${c}%</span>`;

            const charChip = (label, conf) => {
                const badgeColor = conf >= 95 ? 'badge-success' : (conf >= 90 ? 'badge-warning' : 'badge-danger');
                return `<span class="badge ${badgeColor} m-1" style="font-family: var(--bs-font-monospace)">${label} = ${conf}%</span>`;
            };

            const latencyCell = (ms) =>
                `<i class="ki-outline ki-electricity text-warning me-1 fs-6"></i> ${ms} ms`;

            const failedCell = () =>
                `<span class="text-danger fs-7 fw-semibold"><i class="ki-outline ki-cross-circle text-danger fs-6 me-1"></i> Failed</span>`;

            const noPlateCell = () =>
                `<span class="text-warning fs-7 fw-semibold"><i class="ki-outline ki-question text-warning fs-6 me-1"></i> No plate</span>`;

            // Dropzone interactions
            uploadZone.addEventListener('click', () => fileInput.click());

            ['dragenter', 'dragover'].forEach(name => {
                uploadZone.addEventListener(name, (e) => {
                    e.preventDefault();
                    uploadZone.classList.add('dragover');
                });
            });

            ['dragleave', 'drop'].forEach(name => {
                uploadZone.addEventListener(name, (e) => {
                    e.preventDefault();
                    uploadZone.classList.remove('dragover');
                });
            });

            uploadZone.addEventListener('drop', (e) => {
                const files = e.dataTransfer.files;
                if (files.length) {
                    fileInput.files = files;
                    handleFileSelection(files[0]);
                }
            });

            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length) {
                    handleFileSelection(e.target.files[0]);
                }
            });

            function handleFileSelection(file) {
                if (!file.type.startsWith('image/')) {
                    alert('Please select a valid image file.');
                    return;
                }

                const reader = new FileReader();
                reader.onload = (e) => {
                    base64Data = e.target.result;
                    imagePreview.src = base64Data;

                    uploadZone.classList.add('d-none');
                    previewWrapper.classList.remove('d-none');

                    processBtn.removeAttribute('disabled');
                    resetResults();
                };
                reader.readAsDataURL(file);
            }

            clearFileBtn.addEventListener('click', () => {
                fileInput.value = '';
                base64Data = '';
                imagePreview.src = '';
                imageMagnifier.style.backgroundImage = 'none';
                imageMagnifier.classList.add('d-none');

                uploadZone.classList.remove('d-none');
                previewWrapper.classList.add('d-none');
                processBtn.setAttribute('disabled', 'true');
                resetResults();
            });

            // Magnifier Glass Logic
            const magnifierZoom = 2.0;

            function moveMagnifier(e) {
                if (!base64Data) return;

                const rect = imagePreview.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                // Check if cursor is over the image bounds
                if (x < 0 || x > rect.width || y < 0 || y > rect.height) {
                    imageMagnifier.classList.add('d-none');
                    return;
                }

                imageMagnifier.classList.remove('d-none');

                const frameRect = imageFrameWrapper.getBoundingClientRect();
                const glassX = e.clientX - frameRect.left - (imageMagnifier.offsetWidth / 2);
                const glassY = e.clientY - frameRect.top - (imageMagnifier.offsetHeight / 2);

                imageMagnifier.style.left = `${glassX}px`;
                imageMagnifier.style.top = `${glassY}px`;

                imageMagnifier.style.backgroundImage = `url('${imagePreview.src}')`;

                const bgWidth = rect.width * magnifierZoom;
                const bgHeight = rect.height * magnifierZoom;
                imageMagnifier.style.backgroundSize = `${bgWidth}px ${bgHeight}px`;

                let rx = (x * magnifierZoom) - (imageMagnifier.offsetWidth / 2);
                let ry = (y * magnifierZoom) - (imageMagnifier.offsetHeight / 2);

                imageMagnifier.style.backgroundPosition = `-${rx}px -${ry}px`;
            }

            imagePreview.addEventListener('mousemove', moveMagnifier);
            imagePreview.addEventListener('mouseleave', () => imageMagnifier.classList.add('d-none'));

            // Mobile/touch support
            imagePreview.addEventListener('touchmove', (e) => {
                if (e.touches.length > 0) {
                    moveMagnifier(e.touches[0]);
                }
            }, { passive: true });
            imagePreview.addEventListener('touchend', () => imageMagnifier.classList.add('d-none'));

            function resetResults() {
                resultsPlaceholder.classList.remove('d-none');
                loadingSpinner.classList.add('d-none');
                resultsContent.classList.add('d-none');
                partialWarning.classList.add('d-none');

                [cellCandidPlate, cellCandidRegion, cellCandidConf, cellCandidChars, cellCandidVehicle, cellCandidCategory, cellCandidColor, cellCandidViewport, cellCandidTags, cellCandidTime, cellCandidQuality,
                 cellEydeaPlate, cellEydeaRegion, cellEydeaConf, cellEydeaChars, cellEydeaVehicle, cellEydeaCategory, cellEydeaColor, cellEydeaViewport, cellEydeaTags, cellEydeaTime, cellEydeaQuality,
                 cellParkonicPlate, cellParkonicRegion, cellParkonicConf, cellParkonicChars, cellParkonicVehicle, cellParkonicCategory, cellParkonicColor, cellParkonicViewport, cellParkonicTags, cellParkonicTime, cellParkonicQuality,
                 cellAnpr2Plate, cellAnpr2Region, cellAnpr2Conf, cellAnpr2Chars, cellAnpr2Vehicle, cellAnpr2Category, cellAnpr2Color, cellAnpr2Viewport, cellAnpr2Tags, cellAnpr2Time, cellAnpr2Quality]
                    .forEach(cell => { cell.textContent = '-'; });
            }

            // Copy-to-clipboard handlers
            function bindCopy(button, getText, defaultIconClass) {
                button.addEventListener('click', () => {
                    if (!getText()) return;
                    navigator.clipboard.writeText(getText()).then(() => {
                        const icon = button.querySelector('i');
                        icon.className = 'ki-outline ki-check-circle text-success fs-6 ' + (defaultIconClass.includes('me-1') ? 'me-1' : '');
                        setTimeout(() => icon.className = defaultIconClass, 2000);
                    });
                });
            }

            bindCopy(copyCandidJsonBtn, () => candidJsonCode.textContent, 'ki-outline ki-copy fs-6 me-1');
            bindCopy(copyEydeaJsonBtn, () => eydeaJsonCode.textContent, 'ki-outline ki-copy fs-6 me-1');
            bindCopy(copyParkonicJsonBtn, () => parkonicJsonCode.textContent, 'ki-outline ki-copy fs-6 me-1');
            bindCopy(copyAnpr2JsonBtn, () => anpr2JsonCode.textContent, 'ki-outline ki-copy fs-6 me-1');

            // Submit to controller
            processBtn.addEventListener('click', async () => {
                if (!base64Data) return;

                const engine = getSelectedEngine();

                resultsPlaceholder.classList.add('d-none');
                resultsContent.classList.add('d-none');
                partialWarning.classList.add('d-none');
                loadingSpinner.classList.remove('d-none');
                processBtn.setAttribute('disabled', 'true');

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

                try {
                    const response = await fetch('{{ url("/ocr/process") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ base64: base64Data, engine: engine })
                    });

                    const responseData = await response.json();
                    loadingSpinner.classList.add('d-none');
                    processBtn.removeAttribute('disabled');

                    if (!response.ok || !responseData.status) {
                        const errMsg = responseData.error || 'Check server configurations.';
                        alert('Backend request failed: ' + errMsg);
                        resetResults();
                        return;
                    }

                    displayComparativeResults(responseData.results, engine);

                } catch (error) {
                    loadingSpinner.classList.add('d-none');
                    processBtn.removeAttribute('disabled');
                    alert('Network error connecting to the server. See console for details.');
                    console.error('OCR Process error:', error);
                    resetResults();
                }
            });

            function displayComparativeResults(results, engine) {
                let partialFailures = [];

                // Column / tab visibility
                const showCandid = (engine === 'candid' || engine === 'all' || engine === 'both');
                const showEydea = (engine === 'eydea' || engine === 'all' || engine === 'both');
                const showParkonic = (engine === 'parkonic' || engine === 'all' || engine === 'both');
                const showAnpr2 = (engine === 'anpr2' || engine === 'all' || engine === 'both');

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

                // 1. Candid
                if (results.candid) {
                    const candid = results.candid;
                    candidJsonCode.textContent = JSON.stringify(candid.data || candid, null, 2);

                    if (candid.success && candid.data.plates && candid.data.plates.length > 0) {
                        const plate = candid.data.plates[0];
                        cellCandidPlate.innerHTML = renderPlate(plate.text);

                        let regionText = '';
                        if (plate.category) {
                            regionText += (plate.category.city || '') + ' ' + (plate.category.country || '');
                            if (plate.category.description) regionText += ` (${plate.category.description})`;
                        }
                        cellCandidRegion.textContent = regionText.trim() || 'N/A';

                        const confidence = plate.confidence || plate.confidance || 0;
                        cellCandidConf.innerHTML = confidenceBadge(confidence);

                        const charsList = plate.characters || plate.plate_detail || [];
                        if (charsList.length > 0) {
                            cellCandidChars.innerHTML = charsList
                                .map(c => charChip(c.character, c.confidence || c.conf || 0))
                                .join('');
                        } else {
                            cellCandidChars.textContent = 'N/A';
                        }

                        if (plate.vehicle) {
                            const makerStr = formatWithConf(plate.vehicle.maker || plate.vehicle.make, plate.vehicle.maker_confidence || plate.vehicle.make_confidence || plate.vehicle.make_conf || plate.vehicle.maker_conf);
                            const modelStr = formatWithConf(plate.vehicle.model, plate.vehicle.model_confidence || plate.vehicle.model_conf);
                            let vehicleText = '';
                            if (makerStr && makerStr !== 'N/A') vehicleText += makerStr;
                            if (modelStr && modelStr !== 'N/A') vehicleText += (vehicleText ? ' ' : '') + modelStr;
                            cellCandidVehicle.innerHTML = vehicleText || 'N/A';
                            
                            cellCandidCategory.innerHTML = formatWithConf(plate.vehicle.type, plate.vehicle.type_confidence || plate.vehicle.type_conf);
                            cellCandidColor.innerHTML = formatWithConf(plate.vehicle.color, plate.vehicle.color_confidence || plate.vehicle.color_conf);
                        } else {
                            cellCandidVehicle.textContent = 'N/A';
                            cellCandidCategory.textContent = 'N/A';
                            cellCandidColor.textContent = 'N/A';
                        }

                        if (plate.vehicle && (plate.vehicle.viewpoint || plate.vehicle.view)) {
                            const viewVal = (plate.vehicle.viewpoint || plate.vehicle.view).toUpperCase();
                            const viewConf = plate.vehicle.viewpoint_confidence || plate.vehicle.view_confidence || plate.vehicle.viewpoint_conf || plate.vehicle.view_conf;
                            cellCandidViewport.innerHTML = formatWithConf(viewVal, viewConf);
                        } else if (plate.viewpoint) {
                            cellCandidViewport.innerHTML = formatWithConf(plate.viewpoint.toUpperCase(), plate.viewpoint_conf || plate.viewpoint_confidence);
                        } else {
                            cellCandidViewport.textContent = 'N/A';
                        }

                        // Vehicle tags logic (Candid)
                        let tagsHtml = '';
                        if (plate.vehicle && plate.vehicle.tags) {
                            const activeTags = [];
                            for (const [tagKey, tagData] of Object.entries(plate.vehicle.tags)) {
                                if (tagData && (tagData.value === 'yes' || tagData.value === true)) {
                                    const tagLabel = tagKey.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                                    const tagConf = tagData.confidence || tagData.conf;
                                    activeTags.push(formatWithConf(tagLabel, tagConf));
                                }
                            }
                            if (activeTags.length > 0) {
                                tagsHtml = activeTags.map(t => `<span class="badge badge-light-primary m-1 fs-8">${t}</span>`).join('');
                            }
                        }
                        cellCandidTags.innerHTML = tagsHtml || '<span class="text-muted fs-8">None</span>';
                        cellCandidQuality.textContent = 'N/A';
                        cellCandidTime.innerHTML = latencyCell(candid.time_ms);

                    } else {
                        const errMsg = candid.message || 'Plate detection returned 0 results';
                        cellCandidPlate.innerHTML = failedCell();
                        cellCandidRegion.textContent = '-';
                        cellCandidConf.textContent = '-';
                        cellCandidChars.textContent = '-';
                        cellCandidVehicle.textContent = '-';
                        cellCandidCategory.textContent = '-';
                        cellCandidColor.textContent = '-';
                        cellCandidViewport.textContent = '-';
                        cellCandidTags.textContent = '-';
                        cellCandidQuality.textContent = '-';
                        cellCandidTime.textContent = '-';
                        partialFailures.push(`Candid: ${errMsg}`);
                    }
                }

                // 2. Eydea
                if (results.eydea) {
                    const eydea = results.eydea;
                    eydeaJsonCode.textContent = JSON.stringify(eydea.data || eydea, null, 2);

                    if (eydea.success) {
                        const hasPlates = eydea.data.anpr && eydea.data.anpr.length > 0;
                        
                        if (hasPlates) {
                            let platesHtmls = [];
                            let regionsHtmls = [];
                            let confsHtmls = [];
                            let charsHtmls = [];
                            let vehiclesHtmls = [];
                            let categoriesHtmls = [];
                            let colorsHtmls = [];
                            let viewportsHtmls = [];
                            let qualitiesHtmls = [];
                            let tagsHtmls = [];

                            eydea.data.anpr.forEach((anpr, idx) => {
                                const mmr = (Array.isArray(eydea.data.mmr_classification) 
                                    ? eydea.data.mmr_classification[idx] 
                                    : (idx === 0 ? eydea.data.mmr_classification : null)) || {};

                                let compiledText = (anpr.lines && anpr.lines.length > 0)
                                    ? anpr.lines.map(line => line.text).join(' ')
                                    : 'N/A';
                                platesHtmls.push(renderPlate(compiledText));
                                regionsHtmls.push(anpr.plate_type || 'N/A');

                                const confidence = Math.round((anpr.confidence || 0) * 100);
                                confsHtmls.push(confidenceBadge(confidence));

                                let allChars = [];
                                if (anpr.lines && anpr.lines.length > 0) {
                                    anpr.lines.forEach(l => {
                                        if (l.characters_confidences && l.characters_confidences.length > 0) {
                                            l.characters_confidences.forEach(cc => {
                                                allChars.push({
                                                    character: cc.character,
                                                    confidence: Math.round((cc.confidence || 0) * 100)
                                                });
                                            });
                                        } else {
                                            const textStr = l.text || '';
                                            const lineConf = Math.round((l.confidence || 0) * 100);
                                            for (let char of textStr) {
                                                if (char.trim() !== '') {
                                                    allChars.push({
                                                        character: char,
                                                        confidence: lineConf
                                                    });
                                                }
                                            }
                                        }
                                    });
                                }

                                if (allChars.length > 0) {
                                    charsHtmls.push(allChars
                                        .map(c => charChip(c.character, c.confidence))
                                        .join('')
                                    );
                                } else {
                                    charsHtmls.push('N/A');
                                }

                                const makeStr = formatWithConf(mmr.make, mmr.make_confidence);
                                const modelStr = formatWithConf(mmr.model, mmr.model_confidence);
                                let vehicleText = '';
                                if (makeStr && makeStr !== 'N/A') vehicleText += makeStr;
                                if (modelStr && modelStr !== 'N/A') vehicleText += (vehicleText ? ' ' : '') + modelStr;
                                vehiclesHtmls.push(vehicleText || 'N/A');

                                categoriesHtmls.push(formatWithConf(mmr.category, mmr.category_confidence));
                                colorsHtmls.push(formatWithConf(mmr.color, mmr.color_confidence));

                                if (mmr.view) {
                                    const viewVal = mmr.view.toUpperCase();
                                    const viewConf = mmr.view_confidence;
                                    viewportsHtmls.push(formatWithConf(viewVal, viewConf));
                                } else {
                                    viewportsHtmls.push('N/A');
                                }

                                qualitiesHtmls.push('N/A');

                                // Vehicle tags logic (Eydea)
                                let tagsHtml = '';
                                if (mmr.tags) {
                                    const activeTags = [];
                                    for (const [tagKey, tagData] of Object.entries(mmr.tags)) {
                                        if (tagData && (tagData.value === 'yes' || tagData.value === true)) {
                                            const tagLabel = tagKey.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                                            const tagConf = tagData.confidence || tagData.conf;
                                            activeTags.push(formatWithConf(tagLabel, tagConf));
                                        }
                                    }
                                    if (activeTags.length > 0) {
                                        tagsHtml = activeTags.map(t => `<span class="badge badge-light-primary m-1 fs-8">${t}</span>`).join('');
                                    }
                                }
                                tagsHtmls.push(tagsHtml || '<span class="text-muted fs-8">None</span>');
                            });

                            const divider = '<div class="vr mx-3 opacity-20 align-self-stretch" style="border-left: 1px solid rgba(255,255,255,0.15); min-height: 24px;"></div>';
                            cellEydeaPlate.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-2">${platesHtmls.join(divider)}</div>`;
                            cellEydeaRegion.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${regionsHtmls.map(r => `<div>${r}</div>`).join(divider)}</div>`;
                            cellEydeaConf.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${confsHtmls.map(c => `<div>${c}</div>`).join(divider)}</div>`;
                            cellEydeaChars.innerHTML = charsHtmls.join('<hr class="my-2 opacity-15">');
                            cellEydeaVehicle.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${vehiclesHtmls.map(v => `<div>${v}</div>`).join(divider)}</div>`;
                            cellEydeaCategory.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${categoriesHtmls.map(cat => `<div>${cat}</div>`).join(divider)}</div>`;
                            cellEydeaColor.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${colorsHtmls.map(col => `<div>${col}</div>`).join(divider)}</div>`;
                            cellEydeaViewport.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${viewportsHtmls.map(vp => `<div>${vp}</div>`).join(divider)}</div>`;
                            cellEydeaQuality.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${qualitiesHtmls.map(q => `<div>${q}</div>`).join(divider)}</div>`;
                            cellEydeaTags.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${tagsHtmls.map(t => `<div>${t}</div>`).join(divider)}</div>`;

                            cellEydeaTime.innerHTML = latencyCell(eydea.time_ms);

                        } else {
                            cellEydeaPlate.innerHTML = noPlateCell();
                            cellEydeaRegion.textContent = '-';
                            cellEydeaConf.textContent = '-';
                            cellEydeaChars.textContent = '-';
                            cellEydeaVehicle.textContent = '-';
                            cellEydeaCategory.textContent = '-';
                            cellEydeaColor.textContent = '-';
                            cellEydeaViewport.textContent = '-';
                            cellEydeaTags.textContent = '-';
                            cellEydeaQuality.textContent = '-';
                            cellEydeaTime.textContent = '-';
                        }

                    } else {
                        const errMsg = eydea.message || 'Service request returned an error';
                        cellEydeaPlate.innerHTML = failedCell();
                        cellEydeaRegion.textContent = '-';
                        cellEydeaConf.textContent = '-';
                        cellEydeaChars.textContent = '-';
                        cellEydeaVehicle.textContent = '-';
                        cellEydeaCategory.textContent = '-';
                        cellEydeaColor.textContent = '-';
                        cellEydeaViewport.textContent = '-';
                        cellEydeaTags.textContent = '-';
                        cellEydeaQuality.textContent = '-';
                        cellEydeaTime.textContent = '-';
                        partialFailures.push(`Eydea: ${errMsg}`);
                    }
                }

                // 3. Parkonic
                if (results.parkonic) {
                    const parkonic = results.parkonic;
                    let data = parkonic.data;
                    if (typeof data === 'string') {
                        try {
                            data = JSON.parse(data);
                        } catch (e) {
                            console.error('Failed parsing string inside results.parkonic.data', e);
                        }
                    }

                    parkonicJsonCode.textContent = JSON.stringify(data || parkonic, null, 2);

                    if (parkonic.success && data && (data.text || (data.plate_ && data.plate_.length > 0))) {
                        const plate = (data.plate_ && data.plate_.length > 0) ? data.plate_[0] : data;

                        const plateCode = plate.category || '';
                        cellParkonicPlate.innerHTML = renderPlate(plate.text, plateCode);

                        let regionText = plate.cityName || '';
                        if (plate.platetype) regionText += ` (${plate.platetype})`;
                        cellParkonicRegion.textContent = regionText.trim() || 'N/A';

                        const confidence = plate.confidance || plate.confidence || 0;
                        cellParkonicConf.innerHTML = confidenceBadge(confidence);

                        const charsList = plate.plate_detail || plate.characters || [];
                        if (charsList.length > 0) {
                            cellParkonicChars.innerHTML = charsList
                                .map(c => charChip(c.character, c.conf || c.confidence || 0))
                                .join('');
                        } else {
                            cellParkonicChars.textContent = 'N/A';
                        }

                        const makeStr = formatWithConf(plate.make || plate.maker, plate.mmr_make_conf);
                        const modelStr = formatWithConf(plate.model, plate.mmr_model_conf);
                        let vehicleText = '';
                        if (makeStr && makeStr !== 'N/A') vehicleText += makeStr;
                        if (modelStr && modelStr !== 'N/A') vehicleText += (vehicleText ? ' ' : '') + modelStr;
                        cellParkonicVehicle.innerHTML = vehicleText || 'N/A';

                        cellParkonicCategory.innerHTML = formatWithConf(plate.mmr_category || plate.bodytype, plate.mmr_category_conf);
                        
                        let colorText = plate.mmr_color || plate.backcolor || '';
                        let colorHtml = formatWithConf(colorText, plate.mmr_color_conf);
                        if (plate.backcolor && plate.textcolor) {
                            colorHtml += `<br><span class="text-muted fs-8" style="font-size: 0.8rem;">Plate: ${plate.textcolor} on ${plate.backcolor}</span>`;
                        }
                        cellParkonicColor.innerHTML = colorHtml || 'N/A';

                        if (plate.viewpoint) {
                            const viewVal = plate.viewpoint.toUpperCase();
                            const viewConf = plate.viewpoint_conf;
                            cellParkonicViewport.innerHTML = formatWithConf(viewVal, viewConf);
                        } else {
                            cellParkonicViewport.textContent = 'N/A';
                        }

                        // Vehicle tags logic (Parkonic)
                        let tagsHtml = '';
                        if (plate.tags) {
                            const activeTags = [];
                            for (const [tagKey, tagData] of Object.entries(plate.tags)) {
                                if (tagData && (tagData.value === 'yes' || tagData.value === true)) {
                                    const tagLabel = tagKey.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                                    const tagConf = tagData.confidence || tagData.conf;
                                    activeTags.push(formatWithConf(tagLabel, tagConf));
                                }
                            }
                            if (activeTags.length > 0) {
                                tagsHtml = activeTags.map(t => `<span class="badge badge-light-primary m-1 fs-8">${t}</span>`).join('');
                            }
                        }
                        cellParkonicTags.innerHTML = tagsHtml || '<span class="text-muted fs-8">None</span>';
                        cellParkonicQuality.textContent = 'N/A';
                        const timeVal = data.Time_ms || data.time_ms || parkonic.time_ms;
                        cellParkonicTime.innerHTML = latencyCell(timeVal);

                    } else {
                        const errMsg = parkonic.message || 'Plate detection returned 0 results';
                        cellParkonicPlate.innerHTML = failedCell();
                        cellParkonicRegion.textContent = '-';
                        cellParkonicConf.textContent = '-';
                        cellParkonicChars.textContent = '-';
                        cellParkonicVehicle.textContent = '-';
                        cellParkonicCategory.textContent = '-';
                        cellParkonicColor.textContent = '-';
                        cellParkonicViewport.textContent = '-';
                        cellParkonicTags.textContent = '-';
                        cellParkonicQuality.textContent = '-';
                        cellParkonicTime.textContent = '-';
                        partialFailures.push(`Parkonic: ${errMsg}`);
                    }
                }

                // 4. ANPR 2
                if (results.anpr2) {
                    const anpr2 = results.anpr2;
                    let data = anpr2.data;
                    if (typeof data === 'string') {
                        try {
                            data = JSON.parse(data);
                        } catch (e) {
                            console.error('Failed parsing string inside results.anpr2.data', e);
                        }
                    }

                    anpr2JsonCode.textContent = JSON.stringify(data || anpr2, null, 2);

                    const hasVehicles = (data && data.vehicles && data.vehicles.length > 0);
                    const hasPlates = (data && (data.text || (data.plate_ && data.plate_.length > 0)));

                    if (anpr2.success && data && (hasVehicles || hasPlates)) {
                        if (hasVehicles) {
                            let platesHtmls = [];
                            let regionsHtmls = [];
                            let confsHtmls = [];
                            let charsHtmls = [];
                            let vehiclesHtmls = [];
                            let categoriesHtmls = [];
                            let colorsHtmls = [];
                            let viewportsHtmls = [];
                            let qualitiesHtmls = [];

                            data.vehicles.forEach(vehicle => {
                                const ocr = vehicle.ocr || {};
                                const plateCode = ocr.category || '';
                                platesHtmls.push(renderPlate(ocr.number, plateCode));

                                let regionParts = [];
                                if (vehicle.city && vehicle.city.type) {
                                    const cityName = vehicle.city.type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                                    regionParts.push(formatWithConf(cityName, vehicle.city.confidence));
                                }
                                if (vehicle.plate_type && vehicle.plate_type.type) {
                                    const plateType = vehicle.plate_type.type.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ');
                                    regionParts.push(formatWithConf(plateType, vehicle.plate_type.confidence));
                                }
                                regionsHtmls.push(regionParts.length > 0 ? regionParts.join(' / ') : 'N/A');

                                const confidenceVal = ocr.confidence || 0;
                                const confidencePct = Math.round(confidenceVal * 100);
                                confsHtmls.push(confidenceBadge(confidencePct));

                                const charsList = ocr.characters || [];
                                if (charsList.length > 0) {
                                    charsHtmls.push(charsList
                                        .map(c => charChip(c.character, Math.round((c.confidence || 0) * 100)))
                                        .join('')
                                    );
                                } else {
                                    charsHtmls.push('N/A');
                                }

                                if (vehicle.mmr) {
                                    const makeStr = formatWithConf(vehicle.mmr.make, vehicle.mmr.make_confidence);
                                    const modelStr = formatWithConf(vehicle.mmr.model, vehicle.mmr.model_confidence);
                                    let vehicleText = '';
                                    if (makeStr && makeStr !== 'N/A') vehicleText += makeStr;
                                    if (modelStr && modelStr !== 'N/A') vehicleText += (vehicleText ? ' ' : '') + modelStr;
                                    vehiclesHtmls.push(vehicleText || 'N/A');
                                } else {
                                    vehiclesHtmls.push('N/A');
                                }

                                if (vehicle.vehicle_type) {
                                    categoriesHtmls.push(formatWithConf(vehicle.vehicle_type.type, vehicle.vehicle_type.confidence));
                                } else {
                                    categoriesHtmls.push('N/A');
                                }

                                colorsHtmls.push('N/A');

                                if (vehicle.viewpoint) {
                                    const viewVal = vehicle.viewpoint.type.toUpperCase();
                                    const viewConf = vehicle.viewpoint.confidence;
                                    viewportsHtmls.push(formatWithConf(viewVal, viewConf));
                                } else {
                                    viewportsHtmls.push('N/A');
                                }

                                if (vehicle.cropped_Quality) {
                                    qualitiesHtmls.push(formatWithConf(vehicle.cropped_Quality.type, vehicle.cropped_Quality.confidence));
                                } else {
                                    qualitiesHtmls.push('N/A');
                                }
                            });

                            const divider = '<div class="vr mx-3 opacity-20 align-self-stretch" style="border-left: 1px solid rgba(255,255,255,0.15); min-height: 24px;"></div>';
                            cellAnpr2Plate.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-2">${platesHtmls.join(divider)}</div>`;
                            cellAnpr2Region.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${regionsHtmls.map(r => `<div>${r}</div>`).join(divider)}</div>`;
                            cellAnpr2Conf.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${confsHtmls.map(c => `<div>${c}</div>`).join(divider)}</div>`;
                            cellAnpr2Chars.innerHTML = charsHtmls.join('<hr class="my-2 opacity-15">');
                            cellAnpr2Vehicle.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${vehiclesHtmls.map(v => `<div>${v}</div>`).join(divider)}</div>`;
                            cellAnpr2Category.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${categoriesHtmls.map(cat => `<div>${cat}</div>`).join(divider)}</div>`;
                            cellAnpr2Color.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${colorsHtmls.map(col => `<div>${col}</div>`).join(divider)}</div>`;
                            cellAnpr2Viewport.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${viewportsHtmls.map(vp => `<div>${vp}</div>`).join(divider)}</div>`;
                            cellAnpr2Quality.innerHTML = `<div class="d-flex flex-row align-items-center justify-content-start flex-wrap gap-3">${qualitiesHtmls.map(q => `<div>${q}</div>`).join(divider)}</div>`;

                            cellAnpr2Tags.innerHTML = '<span class="text-muted fs-8">None</span>';

                            const timeVal = data.processing_time_ms || anpr2.time_ms;
                            cellAnpr2Time.innerHTML = latencyCell(Math.round(timeVal));

                        } else {
                            // Fallback to legacy Parkonic payload structure if returned
                            const plate = (data.plate_ && data.plate_.length > 0) ? data.plate_[0] : data;

                            const plateCode = plate.category || '';
                            cellAnpr2Plate.innerHTML = renderPlate(plate.text, plateCode);

                            let regionText = plate.cityName || '';
                            if (plate.platetype) regionText += ` (${plate.platetype})`;
                            cellAnpr2Region.textContent = regionText.trim() || 'N/A';

                            const confidence = plate.confidance || plate.confidence || 0;
                            cellAnpr2Conf.innerHTML = confidenceBadge(confidence);

                            const charsList = plate.plate_detail || plate.characters || [];
                            if (charsList.length > 0) {
                                cellAnpr2Chars.innerHTML = charsList
                                    .map(c => charChip(c.character, c.conf || c.confidence || 0))
                                    .join('');
                            } else {
                                cellAnpr2Chars.textContent = 'N/A';
                            }

                            const makeStr = formatWithConf(plate.make || plate.maker, plate.mmr_make_conf);
                            const modelStr = formatWithConf(plate.model, plate.mmr_model_conf);
                            let vehicleText = '';
                            if (makeStr && makeStr !== 'N/A') vehicleText += makeStr;
                            if (modelStr && modelStr !== 'N/A') vehicleText += (vehicleText ? ' ' : '') + modelStr;
                            cellAnpr2Vehicle.innerHTML = vehicleText || 'N/A';

                            cellAnpr2Category.innerHTML = formatWithConf(plate.mmr_category || plate.bodytype, plate.mmr_category_conf);
                            
                            let colorText = plate.mmr_color || plate.backcolor || '';
                            let colorHtml = formatWithConf(colorText, plate.mmr_color_conf);
                            if (plate.backcolor && plate.textcolor) {
                                colorHtml += `<br><span class="text-muted fs-8" style="font-size: 0.8rem;">Plate: ${plate.textcolor} on ${plate.backcolor}</span>`;
                            }
                            cellAnpr2Color.innerHTML = colorHtml || 'N/A';

                            if (plate.viewpoint) {
                                const viewVal = plate.viewpoint.toUpperCase();
                                const viewConf = plate.viewpoint_conf;
                                cellAnpr2Viewport.innerHTML = formatWithConf(viewVal, viewConf);
                            } else {
                                cellAnpr2Viewport.textContent = 'N/A';
                            }

                            let tagsHtml = '';
                            if (plate.tags) {
                                const activeTags = [];
                                for (const [tagKey, tagData] of Object.entries(plate.tags)) {
                                    if (tagData && (tagData.value === 'yes' || tagData.value === true)) {
                                        const tagLabel = tagKey.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
                                        const tagConf = tagData.confidence || tagData.conf;
                                        activeTags.push(formatWithConf(tagLabel, tagConf));
                                    }
                                }
                                if (activeTags.length > 0) {
                                    tagsHtml = activeTags.map(t => `<span class="badge badge-light-primary m-1 fs-8">${t}</span>`).join('');
                                }
                            }
                            cellAnpr2Tags.innerHTML = tagsHtml || '<span class="text-muted fs-8">None</span>';
                            cellAnpr2Quality.textContent = 'N/A';

                            const timeVal = data.Time_ms || data.time_ms || anpr2.time_ms;
                            cellAnpr2Time.innerHTML = latencyCell(timeVal);
                        }

                    } else {
                        const errMsg = anpr2.message || 'Plate detection returned 0 results';
                        cellAnpr2Plate.innerHTML = failedCell();
                        cellAnpr2Region.textContent = '-';
                        cellAnpr2Conf.textContent = '-';
                        cellAnpr2Chars.textContent = '-';
                        cellAnpr2Vehicle.textContent = '-';
                        cellAnpr2Category.textContent = '-';
                        cellAnpr2Color.textContent = '-';
                        cellAnpr2Viewport.textContent = '-';
                        cellAnpr2Tags.textContent = '-';
                        cellAnpr2Quality.textContent = '-';
                        cellAnpr2Time.textContent = '-';
                        partialFailures.push(`Parkonic Engine: ${errMsg}`);
                    }
                }

                if ((engine === 'all' || engine === 'both') && partialFailures.length > 0) {
                    partialWarningText.innerHTML = `<strong>Partial results:</strong> ${partialFailures.join(' | ')}`;
                    partialWarning.classList.remove('d-none');
                } else {
                    partialWarning.classList.add('d-none');
                }

                resultsContent.classList.remove('d-none');
            }

            closeControlCenterBtn.addEventListener('click', () => {
                controlCenterColumn.classList.add('d-none');
                resultsColumn.classList.remove('col-lg-7');
                resultsColumn.classList.add('col-lg-12');
                openControlCenterBtn.classList.remove('d-none');
                window.dispatchEvent(new Event('resize'));
            });

            openControlCenterBtn.addEventListener('click', () => {
                controlCenterColumn.classList.remove('d-none');
                resultsColumn.classList.remove('col-lg-12');
                resultsColumn.classList.add('col-lg-7');
                openControlCenterBtn.classList.add('d-none');
                window.dispatchEvent(new Event('resize'));
            });
        });
    </script>
@endsection