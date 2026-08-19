<x-filament-widgets::widget>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; width: 100%;">
        
        <!-- Total Documents -->
        <div style="background-color: white; border-radius: 0.5rem; border-top: 6px solid #ea580c; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
            <h3 style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; margin-top: 0;">Total Documents</h3>
            <p style="font-size: 2.5rem; font-weight: 700; color: #111827; margin: 0; line-height: 1;">{{ $total ?? 0 }}</p>
        </div>

        <!-- Pending -->
        <div style="background-color: white; border-radius: 0.5rem; border-top: 6px solid #eab308; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
            <h3 style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; margin-top: 0;">Pending</h3>
            <p style="font-size: 2.5rem; font-weight: 700; color: #111827; margin: 0; line-height: 1;">{{ $pending ?? 0 }}</p>
        </div>

        <!-- Active -->
        <div style="background-color: white; border-radius: 0.5rem; border-top: 6px solid #6366f1; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
            <h3 style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; margin-top: 0;">Active</h3>
            <p style="font-size: 2.5rem; font-weight: 700; color: #111827; margin: 0; line-height: 1;">{{ $active ?? 0 }}</p>
        </div>

        <!-- Completed -->
        <div style="background-color: white; border-radius: 0.5rem; border-top: 6px solid #22c55e; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);">
            <h3 style="font-size: 0.75rem; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.5rem; margin-top: 0;">Completed</h3>
            <p style="font-size: 2.5rem; font-weight: 700; color: #111827; margin: 0; line-height: 1;">{{ $completed ?? 0 }}</p>
        </div>

    </div>
</x-filament-widgets::widget>