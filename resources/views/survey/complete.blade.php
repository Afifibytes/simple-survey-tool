@extends('layouts.app')

@section('title', 'Survey Complete')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-lg shadow-md p-8 text-center">
        <div class="mb-6">
            <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-4">Thank You!</h1>
        <p class="text-lg text-gray-600 mb-8">Your response has been successfully recorded. We appreciate your feedback!</p>
        
        <div class="bg-gray-50 rounded-lg p-6">
            <h2 class="text-lg font-medium text-gray-900 mb-2">What happens next?</h2>
            <p class="text-gray-600">
                Your feedback helps us improve our services. We review all responses carefully and use them to make meaningful improvements to your experience.
            </p>
        </div>
    </div>
</div>
@endsection
