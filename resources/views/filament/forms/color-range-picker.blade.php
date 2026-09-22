@php
    $grid = \App\Support\ColorPalette::presetGrid();
    $initialState = strtoupper((string) ($getState() ?? ''));
    $initialBaseColor = preg_match('/^#[0-9A-F]{6}$/i', $initialState)
        ? $initialState
        : $grid[1][0];
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        class="color-range-picker"
        x-data="{
            state: $wire.$entangle(@js($getStatePath())),
            baseColor: @js($initialBaseColor),
            shade: 50,
            get colors() {
                return {
                    light: this.mix('#FFFFFF', this.baseColor, 0.2),
                    standard: this.baseColor,
                    dark: this.mix(this.baseColor, '#000000', 0.8),
                }
            },
            get color() {
                return this.colorAt(this.shade)
            },
            get generatedChoices() {
                return [0, 17, 33, 50, 67, 83, 100].map(shade => ({
                    shade,
                    color: this.colorAt(shade),
                }))
            },
            colorAt(shade) {
                if (shade <= 50) {
                    return this.mix(this.colors.light, this.colors.standard, shade / 50)
                }

                return this.mix(this.colors.standard, this.colors.dark, (shade - 50) / 50)
            },
            hexToRgb(hex) {
                const value = hex.replace('#', '')

                return {
                    r: parseInt(value.slice(0, 2), 16),
                    g: parseInt(value.slice(2, 4), 16),
                    b: parseInt(value.slice(4, 6), 16),
                }
            },
            rgbToHex(rgb) {
                return `#${[rgb.r, rgb.g, rgb.b].map(value => Math.round(value).toString(16).padStart(2, '0')).join('')}`.toUpperCase()
            },
            mix(start, end, amount) {
                const first = this.hexToRgb(start)
                const second = this.hexToRgb(end)

                return this.rgbToHex({
                    r: first.r + ((second.r - first.r) * amount),
                    g: first.g + ((second.g - first.g) * amount),
                    b: first.b + ((second.b - first.b) * amount),
                })
            },
            syncColor() {
                this.state = this.color
            },
            selectColor(color) {
                this.baseColor = color
                this.shade = 50
                this.syncColor()
            },
            setCustomColor(event) {
                this.baseColor = event.target.value.toUpperCase()
                this.shade = 50
                this.syncColor()
            },
        }"
        x-init="state = state || color"
    >
        <div class="color-range-picker__grid" role="group" aria-label="Color choices">
            @foreach ($grid as $row)
                @foreach ($row as $color)
                    <button
                        type="button"
                        class="color-range-picker__swatch"
                        :class="{ 'is-selected': state === @js($color) }"
                        @click="selectColor(@js($color))"
                        title="{{ $color }}"
                        aria-label="Choose {{ $color }}"
                    >
                        <span style="background-color: {{ $color }}"></span>
                    </button>
                @endforeach
            @endforeach

            <button
                type="button"
                class="color-range-picker__swatch color-range-picker__add"
                @click="$refs.customColor.value = baseColor; $refs.customColor.click()"
                title="Add custom color"
                aria-label="Add custom color"
            >
                <span>+</span>
            </button>

            <input
                x-ref="customColor"
                class="color-range-picker__custom-input"
                type="color"
                @input="setCustomColor($event)"
                aria-label="Choose custom color"
            >
        </div>

        <div class="color-range-picker__custom-choices" role="group" aria-label="Custom shades">
            <span class="color-range-picker__custom-label">Custom shades</span>
            <div class="color-range-picker__custom-swatches">
                <template x-for="option in generatedChoices" :key="option.shade">
                    <button
                        type="button"
                        class="color-range-picker__swatch"
                        :class="{ 'is-selected': state === option.color }"
                        @click="shade = option.shade; syncColor()"
                        :title="option.color"
                        :aria-label="`Use ${option.color}`"
                    >
                        <span :style="`background-color: ${option.color}`"></span>
                    </button>
                </template>
            </div>
        </div>
    </div>
</x-dynamic-component>

<style>
    .color-range-picker {
        display: grid;
        gap: 0.75rem;
        max-width: 100%;
        overflow: hidden;
    }

    .color-range-picker__grid {
        display: grid;
        grid-template-columns: repeat(10, minmax(0, 1.45rem));
        gap: 0.25rem;
        width: max-content;
        max-width: 100%;
    }

    .color-range-picker__custom-swatches {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1.45rem));
        gap: 0.25rem;
        width: max-content;
        max-width: 100%;
    }

    .color-range-picker__swatch {
        display: inline-flex;
        width: 1.45rem;
        height: 1.45rem;
        align-items: center;
        justify-content: center;
        border: 2px solid transparent;
        border-radius: 9999px;
        background: transparent;
        cursor: pointer;
        transition: border-color 150ms ease, transform 150ms ease;
    }

    .color-range-picker__swatch:hover {
        transform: translateY(-1px);
    }

    .color-range-picker__swatch.is-selected {
        border-color: #6366f1;
    }

    .color-range-picker__swatch > span {
        display: inline-flex;
        width: 1.3rem;
        height: 1.3rem;
        align-items: center;
        justify-content: center;
        border: 1px solid rgb(100 116 139 / 0.7);
        border-radius: 9999px;
    }

    .color-range-picker__add {
        border-color: #94a3b8;
        color: #64748b;
        font-size: 1.2rem;
        line-height: 1;
    }

    .color-range-picker__add > span {
        border: 0;
        font-size: 1.2rem;
        line-height: 1;
    }

    .color-range-picker__custom-input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .color-range-picker__custom-choices {
        display: grid;
        gap: 0.4rem;
    }

    .color-range-picker__custom-label {
        color: #64748b;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .dark .color-range-picker__custom-label {
        color: #9ca3af;
    }

    @media (max-width: 640px) {
        .color-range-picker__grid {
            grid-template-columns: repeat(10, minmax(0, 1fr));
            width: 100%;
        }

        .color-range-picker__custom-swatches {
            grid-template-columns: repeat(7, minmax(0, 1fr));
            width: 100%;
        }

        .color-range-picker__swatch {
            width: 100%;
            height: 1.45rem;
        }

        .color-range-picker__swatch > span {
            width: 1.1rem;
            height: 1.1rem;
        }
    }
</style>
