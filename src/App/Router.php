<?php
namespace G4\Api\App;

use G4\Api\Config\Route as Config;

/**
 * Routeur HTTP minimaliste basé sur le Singleton.
 * Les routes sont définies une seule fois via Config\Route::load() au premier accès.
 */
class Router extends SingletonAbstract
{
    private array  $routes    = [];
    private string $baseroute = '';
    private string $method    = '';

    /** Retourne l'instance unique et charge les routes si elles ne l'ont pas encore été. */
    public static function getInstance(): static
    {
        $instance = parent::getInstance();
        if (empty($instance->routes)) {
            Config::load($instance);
        }
        return $instance;
    }

    /**
     * Définit la méthode HTTP courante à faire correspondre lors du dispatch.
     * @throws \InvalidArgumentException si la méthode n'est pas dans la liste autorisée.
     */
    public function setMethod(string $method): void
    {
        $allowed = ['GET' => true, 'POST' => true, 'DELETE' => true, 'PUT' => true, 'HEAD' => true];
        $method  = strtoupper(trim($method));

        if (!isset($allowed[$method])) {
            throw new \InvalidArgumentException(sprintf(
                'Router: method [%s] is not allowed. Expected: %s',
                $method,
                implode(', ', array_keys($allowed))
            ));
        }
        $this->method = $method;
    }

    /**
     * Enregistre une route pour une ou plusieurs méthodes HTTP séparées par '|'.
     * Le pattern est une regex (les groupes capturants sont passés en arguments à $fn).
     */
    public function match(string $methods, string $pattern, callable $fn): static
    {
        $pattern = $this->baseroute . '/' . trim($pattern, '/');
        $pattern = $this->baseroute ? rtrim($pattern, '/') : $pattern;

        foreach (explode('|', $methods) as $method) {
            $this->routes[$method][] = ['pattern' => $pattern, 'fn' => $fn];
        }
        return $this;
    }

    /** Parcourt les routes enregistrées pour la méthode courante et retourne le résultat du premier match. */
    public function run(string $uri): ?array
    {
        if (isset($this->routes[$this->method])) {
            return $this->handle($this->routes[$this->method], $uri);
        }
        return null;
    }

    /**
     * Tente de faire correspondre l'URI à chaque route.
     * Utilise PREG_OFFSET_CAPTURE pour extraire précisément les groupes capturants
     * même lorsqu'ils sont adjacents dans l'URI.
     */
    private function handle(array $routes, string $uri): ?array
    {
        foreach ($routes as $route) {
            if (!preg_match_all('#^' . $route['pattern'] . '$#', $uri, $matches, PREG_OFFSET_CAPTURE)) {
                continue;
            }

            $matches = array_slice($matches, 1);
            $params  = array_map(function (array $match, int $index) use ($matches): ?string {
                if (isset($matches[$index + 1][0]) && is_array($matches[$index + 1][0])) {
                    return trim(substr($match[0][0], 0, $matches[$index + 1][0][1] - $match[0][1]), '/');
                }
                return isset($match[0][0]) ? trim($match[0][0], '/') : null;
            }, $matches, array_keys($matches));

            return call_user_func_array($route['fn'], $params);
        }
        return null;
    }
}
