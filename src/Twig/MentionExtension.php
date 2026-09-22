<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Highlights @mentions in comment text visually. This is display-only: it
 * marks up anything shaped like "@word", independent of whether that word
 * matched a real, notifiable user (that check lives in IssueController,
 * where the actual notifications are created).
 */
class MentionExtension extends AbstractExtension
{
    private const MENTION_PATTERN = '/@([a-zA-Z0-9_.\-]{3,180})/';

    public function getFilters(): array
    {
        return [
            new TwigFilter('highlight_mentions', $this->highlightMentions(...), ['is_safe' => ['html']]),
        ];
    }

    public function highlightMentions(?string $text): string
    {
        $escaped = htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');

        return preg_replace(self::MENTION_PATTERN, '<span class="mention">@$1</span>', $escaped);
    }
}
