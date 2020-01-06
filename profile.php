<?php



class Profile{
    public $id;
    public $username;
    private $is_public;



    public function isPublic() : bool{
        return (bool) $this->is_public;
    }
}