<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;

abstract class Controller extends \Illuminate\Routing\Controller {
    protected Authenticatable $user;

    /**
     * @throws \Exception
     */
    public function __construct() {
        $user = auth()->user();


        $this->user = $user;
    }
}
