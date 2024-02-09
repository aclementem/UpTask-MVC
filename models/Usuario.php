<?php

namespace Model;

class Usuario extends ActiveRecord
{
    protected static $tabla = 'usuarios';
    protected static $columnasDB = ['id', 'nombre', 'email', 'password', 'token', 'confirmado'];

    public $id;
    public $nombre;
    public $email;
    public $password;
    public $password2;
    public $token;
    public $confirmado;

    public function __construct($args = [])
    {
        $this->id = $args['id'] ?? null;
        $this->nombre = $args['nombre'] ?? '';
        $this->email = $args['email'] ?? '';
        $this->password = $args['password'] ?? '';
        $this->password2 = $args['password2'] ?? '';
        $this->password_actual = $args['password_actual'] ?? '';
        $this->password_nueva = $args['password_nueva'] ?? '';
        $this->token = $args['token'] ?? '';
        $this->confirmado = $args['confirmado'] ?? 0;
    }

    public function validarLogin()
    {
        Usuario::validarEmail();
        if (!$this->password) {
            self::$alertas['error'][] = 'El password es un campo obligatorio';
        }

        return self::$alertas;
    }

    // Validación para cuentas nuevas
    public function validarNuevaCuenta(): array
    {
        if (!$this->nombre) {
            self::$alertas['error'][] = 'El nombre del usuario es obligatorio';
        }
        if (!$this->email) {
            self::$alertas['error'][] = 'El email del usuario es obligatorio';
        }
        if (!$this->password) {
            self::$alertas['error'][] = 'El password es un campo obligatorio';
        }
        if (strlen($this->password) < 6) {
            self::$alertas['error'][] = 'El password debe tener un mínimo de 6 caracteres';
        }
        if ($this->password !== $this->password2) {
            self::$alertas['error'][] = 'Los passwords no coinciden';
        }


        return self::$alertas;
    }

    public function comprobarPassword(): bool
    {
        return password_verify($this->password_actual, $this->password);
    }

    public function hashPassword(): void
    {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
    }

    public function crearToken()
    {
        $this->token = uniqid();
    }

    public function validarEmail()
    {
        if (!$this->email) {
            self::$alertas['error'][] = 'El email es obligatorio';
        }

        if (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            self::$alertas['error'][] = 'El email no es válido';
        }

        return self::$alertas;
    }

    public function validarPassword()
    {
        if (!$this->password) {
            self::$alertas['error'][] = 'El password es un campo obligatorio';
        }
        if (strlen($this->password) < 6) {
            self::$alertas['error'][] = 'El password debe tener un mínimo de 6 caracteres';
        }

        return self::$alertas;
    }

    public function validarPerfil()
    {
        if (!$this->nombre) {
            self::$alertas['error'][] = 'El campo nombre no puede estar vacío';
        }

        if (!$this->email) {
            self::$alertas['error'][] = 'El campo email es obligatorio';
        }

        return self::$alertas;
    }

    public function nuevoPassword()
    {
        if (!$this->password_actual) {
            self::$alertas['error'][] = 'El password actual no puede estar vacío';
        }
        if (strlen($this->password_nueva) < 6) {
            self::$alertas['error'][] = 'El password debe contener al menos 6 caracteres';
        }
        return self::$alertas;
    }
}
