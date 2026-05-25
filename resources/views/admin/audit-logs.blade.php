@extends('layouts.executive')
@section('page-title', 'Audit Logs')
@section('content')
<x-data-table title="Activity Log" :columns="['User','Action','IP','Date']" :rows="$logs->map(fn($l)=>[$l->user?->name??'System',$l->action,$l->ip_address,$l->created_at->format('Y-m-d H:i')])->all()" />
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
