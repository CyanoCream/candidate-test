<x-layout>
    <x-slot:title>Suppliers</x-slot:title>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Suppliers</h1>
            <p class="text-muted small">A list of all CLT suppliers in your account including their name, total layups, and creation date.</p>
        </div>
        <div>
            <form action="{{ route('suppliers.store') }}" method="POST" class="d-flex gap-2">
                @csrf
                <input type="text" name="name" placeholder="New Supplier Name" class="form-control form-control-sm" required>
                <button type="submit" class="btn btn-primary btn-sm">Add Supplier</button>
            </form>
        </div>
    </div>

    <!-- Search/Filter -->
    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body d-flex flex-column flex-md-row gap-3 justify-content-between align-items-center">
            <form action="{{ route('suppliers.index') }}" method="GET" class="w-100 flex-grow-1" style="max-width: 400px;">
                <div class="input-group">
                    <input type="text" name="search" value="{{ $search ?? '' }}" class="form-control" placeholder="Search suppliers...">
                    <button class="btn btn-outline-secondary" type="submit">Search</button>
                </div>
            </form>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm">Filter</button>
                <a href="{{ route('suppliers.export_csv', ['search' => request('search')]) }}" class="btn btn-success btn-sm">Export CSV</a>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card shadow-sm border-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Supplier</th>
                        <th>Total Layups</th>
                        <th>Created Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex justify-content-center align-items-center fw-bold" style="width: 40px; height: 40px;">
                                    {{ substr($supplier->name, 0, 2) }}
                                </div>
                                <div class="ms-3">
                                    <div class="fw-bold">{{ $supplier->name }}</div>
                                    <div class="text-muted small">ID: {{ $supplier->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-info text-dark">
                                {{ $supplier->layups_count ?? 0 }} Layups
                            </span>
                        </td>
                        <td class="text-muted small">
                            {{ $supplier->created_at->format('M j, Y') }}
                        </td>
                        <td class="text-end">
                            <a href="{{ route('suppliers.show', $supplier->id) }}" class="btn btn-sm btn-outline-primary">Manage</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="text-center text-muted py-5">
                            <h5 class="mt-2">No suppliers</h5>
                            <p>Get started by creating a new supplier.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layout>
