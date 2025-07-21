@extends('layouts.app')

@section('title', $survey->name)

@section('content')
<div class="flex justify-between items-start mb-8">
    <div>
        <h1 class="text-3xl font-bold text-gray-900">{{ $survey->name }}</h1>
        @if($survey->description)
            <p class="text-gray-600 mt-2">{{ $survey->description }}</p>
        @endif
        <div class="flex items-center mt-4 space-x-4">
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $survey->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                {{ $survey->is_active ? 'Active' : 'Inactive' }}
            </span>
            <span class="text-sm text-gray-500">Created {{ $survey->created_at->diffForHumans() }}</span>
        </div>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('survey.show', $survey) }}" target="_blank" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
            </svg>
            Preview Survey
        </a>
        <a href="{{ route('admin.surveys.edit', $survey) }}" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
            Edit Survey
        </a>
        <form action="{{ route('admin.surveys.destroy', $survey) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this survey?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700">
                Delete
            </button>
        </form>
    </div>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center">
            <div class="p-2 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Responses</p>
                <p class="text-2xl font-bold text-gray-900">{{ $survey->response_count }}</p>
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
                <p class="text-sm font-medium text-gray-600">Average NPS Score</p>
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
</div>

<!-- Questions -->
<div class="bg-white rounded-lg shadow mb-8">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-medium text-gray-900">Questions</h2>
    </div>
    <div class="p-6">
        <div class="space-y-4">
            @foreach($survey->questions as $question)
                <div class="border border-gray-200 rounded-lg p-4">
                    <div class="flex items-center justify-between mb-2">
                        <span class="bg-{{ $question->type === 'nps' ? 'blue' : 'green' }}-100 text-{{ $question->type === 'nps' ? 'blue' : 'green' }}-800 text-xs font-medium px-2.5 py-0.5 rounded">
                            {{ $question->type === 'nps' ? 'NPS Question' : 'Open Text Question' }}
                        </span>
                        <span class="text-sm text-gray-500">Question {{ $question->order + 1 }}</span>
                    </div>
                    <p class="text-gray-900">{{ $question->text }}</p>
                </div>
            @endforeach
        </div>
    </div>
</div>

<!-- Recent Responses -->
<div class="bg-white rounded-lg shadow">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h2 class="text-lg font-medium text-gray-900">Recent Responses</h2>
        <a href="{{ route('admin.surveys.responses', $survey) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
            View All Responses
        </a>
    </div>
    <div class="p-6">
        @if($survey->responses->count() > 0)
            @include('components.response-table', ['responses' => $survey->responses])
        @else
            <div class="text-center py-8">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No responses yet</h3>
                <p class="mt-1 text-sm text-gray-500">Share your survey to start collecting responses.</p>
            </div>
        @endif
    </div>
</div>
@endsection
