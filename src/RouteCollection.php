<?php

namespace Mk4U\Router;

/**
 * RouteCollection class
 * 
 * Encargada de crear y recolectar las rutas
 */
class RouteCollection
{
    /* @param array $routes Almacena todas las rutas */
    private array $routes = [];

    /* @param string $group Almacena el grupo de la ruta */
    private ?string $group;

    /* @param string $namespace Almacena el namespaces del action */
    private ?string $namespace;

    /**
     * Almacena las rutas
     */
    private function add(array|string $method, string $path, array|callable $action): void
    {
        if (key_exists($this->group . $path, $this->all())) {
            throw new \InvalidArgumentException(sprintf('"%s" ya fue definida con anterioridad', $path));
        }

        if (is_array($action)) {
            $action[0] = $this->namespace . $action[0];
        }

        $this->routes[$this->group . $path] = [
            'method' => $method,
            'action' => $action,
            //'option' => ['before','after']
        ];
    }

    /**
     * Establece las rutas mediante el metodo GET
     */
    public function get(string $path, array|callable $action): self
    {
        $this->add('GET', $path, $action);
        return $this;
    }

    /**
     * Establece las rutas mediante el metodo POST
     */
    public function post(string $path, array|callable $action): self
    {
        $this->add('POST', $path, $action);
        return $this;
    }

    /**
     * Establece las rutas mediante el metodo PUT
     */
    public function put(string $path, array|callable $action): self
    {
        $this->add('POST', $path, $action);
        return $this;
    }

    /**
     * Establece las rutas mediante el metodo DELETE
     */
    public function delete(string $path, array|callable $action): self
    {
        $this->add('POST', $path, $action);
        return $this;
    }

    /**
     * Establece las rutas con varios metodos HTTP
     **/
    public function map(array $methods, string $path, array|callable $action): self
    {
        //Pasar a mayuscula los metodos
        $methods = array_map('strtoupper', $methods);
        $this->add($methods, $path, $action);

        return $this;
    }

    /**
     * Establece las rutas en grupos
     */
    public function group(string $path, callable $callbak): self
    {
        $this->group = $path;
        $callbak($this);
        $this->group = null;

        return $this;
    }

    public function namespace(string $namespace): self
    {
        $this->namespace = $namespace . '\\';
        return $this;
    }

    /**
     * Muestra todas las rutas almacenadas
     */
    public function all(): array
    {
        return $this->routes;
    }

    /**
     * Almacena las rutas directamente
     */
    public function save(array $routes = []): void
    {
        $this->routes = $routes;
    }

    /**
     * Depurar con vardump
     */
    public function __debugInfo(): array
    {
        return $this->all();
    }
}
