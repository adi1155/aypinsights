@extends('layouts.executive')
@section('page-title', 'Company Management')
@section('content')
<div class="grid gap-8 lg:grid-cols-2">
    <form method="POST" action="{{ route('admin.companies.store') }}" class="glass-card space-y-3 p-6">
        @csrf
        <input name="erpnext_name" placeholder="ERPNext Company Name" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-white" required>
        <input name="abbr" placeholder="Abbr" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-white">
        <input name="default_currency" value="PKR" class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-white">
        <button class="btn-primary">Add Company</button>
    </form>
    <x-data-table title="Companies" :columns="['Name','Abbr','Currency']" :rows="$companies->map(fn($c)=>[$c->erpnext_name,$c->abbr,$c->default_currency])->all()" />
</div>
@endsection
