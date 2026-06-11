@extends('layouts.executive')
@section('page-title', 'Company Management')
@section('content')
<div class="grid gap-8 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.companies.store') }}" class="glass-card space-y-3 p-6">
        @csrf
        <input name="erpnext_name" placeholder="ERPNext Company Name" class="ayp-input w-full rounded-xl px-3 py-2" required>
        <input name="abbr" placeholder="Abbr" class="ayp-input w-full rounded-xl px-3 py-2">
        <input name="default_currency" value="PKR" class="ayp-input w-full rounded-xl px-3 py-2">
        <button class="btn-primary">Add Company</button>
    </form>
    <x-data-table title="Companies" :columns="['Name','Abbr','Currency']" :rows="$companies->map(fn($c)=>[$c->erpnext_name,$c->abbr,$c->default_currency])->all()" />
</div>
@endsection
