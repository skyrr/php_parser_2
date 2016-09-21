<?php

namespace Model;

use Phalcon\Mvc\Model;

class Resume extends Model
{
    protected $id;
    protected $url;
    protected $phone;
    protected $email;

    public function getId()
    {
        return $this->id;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function getEmail()
    {
        return $this->email;
    }
}
