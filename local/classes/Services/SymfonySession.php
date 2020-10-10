<?php

namespace Local\Services;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\PhpBridgeSessionStorage;

/**
 * Class SymfonySession
 * @package Local\Services
 */
class SymfonySession
{
    /**
     * @var Session $session Сессии Symfony.
     */
    protected $session;

    /**
     * SymfonySessions constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Инициализация.
     *
     * @return void
     */
    public function init(): void
    {
        $this->session = new Session(new PhpBridgeSessionStorage());
        // Если сессия не запущена, то запустить и мигрировать.
        if (!$this->session->isStarted()) {
            $this->session->start();
            $this->migrateSession();
        }
    }

    /**
     * Объект Session.
     *
     * @return Session
     */
    public function session(): Session
    {
        return $this->session;
    }

    /**
     * Установить - получить значение ключа.
     *
     * @param string $key   Ключ.
     * @param mixed  $value Значение.
     *
     * @return mixed
     */
    public function value(string $key, $value = null)
    {
        if ($value === null) {
            return $this->session->get($key);
        }

        $this->session->set($key, $value);

        return null;
    }

    /**
     * Миграция $_SESSION в сессии Symfony.
     *
     * @return void
     */
    public function migrateSession() : void
    {
        foreach ($_SESSION as $key => $item) {
            $this->value($key, $item);
        }
    }

    /**
     * Csrf токен приложения.
     *
     * @return string
     */
    public function csrfTokenApp() : string
    {
        return (string)$this->session->get('csrf_token');
    }
}
