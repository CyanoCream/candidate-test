<x-layout>
    <x-slot:title>{{ $supplier->name }} Details</x-slot:title>

    <div class="mb-4">
        <a href="{{ route('suppliers.index') }}" class="text-decoration-none">&larr; Back to Suppliers</a>
    </div>

    <!-- Overview Card (Section 1) -->
    <div class="card shadow-sm border-0 mb-4 position-relative">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="h3 fw-bold mb-1">{{ $supplier->name }}</h1>
                    <p class="text-muted small mb-0">Supplier ID: {{ $supplier->id }}</p>
                </div>
                <!-- Top Right Edit Button -->
                <button class="btn btn-outline-primary btn-sm rounded-pill px-3">
                    <i class="bi bi-pencil"></i> Edit Supplier
                </button>
            </div>
            <div class="mt-3 text-muted small">
                Created {{ $supplier->created_at->format('M d, Y') }} &middot; Total Layups: {{ $layups->count() }}
            </div>
        </div>
    </div>

    <div x-data="supplierPageData()">
        <!-- Action Buttons (Section 2) -->
        <div class="d-flex align-items-center gap-2 mb-4">
            <button @click="openImportModal = true" class="btn btn-primary">
                <i class="bi bi-upload"></i> Import
            </button>
            <div class="dropdown position-relative" x-data="{ exportOpen: false }" @click.outside="exportOpen = false">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" @click="exportOpen = !exportOpen">
                    <i class="bi bi-download"></i> Export
                </button>
                <ul class="dropdown-menu" :class="{ 'show': exportOpen }" x-show="exportOpen" style="position: absolute; display: none;" :style="{ display: exportOpen ? 'block' : 'none' }">
                    <li><a class="dropdown-item" href="{{ route('suppliers.export', ['supplier' => $supplier->id, 'type' => 'json']) }}">Export as JSON</a></li>
                    <li><a class="dropdown-item" href="{{ route('suppliers.export', ['supplier' => $supplier->id, 'type' => 'csv']) }}">Export as CSV</a></li>
                </ul>
            </div>
            <button class="btn btn-outline-dark ms-auto">
                <i class="bi bi-plus-lg"></i> Add Layup
            </button>
        </div>

        <!-- Section 3/4: Associative Layups Table -->
        <div class="card shadow-sm border-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Yup ID</th>
                            <th>Name</th>
                            <th>Thickness</th>
                            <th>Ply Count</th>
                            <th>Species/Grade</th>
                            <th>Revision</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="layups-tbody">
                        @forelse($layups as $layup)
                        <tr>
                            <td class="text-muted small">{{ $layup->id }}</td>
                            <td class="fw-bold">{{ $layup->name }}</td>
                            <td>{{ $layup->layers->sum('thickness') }}mm</td>
                            <td>{{ $layup->layers->count() }}</td>
                            <td>{{ $layup->species_grade ?? 'Mixed' }}</td>
                            <td>{{ $layup->revision ?? '1.0' }}</td>
                            <td>
                                <span class="badge {{ ($layup->status ?? 'active') === 'active' ? 'bg-success' : 'bg-secondary' }} bg-opacity-25 text-dark">
                                    {{ ucfirst($layup->status ?? 'Active') }}
                                </span>
                            </td>
                            <td>
                                <a href="/layups/{{ $layup->id }}" class="btn btn-sm btn-outline-primary">Builder</a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                No associated layups found. Click "Import" or "Add Layup" to get started.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Toast Container -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060" x-data="{ toasts: [] }" @show-toast.window="toasts.push($event.detail); setTimeout(() => { toasts.shift() }, 5000)">
            <template x-for="(toast, index) in toasts" :key="index">
                <div class="toast show border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" x-transition.opacity>
                    <div class="toast-header border-bottom-0 text-white" :class="toast.type === 'success' ? 'bg-success' : 'bg-primary'">
                        <i class="me-2 bi" :class="toast.type === 'success' ? 'bi-check-circle-fill' : 'bi-info-circle-fill'"></i>
                        <strong class="me-auto" x-text="toast.title || 'Notification'"></strong>
                        <button type="button" class="btn-close btn-close-white" @click="toasts.splice(index, 1)"></button>
                    </div>
                    <div class="toast-body bg-white text-dark rounded-bottom" x-text="toast.message"></div>
                </div>
            </template>
        </div>

        <!-- Modals Container -->
        
        <!-- Layar Pertama: Import & Deteksi Awal -->
        <div class="modal fade" tabindex="-1" :class="{ 'show d-block': openImportModal }" style="background: rgba(0,0,0,0.5);" x-show="openImportModal" x-transition>
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form @submit.prevent="submitImport" enctype="multipart/form-data">
                        <div class="modal-header border-bottom-0 pb-0">
                            <h5 class="modal-title fw-bold">Import Data</h5>
                            <button type="button" class="btn-close" @click="openImportModal = false"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted small">Upload Layup definitions. Supported formats: .csv, .json (max 10MB)</p>
                            
                            <!-- Download Templates -->
                            <div class="mb-4 bg-light p-3 rounded border">
                                <span class="d-block mb-2 fw-semibold" style="font-size: 0.9rem;">Download Templates:</span>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('suppliers.download_template', ['type'=>'csv']) }}" class="btn btn-sm btn-outline-secondary">Download CSV Template</a>
                                    <a href="{{ route('suppliers.download_template', ['type'=>'json']) }}" class="btn btn-sm btn-outline-secondary">Download JSON Template</a>
                                </div>
                            </div>

                            <div class="row g-4">
                                <div class="col-md-7">
                                    <label 
                                        @dragover.prevent="dragging = true" 
                                        @dragleave.prevent="dragging = false" 
                                        @drop.prevent="dragging = false; handleDrop($event)"
                                        class="d-flex flex-column justify-content-center align-items-center w-100 p-5 border rounded border-2 text-center"
                                        :class="dragging ? 'border-primary bg-primary bg-opacity-10' : 'border-secondary bg-light'"
                                        style="border-style: dashed !important; cursor:pointer;"
                                    >
                                        <i class="bi bi-cloud-arrow-up fs-2 text-primary mb-2"></i>
                                        <h6 class="text-primary mb-1" x-text="file ? file.name : 'Click or Drag & Drop File'"></h6>
                                        <small class="text-muted" x-show="!file">CSV or JSON</small>
                                        <input type="file" accept=".csv,.json" required class="d-none" x-ref="fileInput" @change="handleFileSelect($event)">
                                    </label>
                                </div>
                                <div class="col-md-5 d-flex flex-column">
                                    <div class="mb-3">
                                        <label class="form-label form-label-sm fw-bold">Conflict Resolution Strategy</label>
                                        <select x-model="importParams.strategy" class="form-select form-select-sm">
                                            <option value="manual">Manual Resolution (Recommended)</option>
                                            <option value="skip">Skip Conflicts</option>
                                            <option value="overwrite">Overwrite Existing</option>
                                        </select>
                                    </div>
                                    <div class="mb-3 form-check form-switch mt-auto bg-light border p-2 rounded">
                                        <input class="form-check-input ms-0" type="checkbox" role="switch" id="dryRunToggle" x-model="importParams.dry_run">
                                        <label class="form-check-label ms-2" for="dryRunToggle">
                                            <i class="bi bi-eyedropper text-info"></i> Dry Run Mode
                                        </label>
                                        <div class="text-muted mt-1" style="font-size: 0.75rem; margin-left: 36px;">
                                            Simulate import and check for errors without saving to the database.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="alert alert-danger mt-3 mb-0" x-show="importError" x-text="importError"></div>
                        </div>
                        <div class="modal-footer border-top-0 pt-0">
                            <button type="button" class="btn btn-light" @click="openImportModal = false">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4" :disabled="loading || !file">
                                <span x-show="!loading">Start Import</span>
                                <span x-show="loading">Processing...</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Layar Kedua: Resolusi Konflik -->
        <div class="modal fade" tabindex="-1" :class="{ 'show d-block': openConflictModal }" style="background: rgba(0,0,0,0.85);" x-show="openConflictModal" x-transition>
            <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content border-0 shadow-lg" style="height: 90vh;">
                    <div class="modal-header bg-dark text-white border-bottom-0">
                        <div>
                            <h5 class="modal-title fw-bold">Conflict Resolution Audit</h5>
                            <p class="mb-0 small text-white-50" x-show="importParams.dry_run">Dry Run Mode: No changes will be saved.</p>
                        </div>
                        <button type="button" class="btn-close btn-close-white" @click="cancelConflictResolution()"></button>
                    </div>
                    <div class="modal-body p-0 d-flex flex-column" style="background: #f8f9fa;">
                        
                        <div class="row g-0 flex-grow-1 overflow-hidden h-100">
                            <!-- Left Sidebar (Sidebar Konflik) -->
                            <div class="col-md-3 border-end bg-white d-flex flex-column h-100">
                                <div class="p-3 border-bottom bg-light d-flex justify-content-between align-items-center">
                                    <h6 class="mb-0 fw-bold">Conflicting Items</h6>
                                    <span class="badge bg-danger rounded-pill" x-text="conflicts.length"></span>
                                </div>
                                <div class="overflow-auto flex-grow-1 p-2">
                                    <template x-if="conflicts.length === 0">
                                        <div class="text-center p-4 text-muted small">No conflicts detected.</div>
                                    </template>
                                    <div class="list-group list-group-flush rounded-0">
                                        <template x-for="(conflict, index) in conflicts" :key="index">
                                            <button 
                                                @click="activeConflict = index"
                                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-1 rounded border-0 border-start border-4"
                                                :class="{
                                                    'border-primary bg-primary bg-opacity-10': activeConflict === index,
                                                    'border-transparent': activeConflict !== index,
                                                    'bg-white': activeConflict !== index
                                                }"
                                            >
                                                <div class="text-truncate">
                                                    <span class="d-block text-truncate fw-bold" style="font-size: 0.9rem;" x-text="conflict.layupName"></span>
                                                    <small class="text-danger" style="font-size: 0.75rem;">Conflict in Layer <span x-text="conflict.incoming.layer_order"></span> <br> (Thickness / Dimension mismatch)</small>
                                                </div>
                                                <div x-show="isResolved(index)">
                                                    <i class="bi bi-check-circle-fill text-success"></i>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                
                            <!-- Main Content (Perbandingan Side-by-Side) -->
                            <div class="col-md-9 d-flex flex-column h-100 bg-light">
                                <template x-if="conflicts.length > 0 && current">
                                    <div class="flex-grow-1 d-flex flex-column h-100">
                                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white shadow-sm z-index-1">
                                            <h5 class="mb-0 fw-bold">
                                                Layer <span x-text="current.incoming.layer_order"></span> &middot; <span x-text="current.layupName" class="text-primary"></span>
                                            </h5>
                                            <div class="d-flex align-items-center gap-3">
                                                <span class="badge" :class="isResolved(activeConflict) ? 'bg-success' : 'bg-warning text-dark'" x-text="isResolved(activeConflict) ? 'Resolved' : 'Pending Resolution'"></span>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-secondary" :disabled="activeConflict === 0" @click="activeConflict--"><i class="bi bi-chevron-left"></i> Prev</button>
                                                    <button class="btn btn-outline-secondary" :disabled="activeConflict === conflicts.length - 1" @click="activeConflict++">Next <i class="bi bi-chevron-right"></i></button>
                                                </div>
                                            </div>
                                        </div>
                
                                        <div class="p-4 flex-grow-1 overflow-auto">
                                            <div class="row g-4 h-100">
                                                <!-- Existing -->
                                                <div class="col-lg-6 h-100">
                                                    <div class="card h-100 shadow-sm border-0 position-relative transition-all" :class="getResolutionValue() === 'existing' ? 'border-primary border-4 overlay-active' : ''">
                                                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 text-center">
                                                            <div class="badge bg-secondary mb-2 rounded-pill px-3 py-2">Existing Version (Database)</div>
                                                            <p class="text-muted small mb-0">Currently saved data.</p>
                                                        </div>
                                                        <div class="card-body mt-3">
                                                            <table class="table table-bordered mb-0">
                                                                <thead class="table-light">
                                                                    <tr><th>Property</th><th>Value</th></tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td class="text-muted w-50">Thickness</td>
                                                                        <td class="fw-bold fs-5" :class="current.existing.thickness != current.incoming.thickness ? 'text-danger bg-danger bg-opacity-10' : ''"><span x-text="current.existing.thickness"></span>mm</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-muted w-50">Width</td>
                                                                        <td class="fw-bold fs-5" :class="current.existing.width != current.incoming.width ? 'text-danger bg-danger bg-opacity-10' : ''"><span x-text="current.existing.width"></span>mm</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-muted w-50">Angle</td>
                                                                        <td class="fw-bold fs-5" :class="current.existing.angle != current.incoming.angle ? 'text-danger bg-danger bg-opacity-10' : ''"><span x-text="current.existing.angle"></span>&deg;</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="card-footer bg-white border-top-0 text-center pb-4">
                                                            <button @click="resolveCurrent('existing')" class="btn btn-outline-primary px-4 py-2 w-100 fw-bold">Keep Existing</button>
                                                        </div>
                                                    </div>
                                                </div>
                
                                                <!-- Incoming -->
                                                <div class="col-lg-6 h-100">
                                                    <div class="card h-100 shadow-sm border-0 position-relative transition-all" :class="getResolutionValue() === 'incoming' ? 'border-success border-4' : ''">
                                                        <div class="card-header bg-white border-bottom-0 pt-4 pb-0 text-center">
                                                            <div class="badge bg-warning text-dark mb-2 rounded-pill px-3 py-2">Importing Version (File)</div>
                                                            <p class="text-muted small mb-0">New values from file.</p>
                                                        </div>
                                                        <div class="card-body mt-3">
                                                            <table class="table table-bordered mb-0">
                                                                <thead class="table-light">
                                                                    <tr><th>Property</th><th>Value</th></tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td class="text-muted w-50">Thickness</td>
                                                                        <td class="fw-bold fs-5" :class="current.existing.thickness != current.incoming.thickness ? 'text-danger bg-danger bg-opacity-10 text-decoration-underline' : ''"><span x-text="current.incoming.thickness"></span>mm</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-muted w-50">Width</td>
                                                                        <td class="fw-bold fs-5" :class="current.existing.width != current.incoming.width ? 'text-danger bg-danger bg-opacity-10 text-decoration-underline' : ''"><span x-text="current.incoming.width"></span>mm</td>
                                                                    </tr>
                                                                    <tr>
                                                                        <td class="text-muted w-50">Angle</td>
                                                                        <td class="fw-bold fs-5" :class="current.existing.angle != current.incoming.angle ? 'text-danger bg-danger bg-opacity-10 text-decoration-underline' : ''"><span x-text="current.incoming.angle"></span>&deg;</td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="card-footer bg-white border-top-0 text-center pb-4">
                                                            <button @click="resolveCurrent('incoming')" class="btn btn-outline-success px-4 py-2 w-100 fw-bold">Accept New</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                
                                <template x-if="conflicts.length === 0">
                                    <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center p-5 text-center bg-white h-100">
                                        <div class="display-1 text-success mb-4"><i class="bi bi-check-circle"></i></div>
                                        <h3 class="fw-bold">Ready to Finalize</h3>
                                        <p class="text-muted fs-5">All conflicts have been resolved or no conflicts were detected.</p>
                                    </div>
                                </template>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-white d-flex justify-content-between align-items-center border-top">
                        <button type="button" class="btn btn-outline-danger" @click="cancelConflictResolution()">Cancel Import</button>
                        <div>
                            <span class="text-muted small me-3 fw-bold">
                                <span x-text="Object.keys(resolutions).length"></span> / <span x-text="conflicts.length"></span> decisions made
                            </span>
                            <button type="button" class="btn btn-primary px-4 py-2 fw-bold" :disabled="!allResolved() || submitLoading" @click="submitFinalResolutions()">
                                <template x-if="importParams.dry_run">
                                    <span>Finish Simulation</span>
                                </template>
                                <template x-if="!importParams.dry_run">
                                    <span>Confirm Import <i class="bi bi-check2"></i></span>
                                </template>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('supplierPageData', () => ({
                openImportModal: false,
                openConflictModal: false,
                dragging: false,
                file: null,
                loading: false,
                submitLoading: false,
                importError: null,
                
                importParams: {
                    strategy: 'manual',
                    dry_run: false
                },
                
                importId: null,
                payload: [],
                conflicts: [],
                activeConflict: 0,
                resolutions: {},

                handleFileSelect(e) {
                    this.file = e.target.files[0];
                    this.importError = null;
                },
                
                triggerToast(title, msg, type = 'success') {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: title, message: msg, type: type } }));
                },

                async refreshTable() {
                    try {
                        const response = await fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        const html = await response.text();
                        const doc = new DOMParser().parseFromString(html, 'text/html');
                        document.querySelector('#layups-tbody').innerHTML = doc.querySelector('#layups-tbody').innerHTML;
                    } catch (e) {
                        console.error('Failed to update table natively', e);
                    }
                },

                handleDrop(e) {
                    if (e.dataTransfer.files.length) {
                        this.file = e.dataTransfer.files[0];
                        this.$refs.fileInput.files = e.dataTransfer.files;
                        this.importError = null;
                    }
                },

                async submitImport() {
                    if (!this.file) return;
                    this.loading = true;
                    this.importError = null;

                    const formData = new FormData();
                    formData.append('import_file', this.file);
                    formData.append('strategy', this.importParams.strategy);
                    if(this.importParams.dry_run) {
                        formData.append('dry_run', '1');
                    }
                    formData.append('_token', '{{ csrf_token() }}');

                    try {
                        const response = await fetch('{{ route('suppliers.import', $supplier->id) }}', {
                            method: 'POST',
                            headers: { 'Accept': 'application/json' },
                            body: formData
                        });
                        
                        const result = await response.json();
                        
                        if (!response.ok) throw new Error(result.message || 'Import failed');

                        if (result.is_manual) {
                            // Setup conflicts
                            this.importId = result.import_id;
                            this.payload = result.payload;
                            this.buildConflictsList();
                            
                            this.openImportModal = false;
                            this.openConflictModal = true;
                        } else {
                            // Non-manual or dry run handled server side simply
                            this.openImportModal = false;
                            if (this.importParams.dry_run) {
                                this.triggerToast("Simulation Complete", result.message || "Data visually verified without DB changes.", "info");
                            } else {
                                await this.refreshTable();
                                this.triggerToast("Import Successful", result.message || "All layout data synced correctly.");
                            }
                        }

                    } catch (err) {
                        this.importError = err.message;
                    } finally {
                        this.loading = false;
                    }
                },

                buildConflictsList() {
                    this.conflicts = [];
                    this.resolutions = {};
                    this.activeConflict = 0;
                    
                    this.payload.forEach((layup, lIdx) => {
                        if (!layup.layers) return;
                        layup.layers.forEach((layer, layerIdx) => {
                            if (layer.status === 'conflict') {
                                this.conflicts.push({
                                    layupName: layup.name,
                                    lIdx: lIdx,
                                    layerIdx: layerIdx,
                                    incoming: layer.incoming,
                                    existing: layer.existing
                                });
                            }
                        });
                    });
                },

                get current() {
                    return this.conflicts[this.activeConflict] || null;
                },
                
                isResolved(index) {
                    if(!this.conflicts[index]) return false;
                    const key = `${this.conflicts[index].lIdx}_${this.conflicts[index].layerIdx}`;
                    return this.resolutions[key] !== undefined;
                },

                getResolutionValue() {
                    if(!this.current) return null;
                    const key = `${this.current.lIdx}_${this.current.layerIdx}`;
                    return this.resolutions[key];
                },

                resolveCurrent(choice) {
                    if (!this.current) return;
                    const key = `${this.current.lIdx}_${this.current.layerIdx}`;
                    this.resolutions[key] = choice;
                    
                    // Auto advance
                    if (this.activeConflict < this.conflicts.length - 1) {
                        setTimeout(() => this.activeConflict++, 200);
                    }
                },

                allResolved() {
                    return Object.keys(this.resolutions).length === this.conflicts.length;
                },

                cancelConflictResolution() {
                    if(confirm("Are you sure you want to cancel the import? All progress will be lost.")) {
                        this.triggerToast("Import Cancelled", "Operation terminated by user.", "info");
                        this.openConflictModal = false;
                        this.importId = null;
                        this.conflicts = [];
                        this.file = null;
                        if(this.$refs.fileInput) this.$refs.fileInput.value = '';
                    }
                },

                async submitFinalResolutions() {
                    if (this.importParams.dry_run) {
                        this.triggerToast("Simulation Review Complete", "No modifications were written to backend.", "info");
                        this.openConflictModal = false;
                        return;
                    }

                    this.submitLoading = true;
                    try {
                        const response = await fetch(`/import/conflicts/${this.importId}`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ resolutions: this.resolutions })
                        });
                        
                        const result = await response.json();
                        if (response.ok) {
                            this.openConflictModal = false;
                            await this.refreshTable();
                            this.triggerToast("Resolution Applied", result.message || "Manual resolutions synchronized smoothly.");
                        } else {
                            throw new Error(result.message || 'Error resolving conflicts');
                        }
                    } catch (err) {
                        this.triggerToast("Error", err.message, "error");
                    } finally {
                        this.submitLoading = false;
                    }
                }
            }));
        });
    </script>
    <style>
        .transition-all { transition: all 0.2s ease-in-out; }
    </style>
    @endpush
</x-layout>
