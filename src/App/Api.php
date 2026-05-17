<?php
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

    protected string $contentType    = 'application/json';
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

    /**
     * Pipeline principal de traitement d'une requête entrante :
     * 1. Détecte et normalise la méthode HTTP (gère X-HTTP-Method-Override et HEAD).
     * 2. Nettoie les inputs.
     * 3. Résout la route via le Router (sinon tente une convention Controller/Action).
     * 4. Instancie le contrôleur et appelle l'action correspondante.
     */
    public function processRequest(): static
    {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            $this->code     = 406;
            $this->response = $this->getHttpCodeMessage(406);
            return $this;
        }

        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $request      = rtrim($_REQUEST['request'], '/');

        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'text/markdown')) {
            $this->contentType = 'text/markdown; charset=utf-8';
        }

        if ($this->method === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD'])) {
            $override = $_SERVER['HTTP_X_HTTP_METHOD'];
            if (in_array($override, ['DELETE', 'PUT'], true)) {
                $this->method = $override;
            } else {
                $headers = $this->getRequestHeaders();
                if (isset($headers['X-HTTP-Method-Override'])
                    && in_array($headers['X-HTTP-Method-Override'], ['PUT', 'DELETE', 'PATCH'], true)
                ) {
                    $this->method = $headers['X-HTTP-Method-Override'];
                } else {
                    $this->code     = 405;
                    $this->response = $this->getHttpCodeMessage(405);
                    return $this;
                }
            }
        } elseif ($this->method === 'HEAD') {
            // HEAD = GET sans corps de réponse (RFC 7231 §4.3.2)
            ob_start();
            $this->method = 'GET';
        }

        $this->cleanRequest($this->method);

        $route  = Router::getInstance();
        $route->setMethod($this->method);
        $result = $route->run($request);

        if (isset($result['controller'])) {
            $this->controllerName = $result['controller'];
            $this->action         = $result['action'] ?? 'index';
            $params               = $result['params'] ?? [];
        } else {
            // Fallback : convention Controller/Action extraite de l'URI
            $params               = explode('/', $request);
            $this->controllerName = array_shift($params);
            if (isset($this->args[0]) && !is_numeric($params[0] ?? '')) {
                $this->action = array_shift($this->args);
            }
        }

        $className = '\G4\Api\Controller\\' . ucfirst(strtolower($this->controllerName));
        $method    = strtolower($this->method) . ucfirst(strtolower($this->action)) . 'Action';

        if (!class_exists($className)) {
            $this->code     = 404;
            $this->response = $this->getHttpCodeMessage(404);
            return $this;
        }

        if (!is_callable([$className, $method])) {
            $this->code     = 404;
            $this->response = $this->getHttpCodeMessage(404);
            return $this;
        }

        if (!empty($this->args)) {
            foreach ($this->args as $key => $value) {
                $params[$key] = $value;
            }
        }

        $this->controller = new $className;

        if (!empty($params)) {
            $this->controller->setParams($params);
        }

        try {
            $this->response = $this->controller->$method();
        } catch (\Exception $e) {
            $this->response = $e->getMessage();
        }

        $this->code = $this->controller->getCode();

        return $this;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    /** Encode la réponse dans le format de contenu configuré (JSON par défaut). */
    public function getFormatedResponseForContent(): string
    {
        if (empty($this->response)) {
            return '';
        }
        return match (true) {
            str_contains($this->contentType, 'text/markdown') => $this->toMarkdown($this->response),
            default                                            => json_encode($this->response, JSON_PRETTY_PRINT),
        };
    }

    /**
     * Convertit récursivement un scalaire ou un tableau en Markdown.
     * Les tableaux associatifs produisent du Markdown structuré (titres + bold keys) ;
     * les listes séquentielles produisent des listes à puces.
     */
    private function toMarkdown(mixed $data, int $depth = 0): string
    {
        if (!is_array($data)) {
            return (string) $data;
        }
        $lines  = [];
        $isList = array_is_list($data);
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $heading = str_repeat('#', min($depth + 2, 6));
                $lines[] = $isList
                    ? $this->toMarkdown($value, $depth + 1)
                    : "$heading $key\n\n" . $this->toMarkdown($value, $depth + 1);
            } else {
                $lines[] = $isList ? "- $value" : "**$key** : $value";
            }
        }
        return implode("\n", $lines);
    }

    /**
     * Envoie les headers puis le corps de la réponse et termine le processus.
     * Pour HEAD, vide le buffer de sortie conformément à la RFC.
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
     * Détecte automatiquement application/json et parse le corps en conséquence.
     * Note : strip_tags/trim ne constituent pas une protection contre l'injection SQL —
     * les modèles utilisent des requêtes préparées à cet effet.
     */
    protected function cleanRequest(string $method): void
    {
        $isJson = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

        $this->args = match ($method) {
            'POST'  => $this->cleanInputs($isJson
                ? (json_decode(file_get_contents('php://input'), true) ?? [])
                : $_POST),
            'PUT'   => $this->cleanInputs((function () use ($isJson): array {
                $raw = file_get_contents('php://input');
                if ($isJson) {
                    return json_decode($raw, true) ?? [];
                }
                parse_str($raw, $data);
                return $data;
            })()),
            default => $this->cleanInputs($this->args),
        };
    }

    /** Supprime les balises HTML et les espaces superflus de façon récursive. */
    protected function cleanInputs(mixed $data): mixed
    {
        if (is_array($data)) {
            return array_map(fn(mixed $v): mixed => $this->cleanInputs($v), $data);
        }
        return trim(strip_tags((string) $data));
    }

    /** Retourne le libellé HTTP associé au code, ou 'Internal Server Error' si inconnu. */
    protected function getHttpCodeMessage(int $code = 500): string
    {
        return self::HTTP_MESSAGES[$code] ?? self::HTTP_MESSAGES[500];
    }

    /**
     * Écrit les headers HTTP de la réponse avec une politique de non-cache.
     * Sans effet si les headers ont déjà été envoyés (ex. pendant les tests).
     */
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

    /**
     * Retourne les headers HTTP de la requête de façon portable.
     * Utilise getallheaders() si disponible (Apache/FPM), sinon reconstruit depuis $_SERVER.
     */
    private function getRequestHeaders(): array
    {
        if (function_exists('getallheaders')) {
            return getallheaders() ?: [];
        }
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', ucwords(strtolower(substr($key, 5)), '_'))] = $value;
            }
        }
        return $headers;
    }
}
