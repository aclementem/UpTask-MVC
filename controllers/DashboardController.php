<?php

namespace Controllers;

use MVC\Router;
use Model\Usuario;
use Model\Proyecto;

class DashboardController
{
    public static function index(Router $router)
    {
        session_start();
        isAuth();

        $proyectos = Proyecto::belongsTo('propietarioId', $_SESSION['id']);

        $router->render('dashboard/index', [
            'titulo' => 'Proyectos',
            'proyectos' => $proyectos
        ]);
    }

    public static function crear_proyecto(Router $router)
    {
        session_start();
        isAuth();

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $proyecto = new Proyecto($_POST);
            $alertas = $proyecto->validarProyecto();

            if (empty($alertas)) {
                // Generar una URL única
                $hash = md5(uniqid());
                $proyecto->url = $hash;
                // Almacenar creador del proyecto
                $proyecto->propietarioId = $_SESSION['id'];
                // Guardar Proyecto
                $proyecto->guardar();

                header('Location: /proyecto?url=' . $proyecto->url);
            }
        }

        $router->render('dashboard/crear-proyecto', [
            'titulo' => 'Crear Proyecto',
            'alertas' => $alertas
        ]);
    }

    public static function proyecto(Router $router)
    {
        session_start();
        isAuth();

        $token = $_GET['url'];

        if (!$token) header('Location: /dashboard');
        // Revisar que la persona que visita el proyecto es el que lo creó
        $proyecto = Proyecto::where('url', $token);

        if ($proyecto->propietarioId !== $_SESSION['id']) header('Location: /dashboard');
        // debuguear($proyecto);

        $router->render('dashboard/proyecto', [
            'titulo' => $proyecto->proyecto
        ]);
    }

    public static function perfil(Router $router)
    {
        session_start();
        isAuth();

        $usuario = Usuario::find($_SESSION['id']);
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarPerfil();
            //debuguear($usuario);

            if (empty($alertas)) {

                $existeUsuario = Usuario::where('email', $usuario->email);

                if ($existeUsuario && $existeUsuario->id !== $usuario->id) {

                    Usuario::setAlerta('error', 'Ya existe un usuario con este email');
                    $alertas = $usuario->getAlertas();
                } else {
                    $usuario->guardar();

                    Usuario::setAlerta('exito', 'Guardado correctamente');
                    $alertas = $usuario->getAlertas();

                    $_SESSION['nombre'] = $usuario->nombre;
                    $_SESSION['email'] = $usuario->email;
                }
            }
        }

        $router->render('dashboard/perfil', [
            'titulo' => 'Perfil',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function cambiar_password(Router $router)
    {
        session_start();
        isAuth();
        $usuario = Usuario::find($_SESSION['id']);

        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {


            $usuario->sincronizar($_POST);
            $alertas = $usuario->nuevoPassword();

            if (empty($alertas)) {
                $resultado = $usuario->comprobarPassword();

                if ($resultado) {
                    $usuario->password = $usuario->password_nueva;
                    unset($usuario->password_actual);
                    unset($usuario->password_nueva);
                    $usuario->hashPassword();
                    $resultado = $usuario->guardar();

                    if ($resultado) {
                        Usuario::setAlerta('exito', 'Password actualizada correctamente');
                        $alertas = $usuario->getAlertas();
                    }
                } else {
                    Usuario::setAlerta('error', 'Password actual incorrecta');
                    $alertas = $usuario->getAlertas();
                }
            }
            // debuguear($usuario);
        }

        $router->render('dashboard/cambiar-password', [
            'titulo' => 'Cambiar Password',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }
}
