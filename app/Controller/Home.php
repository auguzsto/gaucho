<?php
namespace App\Controller;

use Gaucho\Gaucho;

class Home extends Gaucho
{

    function GET()
    {
        $data = [
            'name' => 'world',
            'title' => 'Início'
        ];
        $this->chaplin('inc/header',$data);
        $this->chaplin('home',$data);
        $this->chaplin('inc/footer',$data);
    }
}