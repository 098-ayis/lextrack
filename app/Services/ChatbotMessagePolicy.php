<?php

namespace App\Services;

class ChatbotMessagePolicy
{
    public function prohibitedMessage(): string
    {
        return (string) config(
            'chatbot.prohibited_message',
            'Please use respectful language. Offensive or prohibited words are not allowed.',
        );
    }

    public function containsProhibitedTerm(string $message): bool
    {
        $normalizedMessage = $this->normalize($message);

        if ($normalizedMessage === '') {
            return false;
        }

        foreach ((array) config('chatbot.prohibited_terms', []) as $term) {
            if (! is_string($term) || trim($term) === '') {
                continue;
            }

            $normalizedTerm = $this->normalize($term);

            if ($normalizedTerm === '') {
                continue;
            }

            // Allow separators between every character so inserted spaces or
            // punctuation are caught, but require boundaries so a term such
            // as "puta" does not match an ordinary word like "reputation".
            $characters = preg_split('//u', $normalizedTerm, -1, PREG_SPLIT_NO_EMPTY);
            $termPattern = implode('[^\p{L}\p{N}]*', array_map(
                static fn (string $character): string => preg_quote($character, '/'),
                $characters ?: [],
            ));

            if ($termPattern !== '' && preg_match(
                '/(?<![\p{L}\p{N}])' . $termPattern . '(?![\p{L}\p{N}])/u',
                $normalizedMessage,
            ) === 1) {
                return true;
            }

            if ($this->matchesTypoVariant($normalizedMessage, $normalizedTerm)) {
                return true;
            }
        }

        return false;
    }

    private function normalize(string $message): string
    {
        $message = mb_strtolower(trim($message), 'UTF-8');

        // Normalize common leetspeak substitutions before punctuation is
        // removed. This remains deliberately conservative for valid LexTrack
        // terms such as request, original, and clearance.
        $message = strtr($message, [
            '0' => 'o',
            '1' => 'i',
            '3' => 'e',
            '4' => 'a',
            '5' => 's',
            '7' => 't',
            '8' => 'b',
        ]);

        $message = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $message) ?? '';
        $message = preg_replace('/([\p{L}\p{N}])\1+/u', '$1', $message) ?? $message;

        return trim(preg_replace('/\s+/u', ' ', $message) ?? '');
    }

    private function matchesTypoVariant(string $message, string $term): bool
    {
        $compactTerm = $this->compact($term);

        // Limit fuzzy matching to longer terms so ordinary short words are
        // not blocked while common misspellings are still recognized.
        if (mb_strlen($compactTerm, 'UTF-8') < 5) {
            return false;
        }

        $maxDistance = mb_strlen($compactTerm, 'UTF-8') >= 7 ? 2 : 1;
        $tokens = preg_split('/\s+/u', $message, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            if (mb_strlen($token, 'UTF-8') >= 4
                && levenshtein($token, $compactTerm) <= $maxDistance) {
                return true;
            }
        }

        return false;
    }

    private function compact(string $message): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', $message) ?? '';
    }
}
