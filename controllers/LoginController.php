<?php

namespace Controllers;

use MVC\Router;
use Classes\Email;
use Model\Usuario;

class LoginController
{
    public static function login(Router $router)
    {
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarLogin();

            if (empty($alertas)) {
                $usuario = Usuario::where('email', $usuario->email);

                if (!$usuario || !$usuario->confirmado) {
                    Usuario::setAlerta('error', 'El usuario no existe o no esta confirmado');
                } else {
                    // El usuario  existe , revisar password
                    if (password_verify($_POST['password'], $usuario->password)) {
                        // Iniciamos sesion
                        session_start();
                        $_SESSION['id'] = $usuario->id;
                        $_SESSION['nombre'] = $usuario->nombre;
                        $_SESSION['email'] = $usuario->email;
                        $_SESSION['login'] = true;

                        // Redireccionar
                        header('Location: /dashboard');
                    } else {
                        Usuario::setAlerta('error', 'El password es incorrecto');
                    }
                }
            }
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/login', [
            'titulo' => 'Iniciar Sesión',
            'alertas' => $alertas
        ]);
    }

    public static function logout()
    {
        session_start();
        $_SESSION = [];
        header('Location: /');
    }

    public static function crear(Router $router)
    {
        $usuario = new Usuario;
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarNuevaCuenta();

            if (empty($alertas)) {
                $existeUsuario = Usuario::where('email', $usuario->email);

                if ($existeUsuario) {
                    Usuario::setAlerta('error', 'El usuario ' . $usuario->email . ' ya esta registrado');
                    $alertas = Usuario::getAlertas();
                } else {
                    // Hashear el Password
                    $usuario->hashPassword();
                    // Eliminar atributos que no existen en la BD
                    unset($usuario->password2);
                    // Generar Token
                    $usuario->crearToken();
                    // Crear nuevo Usuario
                    $resultado = $usuario->guardar();

                    // Enviar Email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);

                    $email->enviarConfirmacion();

                    if ($resultado) {
                        header('Location: /mensaje');
                    }
                }
            }
        }

        $router->render('auth/crear', [
            'titulo' => 'Crear tu cuenta',
            'usuario' => $usuario,
            'alertas' => $alertas
        ]);
    }

    public static function olvide(Router $router)
    {
        $alertas = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $usuario = new Usuario($_POST);
            $alertas = $usuario->validarEmail();

            if (empty($alertas)) {
                $usuario = Usuario::where('email', $usuario->email);

                if ($usuario && $usuario->confirmado) {
                    // Generar nuevo token
                    $usuario->crearToken();
                    unset($usuario->password2);
                    // Actualizar Usuario
                    $usuario->guardar();
                    // Enviar email
                    $email = new Email($usuario->email, $usuario->nombre, $usuario->token);
                    $email->enviarInstrucciones();
                    // Generar alerta
                    Usuario::setAlerta('exito', 'Hemos enviado las instrucciones a tu email');
                    //debuguear($usuario);
                } else {
                    Usuario::setAlerta('error', 'No existe ningún usuario con ese email o no esta confirmado');
                }
            }
        }
        $alertas = Usuario::getAlertas();

        $router->render('auth/olvide', [
            'titulo' => 'Recupera tu password',
            'alertas' => $alertas
        ]);
    }

    public static function reestablecer(Router $router)
    {
        $token = s($_GET['token']);
        $mostrar = true;

        if (!$token) {
            header('Location: /');
        }

        // Encontrar al usuario con ese token
        $usuario = Usuario::where('token', $token);

        if (empty($usuario)) {
            // Ningun usuario con ese token
            Usuario::setAlerta('error', 'Token no válido');
            $mostrar = false;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Añadir el nuevo password
            $usuario->sincronizar($_POST);
            $alertas = $usuario->validarPassword();
            //debuguear($usuario);

            if (empty($alertas)) {
                // Hashear el nuevo Password
                $usuario->hashPassword();
                unset($usuario->password2);
                // Eliminar token
                $usuario->token = null;
                // Guardar en la base de datos
                $resultado = $usuario->guardar();
                // Redireccionar
                if ($resultado) {
                    // header('Location: /');
                    Usuario::setAlerta('exito', 'Password reestablecida con éxito');
                    header("refresh:3;url=/"); // Enseña alerta y refresca pagina a los 3 segundos
                }
            }
        }

        $alertas = Usuario::getAlertas();

        $router->render('auth/reestablecer', [
            'titulo' => 'Reestablecer tu password',
            'alertas' => $alertas,
            'mostrar' => $mostrar
        ]);
    }

    public static function mensaje(Router $router)
    {
        $router->render('auth/mensaje', [
            'titulo' => 'Cuenta creada'
        ]);
    }

    public static function confirmar(Router $router)
    {
        $token = s($_GET['token']);

        if (!$token) {
            header('Location: /');
        }

        // Encontrar al usuario con ese token
        $usuario = Usuario::where('token', $token);

        if (empty($usuario)) {
            // Ningun usuario con ese token
            Usuario::setAlerta('error', 'Token no válido');
        } else {
            // Confirmar cuenta
            $usuario->confirmado = 1;
            $usuario->token = null;
            unset($usuario->password2);

            $usuario->guardar();
            Usuario::setAlerta('exito', 'Cuenta comprobada correctamente');
        }

        $alertas = Usuario::getAlertas();


        $router->render('auth/confirmar', [
            'titulo' => 'Cuenta confirmada',
            'alertas' => $alertas
        ]);
    }
}
