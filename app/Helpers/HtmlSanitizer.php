<?php

/**
 * Limpia HTML enviado desde el editor enriquecido (Quill) del panel de administración.
 * Solo se permiten etiquetas de formato; las imágenes se habilitan solo donde hacen falta. Se eliminan scripts, estilos,
 * atributos (onclick, style, etc.) y enlaces con protocolos peligrosos.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's',
        'ol', 'ul', 'li', 'h1', 'h2', 'h3', 'h4', 'blockquote', 'a', 'img',
    ];

    private const DROP_WITH_CONTENT = [
        'script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button',
        'textarea', 'select', 'svg', 'math', 'link', 'meta', 'base', 'template',
    ];

    private const ALLOWED_PROTOCOLS = ['http', 'https', 'mailto', 'tel'];

    /**
     * @return string|null HTML limpio, o null si el contenido está vacío o excede $maxLength.
     */
    public static function clean($html, int $maxLength = 20000, bool $allowImages = false): ?string
    {
        if (!is_string($html)) {
            return null;
        }

        $html = trim($html);
        if ($html === '') {
            return null;
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML(
            '<?xml encoding="UTF-8"><div>' . $html . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return null;
        }

        $roots = $document->getElementsByTagName('div');
        $root = $roots->length > 0 ? $roots->item(0) : null;
        if ($root === null) {
            return null;
        }

        self::cleanChildren($root, $allowImages);

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $document->saveHTML($child);
        }
        $output = trim($output);

        // Quill deja "<p><br></p>" cuando el editor está vacío.
        $textOnly = trim(html_entity_decode(strip_tags($output), ENT_QUOTES, 'UTF-8'));
        $textOnly = trim(str_replace("\xC2\xA0", ' ', $textOnly));
        if ($textOnly === '' && strpos($output, '<img ') === false) {
            return null;
        }

        if (mb_strlen($output) > $maxLength) {
            return null;
        }

        return $output;
    }

    private static function cleanChildren(DOMNode $parent, bool $allowImages): void
    {
        $children = [];
        foreach ($parent->childNodes as $child) {
            $children[] = $child;
        }

        foreach ($children as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                continue;
            }

            if ($child->nodeType !== XML_ELEMENT_NODE) {
                $parent->removeChild($child);
                continue;
            }

            $tag = strtolower($child->nodeName);

            if (in_array($tag, self::DROP_WITH_CONTENT, true)) {
                $parent->removeChild($child);
                continue;
            }

            if ($tag === 'img' && !$allowImages) {
                $parent->removeChild($child);
                continue;
            }

            self::cleanChildren($child, $allowImages);

            if (!in_array($tag, self::ALLOWED_TAGS, true)) {
                // Etiqueta no permitida: se conserva solo su contenido.
                while ($child->firstChild) {
                    $parent->insertBefore($child->firstChild, $child);
                }
                $parent->removeChild($child);
                continue;
            }

            self::cleanAttributes($child, $tag);
        }
    }

    private static function cleanAttributes(DOMElement $element, string $tag): void
    {
        if ($tag === 'img') {
            $src = self::safeImageUrl($element->getAttribute('src'));
            $alt = trim($element->getAttribute('alt'));
            while ($element->attributes->length > 0) {
                $element->removeAttributeNode($element->attributes->item(0));
            }
            if ($src === null) {
                $element->parentNode?->removeChild($element);
                return;
            }
            $element->setAttribute('src', $src);
            if ($alt !== '') {
                $element->setAttribute('alt', mb_substr($alt, 0, 255));
            }
            return;
        }

        $href = null;
        if ($tag === 'a' && $element->hasAttribute('href')) {
            $href = self::safeUrl($element->getAttribute('href'));
        }

        $names = [];
        foreach ($element->attributes as $attribute) {
            $names[] = $attribute->nodeName;
        }
        foreach ($names as $name) {
            $element->removeAttribute($name);
        }

        if ($tag === 'a' && $href !== null) {
            $element->setAttribute('href', $href);
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function safeImageUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if ($scheme === 'https' && filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        if (preg_match('#^(?:/(?!/)|assets/|uploads/)[A-Za-z0-9_./?=&%-]+$#D', $url) === 1
            && strpos($url, '..') === false) {
            return $url;
        }

        return null;
    }

    private static function safeUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        // Quita caracteres de control que algunos navegadores ignoran dentro del protocolo.
        $compact = preg_replace('/[\x00-\x20\x7F]+/', '', $url);
        if (!preg_match('/^([a-z][a-z0-9+.\-]*):/i', $compact, $matches)) {
            return null;
        }

        return in_array(strtolower($matches[1]), self::ALLOWED_PROTOCOLS, true) ? $url : null;
    }
}
