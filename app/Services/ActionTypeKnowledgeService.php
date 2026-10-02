<?php

namespace App\Services;

class ActionTypeKnowledgeService
{
    /** @var list<string>|null */
    private ?array $approvedActionTypes = null;

    public function matchDefinitionQuestion(string $message): ?string
    {
        if (! $this->looksLikeDefinitionQuestion($message)) {
            return null;
        }

        $normalized = $this->normalize($message);
        $types = $this->approvedActionTypes();

        usort(
            $types,
            static fn (string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left),
        );

        foreach ($types as $type) {
            $candidate = $this->normalize($type);

            if (preg_match('/(?<![\\p{L}\\p{N}])' . preg_quote($candidate, '/') . '(?![\\p{L}\\p{N}])/u', $normalized) === 1) {
                return $type;
            }
        }

        return null;
    }

    public function looksLikeDefinitionQuestion(string $message): bool
    {
        $normalized = $this->normalize($message);
        $hasDefinitionCue = preg_match(
            '/\\b(?:what\\s+does|what\\s+is|what\\s+is\\s+the\\s+meaning|meaning(?:\\s+of|\\s+ng)?|means?|define|explain|ano(?:\\s+ang)?\\s+ibig\\s+sabihin(?:\\s+ng)?|ibig\\s+sabihin(?:\\s+ng)?|para\\s+saan(?:\\s+ang)?)\\b/u',
            $normalized,
        ) === 1;

        return $hasDefinitionCue
            && preg_match('/\\bfor\\s+[\\p{L}][\\p{L}\\s-]*\\b/u', $normalized) === 1;
    }

    /** @return list<string> */
    public function approvedActionTypes(): array
    {
        if ($this->approvedActionTypes !== null) {
            return $this->approvedActionTypes;
        }

        $path = storage_path('app/ai-knowledge/lextrack-guide.md');
        $knowledge = is_file($path) ? file_get_contents($path) : false;

        if (! is_string($knowledge)
            || preg_match('/^Status:\\s*APPROVED\\b/im', $knowledge) !== 1
            || preg_match('/^# 8\\. Document Action Types\\s*$([\\s\\S]*?)(?=^# 9\\.)/mi', $knowledge, $section) !== 1) {
            return $this->approvedActionTypes = [];
        }

        preg_match_all(
            '/^##\\s+8\\.\\d+\\s+(For\\s+[^\\r\\n]+)\\s*$/mi',
            $section[1],
            $matches,
        );

        return $this->approvedActionTypes = array_values(array_unique(array_map(
            static fn (string $value): string => trim($value),
            $matches[1] ?? [],
        )));
    }

    private function normalize(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^\\p{L}\\p{N}]+/u', ' ', $value) ?? $value;

        return trim((string) preg_replace('/\\s+/u', ' ', $value));
    }
}
