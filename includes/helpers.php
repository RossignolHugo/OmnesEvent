<?php
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function rediriger(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function messageFlash(?string $type = null, ?string $message = null): ?array
{
    if ($type !== null && $message !== null) {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
        return null;
    }

    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }

    return null;
}

function genererCodeBillet(): string
{
    return 'TKT-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(4)));
}
