<x-filament-panels::page>
    
    <style>
        .fi-input-wrp:focus-within, 
        .fi-input:focus, 
        input:focus, 
        select:focus, 
        textarea:focus {
            border-color: #6366F1 !important;
            --tw-ring-color: #6366F1 !important;
            box-shadow: 0 0 0 2px rgba(98, 59, 231, 0.25) !important;
        }

        .filepond--root {
            background-color: #ffffff !important;
            border: 2px dashed #623BE7 !important;
            border-radius: 0.75rem !important;
            transition: background-color 0.3s ease-in-out !important;
        }

        .dark .client-upload-page .filepond--root {
            background-color: #1f2937 !important;
            border-color: #818cf8 !important;
        }

        .filepond--panel-root {
            background-color: transparent !important;
            border: none !important;
        }

        .filepond--drop-label {
            min-height: 140px !important;
            color: #4b5563 !important;
            transition: color 0.3s ease-in-out !important;
        }

        .dark .client-upload-page .filepond--drop-label {
            color: #d1d5db !important;
        }

        .filepond--root:has(.filepond--item) {
            background-color: #623BE7 !important;
        }

        .filepond--root:has(.filepond--item) .filepond--drop-label,
        .filepond--root:has(.filepond--item) .filepond--label-action {
            color: #ffffff !important;
        }

        .filepond--item-panel {
            background-color: #ffffff !important; 
        }

        .dark .client-upload-page .filepond--item-panel {
            background-color: #374151 !important;
        }
        
        .filepond--file-info {
            color: #1f2937 !important;
        }

        .dark .client-upload-page .filepond--file-info {
            color: #f3f4f6 !important;
        }

        .filepond--label-action {
            color: #623BE7 !important;
            text-decoration: none !important;
            font-weight: 600 !important;
            background: transparent !important;
            padding: 0 !important;
        }

        .filepond--label-action:hover {
            text-decoration: underline !important;
        }

        .dark .client-upload-page .filepond--label-action {
            color: #a5b4fc !important;
        }

        .btn-custom-primary {
            background-color: #623BE7 !important;
            color: white !important;
            border: none !important;
        }
        
        .btn-custom-primary:hover {
            background-color: #502ec3 !important; 
        }
    </style>

    <div class="client-upload-page bg-white rounded-xl shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        
        <div style="background-color: #0F172A; border-top-left-radius: 0.75rem; border-top-right-radius: 0.75rem; padding: 1.5rem;">
            <h2 style="color: #6366f1; font-size: 1.5rem; font-weight: 600; margin: 0;">
                Submit A Document
            </h2>
            <p style="color: #e5e7eb; margin-top: 0.25rem; font-size: 0.875rem;">
                Please provide the details of your request and upload the necessary files.
            </p>
        </div>

        <!-- Form and Buttons Container -->
        <div class="p-6">
            
            <form wire:submit="submit">
                
                {{ $this->form }}

                <!-- Action Buttons aligned to the right -->
                <div class="flex justify-end gap-4 mt-6">
                    
                    <x-filament::button color="gray" variant="outline" wire:click="clearForm" size="lg" type="button">
                        Clear
                    </x-filament::button>

                    <x-filament::button type="submit" size="lg" class="btn-custom-primary">
                        Submit
                    </x-filament::button>
                    
                </div>
                
            </form>
            
        </div>
        
    </div>

</x-filament-panels::page>
