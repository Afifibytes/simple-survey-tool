@extends('layouts.public')

@section('title', $survey->name)
@section('body-class', 'survey-page')

@section('content')
<div class="bg-white rounded-lg shadow-md p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ $survey->name }}</h1>

        @if($survey->description)
            <p class="text-gray-600 mb-6">{{ $survey->description }}</p>
        @endif

        <form id="survey-form" class="space-y-6">
            @csrf

            @foreach($survey->questions as $index => $question)
                <div class="question-container" data-question-id="{{ $question->id }}">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        {{ $question->text }}
                    </label>

                    @if($question->type === 'nps')
                        <div class="nps-scale flex justify-between items-center">
                            @for($i = 0; $i <= 10; $i++)
                                <label class="nps-option">
                                    <input type="radio" name="nps_score" value="{{ $i }}" class="sr-only">
                                    <span class="nps-button">{{ $i }}</span>
                                </label>
                            @endfor
                        </div>
                        <div class="flex justify-between text-xs text-gray-500 mt-2">
                            <span>Not likely</span>
                            <span>Very likely</span>
                        </div>
                    @elseif($question->type === 'text')
                        <textarea
                            name="open_text"
                            rows="4"
                            class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="Please share your thoughts..."
                        ></textarea>
                    @endif
                </div>
            @endforeach

            <button
                type="submit"
                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                Submit Response
            </button>
        </form>

        <!-- AI Follow-up Question Container -->
        <div id="ai-followup" class="hidden mt-6 p-4 bg-blue-50 rounded-lg">
            <h3 class="font-medium text-gray-900 mb-2">One more question:</h3>
            <p id="ai-question" class="text-gray-700 mb-4"></p>
            <textarea
                id="ai-answer"
                rows="3"
                class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Your answer..."
            ></textarea>
            <button
                id="submit-followup"
                class="mt-2 bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700"
            >
                Submit Final Answer
            </button>
        </div>

        <!-- Thank You Message -->
        <div id="thank-you" class="hidden text-center py-8">
            <h2 class="text-2xl font-bold text-green-600 mb-2">Thank You!</h2>
            <p class="text-gray-600">Your response has been recorded.</p>
    </div>
@endsection

@push('styles')
<style>
.nps-button {
    display: inline-block;
    width: 40px;
    height: 40px;
    line-height: 40px;
    text-align: center;
    border: 2px solid #d1d5db;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.2s;
}

.nps-option input:checked + .nps-button {
    background-color: #3b82f6;
    color: white;
    border-color: #3b82f6;
}

.nps-button:hover {
    border-color: #3b82f6;
    background-color: #eff6ff;
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('survey-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    try {
        console.log('Submitting survey response...');

        const response = await fetch('{{ route("survey.store", $survey) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        console.log('Response status:', response.status, response.statusText);

        // Check if the HTTP response is ok
        if (!response.ok) {
            const errorText = await response.text();
            console.error('HTTP error response:', errorText);
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');
        console.log('Response content-type:', contentType);

        if (!contentType || !contentType.includes('application/json')) {
            const responseText = await response.text();
            console.error('Non-JSON response:', responseText);
            throw new Error('Server returned non-JSON response');
        }

        const data = await response.json();
        console.log('Parsed response data:', data);

        // Check if the backend operation was successful
        if (data.success) {
            console.log('Survey submission successful');
            this.style.display = 'none';

            if (data.has_follow_up && data.response && data.response.ai_follow_up_question) {
                console.log('Showing AI follow-up question');
                document.getElementById('ai-question').textContent = data.response.ai_follow_up_question;
                document.getElementById('ai-followup').classList.remove('hidden');
            } else {
                console.log('Showing thank you message (no AI follow-up)');
                document.getElementById('thank-you').classList.remove('hidden');
            }
        } else {
            // Backend returned success: false
            console.error('Backend error:', data.message || 'Unknown error');
            alert(data.message || 'Failed to submit survey. Please try again.');
        }
    } catch (error) {
        console.error('Error submitting survey:', error);
        console.error('Error stack:', error.stack);
        alert('There was an error submitting your response. Please try again.');
    }
});

document.getElementById('submit-followup').addEventListener('click', async function() {
    const answer = document.getElementById('ai-answer').value;

    if (!answer.trim()) {
        alert('Please provide an answer to the follow-up question.');
        return;
    }

    try {
        const response = await fetch('{{ route("survey.followup", $survey) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                ai_follow_up_answer: answer
            })
        });

        const data = await response.json();

        if (data.success) {
            document.getElementById('ai-followup').style.display = 'none';
            document.getElementById('thank-you').classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error submitting follow-up:', error);
        alert('There was an error submitting your answer. Please try again.');
    }
});
</script>
@endpush
