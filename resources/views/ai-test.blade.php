<!DOCTYPE html>
<html>
<head>
    <title>AI Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>

<h2>Test Ollama</h2>

<input type="text" id="prompt" placeholder="Enter a prompt">
<button onclick="askAI()">Ask AI</button>

<pre id="response"></pre>

<script>
async function askAI() {
    const output = document.getElementById('response');
    output.textContent = "Thinking...";

    try {
        const response = await fetch('/ask-ai', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                prompt: document.getElementById('prompt').value
            })
        });

        const text = await response.text();

        if (!response.ok) {
            output.textContent =
                `HTTP ${response.status}\n\n${text}`;
            return;
        }

        const data = JSON.parse(text);
        output.textContent = data.response;

    } catch (error) {
        output.textContent = "Error:\n\n" + error.message;
        console.error(error);
    }
}
</script>

</body>
</html>