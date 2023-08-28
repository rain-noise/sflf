<?php
namespace Model;

class User
{
    const DAO_IGNORE_FILED = ['article_count'];

    public $user_id;
    public $name;
    public $gender   = null;
    public $birthday = null;
    public $email;
    public $role;
    public $password;
    public $created_at = null;
    public $updated_at = null;

    public $article_count;
}
