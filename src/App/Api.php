<?php

declare(strict_types=1);

namespace G4\Api\App;

/**
 * Noyau de l'API : réception, dispatch et envoi des réponses HTTP.
 * Point d'entrée unique appelé depuis public/index.php.
 */
class Api
{
    private const array HTTP_MESSAGES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested Range Not Satisfiable',
        417 => 'Expectation Failed',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
    ];

    protected string $contentType    = 'application/json; charset=utf-8';
    protected string $method         = '';
    protected array  $args           = [];
    protected mixed  $controller     = null;
    protected string $controllerName = '';
    protected string $action         = 'Index';
    protected int    $code           = 500;
    protected mixed  $response       = '';

    public function getResponse(): mixed
    {
        return $this->response;
    }

    public function setResponse(mixed $response): void
    {
        $this->response = $response;
    }

    public function getController(): mixed
    {
        return $this->controller;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /**
     * Pipeline principal de traitement d'une requête entrante.
     */
    public function processRequest(): static
    {
        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        if ($requestMethod === '') {
            $this->code     = 406;
            $this->response = $this->getHttpCodeMessage(406);
            return $this;
        }

        $this->method = strtoupper($requestMethod);
        $this->detectContentType();
        $this->normalizeHttpMethod();
        $this->cleanRequest($this->method);

        $params = $this->resolveRoute();

        if (!$this->dispatchController($params)) {
            return $this;
        }

        $this->code = $this->controller->getCode();
        return $this;
    }

    /** Détecte si le client accepte le Markdown. */
    protected function detectContentType(): void
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains((string) $accept, 'text/markdown')) {
            $this->contentType = 'text/markdown; charset=utf-8';
        }
    }

    /**
     * Normalise la méthode HTTP : gère X-HTTP-Method-Override et HEAD.
     * Retourne false si la méthode n'est pas autorisée (405).
     */
    protected function normalizeHttpMethod(): bool
    {
        if ($this->method === 'HEAD') {
            ob_start();
            $this->method = 'GET';
            return true;
        }

        if ($this->method !== 'POST') {
            return true;
        }

        if (array_key_exists('HTTP_X_HTTP_METHOD', $_SERVER)) {
            $override = $_SERVER['HTTP_X_HTTP_METHOD'];
            if (in_array($override, ['DELETE', 'PUT'], strict: true)) {
                $this->method = $override;
                return true;
            }
        }

        $headers = $this->getRequestHeaders();
        if (array_key_exists('X-HTTP-Method-Override', $headers)
            && in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH'], strict: true)
        ) {
            $this->method = $headers['X-HTTP-Method-Override'];
            return true;
        }

        $this->code     = 405;
        $this->response = $this->getHttpCodeMessage(405);
        return false;
    }

    /** Résout la route et retourne les paramètres extraits. */
    protected function resolveRoute(): array
    {
        $request = rtrim((string) ($_GET['request'] ?? $_POST['request'] ?? ''), '/');

        $route = Router::getInstance();
        $route->setMethod($this->method);
        $result = $route->run($request);

        if (is_array($result) && array_key_exists('controller', $result)) {
            $this->controllerName = $result['controller'];
            $this->action         = $result['action'] ?? 'index';
            return $result['params'] ?? [];
        }

        return $this->fallbackRoute($request);
    }

    /** Fallback : convention Controller/Action extraite de l'URI. */
    protected function fallbackRoute(string $request): array
    {
        $params               = explode('/', $request);
        $this->controllerName = array_shift($params);
        if (array_key_exists(0, $this->args) && !is_numeric($params[0] ?? '')) {
            $this->action = array_shift($this->args);
        }
        return $params;
    }

    /**
     * Instancie le contrôleur et exécute l'action.
     * Retourne false si le contrôleur ou l'action n'existe pas (404).
     */
    protected function dispatchController(array $params): bool
    {
        $className = '\G4\Api\Controller\\' . ucfirst($this->controllerName);
        $method    = strtolower($this->method) . ucfirst($this->action) . 'Action';

        if (!class_exists($className) || !method_exists($className, $method)) {
            $this->code     = 404;
            $this->response = $this->getHttpCodeMessage(404);
            return false;
        }

        if ($this->args !== []) {
            foreach ($this->args as $key => $value) {
                $params[$key] = $value;
            }
        }

        $this->controller = new $className;
        if ($params !== []) {
            $this->controller->setParams($params);
        }

        try {
            $this->response = $this->controller->$method();
        } catch (\Exception $exception) {
            error_log('[API Dispatch] ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
            $this->response = 'Internal Server Error';
        }

        return true;
    }

    /** Encode la réponse dans le format de contenu configuré (JSON par défaut). */
    public function getFormatedResponseForContent(): string
    {
        if (in_array($this->response, [null, '', []], strict: true)) {
            return '';
        }

        return match (true) {
            str_contains($this->contentType, 'text/markdown') => $this->toMarkdown($this->response),
            default                                            => json_encode($this->response, JSON_PRETTY_PRINT),
        };
    }

    /**
     * Convertit récursivement un scalaire ou un tableau en Markdown.
     */
    private function toMarkdown(mixed $data, int $depth = 0): string
    {
        if (!is_array($data)) {
            return (string) $data;
        }

        $lines  = [];
        $isList = array_is_list($data);
        foreach ($data as $key => $value) {
            if (!is_array($value)) {
                $lines[] = $isList ? '- ' . $value : sprintf('**%s** : %s', $key, $value);
                continue;
            }

            $heading = str_repeat('#', min($depth + 2, 6));
            $lines[] = $isList
                ? $this->toMarkdown($value, $depth + 1)
                : "{$heading} {$key}\n\n" . $this->toMarkdown($value, $depth + 1);
        }

        return implode("\n", $lines);
    }

    /**
     * Envoie les headers puis le corps de la réponse et termine le processus.
     */
    public function sendResponse(): never
    {
        $this->responseHeader();
        echo $this->getFormatedResponseForContent();

        if (($_SERVER['REQUEST_METHOD'] ?? '') === 'HEAD') {
            ob_end_clean();
        }

        exit;
    }

    /**
     * Peuple $args depuis le corps de la requête selon la méthode HTTP.
     */
    protected function cleanRequest(string $method): void
    {
        $isJson = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

        $this->args = match ($method) {
            'POST'  => $this->cleanInputs($isJson
                ? (json_decode(file_get_contents('php://input'), true) ?? [])
                : $_POST),
            'PUT'   => $this->cleanInputs($this->parsePutBody()),
            default => $this->cleanInputs($this->args),
        };
    }

    /** Parse le corps d'une requête PUT (JSON ou form-encoded). */
    protected function parsePutBody(): array
    {
        $raw = file_get_contents('php://input');
        $isJson = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
        if ($isJson) {
            return json_decode($raw, true) ?? [];
        }
        parse_str($raw, $data);
        return $data;
    }

    /** Supprime les balises HTML et les espaces superflus de façon récursive. */
    protected function cleanInputs(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map($this->cleanInputs(...), $data);
        }

        return trim(strip_tags((string) $data));
    }

    /** Retourne le libellé HTTP associé au code. */
    protected function getHttpCodeMessage(int $code = 500): string
    {
        return self::HTTP_MESSAGES[$code] ?? self::HTTP_MESSAGES[500];
    }

    /** Écrit les headers HTTP de la réponse avec une politique de non-cache. */
    protected function responseHeader(): void
    {
        if (headers_sent()) {
            return;
        }

        header('HTTP/1.1 ' . $this->code . ' ' . $this->getHttpCodeMessage($this->code));
        header('Content-Type: ' . $this->contentType);
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Pragma: no-cache');
    }

    /** Retourne les headers HTTP de la requête de façon portable. */
    private function getRequestHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?? [];
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!str_starts_with((string) $key, 'HTTP_')) {
                continue;
            }
            $headers[str_replace('_', '-', ucwords(strtolower(substr((string) $key, 5)), '_'))] = $value;
        }

        return $headers;
    }
}
