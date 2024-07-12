<?php

namespace Mk4U\Router;

use Mk4U\Http\Request;
use Mk4U\Http\Response;
use Mk4U\Http\Status;
use Mk4U\Router\Exceptions\RouterException;

/**
 * Router class
 * 
 * Gestiona las rutas y devuelve la accion para la ruta especificada
 */
class Router
{
    /** @param array regex para codificar la url*/
    private const REGEX = [
        '(:any)'      => '[^/]+',         //cualquier caracter excepto /
        '(:alphanum)' => '[a-zA-Z0-9]+',  //caracteres alfanumericos
        '(:num)'      => '[0-9]+',        //caracteres numericos
        '(:alpha)'    => '[a-zA-Z]+',     //caracteres alfabeticos

    ];
    /** @param Request $request Objeto Solicitud HTTP*/
    private static Request $request;
    /** @param Response $response Objeto Respuesta HTTP*/
    private static Response $response;
    /** @param string $template Plantilla de error proporcionada por el usuario*/
    protected static ?array $template;

    /**
     * Resuelve la ruta y procesa la acción
     */
    public static function resolve(RouteCollection $Route): Response
    {
        self::$request = new Request;
        self::$response = new Response();

        foreach ($Route->all() as $route => $value) {

            if (preg_match('#^' . self::placeholder($route) . '$#', self::target(), $matchs)) {
                if (self::httpMethod($value['method'])) {

                    //Callback function
                    if ($value['action'] instanceof \Closure) {
                        return call_user_func_array($value['action'], self::setParameters($matchs));
                    }
                    //Controllers Instances
                    return self::getController($value['action'], self::setParameters($matchs));
                }
                throw new RouterException(Status::MethodNotAllowed);
            }
        }
        throw new RouterException(Status::NotFound);
    }

    /**
     * Marcador de posicion
     *
     * Cambia los marcadores de posición por patrón de expresión regular en la URI
     **/
    private static function placeholder(string $route): string
    {
        return trim(preg_replace(array_keys(self::REGEX), array_values(self::REGEX), $route), '/');
    }

    /**
     * Metodo HTTP
     *
     * Verifica que el metodo HTTP de la solicitud coincida con el metodo
     * especificado en la ruta
     **/
    private static function httpMethod(array|string $method): bool
    {
        if (is_array($method) && in_array(self::$request->getMethod(), $method)) {
            return true;
        }

        if (self::$request->hasMethod($method)) {
            return true;
        }

        return false;
    }

    /**
     * Destino de la solicitud 
     *
     * Establece el segmento de URI del destino de la solicitud 
     **/
    private static function target(): string
    {
        $base = dirname(self::$request->server('SCRIPT_NAME'));

        //return trim(str_replace($base, '', self::$request->getUri()->getPath()), '/');

        return ($base != '/') ? trim(str_replace($base, '', self::$request->getUri()->getPath()), '/') :
            trim(self::$request->getUri()->getPath(), '/');
    }

    /**
     * Devolver parametros
     */
    private static function setParameters(array $matchs): array
    {
        //unset($matchs[0]);
        return array_merge([self::$request, self::$response], array_slice($matchs, 1));
    }

    /**
     * Resuelve e instancia el controlador
     */
    private static function getController(array $action, array $parameters): Response
    {
        $ref = (new \ReflectionClass($action[0]))->newInstance();
        return empty($action[1]) ? self::ctrlMethod($ref, 'index', $parameters) : self::ctrlMethod($ref, $action[1], $parameters);
    }

    /**
     * Resolver metodos del controlador
     */
    private static function ctrlMethod(object $instance, string $method, array $params): Response
    {
        $ref_method =   new \ReflectionMethod($instance, $method);

        if (!$ref_method->isPublic()) throw new \RuntimeException(sprintf("%s::%s no es un método público", get_class($instance), $method));

        return empty($ref_method->getParameters()) ? $instance->$method() : call_user_func_array([$instance, $method], $params);
    }
}
