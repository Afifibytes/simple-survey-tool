<div class="bg-white rounded-lg shadow hover:shadow-md transition-shadow duration-200">
    <div class="p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900 truncate">{{ $survey->name }}</h3>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $survey->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                {{ $survey->is_active ? 'Active' : 'Inactive' }}
            </span>
        </div>
        
        @if($survey->description)
            <p class="text-gray-600 text-sm mb-4 line-clamp-2">{{ Str::limit($survey->description, 100) }}</p>
        @endif
        
        <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
            <span>{{ $survey->questions_count ?? $survey->questions->count() }} questions</span>
            <span>{{ $survey->responses_count ?? $survey->response_count }} responses</span>
        </div>
        
        <div class="text-xs text-gray-400 mb-4">
            Created {{ $survey->created_at->diffForHumans() }}
        </div>
        
        <div class="flex space-x-2">
            <a href="{{ route('admin.surveys.show', $survey) }}" 
               class="flex-1 bg-blue-600 text-white text-center py-2 px-3 rounded text-sm hover:bg-blue-700 transition-colors">
                View
            </a>
            <a href="{{ route('survey.show', $survey) }}" 
               target="_blank"
               class="flex-1 bg-green-600 text-white text-center py-2 px-3 rounded text-sm hover:bg-green-700 transition-colors">
                Preview
            </a>
            <a href="{{ route('admin.surveys.edit', $survey) }}" 
               class="flex-1 bg-gray-600 text-white text-center py-2 px-3 rounded text-sm hover:bg-gray-700 transition-colors">
                Edit
            </a>
        </div>
    </div>
</div>
