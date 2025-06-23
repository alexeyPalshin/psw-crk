<?php

namespace Palshin\PswCrack\Controllers;

class UsersController extends BaseController
{
    private $capsule;
    private $table = 'not_so_smart_users';

    public function __construct()
    {
        global $capsule;
        $this->capsule = $capsule;
    }

    /**
     * Get all users
     */
    public function index()
    {
        try {
            $users = $this->capsule->connection('db')->table($this->table)->orderBy('user_id')->get();

            $this->json($users);
        } catch (\Exception $e) {
            $this->jsonError('Error fetching users: ' . $e->getMessage(), 500);
        }
    }
}
