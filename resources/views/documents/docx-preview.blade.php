<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Word document preview</title>
    <style>
        body { margin: 0; background: #f3f4f6; }
        #status { padding: 16px; font: 14px sans-serif; color: #6b7280; }
        #document { transform-origin: top left; }
        #document .docx-wrapper { padding: 0; background: transparent; }
        #document .docx-wrapper > section.docx { margin-bottom: 12px; box-shadow: none; }
    </style>
    @vite('resources/js/docx-preview.js')
</head>
<body>
    <p id="status" role="status">Loading document preview…</p>
    <div id="preview"><div id="document"></div></div>
    <script id="document-data" type="application/json">@json($documentData)</script>
</body>
</html>
