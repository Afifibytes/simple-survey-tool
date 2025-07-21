@extends('layouts.app')

@section('title', 'Survey Responses')

@section('content')
<div class="mb-8">
    <div class="flex items-center space-x-2 text-sm text-gray-500 mb-2">
        <a href="{{ route('admin.surveys.index') }}" class="hover:text-gray-700">Surveys</a>
        <span>/</span>
        <a href="{{ route('admin.surveys.show', $survey) }}" class="hover:text-gray-700">{{ $survey->name }}</a>
        <span>/</span>
        <span class="text-gray-900">Responses</span>
    </div>
    <h1 class="text-3xl font-bold text-gray-900">Survey Responses</h1>
    <p class="text-gray-600 mt-2">{{ $survey->name }}</p>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Responses</p>
                <p class="text-2xl font-bold text-gray-900">{{ $responses->total() }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-2 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Average NPS</p>
                <p class="text-2xl font-bold text-gray-900">
                    {{ $survey->average_nps_score ? number_format($survey->average_nps_score, 1) : 'N/A' }}
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-2 bg-purple-100 rounded-lg">
                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">AI Follow-ups</p>
                <p class="text-2xl font-bold text-gray-900">{{ $survey->responses()->whereNotNull('ai_follow_up_question')->count() }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-2 bg-yellow-100 rounded-lg">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Completion Rate</p>
                <p class="text-2xl font-bold text-gray-900">
                    {{ $responses->total() > 0 ? number_format(($survey->responses()->completed()->count() / $responses->total()) * 100, 1) : 0 }}%
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Responses Table -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">All Responses</h2>
    </div>
    <div class="overflow-x-auto">
        @if($responses->count() > 0)
            @include('components.response-table', ['responses' => $responses, 'detailed' => true])
        @else
            <div class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No responses yet</h3>
                <p class="mt-1 text-sm text-gray-500">Share your survey to start collecting responses.</p>
            </div>
        @endif
    </div>
</div>

<!-- Pagination -->
@if($responses->hasPages())
    <div class="mt-8">
        {{ $responses->links() }}
    </div>
@endif
@endsection
