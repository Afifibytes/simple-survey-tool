<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Date
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    NPS Score
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    Open Text Response
                </th>
                @if(isset($detailed) && $detailed)
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        AI Follow-up
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                @endif
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($responses as $response)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $response->created_at->format('M j, Y g:i A') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($response->nps_score !== null)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $response->nps_score >= 9 ? 'bg-green-100 text-green-800' : 
                                   ($response->nps_score >= 7 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                {{ $response->nps_score }}
                            </span>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        @if($response->open_text)
                            <div class="max-w-xs">
                                <p class="truncate" title="{{ $response->open_text }}">
                                    {{ Str::limit($response->open_text, 60) }}
                                </p>
                            </div>
                        @else
                            <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    @if(isset($detailed) && $detailed)
                        <td class="px-6 py-4 text-sm text-gray-900">
                            @if($response->ai_follow_up_question)
                                <div class="max-w-xs">
                                    <p class="text-xs text-gray-600 mb-1">Q: {{ Str::limit($response->ai_follow_up_question, 40) }}</p>
                                    @if($response->ai_follow_up_answer)
                                        <p class="text-xs">A: {{ Str::limit($response->ai_follow_up_answer, 40) }}</p>
                                    @else
                                        <p class="text-xs text-gray-400">No answer</p>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($response->completed_at)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Complete
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    Partial
                                </span>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ isset($detailed) && $detailed ? '5' : '3' }}" class="px-6 py-4 text-center text-gray-500">
                        No responses found
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
