<style>
    .profile-page {
        --profile-indigo: #6366f1;
    }

    .profile-page-header {
        background: #0f172a;
    }

    .profile-field-label {
        display: block;
        margin-bottom: 0.6rem;
        color: #164e77;
        font-size: 1rem;
        font-weight: 700;
    }

    .profile-field-input {
        display: block;
        width: 100%;
        border: 1px solid #9ca3af;
        border-radius: 0.75rem;
        background: #ffffff;
        color: #111827;
        padding: 0.85rem 1rem;
        font-size: 0.875rem;
        outline: none;
        transition: border-color 150ms ease, box-shadow 150ms ease, background-color 150ms ease;
    }

    .profile-field-input:focus {
        border-color: var(--profile-indigo);
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
    }

    .profile-field-input-readonly {
        background: #f9fafb;
        color: #6b7280;
    }

    .profile-primary-button {
        display: inline-flex;
        min-height: 2.5rem;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--profile-indigo);
        border-radius: 0.5rem;
        background: var(--profile-indigo);
        padding: 0.625rem 1.25rem;
        color: #ffffff;
        font-size: 0.875rem;
        font-weight: 600;
        transition: background-color 150ms ease, opacity 150ms ease;
    }

    .profile-primary-button:hover {
        background: #4f46e5;
    }

    .profile-primary-button:disabled {
        cursor: not-allowed;
        opacity: 0.7;
    }

    .profile-error {
        margin-top: 0.35rem;
        color: #dc2626;
        font-size: 0.875rem;
    }

    .dark .profile-field-label {
        color: #f3f4f6;
    }

    .dark .profile-field-input {
        border-color: #4b5563;
        background: #1f2937;
        color: #f9fafb;
    }

    .dark .profile-field-input-readonly {
        background: #111827;
        color: #9ca3af;
    }

    .dark .profile-field-input:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.25);
    }
</style>
