<x-layout>
    <x-slot:title>Layup Builder: {{ $layup->name }}</x-slot:title>

    <div class="mb-4">
        <a href="{{ route('suppliers.show', $layup->supplier_id) }}" class="text-decoration-none">&larr; Back to {{ $layup->supplier->name }}</a>
    </div>

    <!-- Alpine component -->
    <div x-data="layerBuilder()">
        
        <!-- Section 1: Komponen Identitas (Header Area) -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-5 border-end">
                        <div class="d-flex align-items-center gap-3 mb-2">
                            <h3 class="fw-bold mb-0">{{ $layup->name }}</h3>
                            <span class="badge {{ ($layup->status ?? 'active') === 'active' ? 'bg-success' : 'bg-secondary' }} bg-opacity-25 text-dark rounded-pill">
                                {{ ucfirst($layup->status ?? 'Active') }}
                            </span>
                        </div>
                        <p class="text-muted small mb-0"><strong>Layup ID:</strong> {{ $layup->id }}</p>
                        <p class="text-muted small mb-0"><strong>Description:</strong> {{ $layup->description ?? 'No description provided.' }}</p>
                    </div>
                    <div class="col-md-3 border-end px-4">
                        <p class="text-muted small mb-1"><strong>Created By:</strong> Engineering Dept.</p>
                        <p class="text-muted small mb-1"><strong>Revision:</strong> {{ $layup->revision ?? '1.0' }}</p>
                        <p class="text-muted small mb-0"><strong>Last Modified:</strong> {{ $layup->updated_at->format('d M Y, H:i') }}</p>
                    </div>
                    <div class="col-md-4 px-4">
                        <div class="d-flex justify-content-around">
                            <div class="text-center">
                                <span class="d-block text-muted small">Total Thickness</span>
                                <h3 class="mb-0 fw-bold text-primary"><span x-text="totalThickness"></span><small class="fs-6">mm</small></h3>
                            </div>
                            <div class="text-center">
                                <span class="d-block text-muted small">Total Layers</span>
                                <h3 class="mb-0 fw-bold text-dark" x-text="layers.length"></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Left: Editable Table -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center pt-3 pb-3">
                        <div>
                            <h5 class="mb-0 fw-bold">Layer Composition</h5>
                            <p class="text-muted small mb-0">Edit variables for production composition.</p>
                        </div>
                        <button @click="save()" :disabled="saving" class="btn btn-primary px-4 fw-bold">
                            <span x-show="!saving"><i class="bi bi-save"></i> Save Changes</span>
                            <span x-show="saving">Saving...</span>
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 60px;" class="text-center">Order</th>
                                    <th>Thickness (mm)</th>
                                    <th>Width (mm)</th>
                                    <th>Angle (&deg;)</th>
                                    <th>Grade</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(layer, index) in layers" :key="index">
                                    <tr :class="{'table-warning bg-opacity-25': layer.is_new}">
                                        <td class="text-center fw-bold">L-<span x-text="index + 1"></span></td>
                                        <td>
                                            <input type="number" step="0.01" x-model.number="layer.thickness" class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <input type="number" step="1" x-model.number="layer.width" class="form-control form-control-sm">
                                        </td>
                                        <td>
                                            <select x-model.number="layer.angle" class="form-select form-select-sm">
                                                <option value="0">0&deg; (Longitudinal)</option>
                                                <option value="90">90&deg; (Transversal)</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select x-model="layer.grade" class="form-select form-select-sm">
                                                <option value="C16">C16</option>
                                                <option value="C24">C24</option>
                                                <option value="Mixed">Mixed</option>
                                            </select>
                                        </td>
                                        <td class="text-end text-nowrap">
                                            <div class="btn-group btn-group-sm">
                                                <button @click="moveUp(index)" :disabled="index === 0" class="btn btn-outline-secondary" title="Move Up"><i class="bi bi-arrow-up"></i></button>
                                                <button @click="moveDown(index)" :disabled="index === layers.length - 1" class="btn btn-outline-secondary" title="Move Down"><i class="bi bi-arrow-down"></i></button>
                                            </div>
                                            <button @click="removeLayer(index)" class="btn btn-sm btn-outline-danger ms-2" title="Delete Layer"><i class="bi bi-trash"></i></button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="layers.length === 0" x-cloak>
                                    <td colspan="6" class="text-center text-muted py-5">
                                        No layers defined. Click "Add New Layer" to build the composition.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="card-footer bg-light text-center py-3 border-top-0">
                        <button @click="addLayer()" type="button" class="btn btn-outline-dark btn-sm px-4 fw-bold" style="border-style: dashed;">
                            <i class="bi bi-plus-lg"></i> Add New Layer
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right: Visualization -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 sticky-top" style="top: 2rem;">
                    <div class="card-header bg-white pt-3 pb-3">
                        <h5 class="mb-0 fw-bold">Stack Visualization</h5>
                    </div>
                    
                    <div class="card-body">
                        <!-- CSS Stack Visualization -->
                        <div class="position-relative w-100 bg-light border border-2 border-secondary rounded overflow-hidden d-flex flex-column align-items-center justify-content-center" style="min-height: 350px;">
                            <div class="position-absolute top-50 start-50 translate-middle pointer-event-none" x-show="layers.length === 0">
                                <span class="text-muted small fst-italic">Empty stack</span>
                            </div>
                            
                            <div class="w-100 d-flex flex-column px-4 gap-1 py-4 h-100 justify-content-center">
                                <template x-for="(layer, index) in layers" :key="index">
                                    <div class="w-100 rounded d-flex align-items-center justify-content-center text-white small fw-bold shadow-sm border border-light"
                                        :style="`height: ${Math.max(20, layer.thickness * 2.5)}px; background-color: ${layer.angle === 90 ? '#e67e22' : '#d35400'}; transition: all 0.3s ease;`"
                                        :title="`Grade: ${layer.grade}, Angle: ${layer.angle}°, Width: ${layer.width}mm`"
                                        style="cursor: help;"
                                    >
                                        <span x-text="layer.thickness + 'mm'"></span><span class="ms-1" style="font-size: 0.65rem; opacity: 0.8;" x-text="'(' + layer.angle + '°)'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="text-center mt-3 small text-muted">
                            <span class="d-inline-block me-3"><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#d35400;"></span> 0&deg; (Longitudinal)</span>
                            <span class="d-inline-block"><span class="d-inline-block rounded-circle me-1" style="width:10px;height:10px;background:#e67e22;"></span> 90&deg; (Transversal)</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Toast Container -->
        <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1060" @show-toast.window="toasts.push($event.detail); setTimeout(() => { toasts.shift() }, 3000)">
            <template x-for="(toast, index) in toasts" :key="index">
                <div class="toast show border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" x-transition.opacity>
                    <div class="toast-header border-bottom-0 text-white" :class="toast.type === 'error' ? 'bg-danger' : 'bg-success'">
                        <i class="me-2 bi" :class="toast.type === 'error' ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'"></i>
                        <strong class="me-auto" x-text="toast.title"></strong>
                        <button type="button" class="btn-close btn-close-white" @click="toasts.splice(index, 1)"></button>
                    </div>
                    <div class="toast-body bg-white text-dark rounded-bottom" x-text="toast.message"></div>
                </div>
            </template>
        </div>

    </div>

    @push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('layerBuilder', () => ({
                layers: {!! json_encode($layers->map(function($l) {
                            return [
                                'id' => $l->id,
                                'layer_order' => $l->layer_order,
                                'thickness' => (float)$l->thickness,
                                'width' => (float)$l->width,
                                'angle' => (float)$l->angle,
                                'grade' => $l->grade ?? 'C24',
                                'is_new' => false
                            ];
                        })->values()) !!},
                saving: false,
                toasts: [],
                
                get totalThickness() {
                    return this.layers.reduce((sum, layer) => sum + (parseFloat(layer.thickness) || 0), 0).toFixed(1);
                },

                triggerToast(title, msg, type = 'success') {
                    window.dispatchEvent(new CustomEvent('show-toast', { detail: { title: title, message: msg, type: type } }));
                },

                addLayer() {
                    // Default values often alternate angle 0/90
                    let nextAngle = 0;
                    if(this.layers.length > 0) {
                        nextAngle = this.layers[this.layers.length - 1].angle === 0 ? 90 : 0;
                    }
                    
                    this.layers.push({
                        id: null,
                        layer_order: this.layers.length + 1,
                        thickness: 20.00,
                        width: 1200,
                        angle: nextAngle,
                        grade: 'C24',
                        is_new: true
                    });
                },

                removeLayer(index) {
                    if(confirm("Are you sure you want to remove this layer?")) {
                        this.layers.splice(index, 1);
                    }
                },

                moveUp(index) {
                    if (index > 0) {
                        const temp = this.layers[index - 1];
                        this.layers[index - 1] = this.layers[index];
                        this.layers[index] = temp;
                    }
                },

                moveDown(index) {
                    if (index < this.layers.length - 1) {
                        const temp = this.layers[index + 1];
                        this.layers[index + 1] = this.layers[index];
                        this.layers[index] = temp;
                    }
                },

                async save() {
                    this.saving = true;
                    
                    try {
                        const response = await fetch('{{ route('layups.update', $layup->id) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ layers: this.layers })
                        });
                        
                        if (!response.ok) {
                            const result = await response.json();
                            throw new Error(result.message || 'Server error while saving.');
                        }
                        
                        this.layers.forEach(l => l.is_new = false);
                        this.triggerToast("Saved Successfully", "Layer composition has been securely stored in the system.");
                        
                    } catch (e) {
                        this.triggerToast("Error Saving", e.message, "error");
                    } finally {
                        this.saving = false;
                    }
                }
            }));
        });
    </script>
    <style>
        .table-warning { transition: background-color 1s ease; }
    </style>
    @endpush
</x-layout>
