<x-layout>
    <x-slot:title>Resolve Import Conflicts</x-slot:title>

    <div class="mb-4">
        <a href="{{ route('suppliers.show', $supplierId) }}" class="text-decoration-none text-danger">&larr; Cancel Import</a>
    </div>

    @php
        $conflictsList = [];
        foreach ($payload as $lIdx => $layup) {
            foreach ($layup['layers'] as $layerIdx => $layer) {
                if ($layer['status'] === 'conflict') {
                    $conflictsList[] = [
                        'layupName' => $layup['name'],
                        'lIdx' => $lIdx,
                        'layerIdx' => $layerIdx,
                        'incoming' => $layer['incoming'],
                        'existing' => $layer['existing'],
                    ];
                }
            }
        }
    @endphp

    <div class="card shadow-sm border-0" x-data="{
        activeConflict: 0,
        resolutions: {},
        conflicts: @js($conflictsList),
        
        get current() {
            return this.conflicts[this.activeConflict] || null;
        },
        
        isResolved(index) {
            return this.resolutions[`${this.conflicts[index].lIdx}_${this.conflicts[index].layerIdx}`] !== undefined;
        },

        resolveCurrent(choice) {
            const key = `${this.current.lIdx}_${this.current.layerIdx}`;
            this.resolutions[key] = choice;
            if (this.activeConflict < this.conflicts.length - 1) {
                this.activeConflict++;
            }
        },

        allResolved() {
            return Object.keys(this.resolutions).length === this.conflicts.length;
        }
    }">

        <div class="row g-0">
            <!-- Left Sidebar -->
            <div class="col-md-3 border-end bg-light d-flex flex-column" style="min-height: 500px; max-height: 70vh;">
                <div class="p-3 border-bottom bg-white">
                    <h6 class="mb-0 fw-bold">Detected Conflicts (<span x-text="conflicts.length"></span>)</h6>
                </div>
                <div class="overflow-auto p-2 flex-grow-1">
                    <template x-if="conflicts.length === 0">
                        <div class="text-center p-4 text-muted small">No conflicts found. All data will be imported directly.</div>
                    </template>
                    <div class="list-group list-group-flush rounded-0">
                        <template x-for="(conflict, index) in conflicts" :key="index">
                            <button 
                                @click="activeConflict = index"
                                class="list-group-item list-group-item-action d-flex justify-content-between align-items-center mb-1 rounded border"
                                :class="{'active bg-primary border-primary text-white': activeConflict === index}"
                            >
                                <div class="text-truncate">
                                    <span class="d-block text-truncate fw-bold" x-text="conflict.layupName"></span>
                                    <small :class="activeConflict === index ? 'text-white-50' : 'text-muted'">Layer <span x-text="conflict.incoming.layer_order"></span></small>
                                </div>
                                <div x-show="isResolved(index)">
                                    <span class="badge bg-success rounded-pill">&check;</span>
                                </div>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 d-flex flex-column">
                <template x-if="conflicts.length > 0 && current">
                    <div class="flex-grow-1 d-flex flex-column h-100">
                        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-white">
                            <h5 class="mb-0 fw-bold">
                                Layer <span x-text="current.incoming.layer_order"></span> in <span x-text="current.layupName"></span>
                            </h5>
                            <span class="badge" :class="isResolved(activeConflict) ? 'bg-success' : 'bg-warning text-dark'" x-text="isResolved(activeConflict) ? 'Resolved' : 'Pending Resolution'"></span>
                        </div>

                        <!-- Split Screen Data -->
                        <div class="p-4 bg-light flex-grow-1 overflow-auto">
                            <div class="row g-4">
                                <!-- Existing -->
                                <div class="col-lg-6">
                                    <div class="card h-100 shadow-sm" :class="resolutions[`${current.lIdx}_${current.layerIdx}`] === 'existing' ? 'border-primary border-2' : ''">
                                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                            <span class="badge bg-secondary">Existing Data (Database)</span>
                                            <button @click="resolveCurrent('existing')" class="btn btn-sm btn-outline-primary">Keep Existing</button>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-borderless table-sm mb-0">
                                                <tbody>
                                                    <tr>
                                                        <td class="text-muted small ps-3">Thickness</td>
                                                        <td class="fw-bold text-end pe-3" :class="current.existing.thickness != current.incoming.thickness ? 'text-danger bg-danger bg-opacity-10' : ''"><span x-text="current.existing.thickness"></span>mm</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted small ps-3">Width</td>
                                                        <td class="fw-bold text-end pe-3" :class="current.existing.width != current.incoming.width ? 'text-danger bg-danger bg-opacity-10' : ''"><span x-text="current.existing.width"></span>mm</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted small ps-3">Angle</td>
                                                        <td class="fw-bold text-end pe-3" :class="current.existing.angle != current.incoming.angle ? 'text-danger bg-danger bg-opacity-10' : ''"><span x-text="current.existing.angle"></span>&deg;</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                <!-- Incoming -->
                                <div class="col-lg-6">
                                    <div class="card h-100 shadow-sm" :class="resolutions[`${current.lIdx}_${current.layerIdx}`] === 'incoming' ? 'border-success border-2' : ''">
                                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                            <span class="badge bg-warning text-dark">Incoming Data (File)</span>
                                            <button @click="resolveCurrent('incoming')" class="btn btn-sm btn-outline-success">Accept Incoming</button>
                                        </div>
                                        <div class="card-body p-0">
                                            <table class="table table-borderless table-sm mb-0">
                                                <tbody>
                                                    <tr>
                                                        <td class="text-muted small ps-3">Thickness</td>
                                                        <td class="fw-bold text-end pe-3" :class="current.existing.thickness != current.incoming.thickness ? 'text-warning bg-warning bg-opacity-10' : ''"><span x-text="current.incoming.thickness"></span>mm</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted small ps-3">Width</td>
                                                        <td class="fw-bold text-end pe-3" :class="current.existing.width != current.incoming.width ? 'text-warning bg-warning bg-opacity-10' : ''"><span x-text="current.incoming.width"></span>mm</td>
                                                    </tr>
                                                    <tr>
                                                        <td class="text-muted small ps-3">Angle</td>
                                                        <td class="fw-bold text-end pe-3" :class="current.existing.angle != current.incoming.angle ? 'text-warning bg-warning bg-opacity-10' : ''"><span x-text="current.incoming.angle"></span>&deg;</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="conflicts.length === 0">
                    <div class="flex-grow-1 d-flex flex-column align-items-center justify-content-center p-5 text-center">
                        <div class="display-4 text-success mb-3">&check;</div>
                        <h4 class="fw-bold">No Conflicts Detected</h4>
                        <p class="text-muted">You can finalize the import immediately.</p>
                    </div>
                </template>

                <!-- Bottom Bar -->
                <div class="p-3 border-top bg-white d-flex justify-content-between align-items-center">
                    <div class="text-muted small">
                        <span x-text="Object.keys(resolutions).length"></span> of <span x-text="conflicts.length"></span> conflicts resolved
                    </div>
                    <form action="{{ route('suppliers.resolve', $importId) }}" method="POST">
                        @csrf
                        <template x-for="(val, key) in resolutions" :key="key">
                            <input type="hidden" :name="`resolutions[${key}]`" :value="val">
                        </template>
                        <button type="submit" class="btn btn-primary" :disabled="!allResolved()">
                            <span x-text="conflicts.length === 0 ? 'Complete Import' : 'Submit Final Choices'"></span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>
