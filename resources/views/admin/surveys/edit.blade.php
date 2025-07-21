@extends('layouts.app')

@section('title', 'Edit Survey')

@section('content')
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">Edit Survey</h1>
    <p class="text-gray-600 mt-2">Update your survey information and questions</p>
</div>

<div class="bg-white rounded-lg shadow">
    <form action="{{ route('admin.surveys.update', $survey) }}" method="POST" id="admin-survey-form">
        @csrf
        @method('PUT')

        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Survey Information</h2>
        </div>

        <div class="p-6 space-y-6">
            <!-- Survey Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700">Survey Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $survey->name) }}"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Survey Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700">Description (Optional)</label>
                <textarea name="description" id="description" rows="3"
                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">{{ old('description', $survey->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Active Status -->
            <div>
                <div class="flex items-center">
                    <!-- Hidden input to ensure is_active is always sent (0 when unchecked, 1 when checked) -->
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                           {{ old('is_active', $survey->is_active) ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                        Survey is active and accepting responses
                    </label>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-medium text-gray-900">Questions</h2>
            <p class="text-sm text-gray-500 mt-1">Your survey will have exactly 2 questions: one NPS question and one open text question.</p>
        </div>

        <div class="p-6 space-y-6">
            @php
                $npsQuestion = $survey->questions->where('type', 'nps')->first();
                $textQuestion = $survey->questions->where('type', 'text')->first();
            @endphp

            <!-- NPS Question -->
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center mb-3">
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">NPS Question</span>
                </div>
                <label for="questions[0][text]" class="block text-sm font-medium text-gray-700">Question Text</label>
                <input type="hidden" name="questions[0][type]" value="nps">
                <input type="text" name="questions[0][text]" id="questions[0][text]"
                       value="{{ old('questions.0.text', $npsQuestion->text ?? 'How likely are you to recommend us to a friend or colleague?') }}"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                       required>
                @error('questions.0.text')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Open Text Question -->
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex items-center mb-3">
                    <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded">Open Text Question</span>
                </div>
                <label for="questions[1][text]" class="block text-sm font-medium text-gray-700">Question Text</label>
                <input type="hidden" name="questions[1][type]" value="text">
                <input type="text" name="questions[1][text]" id="questions[1][text]"
                       value="{{ old('questions.1.text', $textQuestion->text ?? 'What can we do to improve your experience?') }}"
                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                       required>
                @error('questions.1.text')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-between">
            <a href="{{ route('admin.surveys.show', $survey) }}" class="bg-white py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancel
            </a>
            <button type="submit" class="bg-blue-600 py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:bg-blue-700">
                Update Survey
            </button>
        </div>
    </form>
</div>
@endsection
