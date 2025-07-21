{{-- resources/views/ai-agent/dashboard.blade.php --}}
@extends('admin.layouts.master')
@section('title', 'AI Agent Dashboard')
@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">AI Agent Dashboard</h1>
        <div id="health-status" class="mb-4"></div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- URL Analysis Section -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Analyze Website or YouTube Video</h2>
            
            <form id="url-form" class="space-y-4">
                @csrf
                <div>
                    <label for="url" class="block text-sm font-medium text-gray-700">URL</label>
                    <input type="url" id="url" name="url" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                           placeholder="https://example.com or https://youtube.com/watch?v=...">
                </div>
                
                <div>
                    <label for="url-question" class="block text-sm font-medium text-gray-700">Question (optional)</label>
                    <textarea id="url-question" name="question" rows="3"
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Ask a specific question about the content..."></textarea>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" data-action="summarize" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Summarize
                    </button>
                    <button type="submit" data-action="question" 
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Ask Question
                    </button>
                </div>
            </form>
            
            <div id="url-result" class="mt-6 hidden">
                <h3 class="text-lg font-medium text-gray-700 mb-2">Result:</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div id="url-content"></div>
                </div>
            </div>
        </div>

        <!-- File Analysis Section -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Analyze File</h2>
            
            <form id="file-form" class="space-y-4" enctype="multipart/form-data">
                @csrf
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-700">Upload File</label>
                    <input type="file" id="file" name="file" required 
                           accept=".txt,.pdf,.xlsx,.xls"
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                    <p class="mt-1 text-sm text-gray-500">Supported formats: TXT, PDF, XLSX, XLS (Max: 10MB)</p>
                </div>
                
                <div>
                    <label for="file-question" class="block text-sm font-medium text-gray-700">Question (optional)</label>
                    <textarea id="file-question" name="question" rows="3"
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                              placeholder="Ask a specific question about the file..."></textarea>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" data-action="summarize" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Summarize
                    </button>
                    <button type="submit" data-action="question" 
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                        Ask Question
                    </button>
                </div>
            </form>
            
            <div id="file-result" class="mt-6 hidden">
                <h3 class="text-lg font-medium text-gray-700 mb-2">Result:</h3>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <div id="file-content"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Loading Modal -->
<div id="loading-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-xl">
        <div class="flex items-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            <span class="ml-3 text-gray-700">Processing...</span>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check health status on load
    checkHealthStatus();
    
    // URL Form Handler
    document.getElementById('url-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const action = e.submitter.dataset.action;
        const url = document.getElementById('url').value;
        const question = document.getElementById('url-question').value;
        
        if (action === 'question' && !question.trim()) {
            alert('Please enter a question to ask.');
            return;
        }
        
        handleUrlSubmission(action, url, question);
    });
    
    // File Form Handler
    document.getElementById('file-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const action = e.submitter.dataset.action;
        const fileInput = document.getElementById('file');
        const question = document.getElementById('file-question').value;
        
        if (!fileInput.files[0]) {
            alert('Please select a file to upload.');
            return;
        }
        
        if (action === 'question' && !question.trim()) {
            alert('Please enter a question to ask.');
            return;
        }
        
        handleFileSubmission(action, fileInput.files[0], question);
    });
});

function checkHealthStatus() {
    fetch('/ai-agent/health')
        .then(response => response.json())
        .then(data => {
            const statusDiv = document.getElementById('health-status');
            if (data.healthy) {
                statusDiv.innerHTML = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">AI Agent API is online and healthy</div>';
            } else {
                statusDiv.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">AI Agent API is not responding</div>';
            }
        })
        .catch(error => {
            console.error('Health check failed:', error);
        });
}

function handleUrlSubmission(action, url, question) {
    showLoading();
    
    const endpoint = action === 'summarize' ? '/api/ai-agent/summarize/url' : '/api/ai-agent/question/url';
    const payload = { url: url };
    
    if (question.trim()) {
        payload.question = question;
    }
    
    fetch(endpoint, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        displayUrlResult(data);
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
    });
}

function handleFileSubmission(action, file, question) {
    showLoading();
    
    const endpoint = action === 'summarize' ? '/api/ai-agent/summarize/file' : '/api/ai-agent/question/file';
    const formData = new FormData();
    
    formData.append('file', file);
    if (question.trim()) {
        formData.append('question', question);
    }
    formData.append('_token', document.querySelector('input[name="_token"]').value);
    
    fetch(endpoint, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        displayFileResult(data);
    })
    .catch(error => {
        hideLoading();
        console.error('Error:', error);
        alert('An error occurred while processing your request.');
    });
}

function displayUrlResult(data) {
    const resultDiv = document.getElementById('url-result');
    const contentDiv = document.getElementById('url-content');
    
    if (data.success) {
        contentDiv.innerHTML = `<pre class="whitespace-pre-wrap text-sm">${JSON.stringify(data.data, null, 2)}</pre>`;
        resultDiv.classList.remove('hidden');
    } else {
        contentDiv.innerHTML = `<div class="text-red-600">Error: ${data.message || 'Unknown error occurred'}</div>`;
        resultDiv.classList.remove('hidden');
    }
}

function displayFileResult(data) {
    const resultDiv = document.getElementById('file-result');
    const contentDiv = document.getElementById('file-content');
    
    if (data.success) {
        contentDiv.innerHTML = `<pre class="whitespace-pre-wrap text-sm">${JSON.stringify(data.data, null, 2)}</pre>`;
        resultDiv.classList.remove('hidden');
    } else {
        contentDiv.innerHTML = `<div class="text-red-600">Error: ${data.message || 'Unknown error occurred'}</div>`;
        resultDiv.classList.remove('hidden');
    }
}

function showLoading() {
    document.getElementById('loading-modal').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loading-modal').classList.add('hidden');
}
</script>
@endsection