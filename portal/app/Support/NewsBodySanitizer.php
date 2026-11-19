<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class NewsBodySanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h2', 'h3', 'h4',
        'ul', 'ol', 'li', 'blockquote', 'a', 'span', 'div', 'hr', 'img',
        'figure', 'figcaption',
    ];

    public static function clean(?string $html): string
    {
        if (!$html) {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="news-body-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = null;
        foreach ($document->getElementsByTagName('div') as $candidate) {
            if ($candidate->getAttribute('id') === 'news-body-root') {
                $root = $candidate;
                break;
            }
        }
        if (!$root) {
            return '';
        }

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= self::renderNode($child);
        }

        return $output;
    }

    private static function renderNode(DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return htmlspecialchars($node->nodeValue ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if (!$node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math'], true)) {
            return '';
        }

        $children = '';
        foreach (iterator_to_array($node->childNodes) as $child) {
            $children .= self::renderNode($child);
        }

        if ($tag === 'font') {
            $styles = self::safeStyles($node);
            $style = $styles ? ' style="' . htmlspecialchars(implode(';', $styles), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"' : '';
            return '<span' . $style . '>' . $children . '</span>';
        }

        if ($tag === 'img') {
            $src = trim($node->getAttribute('src'));
            $scheme = strtolower((string) parse_url($src, PHP_URL_SCHEME));
            $isLocalStorage = str_starts_with($src, '/storage/') || str_starts_with($src, 'storage/');
            if ($src === '' || (!in_array($scheme, ['http', 'https'], true) && !$isLocalStorage)) {
                return '';
            }
            $attributes = ' src="' . htmlspecialchars($src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            $alt = trim($node->getAttribute('alt'));
            if ($alt !== '') {
                $attributes .= ' alt="' . htmlspecialchars($alt, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
            }
            $attributes .= ' loading="lazy"';
            return '<img' . $attributes . '>';
        }

        if (!in_array($tag, self::ALLOWED_TAGS, true)) {
            return $children;
        }

        $attributes = '';
        if ($tag === 'a') {
            $href = trim($node->getAttribute('href'));
            $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
            if ($href !== '' && in_array($scheme, ['http', 'https', 'mailto'], true)) {
                $attributes .= ' href="' . htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
                $attributes .= ' rel="noopener noreferrer"';
            }
        }

        $styles = self::safeStyles($node);
        if ($styles !== []) {
            $attributes .= ' style="' . htmlspecialchars(implode(';', $styles), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '"';
        }

        if (in_array($tag, ['br', 'hr'], true)) {
            return '<' . $tag . $attributes . '>';
        }

        return '<' . $tag . $attributes . '>' . $children . '</' . $tag . '>';
    }

    private static function safeStyles(DOMElement $element): array
    {
        $styles = [];
        $declarations = explode(';', $element->getAttribute('style'));
        if (strtolower($element->tagName) === 'font') {
            if ($element->hasAttribute('face')) $declarations[] = 'font-family:' . $element->getAttribute('face');
            if ($element->hasAttribute('color')) $declarations[] = 'color:' . $element->getAttribute('color');
            if ($element->hasAttribute('size')) {
                $fontSizes = ['1' => '10px', '2' => '13px', '3' => '16px', '4' => '18px', '5' => '24px', '6' => '32px', '7' => '40px'];
                $size = $fontSizes[$element->getAttribute('size')] ?? null;
                if ($size) $declarations[] = 'font-size:' . $size;
            }
        }

        foreach ($declarations as $declaration) {
            if (!str_contains($declaration, ':')) {
                continue;
            }
            [$property, $value] = array_map('trim', explode(':', $declaration, 2));
            $property = strtolower($property);
            $value = trim($value, " \t\n\r\0\x0B\"'");

            if ($property === 'text-align' && in_array(strtolower($value), ['left', 'center', 'right', 'justify'], true)) {
                $styles[] = 'text-align:' . strtolower($value);
            } elseif ($property === 'font-family') {
                $font = strtolower($value);
                if (in_array($font, ['arial', 'georgia', 'times new roman', 'verdana', 'sans-serif', 'serif', 'monospace'], true)) {
                    $styles[] = 'font-family:' . $font;
                }
            } elseif ($property === 'font-size' && preg_match('/^(?:[1-3]?[0-9]|40)(?:px|pt)$/i', $value)) {
                $styles[] = 'font-size:' . strtolower($value);
            } elseif ($property === 'color' && preg_match('/^#[0-9a-f]{3}(?:[0-9a-f]{3})?$/i', $value)) {
                $styles[] = 'color:' . strtolower($value);
            }
        }

        return array_values(array_unique($styles));
    }
}
