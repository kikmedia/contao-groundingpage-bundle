<?php

declare(strict_types=1);

const BASE_URL = 'http://127.0.0.1:8000';

final readonly class Response
{
    public function __construct(public int $status, public string $body, public string $url)
    {
    }

    public function contains(string $needle): bool
    {
        return str_contains($this->body, $needle);
    }
}

final class Browser
{
    private readonly string $cookies;

    public function __construct()
    {
        $this->cookies = tempnam(sys_get_temp_dir(), 'cookies');
    }

    public function get(string $url): Response
    {
        return $this->send($url, null);
    }

    public function post(string $url, array $data): Response
    {
        return $this->send($url, $data);
    }

    public function login(string $username, string $password): Response
    {
        $page = $this->get('/contao/login');
        $data = [];

        foreach (xpath($page->body)->query('//input[@type="hidden"]') as $input) {
            $data[$input->getAttribute('name')] = $input->getAttribute('value');
        }

        return $this->post($page->url, $data + ['username' => $username, 'password' => $password]);
    }

    private function send(string $url, array|null $data): Response
    {
        $url = absoluteUrl($url);
        $curl = curl_init($url);
        curl_setopt_array($curl, [
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_COOKIEJAR => $this->cookies,
            \CURLOPT_COOKIEFILE => $this->cookies,
            \CURLOPT_FOLLOWLOCATION => true,
            \CURLOPT_TIMEOUT => 120,
        ]);

        if (null !== $data) {
            curl_setopt($curl, \CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($curl, \CURLOPT_HTTPHEADER, ['Origin: ' . BASE_URL, 'Referer: ' . $url]);
        }

        $body = (string) curl_exec($curl);

        return new Response(curl_getinfo($curl, \CURLINFO_HTTP_CODE), $body, curl_getinfo($curl, \CURLINFO_EFFECTIVE_URL));
    }
}

function env(string $name): string
{
    $value = getenv($name);

    if (false === $value || '' === $value) {
        throw new RuntimeException("Missing environment variable $name");
    }

    return $value;
}

function database(): PDO
{
    $url = parse_url(env('DATABASE_URL'));

    return new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $url['host'], $url['port'] ?? 3306, ltrim($url['path'], '/')),
        rawurldecode($url['user'] ?? ''),
        rawurldecode($url['pass'] ?? ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
    );
}

function hasColumn(PDO $database, string $table, string $column): bool
{
    return false !== $database->query("SHOW COLUMNS FROM `$table` LIKE '$column'")->fetch();
}

function check(string $name, bool $passed, string $info = ''): void
{
    $GLOBALS['failures'] = ($GLOBALS['failures'] ?? 0) + ($passed ? 0 : 1);
    $GLOBALS['checks'] = ($GLOBALS['checks'] ?? 0) + 1;

    echo ($passed ? 'PASS ' : 'FAIL ') . $name . (!$passed && '' !== $info ? "  [$info]" : '') . "\n";
}

function linkedData(string $html): array
{
    preg_match_all('~<script type="application/ld\+json">(.*?)</script>~s', $html, $blocks);
    $nodes = [];

    foreach ($blocks[1] as $block) {
        $data = json_decode(trim($block), true) ?? ['@type' => 'INVALID'];

        if ('https://schema.contao.org' !== ($data['@context'] ?? '')) {
            array_push($nodes, ...($data['@graph'] ?? [$data]));
        }
    }

    return $nodes;
}

function nodeOfType(array $nodes, string $type): array
{
    foreach ($nodes as $node) {
        if ($type === ($node['@type'] ?? null)) {
            return $node;
        }
    }

    return [];
}

function absoluteUrl(string $url): string
{
    if (str_starts_with($url, 'http')) {
        return $url;
    }

    return BASE_URL . '/' . ltrim($url, '/');
}

function xpath(string $html): DOMXPath
{
    $document = new DOMDocument();
    @$document->loadHTML('<?xml encoding="UTF-8">' . $html);

    return new DOMXPath($document);
}

function matchesQuery(string $url, array $parts): bool
{
    foreach ($parts as $part) {
        if (!preg_match('~[?&]' . preg_quote($part, '~') . '(?:&|$)~', $url)) {
            return false;
        }
    }

    return true;
}

function targets(string $html): array
{
    preg_match_all('~(?:href|action)="([^"]+)"~', $html, $matches);

    return array_map(html_entity_decode(...), $matches[1]);
}

function linkTo(string $html, array $parts): string|null
{
    foreach (targets($html) as $url) {
        if (matchesQuery($url, $parts)) {
            return $url;
        }
    }

    return null;
}

function canCreate(string $html): bool
{
    foreach (targets($html) as $url) {
        if (!str_contains($url, 'do=favorites') && preg_match('~[?&](act=create|mode=create)(&|$)~', $url)) {
            return true;
        }
    }

    return false;
}

function operate(Browser $browser, string $html, array $parts): Response|null
{
    $page = xpath($html);

    foreach ($page->query('//form[@action]') as $form) {
        $action = $form->getAttribute('action');

        if ('post' === strtolower($form->getAttribute('method')) && matchesQuery($action, $parts)) {
            $data = [];

            foreach ($page->query('.//input[@type="hidden"]', $form) as $input) {
                $data[$input->getAttribute('name')] = $input->getAttribute('value');
            }

            return $browser->post($action, $data);
        }
    }

    foreach ($page->query('//a[@href]') as $anchor) {
        if (matchesQuery($anchor->getAttribute('href'), $parts)) {
            return $browser->get($anchor->getAttribute('href'));
        }
    }

    return null;
}

function submit(Browser $browser, string|null $url, array $changes, string $form = ''): Response
{
    if (null === $url) {
        return new Response(0, '', '');
    }

    $page = xpath($browser->get($url)->body);
    $marker = '' === $form ? '[starts-with(@value, "tl_") and @value!="tl_filters"]' : "[@value=\"$form\"]";
    $form = $page->query("//form[.//input[@name=\"FORM_SUBMIT\"]$marker]")->item(0);

    if (null === $form) {
        return new Response(0, '', $url);
    }

    $pairs = [];

    foreach ($page->query('.//input|.//select|.//textarea', $form) as $field) {
        $name = $field->getAttribute('name');
        $type = strtolower($field->getAttribute('type'));

        if ('' === $name || $field->hasAttribute('disabled') || in_array($type, ['submit', 'button', 'image', 'file'], true)) {
            continue;
        }

        if (in_array($type, ['checkbox', 'radio'], true) && !$field->hasAttribute('checked')) {
            continue;
        }

        if ('select' === $field->nodeName) {
            $options = $page->query('.//option[@selected]', $field);

            if (0 === $options->length && !$field->hasAttribute('multiple')) {
                $options = $page->query('.//option', $field);
            }

            $value = $options->item(0)?->getAttribute('value') ?? '';
        } elseif ('textarea' === $field->nodeName) {
            $value = $field->textContent;
        } else {
            $value = $field->getAttribute('value');
        }

        $pairs[] = urlencode($name) . '=' . urlencode($value);
    }

    parse_str(implode('&', $pairs), $data);

    return $browser->post($form->getAttribute('action') ?: $url, array_replace($data, $changes, ['save' => '']));
}
